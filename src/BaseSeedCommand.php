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

	/**
	 * Download and set the featured image for a post.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $image_url The image URL.
	 * @return string The image URL.
	 */
	protected function download_and_set_featured_image( $post_id, $image_url ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$image = media_sideload_image( $image_url, $post_id, '', 'id' );

		if ( is_wp_error( $image ) ) {
			WP_CLI::warning( "Could not download image: {$image->get_error_message()}" );
		}

		set_post_thumbnail( $post_id, $image );

		return $image;
	}
}
