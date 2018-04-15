<?php
/*
Plugin Name: WP Ofertas
Plugin URI: https://github.com/thanks-to-it/ofertasmall-ofertas
Description: Gets offers from ofertasmall API
Version: 1.0.0
Author: Thanks to IT
Author URI: https://github.com/thanks-to-it
Text Domain: ofertasmall-ofertas
Domain Path: /languages
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

__( 'Gets offers from ofertasmall API', 'ofertasmall-ofertas' );

// Autoload
require_once( "vendor/autoload.php" );

// Initializes plugin
$plugin = \TxToIT\OMO\Core::get_instance();
$plugin->set_args( array(
	'plugin_file_path' => __FILE__,
	'action_links'     => array(
		array(
			'url'  => admin_url( 'options-general.php?page=ofertasmall-ofertas' ),
			'text' => __( 'Settings', ' ofertasmall-ofertas' ),
		),
		array(
			'url'  => admin_url( 'tools.php?page=ofertasmall-ofertas-import' ),
			'text' => __( 'Import', ' ofertasmall-ofertas' ),
		),
	),
	'translation'      => array(
		'text_domain' => ' ofertasmall-ofertas',
	),
) );
$plugin->init();