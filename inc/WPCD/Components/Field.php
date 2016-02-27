<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD\Components;

use WPCD\Utility as Utility;
use WPCD\Customizer as Customizer;
use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Components\Base as Base;
use WPDLib\FieldTypes\Manager as FieldManager;
use WPDLib\Util\Error as UtilError;
use WP_Error as WPError;
use WP_Customize_Setting as WPCustomizeSetting;
use WPCD\CustomizeControl as WPCustomizeControl;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Components\Field' ) ) {

	class Field extends Base {

		protected $_id = '';

		/**
		 * @since 0.5.0
		 * @var WPDLib\FieldTypes\Base Holds the field type object from WPDLib.
		 */
		protected $_field = null;

		protected $_setting = null;

		protected $_control = null;

		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpcd_field_validated';
		}

		public function __get( $property ) {
			$value = parent::__get( $property );
			if ( null === $value ) {
				$value = $this->_field->$property;
			}
			return $value;
		}

		public function register( $wp_customize, $parent_section = null, $parent_panel = null ) {
			if ( null === $parent_section ) {
				$parent_section = $this->get_parent();
			}
			if ( null === $parent_panel ) {
				$parent_panel = $parent_section->get_parent();
			}

			$setting_args_map = array(
				'mode'					=> 'type',
				'capability'			=> null,
				'theme_supports'		=> null,
				'default'				=> null,
				'transport'				=> null,
			);

			$setting_args = array();
			foreach ( $setting_args_map as $key => $real_key ) {
				if ( isset( $this->args[ $key ] ) && null !== $this->args[ $key ] ) {
					if ( null !== $real_key ) {
						$setting_args[ $real_key ] = $this->args[ $key ];
					} else {
						$setting_args[ $key ] = $this->args[ $key ];
					}
				}
			}

			$setting_args['sanitize_callback'] = array( $this, 'sanitize_setting' );
			$setting_args['sanitize_js_callback'] = array( $this, 'sanitize_setting' );

			$control_args_map = array(
				'title'					=> 'label',
				'description'			=> null,
				'type'					=> null,
				'options'				=> 'choices',
				'position'				=> 'priority',
				'active_callback'		=> null,
			);

			$control_args = array();
			foreach ( $control_args_map as $key => $real_key ) {
				if ( isset( $this->args[ $key ] ) && null !== $this->args[ $key ] ) {
					if ( null !== $real_key ) {
						$control_args[ $real_key ] = $this->args[ $key ];
					} else {
						$control_args[ $key ] = $this->args[ $key ];
					}
				}
			}

			$_remaining_args = array_diff_key( $this->args, $setting_args_map, $control_args_map );
			unset( $_remaining_args['preview_args'] );

			$control_args['input_attrs'] = $_remaining_args;

			$this->_id = $this->slug;
			$parent_section_slug = $parent_section->slug;
			if ( 'general' != $parent_panel->slug ) {
				$this->_id = $parent_panel->slug . '[' . $this->_id . ']';
				$parent_section_slug = $parent_panel->slug . '-' . $parent_section_slug;
			}

			$control_args['section'] = $parent_section_slug;

			$wp_customize->add_setting( new WPCustomizeSetting( $wp_customize, $this->_id, $setting_args ) );
			$this->_setting = $wp_customize->get_setting( $this->_id );

			$wp_customize->add_control( new WPCustomizeControl( $wp_customize, $this->_id, $control_args, $this->_field ) );
			$this->_control = $wp_customize->get_control( $this->_id );
		}

		/**
		 * Sanitizes the customizer setting value for this field.
		 *
		 * @since 0.5.0
		 * @param mixed $setting the setting value to sanitize
		 * @param boolean $formatted whether to return automatically formatted values, ready for output (default is false)
		 */
		public function sanitize_setting( $setting = null, $formatted = false ) {
			if ( null !== $setting ) {
				$setting = $this->_field->parse( $setting, $formatted );
			} else {
				$setting = $this->_field->parse( $this->args['default'], $formatted );
			}
			return $setting;
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

				$this->args = Utility::validate_position_args( $this->args );

				if ( null === $this->args['mode'] ) {
					$this->args['mode'] = $parent->get_parent()->mode;
				}

				if ( is_array( $this->args['class'] ) ) {
					$this->args['class'] = implode( ' ', $this->args['class'] );
				}

				if ( isset( $this->args['options'] ) && ! is_array( $this->args['options'] ) ) {
					$this->args['options'] = array();
				}

				if ( in_array( $this->args['type'], array( 'multiselect', 'multibox' ), true ) ) {
					return new UtilError( 'multichoice_not_supported', sprintf( __( 'The multichoice field type assigned to the field component %s is not supported in the Customizer.', 'customizer-definitely' ), $this->slug ), '', ComponentManager::get_scope() );
				}

				if ( 'repeatable' == $this->args['type'] ) {
					return new UtilError( 'repeatable_not_supported', sprintf( __( 'The repeatable field type assigned to the field component %s is not supported in the Customizer.', 'customizer-definitely' ), $this->slug ), '', ComponentManager::get_scope() );
				}

				$this->_field = FieldManager::get_instance( $this->args );
				if ( null === $this->_field ) {
					return new UtilError( 'no_valid_field_type', sprintf( __( 'The field type %1$s assigned to the field component %2$s is not a valid field type.', 'customizer-definitely' ), $this->args['type'], $this->slug ), '', ComponentManager::get_scope() );
				}
				if ( null === $this->args['default'] ) {
					$this->args['default'] = $this->_field->validate();
				}

				$this->args['preview_args'] = Customizer::instance()->validate_preview_args( $this->args['preview_args'], $this->args['type'], $this->_field );

				if ( null === $this->args['transport'] ) {
					if ( $this->args['preview_args']['update_callback'] ) {
						$this->args['transport'] = 'postMessage';
					} else {
						$this->args['transport'] = 'refresh';
					}
				}
			}

			return $status;
		}

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * Read the plugin guide for more information about the field arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$parent_section = $this->get_parent();
			$parent_panel = $parent_section->get_parent();

			$default_mode = 'theme_mod';
			if ( null !== $parent_panel->settings_mode ) {
				$default_mode = $parent_panel->settings_mode;
			}

			$defaults = array(
				'title'					=> __( 'Field title', 'customizer-definitely' ),
				'description'			=> '',
				'type'					=> 'text',
				'class'					=> '',
				'default'				=> null,
				'required'				=> false,
				'position'				=> null,
				'mode'					=> $default_mode,
				'transport'				=> null,
				'preview_args'			=> array(),
				'capability'			=> null,
				'theme_supports'		=> null,
				'active_callback'		=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each field component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpcd_field_defaults', $defaults );
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
