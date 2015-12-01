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

if ( ! class_exists( 'WPCD\Controls\NumberControl' ) ) {

	class NumberControl extends WPCustomizeControl {
		public $type = 'number';
	}

}