<?php
/**
 * Ofertasmall Ofertas - Admin settings
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Admin_Settings' ) ) {
	class Admin_Settings {

		public static $settings_api;

		public static function admin_init() {
			self::$settings_api = new Custom_Admin_Settings();

			//set the settings
			self::$settings_api->set_sections( self::get_settings_sections() );
			self::$settings_api->set_fields( self::get_settings_fields() );

			//initialize settings
			self::$settings_api->admin_init();
		}

		public static function admin_menu() {
			add_options_page( __( 'WP Ofertas', 'ofertasmall-ofertas' ), __( 'WP Ofertas', 'ofertasmall-ofertas' ), 'delete_posts', 'ofertasmall-ofertas', array( __CLASS__, 'plugin_page' ) );
		}

		public static function get_settings_sections() {
			$sections = array(
				array(
					'id'    => 'omo_general',
					'title' => __( 'General Settings', 'ofertasmall-ofertas' )
				),
			);

			return $sections;
		}

		/**
		 * Returns all the settings fields
		 *
		 * @return array settings fields
		 */
		public static function get_settings_fields() {
			$settings_fields = array(
				'omo_general' => array(
					array(
						'name'    => 'token',
						'label'   => __( 'Shopping Token', 'ofertasmall-ofertas' ),
						'desc'    => __( 'Identifies the Shopping', 'ofertasmall-ofertas' ),
						'type'    => 'text',
						'default' => ''
					),
					array(
						'name'    => 'download_images',
						'label'   => __( 'Download Images', 'ofertasmall-ofertas' ),
						'desc'    => __( 'Downloads images on import', 'ofertasmall-ofertas' ),
						'type'    => 'checkbox',
						'default' => 'on'
					),

					// Ofertas
					array(
						'name'    => 'offers_title',
						'label'   => __( 'Offers', 'ofertasmall-ofertas' ),
						'desc'    => '',
						'type'    => 'title',
						'default' => 'on'
					),
					array(
						'name'    => 'offers_label_singular',
						'label'   => __( 'Singular name', 'ofertasmall-ofertas' ),
						'desc'    => __( 'Singular name', 'ofertasmall-ofertas' ),
						'type'    => 'text',
						'default' => __( 'Offer', 'ofertasmall-ofertas' )
					),
					array(
						'name'    => 'offers_label_plural',
						'label'   => __( 'Plural name', 'ofertasmall-ofertas' ),
						'desc'    => __( 'Plural name', 'ofertasmall-ofertas' ),
						'type'    => 'text',
						'default' => __( 'Offers', 'ofertasmall-ofertas' )
					),
				),

			);

			return $settings_fields;
		}

		public static function plugin_page() {

			echo '<div class="wrap">';
			echo '<h1>' . __( 'WP Ofertas', 'ofertasmall-ofertas' ) . '</h1>';
			self::$settings_api->show_navigation();
			self::$settings_api->show_forms();
			echo '</div>';
		}

		/**
		 * Get all the pages
		 *
		 * @return array page names with key value pairs
		 */
		public static function get_pages() {
			$pages         = get_pages();
			$pages_options = array();
			if ( $pages ) {
				foreach ( $pages as $page ) {
					$pages_options[ $page->ID ] = $page->post_title;
				}
			}

			return $pages_options;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 *
		 * @return mixed
		 */
		public static function get_option( $option, $section = 'omo_general', $default = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}

	}
}