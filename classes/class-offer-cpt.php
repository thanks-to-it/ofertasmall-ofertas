<?php
/**
 * Ofertasmall Ofertas - Offer CPT
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Offer_CPT' ) ) {
	class Offer_CPT {

		public static $post_type = 'ofertas';

		public static function register_cpt() {
			$offers_label_singular = Admin_Settings::get_option( 'offers_label_singular', 'omo_general', __( 'Offer', 'ofertasmall-ofertas' ) );
			$offers_label_plural   = Admin_Settings::get_option( 'offers_label_plural', 'omo_general', __( 'Offers', 'ofertasmall-ofertas' ) );
			$slug   = Admin_Settings::get_option( 'offers_rewrite_slug', 'omo_general', __( 'Offers', 'ofertasmall-ofertas' ) );

			$labels_arr      = apply_filters( 'omo_offer_labels', array(
				'plural'   => $offers_label_plural,
				'singular' => $offers_label_singular,
			) );
			self::$post_type = apply_filters( 'omo_offer_slug', 'ofertas' );

			$plural   = $labels_arr['plural'];
			$singular = $labels_arr['singular'];

			$labels = array(
				'name'               => $plural,
				'singular_name'      => $singular,
				'menu_name'          => $plural,
				'name_admin_bar'     => $singular,
				'add_new'            => _x( 'Add New', 'Offer', 'ofertasmall-ofertas' ),
				'add_new_item'       => sprintf( __( 'Add New %s', 'ofertasmall-ofertas' ), $singular ),
				'new_item'           => sprintf( __( 'New %s', 'ofertasmall-ofertas' ), $singular ),
				'edit_item'          => sprintf( __( 'Edit %s', 'ofertasmall-ofertas' ), $singular ),
				'view_item'          => sprintf( __( 'View %s', 'ofertasmall-ofertas' ), $singular ),
				'all_items'          => $plural,
				'search_items'       => sprintf( __( 'Search %s', 'ofertasmall-ofertas' ), $plural ),
				'parent_item_colon'  => sprintf( __( 'Parent %s:', 'ofertasmall-ofertas' ), $plural ),
				'not_found'          => sprintf( __( 'No %s found.', 'ofertasmall-ofertas' ), $plural ),
				'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'ofertasmall-ofertas' ), $plural ),
			);

			$args = array(
				'labels'             => $labels,
				//'description'        => __( 'Description.', 'ofertasmall-ofertas' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'menu_icon'          => 'dashicons-chart-line',
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $slug ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'thumbnail' )
			);

			register_post_type( self::$post_type, $args );
		}
	}
}