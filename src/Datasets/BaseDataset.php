<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Seeder command for WP CLI.
 *
 * @package   Sematico\Seeder
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */

namespace Sematico\Seeder\Datasets;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

/**
 * Base class for datasets.
 */
abstract class BaseDataset {

	/**
	 * The name of the dataset.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Get the name of the dataset.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set the name of the dataset.
	 *
	 * @param string $name The name of the dataset.
	 * @return void
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Generate the dataset.
	 *
	 * @return void
	 */
	abstract public function generate();

	/**
	 * Ask for a number.
	 *
	 * @param string $label The label to display.
	 * @return string
	 */
	public function ask_number( string $label ) {
		return text(
			label: $label,
			validate: fn ( string $value ) => match (true) {
				! is_numeric( $value ) => 'The value must be a number.',
				default => null
			},
		);
	}

	/**
	 * Ask if images should be generated.
	 *
	 * @return bool
	 */
	public function ask_images() {
		return confirm( 'Do you want to generate images?' );
	}

}
