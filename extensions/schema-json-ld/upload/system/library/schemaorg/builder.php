<?php
namespace Schemaorg;

/**
 * Builds schema.org JSON-LD documents from plain arrays.
 *
 * Has no OpenCart dependencies so it can be unit tested on its own. Controllers collect the
 * store data and pass it in; this class only shapes, cleans and encodes it.
 */
class Builder {
	const MAX_REVIEWS = 5;

	/**
	 * @param array $product name, url, description, images[], sku, mpn, model, gtin12, gtin13,
	 *                       brand_name, price, currency, in_stock, price_valid_until,
	 *                       rating, review_count, reviews[] (author, rating, date, body)
	 */
	public static function product(array $product) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => self::text($product['name']),
		);

		if (!empty($product['description'])) {
			$schema['description'] = self::text($product['description'], 5000);
		}

		if (!empty($product['images'])) {
			$schema['image'] = array_values(array_map(array(__CLASS__, 'url'), array_filter($product['images'])));
		}

		foreach (array('sku', 'mpn', 'model', 'gtin12', 'gtin13') as $key) {
			if (isset($product[$key]) && trim((string)$product[$key]) !== '') {
				$schema[$key] = trim((string)$product[$key]);
			}
		}

		if (!empty($product['brand_name'])) {
			$schema['brand'] = array('@type' => 'Brand', 'name' => self::text($product['brand_name']));
		}

		$offer = array(
			'@type'         => 'Offer',
			'url'           => self::url($product['url']),
			'price'         => number_format((float)$product['price'], 2, '.', ''),
			'priceCurrency' => $product['currency'],
			'availability'  => !empty($product['in_stock']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		);

		if (!empty($product['price_valid_until']) && self::isDate($product['price_valid_until'])) {
			$offer['priceValidUntil'] = substr($product['price_valid_until'], 0, 10);
		}

		$schema['offers'] = $offer;

		if (!empty($product['review_count']) && (float)$product['rating'] > 0) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round((float)$product['rating'], 1),
				'reviewCount' => (int)$product['review_count'],
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		if (!empty($product['reviews'])) {
			foreach (array_slice($product['reviews'], 0, self::MAX_REVIEWS) as $review) {
				$item = array(
					'@type'        => 'Review',
					'author'       => array('@type' => 'Person', 'name' => self::text($review['author'])),
					'reviewRating' => array(
						'@type'       => 'Rating',
						'ratingValue' => (int)$review['rating'],
						'bestRating'  => 5,
						'worstRating' => 1,
					),
				);

				if (!empty($review['date']) && self::isDate($review['date'])) {
					$item['datePublished'] = substr($review['date'], 0, 10);
				}

				if (!empty($review['body'])) {
					$item['reviewBody'] = self::text($review['body'], 2000);
				}

				$schema['review'][] = $item;
			}
		}

		return $schema;
	}

	/**
	 * @param array  $breadcrumbs OpenCart breadcrumbs: list of array('text' => ..., 'href' => ...)
	 * @param string $home_name   name used when a breadcrumb has no text (OpenCart's home crumb is an icon)
	 */
	public static function breadcrumbs(array $breadcrumbs, $home_name = 'Home') {
		$items = array();
		$position = 1;

		foreach ($breadcrumbs as $breadcrumb) {
			$name = self::text(isset($breadcrumb['text']) ? $breadcrumb['text'] : '');

			if ($name === '') {
				$name = $home_name;
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $name,
				'item'     => self::url($breadcrumb['href']),
			);
		}

		if (!$items) {
			return array();
		}

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	public static function organization($name, $url, $logo = '', $telephone = '', $email = '') {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => self::text($name),
			'url'      => self::url($url),
		);

		if ($logo) {
			$schema['logo'] = self::url($logo);
		}

		$contact = array_filter(array('telephone' => trim((string)$telephone), 'email' => trim((string)$email)));

		if ($contact) {
			$schema['contactPoint'] = array('@type' => 'ContactPoint', 'contactType' => 'customer support') + $contact;
		}

		return $schema;
	}

	/**
	 * Encodes for direct output inside <script type="application/ld+json">. JSON_HEX_TAG turns
	 * "<" and ">" into < / > so store data containing "</script>" cannot end the tag.
	 */
	public static function encode(array $schema) {
		return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
	}

	/**
	 * Valid GTIN-12/13 from OpenCart's UPC/EAN fields, or '' when the value is not one.
	 */
	public static function gtin($value, $length) {
		$value = trim((string)$value);

		return preg_match('/^\d{' . (int)$length . '}$/', $value) ? $value : '';
	}

	/** OpenCart stores names and descriptions HTML-encoded; JSON-LD needs plain text. */
	public static function text($value, $max_length = 0) {
		$value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
		$value = strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', ' ', $value));
		$value = trim(preg_replace('/\s+/u', ' ', $value));

		if ($max_length && mb_strlen($value, 'UTF-8') > $max_length) {
			$value = rtrim(mb_substr($value, 0, $max_length - 1, 'UTF-8')) . '…';
		}

		return $value;
	}

	/** OpenCart's URL class returns "&amp;" separators for HTML output; JSON needs a real URL. */
	public static function url($value) {
		return html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
	}

	private static function isDate($value) {
		return (bool)preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$value) && substr((string)$value, 0, 10) !== '0000-00-00';
	}
}
