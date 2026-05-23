<?php
/**
 * Plugin Name:       Dynamic Fields Pro
 * Plugin URI:        https://example.com/dynamic-fields-pro
 * Description:       A complete custom fields solution for WordPress — create field groups, assign them with location rules, and access field data programmatically. A full ACF Pro alternative.
 * Version:           1.0.3
 * Author:            Dynamic Fields Pro
 * Author URI:        https://example.com
 * Text Domain:       dynamic-fields-pro
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DFP_VERSION',  '1.0.3' );
define( 'DFP_PATH',     plugin_dir_path( __FILE__ ) );
define( 'DFP_URL',      plugin_dir_url( __FILE__ ) );
define( 'DFP_BASENAME', plugin_basename( __FILE__ ) );

require_once DFP_PATH . 'includes/class-dfp-field-group.php';
require_once DFP_PATH . 'includes/class-dfp-fields.php';
require_once DFP_PATH . 'includes/class-dfp-location.php';
require_once DFP_PATH . 'includes/class-dfp-data.php';
require_once DFP_PATH . 'includes/class-dfp-admin.php';
require_once DFP_PATH . 'includes/class-dfp-rest.php';
require_once DFP_PATH . 'includes/class-dfp-core.php';

add_action( 'plugins_loaded', array( 'DFP_Core', 'instance' ) );

register_activation_hook( __FILE__, 'dfp_activate' );
register_deactivation_hook( __FILE__, 'dfp_deactivate' );

function dfp_activate() {
	DFP_Field_Group::register_cpt();
	flush_rewrite_rules();
	if ( ! get_option( 'dfp_version' ) ) {
		add_option( 'dfp_version', DFP_VERSION );
	}
}

function dfp_deactivate() {
	flush_rewrite_rules();
}

// ── Public template-tag API ──────────────────────────────────────────────────

if ( ! function_exists( 'get_field' ) ) {
	function get_field( $selector, $post_id = false, $format_value = true ) {
		return DFP_Data::get_field( $selector, $post_id, $format_value );
	}
}

if ( ! function_exists( 'the_field' ) ) {
	function the_field( $selector, $post_id = false ) {
		echo wp_kses_post( DFP_Data::get_field( $selector, $post_id ) );
	}
}

if ( ! function_exists( 'get_fields' ) ) {
	function get_fields( $post_id = false, $format_value = true ) {
		return DFP_Data::get_fields( $post_id, $format_value );
	}
}

if ( ! function_exists( 'get_field_object' ) ) {
	function get_field_object( $selector, $post_id = false ) {
		return DFP_Data::get_field_object( $selector, $post_id );
	}
}

if ( ! function_exists( 'update_field' ) ) {
	function update_field( $selector, $value, $post_id = false ) {
		return DFP_Data::update_field( $selector, $value, $post_id );
	}
}

if ( ! function_exists( 'delete_field' ) ) {
	function delete_field( $selector, $post_id = false ) {
		return DFP_Data::delete_field( $selector, $post_id );
	}
}

if ( ! function_exists( 'have_rows' ) ) {
	function have_rows( $selector, $post_id = false ) {
		return DFP_Data::have_rows( $selector, $post_id );
	}
}

if ( ! function_exists( 'the_row' ) ) {
	function the_row() {
		return DFP_Data::the_row();
	}
}

if ( ! function_exists( 'get_row' ) ) {
	function get_row( $format_value = true ) {
		return DFP_Data::get_row( $format_value );
	}
}

if ( ! function_exists( 'get_row_index' ) ) {
	function get_row_index() {
		return DFP_Data::get_row_index();
	}
}

if ( ! function_exists( 'get_sub_field' ) ) {
	function get_sub_field( $selector, $format_value = true ) {
		return DFP_Data::get_sub_field( $selector, $format_value );
	}
}

if ( ! function_exists( 'the_sub_field' ) ) {
	function the_sub_field( $selector ) {
		echo wp_kses_post( DFP_Data::get_sub_field( $selector ) );
	}
}

if ( ! function_exists( 'reset_rows' ) ) {
	function reset_rows( $selector = false, $post_id = false ) {
		return DFP_Data::reset_rows( $selector, $post_id );
	}
}
