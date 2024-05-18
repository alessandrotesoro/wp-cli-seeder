<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Handle WooCommerce attributes.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Utils;

/**
 * Handle WooCommerce attributes.
 * All methods are static and can be called without instantiating the class.
 */
class Attributes {

	/**
	 * Get all WooCommerce attributes.
	 *
	 * @return array
	 */
	public static function get_wc_attributes(): array {
		$attributes = wc_get_attribute_taxonomies();
		return $attributes;
	}

	/**
	 * Create a new attribute for WooCommerce.
	 *
	 * @param string $name The name of the attribute.
	 * @param string $slug The slug of the attribute.
	 * @return int|WP_Error
	 */
	public static function create_attribute( string $name, string $slug ) {
		$attribute = wc_create_attribute(
			[
				'name'         => $name,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			]
		);

		return $attribute;
	}
}
