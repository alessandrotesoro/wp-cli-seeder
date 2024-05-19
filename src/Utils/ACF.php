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

}
