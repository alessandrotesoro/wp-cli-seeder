<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Trait to upload images to the media library.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Traits;

use WP_CLI;

/**
 * Trait to upload images to the media library.
 */
trait CanUploadImages {
	/**
	 * Download and set the featured image for a post.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $image_url The image URL.
	 * @return string The image URL.
	 */
	public function download_and_set_featured_image( $post_id, $image_url ) {
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
