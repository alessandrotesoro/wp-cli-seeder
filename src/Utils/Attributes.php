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

	/**
	 * Get all terms for a specific attribute.
	 *
	 * @param string $attribute_slug The attribute slug.
	 * @return array
	 */
	public static function get_attribute_terms( string $attribute_slug ) {
		$terms = get_terms(
			[
				'taxonomy'   => 'pa_' . $attribute_slug,
				'hide_empty' => false,
			]
		);

		return $terms;
	}

	/**
	 * Create a new term for a specific attribute.
	 *
	 * @param string $attribute_slug The attribute slug.
	 * @param string $term_name The term name.
	 * @param string $term_slug The term slug.
	 * @return int|WP_Error
	 */
	public static function create_attribute_term( string $attribute_slug, string $term_name, string $term_slug ) {
		$term = wp_insert_term(
			$term_name,
			'pa_' . $attribute_slug,
			[
				'slug' => $term_slug,
			]
		);

		return $term;
	}

	/**
	 * Get the attribute ID by its slug.
	 *
	 * @param string $slug The attribute slug.
	 * @return int
	 */
	public static function get_attribute_id_by_slug( string $slug ): int {
		$attribute = wc_get_attribute_taxonomy_by_name( $slug );
		return $attribute->attribute_id;
	}

	/**
	 * Attach a term to a product as an attribute.
	 *
	 * @param \WP_Term $term The term object.
	 * @param int      $product_id The product ID.
	 * @return void
	 */
	public static function attach_term_attribute_to_product( \WP_Term $term, int $product_id ) {
		$att = new \WC_Product_Attribute();
		$att->set_id( $term->term_id );
		$att->set_name( $term->taxonomy );
		$att->set_options( [ $term->term_id ] );
		$att->set_position( 0 );
		$att->set_visible( true );
		$att->set_variation( false );

		$product = wc_get_product( $product_id );

		$product->set_attributes( [ $term->taxonomy => $att ] );
		$product->save();
	}
}
