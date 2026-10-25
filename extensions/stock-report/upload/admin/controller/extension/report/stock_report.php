<?php
class ControllerExtensionReportStockReport extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/report/stock_report');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('report_stock_report', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/report/stock_report', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/report/stock_report', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true);

		if (isset($this->request->post['report_stock_report_status'])) {
			$data['report_stock_report_status'] = $this->request->post['report_stock_report_status'];
		} else {
			$data['report_stock_report_status'] = $this->config->get('report_stock_report_status');
		}

		if (isset($this->request->post['report_stock_report_sort_order'])) {
			$data['report_stock_report_sort_order'] = $this->request->post['report_stock_report_sort_order'];
		} else {
			$data['report_stock_report_sort_order'] = $this->config->get('report_stock_report_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/stock_report_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/report/stock_report')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function report() {
		$this->load->language('extension/report/stock_report');

		$this->load->model('extension/report/stock_report');

		$filter_data = $this->getFilters();

		if (isset($this->request->get['page'])) {
			$page = max(1, (int)$this->request->get['page']);
		} else {
			$page = 1;
		}

		$limit = (int)$this->config->get('config_limit_admin');

		$filter_data['start'] = ($page - 1) * $limit;
		$filter_data['limit'] = $limit;

		$data['products'] = array();

		$total = $this->model_extension_report_stock_report->getTotalStock($filter_data);

		foreach ($this->model_extension_report_stock_report->getStock($filter_data) as $result) {
			$data['products'][] = $this->formatRow($result);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['filter_name'] = $filter_data['filter_name'];
		$data['filter_quantity_max'] = $filter_data['filter_quantity_max'];

		$url = $this->getFilterUrl();

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'] . '&code=stock_report' . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['export'] = $this->url->link('extension/report/stock_report/export', 'user_token=' . $this->session->data['user_token'] . $url, true);

		return $this->load->view('extension/report/stock_report_info', $data);
	}

	/**
	 * Streams the filtered report (all pages) as a UTF-8 CSV file.
	 */
	public function export() {
		$this->load->language('extension/report/stock_report');

		$this->load->model('extension/report/stock_report');

		$columns = array(
			'name'         => $this->language->get('column_name'),
			'model'        => $this->language->get('column_model'),
			'sku'          => $this->language->get('column_sku'),
			'upc'          => $this->language->get('column_upc'),
			'option'       => $this->language->get('column_option'),
			'quantity'     => $this->language->get('column_quantity'),
			'price'        => $this->language->get('column_price'),
			'manufacturer' => $this->language->get('column_manufacturer'),
			'tax_class'    => $this->language->get('column_tax_class'),
			'shipping'     => $this->language->get('column_shipping'),
			'status'       => $this->language->get('column_status')
		);

		$this->response->addHeader('Content-Type: text/csv; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="stock-report-' . date('Y-m-d') . '.csv"');
		$this->response->addHeader('Cache-Control: no-store');

		$output = fopen('php://temp', 'r+');

		// BOM so Excel opens UTF-8 product names correctly.
		fwrite($output, "\xEF\xBB\xBF");
		fputcsv($output, array_values($columns));

		foreach ($this->model_extension_report_stock_report->getStock($this->getFilters()) as $result) {
			$row = $this->formatRow($result, false);
			$line = array();

			foreach (array_keys($columns) as $key) {
				$line[] = $this->csvCell($row[$key]);
			}

			fputcsv($output, $line);
		}

		rewind($output);

		$this->response->setOutput(stream_get_contents($output));

		fclose($output);
	}

	private function getFilters() {
		$filter_name = isset($this->request->get['filter_name']) ? trim($this->request->get['filter_name']) : '';

		if (isset($this->request->get['filter_quantity_max']) && $this->request->get['filter_quantity_max'] !== '') {
			$filter_quantity_max = (int)$this->request->get['filter_quantity_max'];
		} else {
			$filter_quantity_max = '';
		}

		return array(
			'filter_name'         => $filter_name,
			'filter_quantity_max' => $filter_quantity_max
		);
	}

	private function getFilterUrl() {
		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_quantity_max']) && $this->request->get['filter_quantity_max'] !== '') {
			$url .= '&filter_quantity_max=' . (int)$this->request->get['filter_quantity_max'];
		}

		return $url;
	}

	private function formatRow(array $result, $html = true) {
		$currency = $this->config->get('config_currency');

		return array(
			'product_id'   => $result['product_id'],
			'name'         => $html ? $result['name'] : html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),
			'model'        => $result['model'],
			'sku'          => $result['sku'],
			'upc'          => $result['upc'],
			'option'       => $result['option_name'] !== null ? $result['option_name'] . ': ' . $result['option_value'] : '',
			'quantity'     => (int)$result['quantity'],
			'price'        => $html ? $this->currency->format($result['price'], $currency) : $this->currency->format($result['price'], $currency, '', false),
			'manufacturer' => (string)$result['manufacturer'],
			'tax_class'    => (string)$result['tax_class'],
			'shipping'     => $result['shipping'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
			'status'       => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
			'edit'         => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
		);
	}

	/**
	 * Prevents CSV formula injection: spreadsheet apps execute cells starting with = + - @.
	 */
	private function csvCell($value) {
		$value = (string)$value;

		if ($value !== '' && in_array($value[0], array('=', '+', '-', '@', "\t", "\r"), true) && !is_numeric($value)) {
			return "'" . $value;
		}

		return $value;
	}
}
