<?php
/**
 * Ofertasmall Ofertas - Expired Status
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Expired_Status' ) ) {
	class Expired_Status {
		/**
		 * Add "Hidden" to post status dropdown
		 */
		public static function hidden_post_status_dropdown() {
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
		public static function hidden_post_status_list( $states ) {
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
		public static function hidden_post_status_edit() {
			global $post;
			if ( isset( $post->post_type ) && ( $post->post_type == Offer_CPT::$post_type ) ) {
				echo '<script>
                jQuery(document).ready(function($) {
                  $(".inline-edit-status select").append("<option value=\"hidden\">Hidden</option>");
                });
                </script>';
			}
		}

		public static function add_expiration_post_status() {
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

		public static function schedule_post_expiration( $offer_wp_id ) {
			$expired_status = sanitize_title( Admin_Settings::get_option( 'offers_expire_status', 'omo_general', __( 'Expired', 'ofertasmall-ofertas' ) ) );
			wp_update_post( array(
				'ID'          => $offer_wp_id,
				'post_status' => $expired_status
			) );
		}
	}
}