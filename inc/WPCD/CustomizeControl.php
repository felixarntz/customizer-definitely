<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

use WP_Customize_Control as WPCustomizeControl;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\CustomizeControl' ) ) {

	class CustomizeControl extends WPCustomizeControl {
		public $_field = null;

		public function __construct( $wp_customize, $id, $args, $field ) {
			if ( isset( $args['type'] ) ) {
				$args['type'] = 'wpdlib_' . $args['type'];
			}
			parent::__construct( $wp_customize, $id, $args );
			$this->_field = $field;
		}

		protected function render_content() {
			$wrap_label = true;
			if ( in_array( substr( $this->type, 7 ), array( 'radio', 'checkbox', 'multibox' ), true ) ) {
				$wrap_label = false;
			}

			$id = str_replace( array( '[', ']' ), array( '-', '' ), $this->id );

			if ( $wrap_label ) {
				echo '<label for="' . $id . '">';
			}

			if ( ! empty( $this->label ) ) {
				echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
			}
			if ( ! empty( $this->description ) ) {
				echo '<span class="description customize-control-description">' . $this->description . '</span>';
			}

			$this->_field->__set( 'id', $id );
			$this->_field->__set( 'name', '_customize-' . $this->type . '-' . $this->id );

			$setting_link = $this->get_link_value();
			if ( $setting_link ) {
				$this->_field->__set( 'data-customize-setting-link', $setting_link );
			}

			$this->_field->display( $this->value() );

			if ( $wrap_label ) {
				echo '</label>';
			}
		}

		protected function get_link_value( $setting_key = 'default' ) {
			if ( ! isset( $this->settings[ $setting_key ] ) ) {
				return '';
			}

			return $this->settings[ $setting_key ]->id;
		}
	}

}
