<?php
/**
 * Ofertasmall Ofertas - Import
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Import' ) ) {
	class Import {

		public $import_args = array();
		private $api_result;

		public function __construct( $import_args = array() ) {
			$import_args       = wp_parse_args( $import_args, array(
				'db_key_prefix'    => '_omo_',
				'offers_post_type' => '',
				'offer_tax'        => ''
			) );
			$this->import_args = $import_args;
		}

		public function import_offers_from_array( $offers_array, $background_processing = true ) {
			if ( ! $background_processing ) {
				foreach ( $offers_array as $offer ) {
					self::import_offer( $offer );
				}
			} else {
				//update_option( '_omo_import_count', 0, false );
				update_option( '_omo_imported_offer_ids', array(), false );
				update_option( '_omo_import_error_offer_ids', array(), false );
				$plugin      = Core::get_instance();
				$bkg_process = $plugin->import_bkg_process;
				$bkg_process->cancel_process();
				foreach ( $offers_array as $offer ) {
					$bkg_process->push_to_queue( $offer['id'] );
				}
				$bkg_process->save()->dispatch();
			}
		}

		private function filter_unwanted_custom_fields( $k ) {
			return ! in_array( $k, array(
				'ativo',
				'nome',
				'criado',
				'atualizado',
				'categorias',
				'reservas'
			) );
		}

		private function turn_null_custom_fields_into_empty( $v ) {
			if ( $v == null ) {
				$v = '';
			}

			return $v;
		}

		public function import_offer( $offer ) {
			//$offer['despublicar_em'] = '2018-04-18 01:06:34';

			//error_log(print_r($offer,true));

			// Remove unwanted custom fields
			$metas_to_save = array_filter( $offer, array( $this, 'filter_unwanted_custom_fields' ), ARRAY_FILTER_USE_KEY );

			// Turns null custom fields into empty ones
			$metas_to_save = array_map( array( $this, 'turn_null_custom_fields_into_empty' ), $metas_to_save );

			// Add prefix
			$metas_to_save_with_prefix = array_combine(
				array_map( function ( $k ) {
					return $this->import_args['db_key_prefix'] . $k;
				}, array_keys( $metas_to_save ) ),
				$metas_to_save
			);

			$the_query = new \WP_Query( array(
				'post_status'            => 'any',
				'cache_results'          => false,
				'no_found_rows'          => false,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'fields'                 => 'ids',
				'post_type'              => $this->import_args['offers_post_type'],
				'meta_query'             => array(
					array(
						'key'     => $this->import_args['db_key_prefix'] . 'id',
						'value'   => $offer['id'],
						'compare' => '=',
					),
				),
			) );

			if ( $the_query->have_posts() ) {
				foreach ( $the_query->posts as $post_id ) {
					$post_update_arr       = $this->get_post_update_array( $offer, $metas_to_save_with_prefix );
					$post_update_arr['ID'] = $post_id;
					wp_update_post( $post_update_arr );
					$this->import_terms( $offer, $post_id );
					$this->download_fotos( $offer, $post_id );
					$this->setup_schedule_post_expiration( $offer, $post_id );
				}

				/* Restore original Post Data */
				wp_reset_postdata();
			} else {
				$offer_wp_id = wp_insert_post( $this->get_post_update_array( $offer, $metas_to_save_with_prefix ) );
				$this->import_terms( $offer, $offer_wp_id );
				$this->download_fotos( $offer, $offer_wp_id );
				$this->setup_schedule_post_expiration( $offer, $offer_wp_id );
			}
		}

		public function setup_schedule_post_expiration( $offer, $offer_wp_id ) {
			$despublicar_em        = isset( $offer['despublicar_em'] ) ? $offer['despublicar_em'] : null;
			$despublicar_is_future = false;
			if ( ! empty( $despublicar_em ) ) {
				$date_despublicar_em = new \DateTime( $despublicar_em );

				$date_now = new \DateTime();
				if ( $date_despublicar_em > $date_now ) {
					$despublicar_is_future = true;
				}
			}

			wp_clear_scheduled_hook( 'omo_schedule_post_expiration' );

			if ( $despublicar_is_future ) {
				wp_schedule_single_event( $date_despublicar_em->getTimestamp(), 'omo_schedule_post_expiration', array( $offer_wp_id ) );
			}
		}

		protected function download_fotos( $offer, $offer_wp_id ) {
			$download = filter_var( Admin_Settings::get_option( 'download_images', 'omo_general', 'on' ), FILTER_VALIDATE_BOOLEAN );
			if ( ! $download ) {
				return;
			}
			$photos_max = 5;

			for ( $i = 1; $i <= $photos_max; $i ++ ) {
				if (
					! isset( $offer["foto{$i}"] ) ||
					empty( $offer["foto{$i}"] )
				) {
					continue;
				}

				$foto_url    = $offer["foto{$i}"];
				$result      = $this->media_sideload_image( $foto_url, $offer_wp_id, null, 'id' );
				$old_foto_id = get_post_meta( $offer_wp_id, $this->import_args['db_key_prefix'], true );

				if ( is_wp_error( $result ) ) {
					error_log( print_r( $result, true ) );
					continue;
				}

				if ( ! empty( $old_foto_id ) ) {
					wp_delete_attachment( $old_foto_id, true );
				}
				update_post_meta( $offer_wp_id, $this->import_args['db_key_prefix'] . 'logo_wp_id', $result );
			}
		}

		/**
		 * Downloads an image from the specified URL and attaches it to a post.
		 *
		 * @since 2.6.0
		 * @since 4.2.0 Introduced the `$return` parameter.
		 * @since 4.8.0 Introduced the 'id' option within the `$return` parameter.
		 *
		 * @param string $file The URL of the image to download.
		 * @param int $post_id The post ID the media is to be associated with.
		 * @param string $desc Optional. Description of the image.
		 * @param string $return Optional. Accepts 'html' (image tag html) or 'src' (URL), or 'id' (attachment ID). Default 'html'.
		 *
		 * @return string|WP_Error Populated HTML img tag on success, WP_Error object otherwise.
		 */
		function media_sideload_image( $file, $post_id, $desc = null, $return = 'html' ) {
			if ( ! empty( $file ) ) {

				// Set variables for storage, fix file filename for query strings.
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
				if ( ! $matches ) {
					$extension = wp_get_image_mime( $file );
					if ( $extension !== false ) {
						if ( preg_match( '/(jpe?g|jpe|gif|png)/i', $extension, $ext_matches ) ) {
							$matches = array( '.' . $ext_matches[0] );
						}
					} else {
						return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
					}
				}

				require_once( ABSPATH . 'wp-admin/includes/media.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				$file_array         = array();
				$file_array['name'] = basename( $matches[0] );

				// Download file to temp location.
				$file_array['tmp_name'] = download_url( $file );

				// If error storing temporarily, return the error.
				if ( is_wp_error( $file_array['tmp_name'] ) ) {
					return $file_array['tmp_name'];
				}

				// Do the validation and storage stuff.
				$id = media_handle_sideload( $file_array, $post_id, $desc );

				// If error storing permanently, unlink.
				if ( is_wp_error( $id ) ) {
					@unlink( $file_array['tmp_name'] );

					return $id;
					// If attachment id was requested, return it early.
				} elseif ( $return === 'id' ) {
					return $id;
				}

				$src = wp_get_attachment_url( $id );
			}

			// Finally, check to make sure the file has been saved, then return the HTML.
			if ( ! empty( $src ) ) {
				if ( $return === 'src' ) {
					return $src;
				}

				$alt  = isset( $desc ) ? esc_attr( $desc ) : '';
				$html = "<img src='$src' alt='$alt' />";

				return $html;
			} else {
				return new WP_Error( 'image_sideload_failed' );
			}
		}

		protected function import_terms( $offer, $offer_wp_id ) {
			if (
				! isset( $offer['categorias'] ) ||
				! is_array( $offer['categorias'] ) ||
				empty( $offer['categorias'] )
			) {
				return;
			}
			$offer_tax            = $this->import_args['offer_tax'];
			$custom_fields_prefix = $this->import_args['db_key_prefix'];

			$categorias = $offer['categorias'];


			//$parent_wp_term_id = - 1;
			foreach ( $categorias as $categoria ) {
				$categoria_meta_key = $custom_fields_prefix . 'categoria_id';
				$term_categoria_id  = $categoria['id'];
				if ( empty( $term_categoria_id ) ) {
					continue;
				}
				global $wpdb;
				$result = $wpdb->get_var( $wpdb->prepare(
					"
						SELECT term_id
						FROM $wpdb->termmeta 
						WHERE meta_key = %s AND meta_value = %s
					",
					$categoria_meta_key, $term_categoria_id
				) );

				$categoria_nome = $categoria['nome'];
				$term_args      = array();
				$final_term_id  = $result;
				if ( empty( $result ) ) {
					$term = wp_insert_term( $categoria_nome, $offer_tax, $term_args );
					if ( ! is_wp_error( $term ) ) {
						$term_id       = $term['term_id'];
						$final_term_id = $term_id;
						update_term_meta( $term_id, $categoria_meta_key, $term_categoria_id );
					}
				} else {
					$term_args['name'] = $categoria_nome;
					wp_update_term( $result, $offer_tax, $term_args );
				}
				$existing_terms = wp_get_post_terms( $offer_wp_id, $offer_tax, array( 'fields' => 'ids' ) );
				if ( ! empty( $existing_terms ) && ! is_wp_error( $existing_terms ) ) {
					$existing_terms[] = $final_term_id;
					wp_set_post_terms( $offer_wp_id, $existing_terms, $offer_tax );
				} else {
					wp_set_post_terms( $offer_wp_id, $final_term_id, $offer_tax );
				}
			}
		}

		protected function get_post_update_array( $offer, $metas ) {
			$publicar_em        = isset( $offer['publicar_em'] ) ? $offer['publicar_em'] : null;
			$date_now           = new \DateTime();
			$publicar_is_future = false;
			if ( ! empty( $publicar_em ) ) {
				$date_publicar_em = new \DateTime( $publicar_em );
				if ( $date_publicar_em > $date_now ) {
					$publicar_is_future = true;
				}
			}

			//$offer['despublicar_em'] = '2018-04-16 02:55:23';
			$despublicar_em = isset( $offer['despublicar_em'] ) ? $offer['despublicar_em'] : null;
			if ( ! empty( $despublicar_em ) ) {
				$date_despublicar_em = new \DateTime( $despublicar_em );
			}

			$post_status = 'publish';
			if ( $publicar_is_future ) {
				$post_status = 'future';
			}
			if (
				! empty( $despublicar_em ) &&
				$date_despublicar_em <= $date_now
			) {
				$publicar_is_future = false;
				$post_status        = sanitize_title( Admin_Settings::get_option( 'offers_expire_status', 'omo_general', __( 'Expired', 'ofertasmall-ofertas' ) ) );
			}

			return array(
				'post_type'     => $this->import_args['offers_post_type'],
				'post_title'    => $offer['nome'],
				'post_name'     => sanitize_title( $offer['nome'] ),
				'post_date'     => empty( $publicar_em ) ? $offer['criado'] : $publicar_em,
				'post_date_gmt' => empty( $publicar_em ) ? get_gmt_from_date( $offer['criado'] ) : get_gmt_from_date( $publicar_em ),
				'post_modified' => empty( $publicar_em ) ? $offer['atualizado'] : $publicar_em,
				'post_status'   => $post_status,
				'meta_input'    => $metas
			);
		}

		public function save_offers_on_database( $offers ) {
			$prefix = $this->import_args['db_key_prefix'];
			update_option( $prefix . 'offers_from_api', $offers, false );
		}

		public function update_bkg_process_task( $offer_id ) {
			$offers = get_option( '_omo_offers_from_api' );
			$offer  = wp_list_filter( $offers, array(
				'id' => $offer_id
			) );

			$imported_offer_ids = get_option( '_omo_imported_offer_ids', 0 );
			if ( ! array_search( $offer_id, $imported_offer_ids ) ) {
				array_push( $imported_offer_ids, $offer_id );
				update_option( '_omo_imported_offer_ids', $imported_offer_ids, false );
			} else {
				$error_offers = get_option( '_omo_import_error_offer_ids', array() );
				array_push( $error_offers, $offer_id );
				update_option( '_omo_import_error_offer_ids', $error_offers, false );

				return false;
			}

			reset( $offer );
			$first_key = key( $offer );
			$this->import_offer( $offer[ $first_key ] );

			return false;
		}

		public function get_bkg_process_percentage() {
			$offers     = get_option( '_omo_offers_from_api' );
			$total      = count( $offers );
			$count      = count( get_option( '_omo_imported_offer_ids', array() ) );
			$percentage = round( $count / $total, 2 );

			return $percentage;
		}

		public function show_error_message_from_api() {
			$class   = 'notice notice-error';
			$message = __( 'Sorry, some error ocurred.', 'ofertasmall-ofertas' );
			$message .= '<br /><strong>' . __( 'API Message:', 'ofertasmall-ofertas' ) . '</strong>';
			$message .= ' ' . $this->api_result['message'];
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}

		public function show_ok_notice() {
			$class   = 'notice notice-success';
			$message = __( 'The API is working. The import process has started.', 'ofertasmall-ofertas' );
			$message .= '<br />' . __( 'You can navigate normally while the process continues', 'ofertasmall-ofertas' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}

		public function import_offers_from_offers_api( Ofertasmall_Offers_API $api ) {
			$offers = $api->get_offers( array(
				'hasCategoria' => 1,
				'hasReserva'   => 1,
			) );
			if (
				! is_array( $offers ) ||
				( isset( $offers['success'] ) && ! filter_var( $offers['success'], FILTER_VALIDATE_BOOLEAN ) )
			) {
				$this->api_result = $offers;
				add_action( 'admin_notices', array( $this, 'show_error_message_from_api' ) );

				return;
			}

			add_action( 'admin_notices', array( $this, 'show_ok_notice' ) );
			$this->save_offers_on_database( $offers );
			$this->import_offers_from_array( $offers );
		}

	}
}