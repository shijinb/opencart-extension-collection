<?php
// Unit tests for extensions/schema-json-ld/upload/system/library/schemaorg/builder.php (no OpenCart needed).
declare(strict_types=1);

require __DIR__ . '/../../extensions/schema-json-ld/upload/system/library/schemaorg/builder.php';

use Schemaorg\Builder;

$failures = 0;
$count = 0;
function check(string $name, bool $condition, $detail = null): void {
    global $failures, $count;
    $count++;
    echo ($condition ? '  ok   ' : '  FAIL ') . $name . ($condition || $detail === null ? '' : ' -- got: ' . json_encode($detail, JSON_UNESCAPED_SLASHES)) . "\n";
    $failures += $condition ? 0 : 1;
}

$base = [
    'name'              => 'Aloe &amp; Neem Gel',
    'url'               => 'https://shop.example/index.php?route=product/product&amp;product_id=42',
    'description'       => '&lt;p&gt;Cooling gel.&lt;br&gt;For dry skin.&lt;/p&gt;',
    'images'            => ['https://shop.example/image/cache/a.jpg', ''],
    'sku'               => 'AN-100',
    'mpn'               => '',
    'model'             => 'AN-100',
    'gtin12'            => '',
    'gtin13'            => Builder::gtin('8901234567890', 13),
    'brand_name'        => 'Example Herbals',
    'price'             => 12.5,
    'currency'          => 'INR',
    'in_stock'          => true,
    'price_valid_until' => '2099-12-31',
    'rating'            => 4.5,
    'review_count'      => 2,
    'reviews'           => [
        ['author' => 'Asha', 'rating' => '5', 'date' => '2024-05-01 10:00:00', 'body' => 'Works well.'],
        ['author' => 'Ravi', 'rating' => '3', 'date' => '2024-06-01 10:00:00', 'body' => 'OK.'],
    ],
];

echo "Product\n";
$p = Builder::product($base);
check('type and https context', $p['@type'] === 'Product' && $p['@context'] === 'https://schema.org');
check('HTML entities decoded in name', $p['name'] === 'Aloe & Neem Gel', $p['name']);
check('description is plain text', $p['description'] === 'Cooling gel. For dry skin.', $p['description']);
check('offer URL has real "&" separators', $p['offers']['url'] === 'https://shop.example/index.php?route=product/product&product_id=42', $p['offers']['url']);
check('empty image entries dropped', $p['image'] === ['https://shop.example/image/cache/a.jpg']);
check('empty identifiers omitted', !isset($p['mpn']) && !isset($p['gtin12']));
check('valid EAN emitted as gtin13', $p['gtin13'] === '8901234567890');
check('invalid GTIN rejected', Builder::gtin('ABC-123', 13) === '' && Builder::gtin('123', 12) === '');
check('price formatted with two decimals', $p['offers']['price'] === '12.50', $p['offers']['price']);
check('in stock', $p['offers']['availability'] === 'https://schema.org/InStock');
check('priceValidUntil from special end date', $p['offers']['priceValidUntil'] === '2099-12-31');
check('aggregate rating keeps one decimal', $p['aggregateRating']['ratingValue'] === 4.5 && $p['aggregateRating']['reviewCount'] === 2);
check('each review keeps its own rating (no fixed value)', array_column(array_column($p['review'], 'reviewRating'), 'ratingValue') === [5, 3]);
check('review date is Y-m-d', $p['review'][0]['datePublished'] === '2024-05-01');

$out = Builder::product(['in_stock' => false, 'price_valid_until' => '0000-00-00', 'rating' => 0, 'review_count' => 0, 'reviews' => []] + $base);
check('out of stock when quantity is 0', $out['offers']['availability'] === 'https://schema.org/OutOfStock');
check('zero date is not emitted', !isset($out['offers']['priceValidUntil']));
check('no aggregateRating without reviews', !isset($out['aggregateRating']) && !isset($out['review']));

$many = Builder::product(['reviews' => array_fill(0, 9, $base['reviews'][0])] + $base);
check('at most 5 reviews', count($many['review']) === Builder::MAX_REVIEWS);

echo "BreadcrumbList\n";
$b = Builder::breadcrumbs([
    ['text' => '<i class="fa fa-home"></i>', 'href' => 'https://shop.example/index.php?route=common/home'],
    ['text' => 'Skin &amp; Hair', 'href' => 'https://shop.example/index.php?route=product/category&amp;path=1'],
], 'Example Store');
check('icon-only crumb uses the fallback name', $b['itemListElement'][0]['name'] === 'Example Store');
check('positions start at 1', array_column($b['itemListElement'], 'position') === [1, 2]);
check('ListItem uses name + item URL', $b['itemListElement'][1]['name'] === 'Skin & Hair' && $b['itemListElement'][1]['item'] === 'https://shop.example/index.php?route=product/category&path=1');
check('empty breadcrumbs produce nothing', Builder::breadcrumbs([]) === []);

echo "Organization\n";
$o = Builder::organization('Example Store', 'https://shop.example/', 'https://shop.example/image/logo.png', '+91 00000 00000', '');
check('organization fields', $o['@type'] === 'Organization' && $o['url'] === 'https://shop.example/' && $o['logo'] === 'https://shop.example/image/logo.png');
check('empty contact fields omitted', $o['contactPoint'] === ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'telephone' => '+91 00000 00000']);
check('no contactPoint without contact details', !isset(Builder::organization('S', 'https://s/')['contactPoint']));

echo "Encoding\n";
check('text() strips markup from store data', Builder::product(['name' => 'Bad </script><script>alert(1)</script>'] + $base)['name'] === 'Bad alert(1)');
$json = Builder::encode(['name' => 'Bad </script><script>alert(1)</script>']);
check('encode() escapes "<" and ">" so "</script>" cannot close the tag', stripos($json, '</script') === false && strpos($json, chr(92) . 'u003C/script' . chr(92) . 'u003E') !== false, $json);
check('unicode kept readable', strpos(Builder::encode(['name' => 'Kattar vazha – കറ്റാർവാഴ']), 'കറ്റാർവാഴ') !== false);
check('output is valid JSON that decodes back', json_decode($json, true)['name'] === 'Bad </script><script>alert(1)</script>');

echo sprintf("  %d checks, %d failed\n", $count, $failures);
exit($failures ? 1 : 0);
