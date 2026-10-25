<?php
class ControllerExtensionReportStockReport extends Controller {
	public function index() {
		$this->load->language('extension/report/stock_report');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		/*if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('report_sale_order', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true));
		}
		*/
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

		if (isset($this->request->post['report_sale_order_status'])) {
			$data['report_sale_order_status'] = $this->request->post['report_sale_order_status'];
		} else {
			$data['report_sale_order_status'] = $this->config->get('report_sale_order_status');
		}

		if (isset($this->request->post['report_sale_order_sort_order'])) {
			$data['report_sale_order_sort_order'] = $this->request->post['report_sale_order_sort_order'];
		} else {
			$data['report_sale_order_sort_order'] = $this->config->get('report_sale_order_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/stock_report_form', $data));
	}
	
	// protected function validate() {
	// 	if (!$this->user->hasPermission('modify', 'extension/report/stock_report')) {
	// 		$this->error['warning'] = $this->language->get('error_permission');
	// 	}

	// 	return !$this->error;
	// }
		
	public function report() {
		
		$this->load->language('extension/report/stock_report');

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name="";
		}
		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			//$filter_date_start = date('Y-m-d', strtotime(date('Y') . '-' . date('m') . '-01'));
			$filter_date_start="";
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			//$filter_date_end = date('Y-m-d');
			$filter_date_end="";
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		$this->load->model('extension/report/stock');
		$data['orders'] = array();

		$filter_data = array(
			'filter_name'			 => $filter_name,
			'filter_date_start'	     => $filter_date_start,
			'filter_date_end'	     => $filter_date_end,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		if (isset($this->request->get['download'])) {
			$this->export($filter_data);
		}
		$order_total = $this->model_extension_report_stock->getTotalStocks($filter_data);
		$results = $this->model_extension_report_stock->getStocks($filter_data);
		// var_dump($results);die();
		// foreach ($results as $result) {
		// 	$data['orders'][] = array(
		// 		'products'   => $result['products'],
		// 		'tax'        => $this->currency->format($result['tax'], $this->config->get('config_currency')),
		// 		'total'      => $this->currency->format($result['total'], $this->config->get('config_currency'))
		// 	);
		// }
		$data['products']=$results;
		$data['user_token'] = $this->session->data['user_token'];
		$url = '';
		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . $this->request->get['filter_name'];
		} 
		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'] . '&code=stock_report' . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_name'] = $filter_name;
		$data['export'] = $this->url->link('report/report&code=stock_report', 'user_token=' . $this->session->data['user_token'] . $url."&download=1", true);
		$data['button_export'] = $this->language->get('button_export');
		return $this->load->view('extension/report/stock_report_info', $data);
	}
	public function export($data) {
		$filter_data = array();
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name="";
		}
		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.product_id';
		}
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}
		$filter_data = array(
			'filter_name'			 => $filter_name,
			'sort'                   => $sort,
			'order'                  => $order
		);
		$products = array();
		$products_column=array();
		$this->load->model('extension/report/stock');
		$results = $this->model_extension_report_stock->getStocks($filter_data);
		$product_list = array();
		foreach ($results as $result) {
			$product_list[] = array( 'Product name'=>$result['name'],'Product name'=>$result['name'],'Priority'=>$result['product_priority'],'SKU'=>$result['sku'],'UPC'=>$result['upc'],'Option'=>$result['option_name'],'Stock'=>$result['quantity'],'Price'=>$result['option_price'],'Model'=>$result['model'],'manufacturer'=>$result['manufacturer'],'Shipping'=>$result['shipping'],'Status'=>$result['product_status'],'Tax Class ID'=>$result['tax_class_id'],'Keep Selling Flag'=>$result['keep_selling']);
			}
			$products_column = array('Product name','Priority','SKU','UPC','Option','Stock','Price','Model','manufacturer','Shipping','Status','Tax Class ID','Keep Selling Flag');

			$products[0]= $products_column; foreach($product_list as $products_row) { $products[]= $products_row; } require_once(DIR_SYSTEM . 'library/PHPExcel.php'); $filename='productlist_'.date('Y-m-d _ H:i:s'); $filename = preg_replace('/[^aA-zZ0-9\_\-]/', '', $filename); 
			// Create new PHPExcel object
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getActiveSheet()->fromArray($products, null, 'A1');
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);// Save Excel 95 file
			
			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename='.$filename. ".xlsx");
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');
			exit;

			/*$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); //Setting the header type
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header("Content-Disposition: attachment; filename=\"" . $filename . ".xlsx\""); header('Cache-Control: max-age=0'); $objWriter->save('php://output');*/

	}
}