<?php
/**
 * Plugin Name: WP CLI Seeder
 * Plugin URI: https://alessandrotesoro.me
 * Description: A WP CLI command to seed your WordPress database with dummy data.
 * Version: 0.1.0
 * Author Name: Alessandro Tesoro
 * Author URI: https://alessandrotesoro.me
 */

namespace Sematico\Seeder;

if ( ! class_exists( '\WP_CLI' ) ) {
	return;
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload.php';
}

\WP_CLI::add_command( 'seed', SeedCommand::class );
