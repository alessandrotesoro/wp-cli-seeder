<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Handles functionality for post types.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Utils;

/**
 * Handles functionality for post types.
 */
class PostTypes {

	/**
	 * Get all public post types.
	 *
	 * @return array
	 */
	public static function get_post_types() {
		return get_post_types( [ 'public' => true ], 'objects' );
	}

	/**
	 * Get all public post types for a dropdown.
	 * The search term is used to filter the post types.
	 *
	 * @param string $search The search term.
	 * @return array
	 */
	public static function get_post_types_for_dropdown( $search = '' ) {
		$post_types = self::get_post_types();

		$post_types = array_filter(
			$post_types,
			function ( $post_type ) use ( $search ) {
				return strpos( $post_type->name, $search ) !== false;
			}
		);

		$post_types = array_map(
			function ( $post_type ) {
				return $post_type->label;
			},
			$post_types
		);

		return $post_types;
	}
}
