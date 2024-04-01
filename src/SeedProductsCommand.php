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
use Sematico\Seeder\Traits\CanDeleteTerms;
use WP_CLI;

/**
 * Command for generating WooCommerce products data.
 */
class SeedProductsCommand extends BaseSeedCommand {

	use CanDeleteTerms;

	protected $post_type = 'product';

	protected $items_to_seed = 100;

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
	 *     wp seed products generate --items=100
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$items = $assoc_args['items'];

		if ( ! is_numeric( $items ) ) {
			WP_CLI::error( 'The --items argument must be a number.' );
		}

		if ( $items < 1 ) {
			WP_CLI::error( 'The --items argument must be greater than 0.' );
		}

		if ( $items > 10000 ) {
			WP_CLI::error( 'The --items argument must be less than 10000.' );
		}

		$this->items_to_seed = $items;

		// Check if WooCommerce is installed.
		$this->check_woocommerce();

		$products = Items::fromFile( SEEDER_PATH . 'data/10k_records.json', [ 'decoder' => new ExtJsonDecoder( true ) ] );

		// Get the number of items to seed from the products array.
		$products = array_slice( iterator_to_array( $products ), 0, $items );

		// Collect the "hierarchicalCategories" from the products array.
		$categories = array_column( $products, 'hierarchicalCategories' );

		$this->process_categories( $categories );

		$this->process_attributes( $products );

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

		// Get the brand from the product data.
		$brand = $data['brand'];

		// Find the brand by name and add it to the product.
		$term = get_term_by( 'name', $brand, 'pa_brand' );

		$att  = null;
		$att2 = null;

		if ( $term ) {
			$att = new \WC_Product_Attribute();
			$att->set_id( $term->term_id );
			$att->set_name( 'pa_brand' );
			$att->set_options( [ $term->term_id ] );
			$att->set_position( 0 );
			$att->set_visible( true );
			$att->set_variation( false );
		}

		// Get the type from the product data.
		$type = $data['type'];

		// Find the type by name and add it to the product.
		$term2 = get_term_by( 'name', $type, 'pa_product_type' );

		if ( $term2 ) {
			$att2 = new \WC_Product_Attribute();
			$att2->set_id( $term2->term_id );
			$att2->set_name( 'pa_product_type' );
			$att2->set_options( [ $term2->term_id ] );
			$att2->set_position( 0 );
			$att2->set_visible( true );
			$att2->set_variation( false );
		}

		if ( $att && $att2 ) {
			$product->set_attributes( [ $att, $att2 ] );
		} elseif ( $att ) {
			$product->set_attributes( [ $att ] );
		} elseif ( $att2 ) {
			$product->set_attributes( [ $att2 ] );
		}

		$product_id = $product->save(); // Save the product

		// Set the featured image for the product.
		if ( isset( $data['image'] ) ) {
			$this->download_and_set_featured_image( $product_id, $data['image'] );
		}
	}

	/**
	 * Process the attributes from the products array.
	 *
	 * @param array $products The products to process.
	 * @return void
	 */
	private function process_attributes( $products ) {
		// First delete all existing terms from the "brand" and "type" taxonomies.
		$this->delete_terms(
			null,
			[
				'taxonomy' => 'pa_brand',
				'force'    => true,
			]
		);
		$this->delete_terms(
			null,
			[
				'taxonomy' => 'pa_product_type',
				'force'    => true,
			]
		);

		// Collect the "brand" from the products array.
		$brands = array_column( $products, 'brand' );

		// Remove duplicates from the array.
		$brands = array_unique( $brands );

		// Check if the "brand" attribute exists.
		$attribute = wc_get_attribute( 'brand' );

		if ( ! $attribute ) {
			$attribute = wc_create_attribute(
				[
					'name'         => 'Brand',
					'slug'         => 'brand',
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				]
			);
		}

		// Create the "brand" terms by looping through the brands array.
		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating brand terms', count( $brands ) );

		foreach ( $brands as $brand ) {
			$term = term_exists( $brand, 'pa_brand' );

			if ( 0 === $term || null === $term ) {
				wp_insert_term( $brand, 'pa_brand' );
			}

			$progress->tick();
		}

		$progress->finish();

		// Now collect the "types" from the products array.
		$types = array_column( $products, 'type' );

		// Remove duplicates from the array.
		$types = array_unique( $types );

		// Check if the "type" attribute exists.
		$attribute_type = wc_get_attribute( 'product_type' );

		if ( ! $attribute_type ) {
			$attribute_type = wc_create_attribute(
				[
					'name'         => 'Type',
					'slug'         => 'product_type',
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				]
			);
		}

		// Create the "type" terms by looping through the types array.
		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating type terms', count( $types ) );

		foreach ( $types as $type ) {
			$term = term_exists( $type, 'pa_product_type' );

			if ( 0 === $term || null === $term ) {
				wp_insert_term( $type, 'pa_product_type' );
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::line( 'Required product attributes have been created.' );
	}
}
