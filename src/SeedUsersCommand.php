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

use Faker\Factory;
use WP_CLI;

class SeedUsersCommand {

	/**
	 * Deletes all users from the database except the administrators.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed users delete
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function delete( $args, $assoc_args ) {
		// Confirm the deletion.
		WP_CLI::confirm( 'Are you sure you want to delete all users?' );

		// Query all users and delete them.
		$users = get_users(
			[
				'fields'       => 'ID',
				'number'       => -1,
				'role__not_in' => [ 'administrator' ],
			]
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Deleting users', count( $users ) );

		foreach ( $users as $user ) {
			wp_delete_user( $user );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( 'All users have been deleted.' );
	}

	/**
	 * Seed the database with dummy users.
	 *
	 * ## OPTIONS
	 *
	 * [--number=<number>]
	 * : How many users to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp seed users generate --number=10
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$number = isset( $assoc_args['number'] ) ? absint( $assoc_args['number'] ) : 10;

		if ( $number < 1 ) {
			WP_CLI::error( 'The number of users to generate must be greater than 0.' );
		}

		$faker = Factory::create();

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating users', $number );

		for ( $i = 0; $i < $number; $i++ ) {
			$user_id = wp_insert_user(
				[
					'user_login' => $faker->userName,
					'user_pass'  => wp_generate_password(),
					'user_email' => $faker->email,
					'first_name' => $faker->firstName,
					'last_name'  => $faker->lastName,
					'role'       => 'subscriber',
				]
			);

			if ( is_wp_error( $user_id ) ) {
				WP_CLI::error( $user_id->get_error_message() );
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( sprintf( 'Successfully generated %d users.', $number ) );
	}
}
