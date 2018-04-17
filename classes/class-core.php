<?php
/**
 * Ofertasmall Ofertas - Core class
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Core' ) ) {
	class Core extends WP_Plugin {

		/**
		 * @var Import_Background_Process
		 */
		public $import_bkg_process;

		public function init() {
			parent::init();

			// Admin Settings page
			add_action( 'admin_init', array( 'TxToIT\OMO\Admin_Settings', 'admin_init' ) );
			add_action( 'admin_menu', array( 'TxToIT\OMO\Admin_Settings', 'admin_menu' ) );

			// Tools Settings page
			add_action( 'admin_init', array( 'TxToIT\OMO\Admin_Tools', 'import_offers' ) );
			add_action( 'admin_menu', array( 'TxToIT\OMO\Admin_Tools', 'admin_menu' ) );



			// Register Custom post type
			add_action( 'init', array( 'TxToIT\OMO\Offer_CPT', 'register_cpt' ), 1 );

			// Register taxonomy
			add_action( 'init', function () {
				Store_Tax::register_taxonomy( Offer_CPT::$post_type );
			}, 1 );

			// Initialize background process
			add_action( 'init', function () {
				$this->import_bkg_process = new Import_Background_Process();
			} );

			// Handle custom fields
			/*add_action( 'admin_init', function () {
				$custom_fields = new Custom_Fields( array(
					'offers_post_type' => Store_CPT::$post_type
				) );
				$custom_fields->create_custom_fields();
			} );*/

			// Reject unsafe urls
			add_filter( 'http_request_args', array( $this, 'turn_off_reject_unsafe_urls' ), 10, 2 );

			// Ajax
			add_action( 'wp_ajax_omo_show_bkg_process_percentage', function () {
				$import     = new Import( array(
					'offers_post_type' => Offer_CPT::$post_type,
					'offer_tax'        => Store_Tax::$taxonomy
				) );
				$percentage = $import->get_bkg_process_percentage();

				wp_send_json_success( array( 'percent' => $percentage ) );
				wp_die();
				//return $import->update_bkg_process_task($item);
			} );

			// Expire post status
			add_action( 'omo_schedule_post_expiration', array( $this, 'schedule_post_expiration' ) );

			// Add expiration post status
			add_action( 'init', array( $this, 'add_expiration_post_status' ), 1 );
			add_filter( 'display_post_states', array( $this, '_s_hidden_post_status_list' ) );
			add_action( 'admin_footer-edit.php', array( $this, '_s_hidden_post_status_edit' ) );
			add_action( 'admin_footer-post.php', array( $this, '_s_hidden_post_status_dropdown' ) );
		}

		/**
		 * Add "Hidden" to post status dropdown
		 */
		function _s_hidden_post_status_dropdown() {
			global $post;
			$complete = $label = '';
			if ( $post->post_type == Offer_CPT::$post_type ) {
				if ( $post->post_status == 'expired' ) {
					$complete = 'selected=\"selected\"';
					$label    = '<span id=\"post-status-display\"> Expired</span>';
				}
				echo '<script>
                jQuery(document).ready(function($) {
                  $("select#post_status").append("<option value=\"expired\" ' . $complete . '>Expired</option>");
                  $(".misc-pub-section label").append("' . $label . '");
                });
                </script>';
			}
		}

		/**
		 * Add "Hidden" as label in post list
		 */
		function _s_hidden_post_status_list( $states ) {
			global $post;
			$arg = get_query_var( 'post_status' );
			if ( $arg != 'expired' ) {
				if ( $post->post_status == 'expired' ) {
					return array( 'Expired' );
				}
			}

			return $states;
		}

		/**
		 * Add "Hidden" to status bulk/quick edit list
		 */
		function _s_hidden_post_status_edit() {
			global $post;
			if ( isset( $post->post_type ) && ( $post->post_type == Offer_CPT::$post_type ) ) {
				echo '<script>
                jQuery(document).ready(function($) {
                  $(".inline-edit-status select").append("<option value=\"hidden\">Hidden</option>");
                });
                </script>';
			}
		}

		public function add_expiration_post_status() {
			$expired_status           = Admin_Settings::get_option( 'offers_expire_status', 'omo_general', __( 'Expired', 'ofertasmall-ofertas' ) );
			$expired_status_sanitized = sanitize_title( $expired_status );

			register_post_status( $expired_status_sanitized, array(
				'label'                     => $expired_status,
				'public'                    => false,
				//'private'                   => true,
				'internal'                  => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Expirado <span class="count">(%s)</span>', 'Expirados <span class="count">(%s)</span>' ),
			) );
		}

		public function schedule_post_expiration( $offer_wp_id ) {
			$expired_status = sanitize_title( Admin_Settings::get_option( 'offers_expire_status', 'omo_general', __( 'Expired', 'ofertasmall-ofertas' ) ) );
			wp_update_post( array(
				'ID'          => $offer_wp_id,
				'post_status' => $expired_status
			) );
		}

		function turn_off_reject_unsafe_urls( $args, $url ) {
			$args['reject_unsafe_urls'] = false;

			return $args;
		}

		/**
		 * @return Core
		 */
		public static function get_instance() {
			return parent::get_instance(); // TODO: Change the autogenerated stub
		}

	}
}