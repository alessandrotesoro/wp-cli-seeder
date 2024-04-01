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
 * Base class for seeding commands.
 */
abstract class BaseSeedCommand {

	protected $post_type;

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
	abstract public function generate( $args, $assoc_args );

	/**
	 * Delete all posts of a given post type from the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products delete
	 *
	 * @when after_wp_load
	 */
	public function delete( $args, $assoc_args ) {
		$post_type = $this->post_type;

		// Confirm the deletion.
		WP_CLI::confirm( "Are you sure you want to delete all items from the '{$post_type}' post type?" );

		// Query all posts of the given post type and delete them.
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( "Deleting items from the '{$post_type}' post type", count( $posts ) );

		foreach ( $posts as $post ) {
			wp_delete_post( $post, true );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( "All {$post_type} have been deleted." );
	}
}
