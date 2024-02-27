<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Seeder command for WP CLI.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder;

use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use WP_CLI;

/**
 * Command for generating WordPress data.
 */
class SeedCommand {

	protected $items_to_seed;

	/**
	 * Seed the database with dummy products.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : How many items to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products --items=100
	 *
	 * @when after_wp_load
	 */
	public function products( $args, $assoc_args ) {
		$items = $assoc_args['items'];

		$this->items_to_seed = $items;

		// Check if WooCommerce is installed.
		$this->check_woocommerce();

		$products = Items::fromFile( SEEDER_PATH . 'data/10k_records.json', [ 'decoder' => new ExtJsonDecoder( true ) ] );

		// Get the number of items to seed from the products array.
		$products = array_slice( iterator_to_array( $products ), 0, $items );

		// Collect the "hierarchicalCategories" from the products array.
		$categories = array_column( $products, 'hierarchicalCategories' );

		$this->process_categories( $categories );

		$this->process_products( $products );

		WP_CLI::success( "Seeding the database with {$items} items." );
	}

	/**
	 * Check if WooCommerce is installed.
	 * If not, display an error message and exit.
	 *
	 * @return void
	 */
	private function check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce is not installed.' );
		}
	}

	/**
	 * Process the categories from the products array.
	 *
	 * @param array $categories The categories to process.
	 * @return void
	 */
	private function process_categories( $categories ) {
		// Filter the array to return only the last item in the array.
		$categories = $this->get_full_hierarchy( $categories );

		$this->delete_all_product_categories();

		foreach ( $categories as $category ) {
			$this->create_product_categories( $this->parse_categories( $category ) );
		}

		WP_CLI::line( 'Required product categories have been created.' );
	}

	/**
	 * Get the full hierarchy of the categories.
	 *
	 * @param array $categories The categories to process.
	 * @return array
	 */
	private function get_full_hierarchy( $categories ) {
		// Filter the array to return only the last item in the array.
		$categories = array_map(
			function ( $category ) {
				return end( $category );
			},
			$categories
		);

		return $categories;
	}

	/**
	 * Parse the categories from the products array.
	 *
	 * @param string $category_string The category string to parse.
	 * @return array
	 */
	private function parse_categories( $category_string ) {
		$categories = explode( ' > ', $category_string );
		$root       = array_shift( $categories );

		if (empty( $categories )) {
			return [ $root ];
		} else {
			return [ $root, $this->parse_categories( implode( ' > ', $categories ) ) ];
		}
	}

	/**
	 * Create product categories.
	 *
	 * @param array $categories The categories to create.
	 * @param int   $parent_id  The parent category ID.
	 * @return void
	 */
	private function create_product_categories( $categories, $parent_id = null ) {
		foreach ( $categories as $category ) {
			if ( is_array( $category ) ) {
				$this->create_product_categories( $category, $parent_id );
			} else {
				$term = term_exists( $category, 'product_cat', $parent_id );

				if (0 === $term || null === $term) {
					$term = wp_insert_term( $category, 'product_cat', [ 'parent' => $parent_id ] );
				}

				$parent_id = $term['term_id'];
			}
		}
	}

	/**
	 * Delete all product categories.
	 *
	 * @return void
	 */
	private function delete_all_product_categories() {
		$terms = get_terms( 'product_cat' );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Deleting existing product categories', count( $terms ) );

		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, 'product_cat' );

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Process the products from the products array.
	 *
	 * @param array $products The products to process.
	 * @return void
	 */
	private function process_products( $products ) {
		$this->delete_all_products();

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating products', $this->items_to_seed );

		foreach ( $products as $product ) {
			$this->insert_woocommerce_product( $product );
			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Delete all existing products.
	 *
	 * @return void
	 */
	private function delete_all_products() {
		$products = wc_get_products(
			[
				'limit' => -1,
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Deleting existing products', count( $products ) );

		foreach ( $products as $product ) {
			$product->delete( true );

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Insert a WooCommerce product.
	 *
	 * @param array $data The product data to insert.
	 * @return void
	 */
	private function insert_woocommerce_product( $data ) {
		$product = new \WC_Product(); // Create an instance of WC_Product class

		$product->set_name( $data['name'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $data['description'] );
		$product->set_price( $data['price'] );
		$product->set_regular_price( $data['price'] );

		$categories     = $data['categories'];
		$categories_ids = [];

		// Find all categories by name and add them to the $categories_ids array.
		foreach ( $categories as $category ) {
			$term             = get_term_by( 'name', $category, 'product_cat' );
			$categories_ids[] = $term->term_id;
		}

		if ( ! empty( $categories_ids ) ) {
			$product->set_category_ids( $categories_ids );
		}

		$product->save(); // Save the product
	}

	/**
	 * Delete all posts of a given post type from the database.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<string>]
	 * : How many items to generate.
	 * ---
	 * default: post
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed delete --post_type=post
	 *
	 * @when after_wp_load
	 */
	public function delete( $args, $assoc_args ) {
		$post_type = $assoc_args['post_type'];

		// Query all posts of the given post type and delete them.
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( "Deleting all {$post_type}", count( $posts ) );

		foreach ( $posts as $post ) {
			wp_delete_post( $post, true );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( "All {$post_type} have been deleted." );
	}
}
