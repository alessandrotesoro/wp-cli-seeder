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

use Sematico\Seeder\Traits\CanDeleteTerms;
use Sematico\Seeder\Traits\CanGenerateTerms;
use Sematico\Seeder\Utils\PostTypes;
use WP_CLI;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

/**
 * Seed the database with dummy posts.
 */
class SeedPostsCommand extends BaseSeedCommand {

	use CanDeleteTerms;
	use CanGenerateTerms;

	/**
	 * Seed the database with dummy posts for the specified post type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed posts generate
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$post_type = search(
			'Which post type do you want to seed?',
			fn ( string $value ) => strlen( $value ) > 0
			? PostTypes::get_post_types_for_dropdown( $value )
			: []
		);

		$this->set_post_type( $post_type );

		$number_of_posts = text(
			label: sprintf( 'How many "%s" do you want to seed?', $post_type ),
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);

		$delete_existing = confirm( sprintf( 'Do you want to delete all existing "%s" before seeding?', $post_type ) );

		if ( $delete_existing ) {
			$this->delete( [], [] );
		}

		$progress = WP_CLI\Utils\make_progress_bar( 'Seeding posts', $number_of_posts );

		for ( $i = 0; $i < $number_of_posts; $i++ ) {
			$this->create_post();
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( sprintf( 'Seeded %d "%s" posts.', $number_of_posts, $post_type ) );
	}

	/**
	 * Create a new post.
	 *
	 * @return int
	 */
	private function create_post() {
		$faker = \Faker\Factory::create();

		$post_id = wp_insert_post(
			[
				'post_title'   => $faker->sentence,
				'post_content' => $faker->paragraphs( 3, true ),
				'post_status'  => 'publish',
				'post_type'    => $this->post_type,
			]
		);

		return $post_id;
	}

	/**
	 * Seed the database with dummy meta fields.
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function custom_fields( $args, $assoc_args ) {
		$post_type = search(
			'Which post type do you want to seed?',
			fn ( string $value ) => strlen( $value ) > 0
			? PostTypes::get_post_types_for_dropdown( $value )
			: []
		);

		$this->set_post_type( $post_type );

		parent::custom_fields( $args, $assoc_args );
	}
}
