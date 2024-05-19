<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Seeder command for WP CLI.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Traits;

use Sematico\Seeder\Utils\PostTypes;
use WP_CLI;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Trait to generate terms for a taxonomy.
 */
trait CanGenerateTerms {
	/**
	 * Generate terms for the specified taxonomy.
	 *
	 * @param string $taxonomy The taxonomy to generate terms for.
	 * @param int    $count    The number of terms to generate.
	 * @return array
	 */
	private function generate_terms( string $taxonomy, int $count ) {
		$terms = [];

		$progress = WP_CLI\Utils\make_progress_bar( 'Generating terms', $count );

		for ( $i = 0; $i < $count; $i++ ) {
			$terms[] = $this->generate_term( $taxonomy );

			$progress->tick();
		}

		$progress->finish();

		return $terms;
	}

	/**
	 * Generate a single term for the specified taxonomy.
	 *
	 * @param string $taxonomy The taxonomy to generate the term for.
	 * @return array
	 */
	private function generate_term( string $taxonomy ) {
		$faker = \Faker\Factory::create();

		$term = $faker->unique()->word;

		$term = wp_insert_term( $term, $taxonomy );

		if ( is_wp_error( $term ) ) {
			WP_CLI::error( $term->get_error_message() );
		}

		return $term;
	}

	/**
	 * Get the taxonomies for the specified post type.
	 *
	 * @param string $post_type The post type to get taxonomies for.
	 * @return array
	 */
	private function get_taxonomies_for_post_type( string $post_type ) {
		$taxonomies = get_object_taxonomies( $post_type );

		return array_filter(
			$taxonomies,
			fn ( $taxonomy ) => ! in_array( $taxonomy, [ 'post_format' ], true )
		);
	}

	/**
	 * Seed the database with dummy terms for the specified taxonomy.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed posts terms
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function terms( $args, $assoc_args ) {
		$post_type = search(
			'Which post type do you want to seed?',
			fn ( string $value ) => strlen( $value ) > 0
				? PostTypes::get_post_types_for_dropdown( $value )
				: []
		);

		$this->set_post_type( $post_type );

		$taxonomies = $this->get_taxonomies_for_post_type( $post_type );

		$taxonomy = select(
			'Which taxonomy do you want to seed?',
			$taxonomies
		);

		$number_of_terms = text(
			label: sprintf( 'How many "%s" terms do you want to seed?', $taxonomy ),
			validate: fn ( string $value ) => match ( true ) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null,
			},
		);

		$confirm = confirm( 'Do you want to delete all existing terms before seeding?' );

		if ( $confirm ) {
			$this->delete_terms(
				null,
				[
					'taxonomy' => $taxonomy,
					'force'    => true,
				]
			);
		}

		WP_CLI::line( 'Generating terms...' );

		$terms = $this->generate_terms( $taxonomy, $number_of_terms );

		WP_CLI::success( sprintf( 'Seeded %d "%s" terms.', $number_of_terms, $taxonomy ) );

		$confirm_assign = confirm( 'Do you want to assign the terms to the posts?' );

		if ( $confirm_assign ) {

			$number_posts = text(
				label: sprintf( 'How many "%s" posts do you want to assign terms to?', $post_type ),
				validate: fn ( string $value ) => match ( true ) {
					! is_numeric( $value ) => 'The value must be a number.',
					default => null,
				},
			);

			$this->assign_terms_to_posts( $terms, $taxonomy, $number_posts );
		}
	}

	/**
	 * Assign terms to posts.
	 *
	 * @param array  $terms    The terms to assign.
	 * @param string $taxonomy The taxonomy to assign the terms to.
	 * @param int    $number_posts The number of posts to assign terms to.
	 * @return void
	 */
	private function assign_terms_to_posts( array $terms, string $taxonomy, int $number_posts ) {
		$posts = get_posts(
			[
				'post_type'   => $this->post_type,
				'orderby'     => 'rand',
				'numberposts' => $number_posts,
			]
		);

		$progress = WP_CLI\Utils\make_progress_bar( 'Assigning terms to posts', count( $posts ) );

		foreach ( $posts as $post ) {
			$faker = \Faker\Factory::create();

			$term = $faker->randomElement( $terms );

			wp_set_post_terms( $post->ID, $term['term_id'], $taxonomy, true );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'Terms have been assigned to posts.' );
	}
}
