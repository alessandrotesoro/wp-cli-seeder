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

use WP_CLI;

/**
 * Command for generating WordPress data.
 */
class SeedCommand {

	/**
	 * Seed the database with dummy products.
	 *
	 * ## OPTIONS
	 *
	 * [--items=<number>]
	 * : How many items to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products --items=100
	 *
	 * @when after_wp_load
	 */
	public function products( $args, $assoc_args ) {
		$items = isset( $assoc_args['items'] ) ? $assoc_args['items'] : 10;

		// Check if WooCommerce is installed.
		$this->check_woocommerce();

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

}
