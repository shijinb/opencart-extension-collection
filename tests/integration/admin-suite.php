<?php
// Admin models: order list filters, previous/next order, stock report.
require __DIR__ . '/bootstrap.php';

oc_load('admin/model/sale/order.php');
oc_load('admin/model/extension/report/stock_report.php');

$orders = new ModelSaleOrder(oc_registry());
$ids = function (array $rows) { return array_map('intval', array_column($rows, 'order_id')); };

echo "order-filter-marketing-code\n";
// Core lists every order with a status above 0 (canceled included) unless a status filter is set.
$rows = $orders->getOrders(['filter_marketing_code' => "SUMMER'24", 'sort' => 'o.order_id', 'order' => 'ASC']);
check('matches tracking code containing a quote (value is escaped)', $ids($rows) === [9501, 9502], $ids($rows));
check('getTotalOrders() works with the filter (no table alias in COUNT query)', (int)$orders->getTotalOrders(['filter_marketing_code' => "SUMMER'24"]) === 2);
check('getTotalSales() works with the filter', (float)$orders->getTotalSales(['filter_marketing_code' => "SUMMER'24"]) === 150.0);
check('combines with the core order status filter', $ids($orders->getOrders(['filter_marketing_code' => "SUMMER'24", 'filter_order_status_id' => 7])) === [9502]);
check('SQL injection attempt matches nothing', $orders->getOrders(['filter_marketing_code' => "x' OR '1'='1"]) === []);

echo "order-filter-telephone\n";
$rows = $orders->getOrders(['filter_telephone' => '+91 98470 00001', 'sort' => 'o.order_id', 'order' => 'ASC']);
check('returns every order with the number', $ids($rows) === [9501, 9504], $ids($rows));
check('getTotalOrders() works with the filter', (int)$orders->getTotalOrders(['filter_telephone' => '+91 98470 00001']) === 2);
check('both filters combine with AND', $ids($orders->getOrders(['filter_telephone' => '+91 98470 00001', 'filter_marketing_code' => 'NEWSLETTER'])) === [9504]);

echo "order-prev-next-navigation\n";
check('next order skips canceled (7) and missing (0) orders', $orders->getAdjacentOrderId(9501, 'next') === 9504, $orders->getAdjacentOrderId(9501, 'next'));
check('previous order skips them too', $orders->getAdjacentOrderId(9504, 'previous') === 9501, $orders->getAdjacentOrderId(9504, 'previous'));
check('no next order after the last one', $orders->getAdjacentOrderId(9504, 'next') === 0);
check('non-numeric order_id is cast, not injected', $orders->getAdjacentOrderId("9504 OR 1=1", 'previous') === 9501);

echo "stock-report\n";
$stock = new ModelExtensionReportStockReport(oc_registry());
$rows = array_values(array_filter($stock->getStock(), function ($r) { return $r['product_id'] >= 9001; }));
$byKey = [];
foreach ($rows as $r) {
    $byKey[$r['product_id'] . ':' . ($r['option_value'] ?? '-')] = $r;
}
$keys = array_keys($byKey);
sort($keys);
check('one row per option value plus one per product without options', $keys === ['9001:100 ml', '9001:50 ml', '9002:-', '9003:-'] && count($rows) === 4, $keys);
check('option value with quantity 0 reports 0 (not the product quantity)', (int)$byKey['9001:100 ml']['quantity'] === 0);
check('option price applies "+" prefix', (float)$byKey['9001:100 ml']['price'] === 14.0);
check('option price applies "-" prefix', (float)$byKey['9001:50 ml']['price'] === 11.0);
check('option name is joined in the admin language only (no duplicate rows)', $byKey['9001:50 ml']['option_name'] === 'Size');
check('product without options uses product quantity', (int)$byKey['9002:-']['quantity'] === 3);

$low = array_column($stock->getStock(['filter_name' => 'gel', 'filter_quantity_max' => 3]), 'option_value', 'product_id');
check('filter_quantity_max keeps only rows at or below the level', count($stock->getStock(['filter_name' => 'Aloe Vera', 'filter_quantity_max' => 0])) === 1);
check('filter_name + quantity filter together', array_key_exists(9002, $low) && array_key_exists(9001, $low) && !array_key_exists(9003, $low), $low);
check('getTotalStock() equals the unpaginated row count', $stock->getTotalStock(['filter_name' => 'gel']) === count($stock->getStock(['filter_name' => 'gel'])));
check('pagination LIMIT is applied', count($stock->getStock(['start' => 0, 'limit' => 2])) === 2);

finish();
