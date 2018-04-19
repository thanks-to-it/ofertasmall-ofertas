<?php
/**
 * Ofertasmall Ofertas - Store Tax
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Store_Tax' ) ) {
	class Store_Tax {

		public static $taxonomy = 'ofertas-categorias';

		public static function set_offers_tax() {
			$cpt            = Admin_Settings::get_option( 'offers_cpt', 'omo_general', 'ofertas' );
			self::$taxonomy = sanitize_title( $cpt . '-categorias' );
		}

		public static function register_taxonomy( $cpt ) {
			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Categories', 'taxonomy general name', 'ofertasmall-ofertas' ),
				'singular_name'     => _x( 'Category', 'taxonomy singular name', 'ofertasmall-ofertas' ),
				'search_items'      => __( 'Search Categories', 'ofertasmall-ofertas' ),
				'all_items'         => __( 'Categories', 'ofertasmall-ofertas' ),
				'parent_item'       => __( 'Parent Category', 'ofertasmall-ofertas' ),
				'parent_item_colon' => __( 'Parent Category:', 'ofertasmall-ofertas' ),
				'edit_item'         => __( 'Edit Category', 'ofertasmall-ofertas' ),
				'update_item'       => __( 'Update Category', 'ofertasmall-ofertas' ),
				'add_new_item'      => __( 'Add New Category', 'ofertasmall-ofertas' ),
				'new_item_name'     => __( 'New Category Name', 'ofertasmall-ofertas' ),
				'menu_name'         => __( 'Category', 'ofertasmall-ofertas' ),
			);

			$args = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'ofertas-categorias' ),
			);

			register_taxonomy( self::$taxonomy, array( $cpt ), $args );
		}
	}
}