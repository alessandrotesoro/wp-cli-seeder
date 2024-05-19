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

use Sematico\Seeder\Utils\ACF;
use WP_CLI;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Base class for seeding commands.
 */
abstract class BaseSeedCommand {

	protected $post_type;

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
	abstract public function generate( $args, $assoc_args );

	/**
	 * Delete all posts of a given post type from the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed products delete
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
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
	 * Seed the database with dummy meta fields.
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function custom_fields( $args, $assoc_args ) {
		$acf = ACF::is_acf_active();

		if ( ! $acf ) {
			WP_CLI::error( 'Advanced Custom Fields is not active.' );
		}

		$post_type = $this->post_type;

		$groups = ACF::get_all_fields_groups_for_post_type( $post_type );
		$fields = ACF::get_fields_from_groups( $groups );

		$dropdown_options = ACF::format_fields_for_dropdown( $fields );

		$selected_field = select(
			'Select a field to seed',
			$dropdown_options
		);

		$number_of_posts = text(
			label: sprintf( 'How many posts of the "%s" post type do you want to seed?', $post_type ),
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);
	}
}
