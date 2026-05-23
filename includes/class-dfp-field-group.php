<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFP_Field_Group {

	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	public static function register_cpt() {
		register_post_type(
			'dfp_group',
			array(
				'label'               => __( 'Field Groups', 'dynamic-fields-pro' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Returns all published dfp_group posts.
	 *
	 * @return WP_Post[]
	 */
	public static function get_field_groups() {
		return get_posts(
			array(
				'post_type'      => 'dfp_group',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_dfp_menu_order',
				'order'          => 'ASC',
			)
		);
	}

	/**
	 * Returns a single group with decoded fields, location, and settings.
	 *
	 * @param int $id Post ID.
	 * @return array|false
	 */
	public static function get_field_group( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'dfp_group' !== $post->post_type ) {
			return false;
		}

		$fields_raw   = get_post_meta( $id, '_dfp_fields', true );
		$location_raw = get_post_meta( $id, '_dfp_location', true );
		$settings_raw = get_post_meta( $id, '_dfp_settings', true );

		$fields   = ! empty( $fields_raw ) ? json_decode( $fields_raw, true ) : array();
		$location = ! empty( $location_raw ) ? json_decode( $location_raw, true ) : array();
		$settings = ! empty( $settings_raw ) ? json_decode( $settings_raw, true ) : array();

		if ( ! is_array( $fields ) )   $fields   = array();
		if ( ! is_array( $location ) ) $location = array();
		if ( ! is_array( $settings ) ) $settings = array();

		return array(
			'id'       => $id,
			'title'    => $post->post_title,
			'fields'   => $fields,
			'location' => $location,
			'settings' => $settings,
		);
	}

	/**
	 * Save or create a field group.
	 *
	 * @param array $data {
	 *   id       int|0   0 = create new.
	 *   title    string
	 *   fields   array
	 *   location array
	 *   settings array
	 * }
	 * @return int|WP_Error Post ID on success.
	 */
	public static function save_field_group( $data ) {
		$id       = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
		$title    = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'Untitled', 'dynamic-fields-pro' );
		$fields   = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
		$location = isset( $data['location'] ) && is_array( $data['location'] ) ? $data['location'] : array();
		$settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : array();

		// Sanitize fields array recursively.
		$fields   = self::sanitize_fields( $fields );
		$location = self::sanitize_location( $location );
		$settings = self::sanitize_settings( $settings );

		$post_data = array(
			'post_title'  => $title,
			'post_type'   => 'dfp_group',
			'post_status' => 'publish',
		);

		if ( $id > 0 ) {
			$post_data['ID'] = $id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$group_id = $result;

		update_post_meta( $group_id, '_dfp_fields',     wp_json_encode( $fields ) );
		update_post_meta( $group_id, '_dfp_location',   wp_json_encode( $location ) );
		update_post_meta( $group_id, '_dfp_settings',   wp_json_encode( $settings ) );
		update_post_meta( $group_id, '_dfp_menu_order', isset( $settings['menu_order'] ) ? absint( $settings['menu_order'] ) : 0 );

		return $group_id;
	}

	/**
	 * Duplicate an existing field group.
	 *
	 * @param int $id
	 * @return int|WP_Error New post ID.
	 */
	public static function duplicate_field_group( $id ) {
		$group = self::get_field_group( $id );
		if ( ! $group ) {
			return new WP_Error( 'not_found', __( 'Field group not found.', 'dynamic-fields-pro' ) );
		}

		// Re-key all fields so keys are unique.
		$fields = self::rekey_fields( $group['fields'] );

		$new_data = array(
			'id'       => 0,
			'title'    => $group['title'] . ' ' . __( '(Copy)', 'dynamic-fields-pro' ),
			'fields'   => $fields,
			'location' => $group['location'],
			'settings' => $group['settings'],
		);

		return self::save_field_group( $new_data );
	}

	/**
	 * Delete a field group.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_field_group( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'dfp_group' !== $post->post_type ) {
			return false;
		}
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Export a field group as a JSON string.
	 *
	 * @param int $id
	 * @return string|false
	 */
	public static function export_field_group( $id ) {
		$group = self::get_field_group( $id );
		if ( ! $group ) {
			return false;
		}
		$group['version'] = DFP_VERSION;
		return wp_json_encode( $group, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Import a field group from a JSON string.
	 *
	 * @param string $json
	 * @return int|WP_Error New post ID.
	 */
	public static function import_field_group( $json ) {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON.', 'dynamic-fields-pro' ) );
		}

		// Force create new (ignore any embedded id).
		$data['id'] = 0;

		// Re-key fields to avoid duplicates.
		if ( ! empty( $data['fields'] ) ) {
			$data['fields'] = self::rekey_fields( $data['fields'] );
		}

		return self::save_field_group( $data );
	}

	// ── Private helpers ──────────────────────────────────────────────────────

	private static function sanitize_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return array();
		}
		$clean = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$f = array(
				'key'          => isset( $field['key'] ) ? sanitize_key( $field['key'] ) : self::generate_key(),
				'label'        => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
				'name'         => isset( $field['name'] ) ? sanitize_key( $field['name'] ) : '',
				'type'         => isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text',
				'instructions' => isset( $field['instructions'] ) ? sanitize_textarea_field( $field['instructions'] ) : '',
				'required'     => ! empty( $field['required'] ) ? 1 : 0,
				'default_value'=> isset( $field['default_value'] ) ? sanitize_text_field( $field['default_value'] ) : '',
				'placeholder'  => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
			);

			// Preserve any extra type-specific settings as-is (they'll be
			// sanitized when a field is saved to post meta via update_value).
			$reserved = array_keys( $f );
			foreach ( $field as $k => $v ) {
				if ( ! in_array( $k, $reserved, true ) ) {
					if ( is_array( $v ) ) {
						$f[ $k ] = $v; // kept as-is; arrays are JSON-encoded
					} else {
						$f[ $k ] = sanitize_text_field( (string) $v );
					}
				}
			}

			// Recurse into sub_fields (repeater / flexible content).
			if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$f['sub_fields'] = self::sanitize_fields( $field['sub_fields'] );
			}

			$clean[] = $f;
		}
		return $clean;
	}

	private static function sanitize_location( $location ) {
		if ( ! is_array( $location ) ) {
			return array();
		}
		$clean = array();
		foreach ( $location as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			$clean_group = array();
			foreach ( $group as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}
				$clean_group[] = array(
					'param'    => sanitize_text_field( isset( $rule['param'] ) ? $rule['param'] : '' ),
					'operator' => in_array( isset( $rule['operator'] ) ? $rule['operator'] : '', array( '==', '!=' ), true ) ? $rule['operator'] : '==',
					'value'    => sanitize_text_field( isset( $rule['value'] ) ? $rule['value'] : '' ),
				);
			}
			if ( ! empty( $clean_group ) ) {
				$clean[] = $clean_group;
			}
		}
		return $clean;
	}

	private static function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}
		$allowed_positions = array( 'normal', 'side', 'acf_after_title' );
		$allowed_styles    = array( 'default', 'seamless' );
		$allowed_placements= array( 'top', 'left' );

		return array(
			'position'             => in_array( isset( $settings['position'] ) ? $settings['position'] : '', $allowed_positions, true ) ? $settings['position'] : 'normal',
			'style'                => in_array( isset( $settings['style'] ) ? $settings['style'] : '', $allowed_styles, true ) ? $settings['style'] : 'default',
			'label_placement'      => in_array( isset( $settings['label_placement'] ) ? $settings['label_placement'] : '', $allowed_placements, true ) ? $settings['label_placement'] : 'top',
			'instruction_placement'=> in_array( isset( $settings['instruction_placement'] ) ? $settings['instruction_placement'] : '', array( 'label', 'field' ), true ) ? $settings['instruction_placement'] : 'label',
			'menu_order'           => isset( $settings['menu_order'] ) ? absint( $settings['menu_order'] ) : 0,
			'active'               => isset( $settings['active'] ) ? (bool) $settings['active'] : true,
			'description'          => isset( $settings['description'] ) ? sanitize_textarea_field( $settings['description'] ) : '',
		);
	}

	private static function rekey_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return array();
		}
		foreach ( $fields as &$field ) {
			$field['key'] = self::generate_key();
			if ( ! empty( $field['sub_fields'] ) ) {
				$field['sub_fields'] = self::rekey_fields( $field['sub_fields'] );
			}
		}
		unset( $field );
		return $fields;
	}

	public static function generate_key() {
		return 'field_' . substr( md5( uniqid( mt_rand(), true ) ), 0, 16 );
	}
}
