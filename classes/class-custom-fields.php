<?php
/**
 * Ofertasmall Ofertas - Custom_Fields
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pablo S G Pacheco
 */

namespace TxToIT\OMO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'TxToIT\OMO\Custom_Fields' ) ) {
	class Custom_Fields {
		public $fields_args = array();

		public function __construct( $fields_args ) {
			$fields_args       = wp_parse_args( $fields_args, array(
				'db_key_prefix' => '_omo_',
				'offers_post_type'     => ''
			) );
			$this->fields_args = $fields_args;
		}

		public function create_custom_fields() {
			if ( function_exists( 'acf_add_local_field_group' ) ):
				$prefix = $this->fields_args['db_key_prefix'];

				acf_add_local_field_group( array(
					'key'      => 'omo_cmb',
					'title'    => __( 'WP Ofertas', 'ofertasmall-ofertas' ),
					'fields'   => array(
						array(
							'key'   => $prefix . 'id',
							'label' => 'ID',
							'name'  => $prefix . 'id',
							'type'  => 'number',
						),
						array(
							'key'   => $prefix . 'shopping_id',
							'label' => __( 'Shopping ID', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'shopping_id',
							'type'  => 'number',
						),
						array(
							'key'   => $prefix . 'id_crm',
							'label' => __( 'CRM', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'id_crm',
							'type'  => 'number',
						),
						array(
							'key'   => $prefix . 'luc',
							'label' => __( 'LUC', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'luc',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'site',
							'label' => __( 'Site', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'site',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'telefone',
							'label' => __( 'Telephone', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'telefone',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'email',
							'label' => __( 'Email', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'email',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'area',
							'label' => __( 'Area', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'area',
							'type'  => 'number',
						),
						array(
							'key'   => $prefix . 'marca',
							'label' => __( 'Branding', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'marca',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'piso',
							'label' => __( 'Floor', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'piso',
							'type'  => 'text',
						),
						array(
							'key'   => $prefix . 'logo',
							'label' => __( 'Logo', 'ofertasmall-ofertas' ),
							'name'  => $prefix . 'logo',
							'type'  => 'text',
						),
					),
					'location' => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => Offer_CPT::$post_type,
							),
						),
					),
				) );

			endif;
		}


	}
}