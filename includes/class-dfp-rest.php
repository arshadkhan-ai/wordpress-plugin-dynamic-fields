<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFP_REST {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// AJAX: fetch rule values for location rule param.
		add_action( 'wp_ajax_dfp_get_rule_values',  array( $this, 'ajax_get_rule_values' ) );

		// AJAX: save a field group from the builder.
		add_action( 'wp_ajax_dfp_save_field_group', array( $this, 'ajax_save_field_group' ) );

		// AJAX: toggle a group's active state.
		add_action( 'wp_ajax_dfp_toggle_group',     array( $this, 'ajax_toggle_group' ) );

		// AJAX: duplicate a group.
		add_action( 'wp_ajax_dfp_duplicate_group',  array( $this, 'ajax_duplicate_group' ) );

		// AJAX: delete a group.
		add_action( 'wp_ajax_dfp_delete_group',     array( $this, 'ajax_delete_group' ) );

		// AJAX: export (non-AJAX redirect via URL).
		add_action( 'wp_ajax_dfp_export_group',     array( $this, 'ajax_export_group' ) );

		// AJAX: import from JSON.
		add_action( 'wp_ajax_dfp_import_group',     array( $this, 'ajax_import_group' ) );

		// AJAX: render a single field row HTML (used by the JS field builder).
		add_action( 'wp_ajax_dfp_render_field_row', array( $this, 'ajax_render_field_row' ) );

		// AJAX: WooCommerce product search.
		add_action( 'wp_ajax_dfp_wc_search_products',         array( $this, 'ajax_wc_search_products' ) );

		// AJAX: WooCommerce products by category (for Product Showcase).
		add_action( 'wp_ajax_dfp_wc_products_by_category',    array( $this, 'ajax_wc_products_by_category' ) );
	}

	// ── REST API ─────────────────────────────────────────────────────────────

	public function register_routes() {
		register_rest_route(
			'dfp/v1',
			'/fields/(?P<post_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_fields' ),
					'permission_callback' => array( $this, 'rest_get_permission' ),
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'validate_callback' => function( $v ) { return is_numeric( $v ) && $v > 0; },
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'rest_update_fields' ),
					'permission_callback' => array( $this, 'rest_update_permission' ),
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'validate_callback' => function( $v ) { return is_numeric( $v ) && $v > 0; },
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Route for a single field.
		register_rest_route(
			'dfp/v1',
			'/fields/(?P<post_id>\d+)/(?P<field_key>[a-zA-Z0-9_]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_single_field' ),
				'permission_callback' => array( $this, 'rest_get_permission' ),
			)
		);

		// Route for field group definitions.
		register_rest_route(
			'dfp/v1',
			'/groups',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_groups' ),
				'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			)
		);
	}

	// ── REST: GET /dfp/v1/fields/{post_id} ──────────────────────────────────

	public function rest_get_permission( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'dfp_not_found', __( 'Post not found.', 'dynamic-fields-pro' ), array( 'status' => 404 ) );
		}
		// Public posts: anyone can read.
		if ( $post->post_status === 'publish' ) {
			return true;
		}
		// Private/drafts: require login.
		return is_user_logged_in();
	}

	public function rest_update_permission( $request ) {
		$post_id = $request->get_param( 'post_id' );
		return current_user_can( 'edit_post', $post_id );
	}

	public function rest_get_fields( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'dfp_not_found', __( 'Post not found.', 'dynamic-fields-pro' ), array( 'status' => 404 ) );
		}

		$fields_data = DFP_Data::get_fields( $post_id, true );

		return rest_ensure_response(
			array(
				'post_id'   => $post_id,
				'post_type' => $post->post_type,
				'fields'    => $fields_data ?: (object) array(),
			)
		);
	}

	// ── REST: POST /dfp/v1/fields/{post_id} ─────────────────────────────────

	public function rest_update_fields( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$body    = $request->get_json_params();

		if ( empty( $body ) || ! is_array( $body ) ) {
			return new WP_Error( 'dfp_bad_request', __( 'No fields data provided.', 'dynamic-fields-pro' ), array( 'status' => 400 ) );
		}

		$updated = array();
		foreach ( $body as $selector => $value ) {
			$selector = sanitize_key( $selector );
			if ( DFP_Data::update_field( $selector, $value, $post_id ) ) {
				$updated[] = $selector;
			}
		}

		return rest_ensure_response(
			array(
				'success'       => true,
				'post_id'       => $post_id,
				'updated_fields'=> $updated,
			)
		);
	}

	// ── REST: GET /dfp/v1/fields/{post_id}/{field_key} ───────────────────────

	public function rest_get_single_field( $request ) {
		$post_id   = $request->get_param( 'post_id' );
		$field_key = $request->get_param( 'field_key' );
		$value     = DFP_Data::get_field( $field_key, $post_id );

		return rest_ensure_response(
			array(
				'post_id'   => $post_id,
				'field_key' => $field_key,
				'value'     => $value,
			)
		);
	}

	// ── REST: GET /dfp/v1/groups ─────────────────────────────────────────────

	public function rest_get_groups( $request ) {
		$groups = DFP_Field_Group::get_field_groups();
		$data   = array();
		foreach ( $groups as $group_post ) {
			$data[] = DFP_Field_Group::get_field_group( $group_post->ID );
		}
		return rest_ensure_response( $data );
	}

	// ── AJAX: dfp_get_rule_values ────────────────────────────────────────────

	public function ajax_get_rule_values() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$param = isset( $_GET['param'] ) ? sanitize_key( $_GET['param'] ) : '';
		if ( ! $param ) {
			wp_send_json_error( array( 'message' => __( 'No param supplied.', 'dynamic-fields-pro' ) ) );
		}

		$values = DFP_Location::get_rule_values( $param );
		wp_send_json_success( $values );
	}

	// ── AJAX: dfp_save_field_group ───────────────────────────────────────────

	public function ajax_save_field_group() {
		check_ajax_referer( 'dfp_save_field_group', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$raw = isset( $_POST['group'] ) ? wp_unslash( $_POST['group'] ) : '';

		if ( is_string( $raw ) ) {
			$data = json_decode( $raw, true );
		} else {
			$data = is_array( $raw ) ? $raw : array();
		}

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'dynamic-fields-pro' ) ) );
		}

		$result = DFP_Field_Group::save_field_group( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$group_data = DFP_Field_Group::get_field_group( $result );

		wp_send_json_success(
			array(
				'group_id'  => $result,
				'group_data'=> $group_data,
				'edit_url'  => admin_url( 'admin.php?page=dfp-field-groups&action=edit&group_id=' . $result ),
			)
		);
	}

	// ── AJAX: dfp_toggle_group ───────────────────────────────────────────────

	public function ajax_toggle_group() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		$active   = isset( $_POST['active'] ) ? (bool) $_POST['active'] : false;

		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group ID.', 'dynamic-fields-pro' ) ) );
		}

		$group_data = DFP_Field_Group::get_field_group( $group_id );
		if ( ! $group_data ) {
			wp_send_json_error( array( 'message' => __( 'Field group not found.', 'dynamic-fields-pro' ) ) );
		}

		$group_data['settings']['active'] = $active;

		$result = DFP_Field_Group::save_field_group( $group_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'group_id' => $group_id,
				'status'   => $active ? 'active' : 'inactive',
			)
		);
	}

	// ── AJAX: dfp_duplicate_group ────────────────────────────────────────────

	public function ajax_duplicate_group() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group ID.', 'dynamic-fields-pro' ) ) );
		}

		$new_id = DFP_Field_Group::duplicate_field_group( $group_id );

		if ( is_wp_error( $new_id ) ) {
			wp_send_json_error( array( 'message' => $new_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'new_group_id' => $new_id,
				'redirect_url' => admin_url( 'admin.php?page=dfp-field-groups' ),
			)
		);
	}

	// ── AJAX: dfp_delete_group ───────────────────────────────────────────────

	public function ajax_delete_group() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		if ( ! $group_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid group ID.', 'dynamic-fields-pro' ) ) );
		}

		$result = DFP_Field_Group::delete_field_group( $group_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete field group.', 'dynamic-fields-pro' ) ) );
		}

		wp_send_json_success( array( 'deleted' => true, 'group_id' => $group_id ) );
	}

	// ── AJAX: dfp_export_group ───────────────────────────────────────────────

	public function ajax_export_group() {
		$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

		if ( ! $group_id || ! check_admin_referer( 'dfp_export_group_' . $group_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'dynamic-fields-pro' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'dynamic-fields-pro' ) );
		}

		$json = DFP_Field_Group::export_field_group( $group_id );
		if ( ! $json ) {
			wp_die( esc_html__( 'Field group not found.', 'dynamic-fields-pro' ) );
		}

		$filename = 'dfp-group-' . $group_id . '-' . date( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		exit;
	}

	// ── AJAX: dfp_import_group ───────────────────────────────────────────────

	public function ajax_import_group() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';

		if ( empty( $json ) ) {
			wp_send_json_error( array( 'message' => __( 'No JSON provided.', 'dynamic-fields-pro' ) ) );
		}

		$result = DFP_Field_Group::import_field_group( $json );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'group_id'    => $result,
				'redirect_url'=> admin_url( 'admin.php?page=dfp-field-groups' ),
			)
		);
	}

	// ── AJAX: dfp_wc_search_products ─────────────────────────────────────────

	public function ajax_wc_search_products() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] )                       : 'publish';

		$args = array(
			'post_type'      => 'product',
			'post_status'    => $status === 'any' ? array( 'publish', 'draft', 'pending', 'private' ) : $status,
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( $search ) {
			$args['s'] = $search;
		}

		$products = get_posts( $args );
		$data     = array();
		foreach ( $products as $p ) {
			$price = '';
			if ( function_exists( 'wc_get_product' ) ) {
				$wc_product = wc_get_product( $p->ID );
				if ( $wc_product ) {
					$price = wp_strip_all_tags( $wc_product->get_price_html() );
				}
			}
			$data[] = array(
				'id'    => $p->ID,
				'title' => $p->post_title,
				'thumb' => get_the_post_thumbnail_url( $p->ID, array( 40, 40 ) ) ?: '',
				'price' => $price,
			);
		}

		wp_send_json_success( $data );
	}

	// ── AJAX: dfp_wc_products_by_category ────────────────────────────────────

	public function ajax_wc_products_by_category() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$cat_id = isset( $_GET['cat_id'] ) ? absint( $_GET['cat_id'] ) : 0;
		if ( ! $cat_id ) {
			wp_send_json_error( array( 'message' => __( 'No category ID.', 'dynamic-fields-pro' ) ) );
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $cat_id,
				),
			),
		);

		$products = get_posts( $args );
		$data     = array();
		foreach ( $products as $p ) {
			$price = '';
			if ( function_exists( 'wc_get_product' ) ) {
				$wc_product = wc_get_product( $p->ID );
				if ( $wc_product ) {
					$price = wp_strip_all_tags( $wc_product->get_price_html() );
				}
			}
			$data[] = array(
				'id'    => $p->ID,
				'title' => $p->post_title,
				'thumb' => get_the_post_thumbnail_url( $p->ID, array( 60, 60 ) ) ?: '',
				'price' => $price,
			);
		}

		wp_send_json_success( $data );
	}

	// ── AJAX: dfp_render_field_row ────────────────────────────────────────────

	public function ajax_render_field_row() {
		check_ajax_referer( 'dfp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'dynamic-fields-pro' ) ), 403 );
		}

		$field_json = isset( $_POST['field'] ) ? wp_unslash( $_POST['field'] ) : '';
		$field      = json_decode( $field_json, true );

		if ( ! is_array( $field ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field data.', 'dynamic-fields-pro' ) ) );
		}

		// Ensure required keys.
		$field = array_merge(
			array( 'key' => DFP_Field_Group::generate_key(), 'label' => '', 'name' => '', 'type' => 'text', 'required' => 0, 'instructions' => '' ),
			$field
		);

		ob_start();
		$admin = new DFP_Admin();
		$admin->render_field_row( $field );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
