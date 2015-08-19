<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD\Components;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Components\Base as Base;
use WPDLib\FieldTypes\Manager as FieldManager;
use WPDLib\Util\Error as UtilError;
use WP_Error as WPError;
use WP_Customize_Setting;
use WP_Customize_Control;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Components\Field' ) ) {

	class Field extends Base {

		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpcd_field_validated';
		}

		/**
		 * @since 0.5.0
		 * @var WPDLib\FieldTypes\Base Holds the field type object from WPDLib.
		 */
		protected $_field = null;

		public function __get( $property ) {
			$value = parent::__get( $property );
			if ( null === $value ) {
				$value = $this->_field->$property;
			}
			return $value;
		}

		public function register( $wp_customize, $parent_section = null, $parent_panel = null ) {
			if ( $parent_section === null ) {
				$parent_section = $this->get_parent();
			}
			if ( $parent_panel === null ) {
				$parent_panel = $parent_section->get_parent();
			}

			$setting_args_map = array(
				'mode'					=> 'type',
				'capability'			=> null,
				'theme_supports'		=> null,
				'default'				=> null,
				'transport'				=> null,
				'sanitize_callback'		=> null,
				'sanitize_js_callback'	=> null,
			);

			$setting_args = array();
			foreach ( $setting_args_map as $key => $real_key ) {
				if ( isset( $this->args[ $key ] ) && null !== $this->args[ $key ] ) {
					if ( null !== $real_key ) {
						$setting_args[ $real_key ] = $this->args[ $key ];
					} else {
						$setting_args[ $key ] = $this->args[ $key ];
					}
				}
			}

			$control_args_map = array(
				'title'					=> 'label',
				'description'			=> null,
				'type'					=> null,
				'options'				=> 'choices',
				'priority'				=> null,
				'active_callback'		=> null,
			);

			$control_args = array();
			foreach ( $control_args_map as $key => $real_key ) {
				if ( isset( $this->args[ $key ] ) && null !== $this->args[ $key ] ) {
					if ( null !== $real_key ) {
						$control_args[ $real_key ] = $this->args[ $key ];
					} else {
						$control_args[ $key ] = $this->args[ $key ];
					}
				}
			}

			$_remaining_args = array_diff_key( $this->args, $setting_args_map, $control_args_map );
			unset( $_remaining_args['live_preview_args'] );

			$control_args['input_attrs'] = $_remaining_args;

			$slug = $this->slug;
			$id = $this->slug;
			$parent_section_slug = $parent_section->slug;
			if ( 'general' != $parent_panel->slug ) {
				$slug = $parent_panel->slug . '-' . $slug;
				$id = $parent_panel->slug . '[' . $slug . ']';
				$parent_section_slug = $parent_panel->slug . '-' . $parent_section->slug;
			}

			$control_args['section'] = $parent_section_slug;
			$control_args['setting'] = $id;

			$wp_customize->add_setting( new WP_Customize_Setting( $wp_customize, $id, $setting_args ) );
			$wp_customize->add_control( new WP_Customize_Control( $wp_customize, $slug, $control_args ) );
		}

		/**
		 * Renders the field.
		 *
		 * This function will show the input field(s) in the post editing screen.
		 *
		 * @since 0.5.0
		 * @param WP_Post $post the post currently being shown
		 */
		public function render( $post ) {
			$parent_section = $this->get_parent();
			$parent_panel = $parent_section->get_parent();

			echo '<tr>';
			echo '<th scope="row"><label for="' . esc_attr( $this->args['id'] ) . '">' . $this->args['title'] . '</label></th>';
			echo '<td>';

			/**
			 * This action can be used to display additional content on top of this field.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current field
			 * @param array the arguments array for the current field
			 * @param string the slug of the current metabox
			 * @param string the slug of the current post type
			 */
			do_action( 'wpcd_field_before', $this->slug, $this->args, $parent_section->slug, $parent_panel->slug );

			$setting = wpcd_get_setting( $this->slug, $parent_panel->slug );

			$this->_field->display( $setting );

			if ( ! empty( $this->args['description'] ) ) {
				if ( 'checkbox' != $this->args['type'] ) {
					echo '<br/>';
				}
				echo '<span class="description">' . $this->args['description'] . '</span>';
			}

			/**
			 * This action can be used to display additional content at the bottom of this field.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current field
			 * @param array the arguments array for the current field
			 * @param string the slug of the current metabox
			 * @param string the slug of the current post type
			 */
			do_action( 'wpcd_field_after', $this->slug, $this->args, $parent_section->slug, $parent_panel->slug );

			echo '</td>';
			echo '</tr>';
		}

		/**
		 * Validates the meta value for this field.
		 *
		 * @since 0.5.0
		 * @param mixed $meta_value the new option value to validate
		 * @return mixed either the validated option or a WP_Error object
		 */
		public function validate_setting( $setting = null, $skip_required = false ) {
			if ( $this->args['required'] && ! $skip_required ) {
				if ( $setting === null || $this->_field->is_empty( $setting ) ) {
					return new WPError( 'invalid_empty_value', __( 'No value was provided for the required field.', 'wpcd' ) );
				}
			}
			return $this->_field->validate( $setting );
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

				if ( null !== $this->args['priority'] ) {
					$this->args['priority'] = floatval( $this->args['priority'] );
				}

				if ( null === $this->args['mode'] ) {
					$this->args['mode'] = $parent->get_parent()->mode;
				}

				if ( ! is_array( $this->args['live_preview_args'] ) ) {
					$this->args['live_preview_args'] = array();
				}
				$this->args['live_preview_args'] = wp_parse_args( $this->args['live_preview_args'], array(
					'callback'		=> '',
					'timeout'		=> 0,
					'selectors'		=> array(),
					'property'		=> '',
					'prefix'		=> '',
					'suffix'		=> '',
				) );
				if ( ! is_array( $this->args['live_preview_args']['selectors'] ) ) {
					if ( ! empty( $this->args['live_preview_args']['selectors'] ) ) {
						$this->args['live_preview_args']['selectors'] = array( $this->args['live_preview_args']['selectors'] );
					} else {
						$this->args['live_preview_args']['selectors'] = array();
					}
				}

				if ( is_array( $this->args['class'] ) ) {
					$this->args['class'] = implode( ' ', $this->args['class'] );
				}

				if ( isset( $this->args['options'] ) && ! is_array( $this->args['options'] ) ) {
					$this->args['options'] = array();
				}

				/*$this->args['id'] = $this->slug;
				$this->args['name'] = $this->slug;*/

				$this->_field = FieldManager::get_instance( $this->args );
				if ( $this->_field === null ) {
					return new UtilError( 'no_valid_field_type', sprintf( __( 'The field type %1$s assigned to the field component %2$s is not a valid field type.', 'wpcd' ), $this->args['type'], $this->slug ), '', ComponentManager::get_scope() );
				}
				if ( null === $this->args['default'] ) {
					$this->args['default'] = $this->_field->validate();
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
			$defaults = array(
				'title'					=> __( 'Field title', 'wpcd' ),
				'description'			=> '',
				'type'					=> 'text',
				'class'					=> '',
				'default'				=> null,
				'required'				=> false,
				'capability'			=> null,
				'priority'				=> null,
				'theme_supports'		=> null,
				'mode'					=> null,
				'transport'				=> null,
				'active_callback'		=> null,
				'sanitize_callback'		=> null,
				'sanitize_js_callback'	=> null,
				'live_preview_args'		=> array(),
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
