<?php
/*
Plugin Name: Customizer Definitely
Plugin URI: http://wordpress.org/plugins/customizer-definitely/
Description: This framework plugin makes adding customizer panels, sections and fields to WordPress very simple, yet flexible. It all works using a single action and an array.
Version: 0.5.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpcd
Domain Path: /languages/
Tags: wordpress, plugin, framework, library, developer, customizer, admin, backend, ui
*/
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\App' ) && file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

\LaL_WP_Plugin_Loader::load_plugin( array(
	'slug'				=> 'customizer-definitely',
	'name'				=> 'Customizer Definitely',
	'version'			=> '0.5.0',
	'main_file'			=> __FILE__,
	'namespace'			=> 'WPCD',
	'textdomain'		=> 'wpcd',
), array(
	'phpversion'		=> '5.3.0',
	'wpversion'			=> '4.2',
) );
