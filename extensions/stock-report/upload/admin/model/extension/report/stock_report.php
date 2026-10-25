<?php
class ModelExtensionReportStockReport extends Model {
	/**
	 * One row per product without options, and one row per option value for products with options
	 * (the option value's own quantity and option-adjusted price).
	 */
	public function getStock($data = array()) {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT p.product_id, pd.name, p.model, p.sku, p.upc, p.status, p.shipping, m.name AS manufacturer, tc.title AS tax_class, od.name AS option_name, ovd.name AS option_value, IF(pov.product_option_value_id IS NULL, p.quantity, pov.quantity) AS quantity, CASE pov.price_prefix WHEN '+' THEN p.price + pov.price WHEN '-' THEN p.price - pov.price ELSE p.price END AS price" . $this->getFrom($language_id, $data);

		$sql .= " ORDER BY pd.name ASC, p.product_id ASC, od.name ASC, ovd.name ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int)$data['start']);
			$limit = (int)$data['limit'] < 1 ? 20 : (int)$data['limit'];

			$sql .= " LIMIT " . $start . "," . $limit;
		}

		return $this->db->query($sql)->rows;
	}

	public function getTotalStock($data = array()) {
		$query = $this->db->query("SELECT COUNT(*) AS total" . $this->getFrom((int)$this->config->get('config_language_id'), $data));

		return (int)$query->row['total'];
	}

	private function getFrom($language_id, $data) {
		$sql = " FROM `" . DB_PREFIX . "product` p";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.manufacturer_id = p.manufacturer_id)";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "tax_class` tc ON (tc.tax_class_id = p.tax_class_id)";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "product_option_value` pov ON (pov.product_id = p.product_id)";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "option_description` od ON (od.option_id = pov.option_id AND od.language_id = '" . $language_id . "')";
		$sql .= " LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ovd.option_value_id = pov.option_value_id AND ovd.language_id = '" . $language_id . "')";
		$sql .= " WHERE 1 = 1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (isset($data['filter_quantity_max']) && $data['filter_quantity_max'] !== '') {
			$sql .= " AND IF(pov.product_option_value_id IS NULL, p.quantity, pov.quantity) <= '" . (int)$data['filter_quantity_max'] . "'";
		}

		return $sql;
	}
}
