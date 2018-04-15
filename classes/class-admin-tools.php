<?php
/**
 * Ofertasmall Ofertas - Admin Tools
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Admin_Tools' ) ) {
	class Admin_Tools {

		public static $settings_api;

		public static function admin_menu() {
			add_management_page( __( 'Import Offers', 'ofertasmall-ofertas' ), __( 'Import Offers', 'ofertasmall-ofertas' ), 'delete_posts', 'ofertasmall-ofertas-import', array( __CLASS__, 'plugin_page' ) );
		}

		public static function import_offers() {
			if (
				! isset( $_POST['omo_import_offers_form'] )
				|| ! wp_verify_nonce( $_POST['omo_import_offers_form'], 'omo_import_offers_action' )
			) {
				return;
			}

			$token      = Admin_Settings::get_option( 'token' );
			$stores_api = new Ofertasmall_Offers_API( array(
				'token' => $token,
			) );

			$import = new Import( array(
				'offers_post_type' => Offer_CPT::$post_type,
				'offer_tax'       => Store_Tax::$taxonomy
			) );
			$import->import_offers_from_offers_api( $stores_api );
		}

		public static function echo_style() {
			?>
            <style>
                .omo-progress-wrapper {
                    width: 100%;
                    display: inline-block;
                    height: 33px;
                    position: relative;
                    border: 1px solid #b3b3b3;
                }

                .omo-progress-bar {
                    position: absolute;
                    left: 0;
                    top: 0;
                    background: #cecece;
                    width: '.$percentage_pretty.'%;
                    height: 100%;
                    transition:all 1s ease-in-out;
                }

                .omo-progress-value {
                    position: absolute;
                    width: 100%;
                    text-align: center;
                    line-height: 32px;
                    color: #676767;
                    font-size: 18px;
                    text-transform: uppercase;
                    z-index: 2;
                }

                .omo-progress-label:after{
                    opacity: 0;
                    content:url('https://media.giphy.com/media/EMspSu9w0djAA/giphy.gif');
                    display: inline-block;
                    margin-left: 13px;
                    vertical-align: middle;
                    transition:all 1s ease-in-out;
                }

                .omo-progress-label.progress:after{
                    opacity: 1;
                }
            </style>
			<?php
		}

		public static function show_background_process_progress() {
			?>
            <script>
                jQuery(document).ready(function ($) {
                    var omo_interval;
                    var percent = 0;
                    var count = 0;
                    var no_queue = false;

                    function omo_call_ajax() {
                        var data = {
                            'action': 'omo_show_bkg_process_percentage'
                        };
                        jQuery.post(ajaxurl, data, function (response) {
                            count++;
                            percent = Math.round(response.data.percent*100);
                            if(percent>0 && percent<100){
                                jQuery('.omo-progress-label').addClass('progress');
                            }
                            $('.omo-progress-bar').css('width', percent + '%');
                            $('.omo-progress-value').html(percent + '%');
                        });
                    }

                    omo_interval = setInterval(handle_interval, 3000);

                    function handle_interval() {
                        if (percent < 100 && !no_queue && percent > 0 || count ==0) {
                            omo_call_ajax();
                        } else {
                            jQuery('.omo-progress-label').removeClass('progress');
                            clearInterval(omo_interval);
                        }
                    }
                });
            </script>
			<?php
		}

		public static function plugin_page() {
			$import            = new Import( array(
				'offers_post_type' => Offer_CPT::$post_type,
				'offer_tax'       => Store_Tax::$taxonomy
			) );
			$percentage        = $import->get_bkg_process_percentage();
			$percentage_pretty = 100 * $percentage;

			self::echo_style();
			self::show_background_process_progress();

			echo '<form method="post">';
			wp_nonce_field( 'omo_import_offers_action', 'omo_import_offers_form' );
			echo '<div class="wrap">';
			echo '<h1>' . __( 'Import Offers', 'ofertasmall-ofertas' ) . '</h1>';
			echo '<table class="form-table">';
			echo '
			<tr>
				<th scope="row"><label class="omo-progress-label" for="blogname">' . __( 'Progress', 'ofertasmall-ofertas' ) . '</label></th>
				<td>
					<div class="omo-progress-wrapper">
						<span class="omo-progress-value">' . $percentage_pretty . '%</span>
						<span class="omo-progress-bar"></span>
					</div>
				</td>
			</tr>';
			echo '</table>';
			echo '<p class="submit">';
			echo '<input type="submit" name="omo_import_offers" id="omo_import_offers" class="button button-primary" value="' . __( 'Import Offers', 'ofertasmall-ofertas' ) . '"/>';
			echo '</p>';
			echo '</form>';
			echo '</div>';
		}


	}
}