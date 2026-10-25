<?php
class ModelExtensionReportStock extends Model {
	public function getStocks($data = array()) {
		$sql = "SELECT p.product_id,pd.name,p.product_priority,p.sku,p.upc,p.price,p.model,p.tax_class_id,m.name AS manufacturer,CASE WHEN p.keep_selling = 1 THEN 'YES' ELSE 'NO' END as keep_selling,CASE WHEN p.shipping = 1 THEN 'YES' ELSE 'NO' END as shipping,CASE WHEN p.status = 1 THEN 'Enabled' ELSE 'Disabled' END as product_status,ovd.name as option_name,
			CASE WHEN pov.quantity != '' THEN pov.quantity ELSE p.quantity END as quantity,
			CASE WHEN pov.price_prefix = '+' THEN p.price+pov.price WHEN pov.price_prefix = '-' THEN p.price-pov.price ELSE p.price END as option_price,pov.price, pov.price_prefix FROM `" . DB_PREFIX . "product`  p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id LEFT JOIN " . DB_PREFIX . "product_option_value pov on pov.product_id=p.product_id LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}
		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " ORDER BY p.product_id DESC";
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}
			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalStocks($data = array()) {		
		$sql = "SELECT COUNT(*) as total FROM `" . DB_PREFIX . "product`  p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id LEFT JOIN " . DB_PREFIX . "product_option_value pov on pov.product_id=p.product_id LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}

		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}
		$query = $this->db->query($sql);
		return $query->row['total'];
	}
}