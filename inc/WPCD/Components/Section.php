<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD\Components;

use WPDLib\Components\Base as Base;
use WP_Customize_Section;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Components\Section' ) ) {

	class Section extends Base {

		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpcd_section_validated';
		}

		public function register( $wp_customize, $parent_panel = null ) {
			if ( $parent_panel === null ) {
				$parent_panel = $this->get_parent();
			}

			$args = array();
			foreach ( $this->args as $key => $value ) {
				if ( null !== $value ) {
					$args[ $key ] = $value;
				}
			}
			if ( isset( $args['mode'] ) ) {
				$args['type'] = $args['mode'];
				unset( $args['mode'] );
			}
			if ( isset( $args['position'] ) ) {
				$args['priority'] = $args['position'];
				unset( $args['position'] );
			}

			$slug = $this->slug;
			if ( 'general' != $parent_panel->slug ) {
				$slug = $parent_panel->slug . '-' . $slug;
				$this->args['panel'] = $parent_panel->slug;
			}

			$wp_customize->add_section( new WP_Customize_Section( $wp_customize, $slug, $args ) );
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {

				if ( null === $this->args['capability'] ) {
					$this->args['capability'] = $parent->capability;
				}

				if ( isset( $this->args['priority'] ) ) {
					if ( null === $this->args['position'] ) {
						$this->args['position'] = $this->args['priority'];
					}
					unset( $this->args['priority'] );
				}

				if ( null !== $this->args['position'] ) {
					$this->args['position'] = floatval( $this->args['position'] );
				}
			}

			return $status;
		}

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * Read the plugin guide for more information about the section arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'title'					=> __( 'Section title', 'wpcd' ),
				'description'			=> '',
				'capability'			=> null,
				'position'				=> null,
				'theme_supports'		=> null,
				'mode'					=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each section component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpcd_section_defaults', $defaults );
		}

		/**
		 * Returns whether this component supports multiple parents.
		 *
		 * @since 0.5.0
		 * @return bool
		 */
		protected function supports_multiparents() {
			return false;
		}

		/**
		 * Returns whether this component supports global slugs.
		 *
		 * If it does not support global slugs, the function either returns false for the slug to be globally unique
		 * or the class name of a parent component to ensure the slug is unique within that parent's scope.
		 *
		 * @since 0.5.0
		 * @return bool|string
		 */
		protected function supports_globalslug() {
			return 'WPCD\Components\Panel';
		}

	}

}
