<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD\Components;

use WPDLib\Components\Base as Base;
use WP_Customize_Panel as WPCustomizePanel;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Components\Panel' ) ) {

	class Panel extends Base {

		protected $_id = '';

		protected $_panel = null;

		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpcd_panel_validated';
		}

		public function register( $wp_customize ) {
			if ( 'general' === $this->slug ) {
				return;
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
			if ( isset( $args['settings_mode'] ) ) {
				unset( $args['settings_mode'] );
			}

			$this->_id = $this->slug;

			$wp_customize->add_panel( new WPCustomizePanel( $wp_customize, $this->_id, $args ) );
			$this->_panel = $wp_customize->get_panel( $this->_id );
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
					$this->args['capability'] = 'edit_theme_options';
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
		 * Read the plugin guide for more information about the panel arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'title'					=> __( 'Panel title', 'customizer-definitely' ),
				'description'			=> '',
				'capability'			=> null,
				'position'				=> null,
				'theme_supports'		=> null,
				'mode'					=> null,
				'settings_mode'			=> null, //TODO: what is this? old?
			);

			/**
			 * This filter can be used by the developer to modify the default values for each panel component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpcd_panel_defaults', $defaults );
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
			return false;
		}

	}

}
