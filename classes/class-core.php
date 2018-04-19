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

			Offer_CPT::set_offers_cpt();
			Store_Tax::set_offers_tax();

			// Admin Settings page
			add_action( 'admin_init', array( 'TxToIT\OMO\Admin_Settings', 'admin_init' ) );
			add_action( 'admin_menu', array( 'TxToIT\OMO\Admin_Settings', 'admin_menu' ) );

			// Tools Settings page
			add_action( 'admin_init', array( 'TxToIT\OMO\Admin_Tools', 'import_offers' ) );
			add_action( 'admin_menu', array( 'TxToIT\OMO\Admin_Tools', 'admin_menu' ) );

			// Register Custom post type
			//add_action( 'init', array( 'TxToIT\OMO\Offer_CPT', 'set_offers_cpt' ), 1 );
			add_action( 'init', array( 'TxToIT\OMO\Offer_CPT', 'register_cpt' ), 2 );

			// Register taxonomy
			add_action( 'init', function () {
				Store_Tax::register_taxonomy( Offer_CPT::$post_type );
			}, 2 );

			// Initialize background process
			add_action( 'init', function () {
				$this->import_bkg_process = new Import_Background_Process();
			} );

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
			} );

			// Change offer post status to Expired
			add_action( 'omo_schedule_post_expiration', array( 'TxToIT\OMO\Expired_Status', 'schedule_post_expiration' ) );

			// Add expiration post status
			add_action( 'init', array( 'TxToIT\OMO\Expired_Status', 'add_expiration_post_status' ), 1 );
			add_filter( 'display_post_states', array( 'TxToIT\OMO\Expired_Status', 'hidden_post_status_list' ) );
			add_action( 'admin_footer-edit.php', array( 'TxToIT\OMO\Expired_Status', 'hidden_post_status_edit' ) );
			add_action( 'admin_footer-post.php', array( 'TxToIT\OMO\Expired_Status', 'hidden_post_status_dropdown' ) );
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