<?php
// Catalog models: storefront search (phrase search + search-only tags), category trail, schema summary.
require __DIR__ . '/bootstrap.php';

oc_load('catalog/model/catalog/product.php');
oc_load('catalog/model/catalog/category.php');

$products = new ModelCatalogProduct(oc_registry());
$categories = new ModelCatalogCategory(oc_registry());
$found = function (array $data) use ($products) {
    $ids = array_map('intval', array_keys($products->getProducts($data)));
    sort($ids);
    return array_values(array_filter($ids, function ($id) { return $id >= 9000; }));
};

echo "product-phrase-search\n";
check('phrase matches the product named with it', $found(['filter_name' => 'aloe vera']) === [9001], $found(['filter_name' => 'aloe vera']));
check('words in a different order no longer match', !in_array(9002, $found(['filter_name' => 'aloe vera']), true));
check('getTotalProducts() agrees', (int)$products->getTotalProducts(['filter_name' => 'aloe vera']) >= 1);

echo "search-only-product-tags\n";
check('search-only tag finds the product', $found(['filter_tag' => 'kattar vazha']) === [9001], $found(['filter_tag' => 'kattar vazha']));
check('regular tag search still works', $found(['filter_tag' => 'cooling']) === [9002]);
check('storefront search (name OR tag) finds by search-only tag', $found(['filter_name' => 'aloe jel', 'filter_tag' => 'aloe jel']) === [9001]);
check('product with NULL search-only tags is still searchable by tag', $found(['filter_tag' => 'cooling']) === [9002]);
check('getTotalProducts() counts search-only tag matches', (int)$products->getTotalProducts(['filter_tag' => 'kattar vazha']) === 1);

echo "product-category-breadcrumbs\n";
$trail = $categories->getProductCategoryTrail(9001);
check('trail follows the deepest category, root first', array_map('intval', array_column($trail, 'category_id')) === [9101, 9102, 9103], $trail);
check('trail has names in the store language', array_column($trail, 'name') === ['Skin Care', 'Face', 'Face Gels']);
check('no trail when a parent category is disabled', $categories->getProductCategoryTrail(9002) === []);
check('no trail for a product without categories', $categories->getProductCategoryTrail(9003) === []);

echo "schema-json-ld (model)\n";
$summary = $products->getStructuredDataSummary(9001);
check('rating is the exact average of approved reviews', abs($summary['rating'] - 4.5) < 0.001, $summary);
check('review count ignores unapproved reviews', $summary['review_count'] === 2);
check('special price end date is returned', $summary['special_date_end'] === '2099-12-31');
$none = $products->getStructuredDataSummary(9002);
check('no reviews and no special', $none['review_count'] === 0 && $none['special_date_end'] === '', $none);

finish();
