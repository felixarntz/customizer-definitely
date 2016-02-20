<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\AJAX' ) ) {
	/**
	 * This class performs the necessary actions in the WordPress customizer.
	 *
	 * This includes both registering and displaying customizer panels, sections and fields, as well as including the required scripts.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class AJAX {

		/**
		 * @since 0.5.0
		 * @var WPCD\AJAX|null Holds the instance of this class.
		 */
		private static $instance = null;

		/**
		 * Gets the instance of this class. If it does not exist, it will be created.
		 *
		 * @since 0.5.0
		 * @return WPCD\AJAX
		 */
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		private $actions = array(
			'post-id-to-field',
			'get-attachment-url',
			'number-format-i18n',
			'date-i18n',
			'content-format',
		);

		/**
		 * Class constructor.
		 *
		 * This will hook functions into the 'customize_register' and 'customize_preview_init' actions.
		 *
		 * @since 0.5.0
		 */
		private function __construct() {
			foreach ( $this->actions as $action ) {
				add_action( 'wp_ajax_wpcd-' . $action, array( $this, 'request' ) );
			}
		}

		public function request() {
			$data = $_POST;

			if ( ! isset( $data['nonce'] ) ) {
				wp_send_json_error( __( 'Missing nonce.', 'customizer-definitely' ) );
			}

			if ( ! check_ajax_referer( 'wpcd-customize-preprocess', 'nonce', false ) ) {
				wp_send_json_error( __( 'Invalid nonce.', 'customizer-definitely' ) );
			}

			$method_name = str_replace( '-', '_', str_replace( 'wp_ajax_wpcd-', '', current_action() ) );
			if ( ! method_exists( $this, $method_name ) ) {
				wp_send_json_error( __( 'Invalid action.', 'customizer-definitely' ) );
			}

			$response = $this->$method_name( $data );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( $response->get_error_message() );
			}

			wp_send_json_success( $response );
		}

		public function manual_request( $action, $data ) {
			$method_name = str_replace( '-', '_', str_replace( 'wpcd-', '', $action ) );
			if ( ! method_exists( $this, $method_name ) ) {
				return new WPError( 'invalid_action', __( 'Invalid action.', 'customizer-definitely' ) );
			}

			return $this->$method_name( $data );
		}

		private function post_id_to_field( $data ) {
			if ( ! isset( $data['id'] ) ) {
				return new WPError( 'missing_request_field', sprintf( __( 'Missing request field %s.', 'customizer-definitely' ), 'id' ) );
			}

			$post_id = absint( $data['id'] );

			$post = get_post( $post_id );

			if ( ! $post ) {
				return new WPError( 'invalid_id', sprintf( __( 'The post with ID %s does not exist.', 'customizer-definitely' ), $post_id ) );
			}

			$field = isset( $data['field'] ) ? $data['field'] : 'title';

			if ( isset( $data['mode'] ) && 'meta' === $data['mode'] ) {
				return get_post_meta( $post_id, $field, true );
			}

			if ( ! isset( $post->$field ) ) {
				$field = 'post_' . $field;
				if ( ! isset( $post->$field ) ) {
					return new WPError( 'invalid_field', sprintf( __( 'The post %1$s does not have a field called %2$s.', 'customizer-definitely' ), $post_id, $field ) );
				}
			}

			return $post->$field;
		}

		private function get_attachment_url( $data ) {
			if ( ! isset( $data['id'] ) ) {
				return new WPError( 'missing_request_field', sprintf( __( 'Missing request field %s.', 'customizer-definitely' ), 'id' ) );
			}

			$attachment_id = 0;
			if ( ! is_numeric( $data['id'] ) ) {
				$attachment_id = attachment_url_to_postid( $data['id'] );
			} else {
				$attachment_id = absint( $data['id'] );
			}

			$post = get_post( $attachment_id );
			if ( ! $post ) {
				return new WPError( 'invalid_id', sprintf( __( 'The attachment with ID %s does not exist.', 'customizer-definitely' ), $post_id ) );
			}

			if ( isset( $data['size'] ) && 'full' !== $data['size'] ) {
				$src = wp_get_attachment_image_src( $attachment_id, $data['size'] );
				if ( ! isset( $src['0'] ) ) {
					return new WPError( 'invalid_size', sprintf( __( 'The attachment %1$s does not exist in size %2$s.', 'customizer-definitely' ), $attachment_id, $data['size'] ) );
				}
				return $src['0'];
			}

			$url = wp_get_attachment_url( $attachment_id );
			if ( ! $url ) {
				return new WPError( 'invalid_size', sprintf( __( 'The attachment %1$s does not exist in size %2$s.', 'customizer-definitely' ), $attachment_id, 'full' ) );
			}

			return $url;
		}

		private function number_format_i18n( $data ) {
			if ( ! isset( $data['number'] ) ) {
				return new WPError( 'missing_request_field', sprintf( __( 'Missing request field %s.', 'customizer-definitely' ), 'number' ) );
			}

			$number = $data['number'];
			$decimals = isset( $data['decimals'] ) ? absint( $data['decimals'] ) : 0;

			return number_format_i18n( $number, $decimals );
		}

		private function date_i18n( $data ) {
			if ( ! isset( $data['date'] ) ) {
				return new WPError( 'missing_request_field', sprintf( __( 'Missing request field %s.', 'customizer-definitely' ), 'date' ) );
			}

			$date = $data['date'];
			$format = isset( $data['format'] ) ? $data['format'] : 'Y-m-d';

			return date_i18n( $format, strtotime( $date ) );
		}

		private function content_format( $data ) {
			if ( ! isset( $data['content'] ) ) {
				return new WPError( 'missing_request_field', sprintf( __( 'Missing request field %s.', 'customizer-definitely' ), 'content' ) );
			}

			$content = $data['content'];

			if ( isset( $data['filters'] ) ) {
				foreach ( $data['filters'] as $filter ) {
					if ( is_callable( $filter ) ) {
						$content = call_user_func( $filter, $content );
					}
				}
			}

			return $content;
		}
	}
}
