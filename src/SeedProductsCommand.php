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
use Sematico\Seeder\Utils\Attributes;
use WP_CLI;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Command for generating WooCommerce products data.
 */
class SeedProductsCommand extends BaseSeedCommand {

	use CanDeleteTerms;

	protected $post_type = 'product';

	protected $items_to_seed = 100;

	protected $skip_images = false;

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
	 * [--skip-images]
	 * : Whether to download images or not.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products generate --items=100
	 *     wp seed products generate --items=1000 --skip-images
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$this->items_to_seed = $assoc_args['items'];
		$this->skip_images   = isset( $assoc_args['skip-images'] );

		$items = $this->items_to_seed;

		if ( ! is_numeric( $items ) ) {
			WP_CLI::error( 'The --items argument must be a number.' );
		}

		if ( $items < 1 ) {
			WP_CLI::error( 'The --items argument must be greater than 0.' );
		}

		if ( $items > 10000 ) {
			WP_CLI::error( 'The --items argument must be less than 10000.' );
		}

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
		if ( isset( $data['image'] ) && ! $this->skip_images ) {
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

	/**
	 * Randomly set a sale price for products.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : For how many items to generate sales.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products sale --items=10
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function sales( $args, $assoc_args ) {

		WP_CLI::confirm( 'Are you sure you want to generate discounted prices for products?' );

		if ( ! $confirm ) {
			return;
		}

		$products = wc_get_products(
			[
				'limit'   => $assoc_args['items'] ?? 10,
				'orderby' => 'rand',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating sale prices', count( $products ) );

		foreach ( $products as $product ) {
			$product->set_sale_price( $product->get_price() * 0.8 );
			$product->save();

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Sale prices have been generated.' );
	}

	/**
	 * Randomly set products as featured.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : For how many items to generate sales.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products featured --items=10
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function featured( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will set random products as featured. Are you sure?' );

		$products = wc_get_products(
			[
				'limit'   => $assoc_args['items'] ?? 10,
				'orderby' => 'rand',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating featured products', count( $products ) );

		foreach ( $products as $product ) {
			$product->set_featured( true );
			$product->save();

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Featured products have been generated.' );
	}

	/**
	 * Randomly set stock status for products.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : For how many items to generate sales.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products stock_status --items=10
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function stock_status( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will update the stock status for random products. Are you sure?' );

		$stati = wc_get_product_stock_status_options();

		$role = select(
			label: 'Select a stock status',
			options: $stati,
			default: 'instock'
		);

		$products = wc_get_products(
			[
				'limit'   => $assoc_args['items'] ?? 10,
				'orderby' => 'rand',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Updating stock status', count( $products ) );

		foreach ( $products as $product ) {
			$product->set_stock_status( $role );
			$product->save();

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Stock status has been updated.' );
	}

	/**
	 * Randomly set stock quantity for products.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : For how many items to generate sales.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products stock_quantity --items=10
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function stock_quantity( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will update the stock quantity for random products. Are you sure?' );

		$products = wc_get_products(
			[
				'limit'   => $assoc_args['items'] ?? 10,
				'orderby' => 'rand',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Updating stock quantity', count( $products ) );

		foreach ( $products as $product ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( wp_rand( 1, 100 ) );
			$product->save();

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Stock quantity has been updated.' );
	}

	/**
	 * Generate product tags.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products tags
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function tags( $args, $assoc_args ) {
		$proceed = confirm( 'Do you want to generate product tags?' );

		if ( ! $proceed ) {
			return;
		}

		$confirmed = confirm( 'Do you want to delete all existing tags first?' );

		if ( $confirmed ) {
			$this->delete_terms(
				null,
				[
					'taxonomy' => 'product_tag',
					'force'    => true,
				]
			);
		}

		WP_CLI::line( '' );

		$number = text(
			label: 'Enter the number of tags to generate',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$tags = \Faker\Factory::create()->words( $number );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating tags', count( $tags ) );

		foreach ( $tags as $tag ) {
			wp_insert_term( $tag, 'product_tag' );
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Tags have been generated.' );
		WP_CLI::line( '' );

		$assign = confirm( 'Do you want to assign tags to products?' );

		if ( $assign ) {

			$num = text(
				label: 'Enter the number of products to assign tags to',
				validate: fn ( string $value ) => match (true) {
					! is_numeric( $value ) => 'The value must be a number.',
					default => null
				},
			);

			$products = wc_get_products(
				[
					'limit'   => $num,
					'orderby' => 'rand',
				]
			);

			$tags_to_assign = get_terms(
				[
					'taxonomy'   => 'product_tag',
					'hide_empty' => false,
				]
			);

			$progress = \WP_CLI\Utils\make_progress_bar( 'Assigning tags to products', count( $products ) );

			foreach ( $products as $product ) {
				$num_to_pick = rand( 1, count( $tags_to_assign ) );

				// Slice the array to get the required number of random elements
				$random_terms = array_slice( $tags_to_assign, 0, $num_to_pick );

				$tags = array_map(
					function ( $term ) {
						return $term->term_id;
					},
					$random_terms
				);

				$product->set_tag_ids( $tags );
				$product->save();

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::success( 'Tags have been assigned to products.' );
		}
	}

	/**
	 * Generate product categories.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products categories
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function categories( $args, $assoc_args ) {
		$proceed = confirm( 'Do you want to generate product categories?' );

		if ( ! $proceed ) {
			return;
		}

		$confirmed = confirm( 'Do you want to delete all existing categories first?' );

		if ( $confirmed ) {
			$this->delete_terms(
				null,
				[
					'taxonomy' => 'product_cat',
					'force'    => true,
				]
			);
		}

		WP_CLI::line( '' );

		$number = text(
			label: 'Enter the number of categories to generate',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$categories = \Faker\Factory::create()->words( $number );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating categories', count( $categories ) );

		foreach ( $categories as $category ) {
			wp_insert_term( $category, 'product_cat' );
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Categories have been generated.' );
		WP_CLI::line( '' );

		$assign = confirm( 'Do you want to assign categories to products?' );

		if ( $assign ) {
			$num = text(
				label: 'Enter the number of products to assign categories to',
				validate: fn ( string $value ) => match (true) {
					! is_numeric( $value ) => 'The value must be a number.',
					default => null
				},
			);

			$products = wc_get_products(
				[
					'limit'   => $num,
					'orderby' => 'rand',
				]
			);

			$categories_to_assign = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				]
			);

			$progress = \WP_CLI\Utils\make_progress_bar( 'Assigning categories to products', count( $products ) );

			foreach ( $products as $product ) {
				$num_to_pick = rand( 1, count( $categories_to_assign ) );

				// Slice the array to get the required number of random elements
				$random_terms = array_slice( $categories_to_assign, 0, $num_to_pick );

				$categories = array_map(
					function ( $term ) {
						return $term->term_id;
					},
					$random_terms
				);

				$product->set_category_ids( $categories );
				$product->save();

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::success( 'Categories have been assigned to products.' );

		}
	}

	/**
	 * Generate product attributes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products attributes
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function attributes( $args, $assoc_args ) {
		$proceed = confirm( 'Do you want to generate product attributes?' );

		if ( ! $proceed ) {
			return;
		}

		$attributes = Attributes::get_wc_attributes();

		if ( empty( $attributes ) ) {
			WP_CLI::error( 'No attributes found.' );
		}

		$attributes = array_column( $attributes, 'attribute_label', 'attribute_name' );

		$attribute_slug = select(
			label: 'Select an attribute',
			options: $attributes,
		);

		$maybe_delete = confirm( 'Do you want to delete all terms for this attribute?' );

		if ( $maybe_delete ) {
			$this->delete_terms(
				null,
				[
					'taxonomy' => 'pa_' . $attribute_slug,
					'force'    => true,
				]
			);

			WP_CLI::line( '' );
		}

		$number = text(
			label: 'Enter the number of terms to generate',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$terms = \Faker\Factory::create()->words( $number );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating terms', count( $terms ) );

		foreach ( $terms as $term ) {
			Attributes::create_attribute( $term, $term );
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Terms have been generated.' );
		WP_CLI::line( '' );

		$assign = confirm( 'Do you want to assign terms to products?' );

		if ( $assign ) {

			$num = text(
				label: 'Enter the number of products to assign terms to',
				validate: fn ( string $value ) => match (true) {
					! is_numeric( $value ) => 'The value must be a number.',
					default => null
				},
			);

			$products = wc_get_products(
				[
					'limit'   => $num,
					'orderby' => 'rand',
				]
			);

			$terms_to_assign = get_terms(
				[
					'taxonomy'   => 'pa_' . $attribute_slug,
					'hide_empty' => false,
				]
			);

			$progress = \WP_CLI\Utils\make_progress_bar( 'Assigning terms to products', count( $products ) );

			foreach ( $products as $product ) {
				$num_to_pick = rand( 1, count( $terms_to_assign ) );

				// Slice the array to get the required number of random elements
				$random_terms = array_slice( $terms_to_assign, 0, $num_to_pick );

				$terms = array_map(
					function ( $term ) {
						return $term->term_id;
					},
					$random_terms
				);

				$product->set_attribute( 'pa_' . $attribute_slug, $terms );
				$product->save();

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::success( 'Terms have been assigned to products.' );
		}
	}
}
