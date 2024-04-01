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

}
