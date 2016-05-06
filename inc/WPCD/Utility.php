<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Utility' ) ) {
	/**
	 * This class contains some utility functions.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Utility {

		/**
		 * This function correctly parses (and optionally formats) a customizer setting.
		 *
		 * @see wpcd_get_customizer_settings()
		 * @see wpcd_get_customizer_setting()
		 * @since 0.5.0
		 * @param mixed $setting the setting to parse (or format)
		 * @param WPCD\Components\Field $field the field component the setting belongs to
		 * @param boolean $formatted whether to return automatically formatted values, ready for output (default is false)
		 * @return mixed the parsed (or formatted) setting
		 */
		public static function parse_setting( $setting, $field, $formatted = false ) {
			return $field->sanitize_setting( $setting, $formatted );
		}

		/**
		 * Validates the position argument.
		 *
		 * @see WPCD\Components\Panel
		 * @see WPCD\Components\Section
		 * @see WPCD\Components\Field
		 * @since 0.5.0
		 * @param array $args array of arguments
		 * @return array the validated arguments
		 */
		public static function validate_position_args( $args ) {
			if ( null !== $args['position'] ) {
				$args['position'] = floatval( $args['position'] );
			}

			return $args;
		}

	}
}
