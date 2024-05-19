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

use Sematico\Seeder\Datasets\TenKProducts;
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

	/**
	 * Seed the database with dummy products.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products generate
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		// Check if WooCommerce is installed.
		$this->check_woocommerce();

		$dataset = new TenKProducts();
		$dataset->generate();

		WP_CLI::success( 'Seed data has been generated.' );
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
	 * Randomly set a sale price for products.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products sale
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

		$number_of_products = text(
			label: 'Enter the number of products to generate sales for',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$products = wc_get_products(
			[
				'limit'   => $number_of_products,
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
	 * ## EXAMPLES
	 *
	 *     wp seed products featured
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function featured( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will set random products as featured. Are you sure?' );

		$num = text(
			label: 'Enter the number of products to set as featured',
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
	 * ## EXAMPLES
	 *
	 *     wp seed products stock_status
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

		$number_of_products = text(
			label: 'Enter the number of products to update stock status for',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$products = wc_get_products(
			[
				'limit'   => $number_of_products,
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
	 * ## EXAMPLES
	 *
	 *     wp seed products stock_quantity
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function stock_quantity( $args, $assoc_args ) {
		WP_CLI::confirm( 'This will update the stock quantity for random products. Are you sure?' );

		$number_of_products = text(
			label: 'Enter the number of products to update stock quantity for',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$products = wc_get_products(
			[
				'limit'   => $number_of_products,
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
			Attributes::create_attribute_term(
				$attribute_slug,
				$term,
				sanitize_title( $term )
			);
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

				foreach ( $random_terms as $random_term ) {
					Attributes::attach_term_attribute_to_product( $random_term, $product->get_id() );
				}

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::success( 'Terms have been assigned to products.' );
		}
	}

	/**
	 * Generate product reviews.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products reviews
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function reviews( $args, $assoc_args ) {

		$proceed = confirm( 'Do you want to generate product reviews?' );

		if ( ! $proceed ) {
			return;
		}

		$number_of_products = text(
			label: 'Enter the number of products to generate reviews for',
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$products = wc_get_products(
			[
				'limit'   => $number_of_products,
				'orderby' => 'rand',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating reviews', count( $products ) );

		foreach ( $products as $product ) {
			$faker = \Faker\Factory::create();

			$request = new \WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
			$request->set_param( 'reviewer', $faker->name );
			$request->set_param( 'reviewer_email', $faker->email );
			$request->set_param( 'rating', wp_rand( 1, 5 ) );
			$request->set_param( 'review', $faker->text );
			$request->set_param( 'status', 'approved' );
			$request->set_param( 'product_id', $product->get_id() );

			$response = rest_do_request( $request );

			if ( is_wp_error( $response ) ) {
				WP_CLI::error( $response->get_error_message() );
			}

			if ( 201 !== $response->get_status() ) {
				dd( $response );
				WP_CLI::error( 'An error occurred while generating reviews. You might want to check the logs or authenticate first via WP CLI.' );
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Reviews have been generated.' );
	}
}
