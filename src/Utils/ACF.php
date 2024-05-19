<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Utility class to handle ACF fields.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Utils;

/**
 * Utility class to handle ACF fields.
 * All methods are static and can be called without instantiating the class.
 */
class ACF {

	/**
	 * Check if ACF is active.
	 *
	 * @return bool
	 */
	public static function is_acf_active(): bool {
		return class_exists( 'acf' );
	}

	/**
	 * Get all field groups for a given post type.
	 *
	 * @param string $post_type The post type to get the field groups for.
	 * @return array
	 */
	public static function get_all_fields_groups_for_post_type( string $post_type ): array {
		// Get all field groups
		$field_groups = acf_get_field_groups();

		// Initialize an array to hold the filtered field groups
		$filtered_field_groups = [];

		// Loop through each field group
		foreach ( $field_groups as $field_group ) {
			// Get the location rules for the field group
			$location_rules = self::get_group_locations( $field_group['key'] );

			// Check each location rule
			foreach ( $location_rules as $group ) {
				foreach ( $group as $rule ) {
					if ( $rule['param'] === 'post_type' && $rule['operator'] === '==' && $rule['value'] === $post_type ) {
						$filtered_field_groups[] = $field_group;
						break 2; // Exit both loops once a match is found
					}
				}
			}
		}

		return $filtered_field_groups;
	}

	/**
	 * Get the locations for a given field group.
	 *
	 * @param string $group_key The key of the field group.
	 * @return array
	 */
	public static function get_group_locations( $group_key ) {
		$field_group = acf_get_field_group( $group_key );
		return $field_group['location'];
	}

	/**
	 * Get all fields from all the given groups.
	 *
	 * @param array $groups An array of field groups.
	 * @return array
	 */
	public static function get_fields_from_groups( array $groups ): array {
		$fields = [];

		foreach ( $groups as $group ) {
			$fields = array_merge( $fields, acf_get_fields( $group['key'] ) );
		}

		return $fields;
	}

	/**
	 * Format fields for a dropdown.
	 *
	 * @param array $fields An array of fields.
	 * @return array
	 */
	public static function format_fields_for_dropdown( array $fields ): array {
		$formatted_fields = [];

		foreach ( $fields as $field ) {
			$formatted_fields[ $field['name'] ] = $field['label'];
		}

		return $formatted_fields;
	}

	/**
	 * Seed data for a post by field type.
	 *
	 * @param array  $field            The field data.
	 * @param int    $number_of_posts  The number of posts to seed.
	 * @param string $post_type        The post type to seed.
	 * @return void
	 */
	public static function seed_data_for_post_by_field_type( array $field, int $number_of_posts, string $post_type ) {
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'posts_per_page' => $number_of_posts,
				'fields'         => 'ids',
			]
		);

		$type = $field['type'];

		$progress = \WP_CLI\Utils\make_progress_bar( "Seeding data for the '{$field['label']}' field", count( $posts ) );

		foreach ( $posts as $post ) {
			$value = self::generate_field_value( $type, $field );

			update_field( $field['key'], $value, $post );

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Generate a value for a given field type.
	 *
	 * @param string $type  The field type.
	 * @param array  $field The field data.
	 * @return mixed
	 */
	public static function generate_field_value( string $type, array $field ) {
		$method = 'generate_field_' . $type . '_value';

		if ( method_exists( __CLASS__, $method ) ) {
			return self::$method( $field );
		}

		WP_CLI::error( 'The field type is not yet supported. The currently supported field types are: text, textarea, number, select, checkbox, radio.' );
	}

	/**
	 * Generate a value for a text field.
	 *
	 * @param array $field The field data.
	 * @return string
	 */
	public static function generate_field_text_value( array $field ) {
		return \Faker\Factory::create()->word();
	}

	/**
	 * Generate a value for a textarea field.
	 *
	 * @param array $field The field data.
	 * @return string
	 */
	public static function generate_field_textarea_value( array $field ) {
		return \Faker\Factory::create()->sentence();
	}

	/**
	 * Generate a value for a number field.
	 *
	 * @param array $field The field data.
	 * @return int
	 */
	public static function generate_field_number_value( array $field ) {
		return \Faker\Factory::create()->numberBetween( 1, 100 );
	}

	/**
	 * Generate a value for a select field.
	 *
	 * @param array $field The field data.
	 * @return string
	 */
	public static function generate_field_select_value( array $field ) {
		$options = $field['choices'];
		$option  = array_rand( $options, 1 );
		return $options[ $option ];
	}

	/**
	 * Generate a value for a checkbox field.
	 *
	 * @param array $field The field data.
	 * @return array
	 */
	public static function generate_field_checkbox_value( array $field ) {
		$options = $field['choices'];
		$option  = array_rand( $options, 1 );
		return [ $options[ $option ] ];
	}

	/**
	 * Generate a value for a radio field.
	 *
	 * @param array $field The field data.
	 * @return string
	 */
	public static function generate_field_radio_value( array $field ) {
		$options = $field['choices'];
		$option  = array_rand( $options, 1 );
		return $options[ $option ];
	}
}
