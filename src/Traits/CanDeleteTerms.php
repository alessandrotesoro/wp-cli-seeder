<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Trait to delete terms from a given taxonomy.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Traits;

use WP_CLI;

/**
 * Trait to delete terms from a given taxonomy.
 */
trait CanDeleteTerms {
	/**
	 * Delete all terms of a given taxonomy from the database.
	 *
	 * ## OPTIONS
	 *
	 * [--taxonomy=<string>]
	 * : The taxonomy to delete terms from.
	 * ---
	 * default: category
	 * ---
	 *
	 * [--force]
	 * : Force the deletion without confirmation.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed delete_terms --taxonomy=category
	 *
	 * @when after_wp_load
	 */
	public function delete_terms( $args, $assoc_args ) {
		$taxonomy = $assoc_args['taxonomy'];

		// Confirm the deletion.
		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'force' ) ) {
			WP_CLI::confirm( "Are you sure you want to delete all terms from {$taxonomy}?" );
		}

		// Query all terms of the given taxonomy and delete them.
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			]
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			WP_CLI::line( "No terms found in {$taxonomy}. Nothing to delete." );
			return;
		}

		$progress = \WP_CLI\Utils\make_progress_bar( "Deleting all terms from {$taxonomy}", count( $terms ) );

		foreach ( $terms as $term ) {
			wp_delete_term( $term, $taxonomy );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( "All terms from {$taxonomy} have been deleted." );
	}
}
