<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD\Controls;

use WP_Customize_Control as WPCustomizeControl;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Controls\CustomizeControl' ) ) {

	class CustomizeControl extends WPCustomizeControl {
		public $_field = null;

		public function __construct( $wp_customize, $id, $args, $field ) {
			parent::__construct( $wp_customize, $id, $args );
			$this->_field = $field;
		}
	}

}
