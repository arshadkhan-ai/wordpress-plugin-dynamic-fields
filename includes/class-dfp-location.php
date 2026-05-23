<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFP_Location {

	// ── Meta-box registration ────────────────────────────────────────────────

	public function render_meta_boxes() {
		global $post;
		if ( ! $post || is_null( $post ) ) {
			return;
		}

		$groups = self::get_groups_for_post( $post->ID, $post );

		foreach ( $groups as $group_data ) {
			$settings = isset( $group_data['settings'] ) ? $group_data['settings'] : array();
			$position = isset( $settings['position'] ) ? $settings['position'] : 'normal';
			$style    = isset( $settings['style'] )    ? $settings['style']    : 'default';

			$context  = 'normal';
			if ( $position === 'side' )            { $context = 'side'; }
			if ( $position === 'acf_after_title' ) { $context = 'normal'; }

			add_meta_box(
				'dfp_group_' . absint( $group_data['id'] ),
				esc_html( $group_data['title'] ),
				array( $this, 'render_meta_box_content' ),
				$post->post_type,
				$context,
				'high',
				array(
					'group_data' => $group_data,
					'style'      => $style,
				)
			);
		}
	}

	public function render_meta_box_content( $post, $meta_box ) {
		$group_data     = $meta_box['args']['group_data'];
		$style          = isset( $meta_box['args']['style'] ) ? $meta_box['args']['style'] : 'default';
		$fields         = isset( $group_data['fields'] ) ? $group_data['fields'] : array();
		$settings       = isset( $group_data['settings'] ) ? $group_data['settings'] : array();
		$label_placement= isset( $settings['label_placement'] ) ? $settings['label_placement'] : 'top';
		$inst_placement = isset( $settings['instruction_placement'] ) ? $settings['instruction_placement'] : 'label';

		wp_nonce_field(
			'dfp_save_fields_' . absint( $group_data['id'] ),
			'_dfp_nonce_' . absint( $group_data['id'] )
		);

		echo '<div class="dfp-meta-box dfp-label-' . esc_attr( $label_placement ) . ' dfp-style-' . esc_attr( $style ) . '">';

		if ( ! empty( $settings['description'] ) ) {
			echo '<div class="dfp-group-description">' . wp_kses_post( $settings['description'] ) . '</div>';
		}

		foreach ( $fields as $field ) {
			self::render_field_wrap( $field, $post->ID, $settings, $inst_placement );
		}

		echo '</div>';
	}

	public static function render_field_wrap( $field, $post_id, $settings = array(), $inst_placement = 'label' ) {
		$field_type = DFP_Fields::get_field_type( isset( $field['type'] ) ? $field['type'] : '' );
		if ( ! $field_type ) {
			return;
		}

		$raw_value = get_post_meta( $post_id, $field['key'], true );
		$value     = $field_type->load_value( $raw_value, $post_id, $field );

		$required     = ! empty( $field['required'] ) ? ' <span class="dfp-required" aria-hidden="true">*</span>' : '';
		$instructions = '';
		if ( ! empty( $field['instructions'] ) ) {
			$instructions = '<p class="description dfp-instructions">' . esc_html( $field['instructions'] ) . '</p>';
		}

		echo '<div class="dfp-field dfp-field-type-' . esc_attr( $field['type'] ) . '" data-key="' . esc_attr( $field['key'] ) . '">';
		echo '<div class="dfp-label">';
		echo '<label for="' . esc_attr( $field['key'] ) . '">';
		echo esc_html( $field['label'] ) . $required;
		echo '</label>';
		if ( $inst_placement === 'label' ) {
			echo $instructions;
		}
		echo '</div>';
		echo '<div class="dfp-input">';
		$field_type->render_field( $field, $value );
		if ( $inst_placement === 'field' ) {
			echo $instructions;
		}
		echo '</div>';
		echo '</div>';
	}

	// ── Location matching ────────────────────────────────────────────────────

	/**
	 * Get all field groups that match the current post.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 * @return array  Array of group data arrays.
	 */
	public static function get_groups_for_post( $post_id, $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}
		if ( ! $post ) {
			return array();
		}

		$all_groups     = DFP_Field_Group::get_field_groups();
		$matched_groups = array();

		foreach ( $all_groups as $group_post ) {
			$group_data = DFP_Field_Group::get_field_group( $group_post->ID );
			if ( ! $group_data ) {
				continue;
			}
			$settings = isset( $group_data['settings'] ) ? $group_data['settings'] : array();
			if ( isset( $settings['active'] ) && $settings['active'] === false ) {
				continue;
			}
			if ( self::match_location( $group_data, $post ) ) {
				$matched_groups[] = $group_data;
			}
		}

		// Sort by menu_order.
		usort( $matched_groups, function( $a, $b ) {
			$ao = isset( $a['settings']['menu_order'] ) ? (int) $a['settings']['menu_order'] : 0;
			$bo = isset( $b['settings']['menu_order'] ) ? (int) $b['settings']['menu_order'] : 0;
			return $ao - $bo;
		} );

		return $matched_groups;
	}

	/**
	 * Returns true if the group should appear for the given post.
	 *
	 * @param array   $group_data
	 * @param WP_Post $post
	 * @return bool
	 */
	public static function match_location( $group_data, $post ) {
		$location_rules = isset( $group_data['location'] ) ? $group_data['location'] : array();
		if ( empty( $location_rules ) ) {
			return false;
		}
		// Rule groups are OR'd together; rules within a group are AND'd.
		foreach ( $location_rules as $rule_group ) {
			if ( ! is_array( $rule_group ) || empty( $rule_group ) ) {
				continue;
			}
			$group_matches = true;
			foreach ( $rule_group as $rule ) {
				if ( ! self::match_rule( $rule, $post ) ) {
					$group_matches = false;
					break;
				}
			}
			if ( $group_matches ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Test a single rule against the post.
	 *
	 * @param array   $rule   { param, operator, value }
	 * @param WP_Post $post
	 * @return bool
	 */
	public static function match_rule( $rule, $post ) {
		$param    = isset( $rule['param'] )    ? $rule['param']    : '';
		$operator = isset( $rule['operator'] ) ? $rule['operator'] : '==';
		$value    = isset( $rule['value'] )    ? $rule['value']    : '';

		$match = false;

		switch ( $param ) {
			case 'post_type':
				$match = ( $post->post_type === $value );
				break;

			case 'post_status':
				$match = ( $post->post_status === $value );
				break;

			case 'post_template':
			case 'page_template':
				$template = get_page_template_slug( $post->ID );
				if ( empty( $template ) ) {
					$template = 'default';
				}
				$match = ( $template === $value );
				break;

			case 'page_parent':
				if ( $value === 'any_parent' ) {
					$match = ( $post->post_parent > 0 );
				} else {
					$match = ( (int) $post->post_parent === (int) $value );
				}
				break;

			case 'page_type':
				switch ( $value ) {
					case 'front_page':
						$match = ( (int) get_option( 'page_on_front' ) === $post->ID );
						break;
					case 'posts_page':
						$match = ( (int) get_option( 'page_for_posts' ) === $post->ID );
						break;
					case 'top_level':
						$match = ( 0 === (int) $post->post_parent );
						break;
					case 'child_page':
						$match = ( $post->post_parent > 0 );
						break;
					default:
						$match = false;
				}
				break;

			case 'post_format':
				$fmt   = get_post_format( $post->ID );
				$fmt   = $fmt ?: 'standard';
				$match = ( $fmt === $value );
				break;

			case 'post_category':
				$cats  = wp_get_post_categories( $post->ID, array( 'fields' => 'ids' ) );
				$match = in_array( (int) $value, array_map( 'intval', $cats ), true );
				break;

			case 'post_taxonomy':
				// value format: taxonomy_slug:term_id
				$parts = explode( ':', $value, 2 );
				if ( count( $parts ) === 2 ) {
					$terms = wp_get_object_terms( $post->ID, $parts[0], array( 'fields' => 'ids' ) );
					$match = ! is_wp_error( $terms ) && in_array( (int) $parts[1], array_map( 'intval', $terms ), true );
				}
				break;

			case 'post_author':
				$match = ( (int) $post->post_author === (int) $value );
				break;

			case 'current_user_role':
				$user  = wp_get_current_user();
				$match = in_array( $value, (array) $user->roles, true );
				break;

			case 'current_user':
				$match = ( (int) get_current_user_id() === (int) $value );
				break;

			case 'user_role':
				// Used on user profile screens.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$uid   = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
				if ( $uid ) {
					$u     = get_user_by( 'id', $uid );
					$match = $u && in_array( $value, (array) $u->roles, true );
				}
				break;
		}

		return $operator === '!=' ? ! $match : $match;
	}

	// ── AJAX helper ─────────────────────────────────────────────────────────

	/**
	 * Return available values for a given location rule param.
	 *
	 * @param string $param
	 * @return array  [ { value, label } ]
	 */
	public static function get_rule_values( $param ) {
		$values = array();

		switch ( $param ) {
			case 'post_type':
				$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
				foreach ( $post_types as $pt ) {
					if ( $pt->name === 'dfp_group' ) {
						continue;
					}
					$values[] = array( 'value' => $pt->name, 'label' => $pt->label . ' (' . $pt->name . ')' );
				}
				break;

			case 'post_status':
				foreach ( get_post_statuses() as $slug => $label ) {
					$values[] = array( 'value' => $slug, 'label' => $label );
				}
				break;

			case 'post_template':
			case 'page_template':
				$values[] = array( 'value' => 'default', 'label' => __( 'Default Template', 'dynamic-fields-pro' ) );
				// get_page_templates() merges theme + plugin-registered templates (e.g. Elementor, Divi).
				$templates = get_page_templates();
				arsort( $templates );
				foreach ( $templates as $name => $file ) {
					$values[] = array( 'value' => $file, 'label' => $name );
				}
				break;

			case 'page_parent':
				$values[] = array( 'value' => 'any_parent', 'label' => __( 'Any parent', 'dynamic-fields-pro' ) );
				$pages    = get_posts( array( 'post_type' => 'page', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
				foreach ( $pages as $p ) {
					$values[] = array( 'value' => $p->ID, 'label' => $p->post_title );
				}
				break;

			case 'page_type':
				$values = array(
					array( 'value' => 'front_page', 'label' => __( 'Front Page', 'dynamic-fields-pro' ) ),
					array( 'value' => 'posts_page', 'label' => __( 'Posts Page', 'dynamic-fields-pro' ) ),
					array( 'value' => 'top_level',  'label' => __( 'Top Level Page', 'dynamic-fields-pro' ) ),
					array( 'value' => 'child_page', 'label' => __( 'Child Page', 'dynamic-fields-pro' ) ),
				);
				break;

			case 'post_format':
				if ( function_exists( 'get_post_format_strings' ) ) {
					foreach ( get_post_format_strings() as $slug => $label ) {
						$values[] = array( 'value' => $slug ?: 'standard', 'label' => $label );
					}
				}
				break;

			case 'post_category':
				$categories = get_categories( array( 'hide_empty' => false ) );
				foreach ( $categories as $cat ) {
					$values[] = array( 'value' => $cat->term_id, 'label' => $cat->name );
				}
				break;

			case 'current_user_role':
			case 'user_role':
				global $wp_roles;
				foreach ( $wp_roles->roles as $slug => $data ) {
					$values[] = array( 'value' => $slug, 'label' => $data['name'] );
				}
				break;

			case 'current_user':
				$values[] = array( 'value' => get_current_user_id(), 'label' => __( 'Current logged-in user', 'dynamic-fields-pro' ) );
				break;

			case 'post_author':
				$users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'number' => 200 ) );
				foreach ( $users as $u ) {
					$values[] = array( 'value' => $u->ID, 'label' => $u->display_name );
				}
				break;

			default:
				$values = apply_filters( 'dfp/location/rule_values', array(), $param );
				break;
		}

		return $values;
	}

	/**
	 * Available rule params for the location rule param dropdown.
	 *
	 * @return array  [ { value, label } ]
	 */
	public static function get_rule_params() {
		return apply_filters(
			'dfp/location/rule_params',
			array(
				array( 'value' => 'post_type',        'label' => __( 'Post Type',        'dynamic-fields-pro' ) ),
				array( 'value' => 'post_status',      'label' => __( 'Post Status',      'dynamic-fields-pro' ) ),
				array( 'value' => 'page_template',    'label' => __( 'Page Template',    'dynamic-fields-pro' ) ),
				array( 'value' => 'page_type',        'label' => __( 'Page Type',        'dynamic-fields-pro' ) ),
				array( 'value' => 'page_parent',      'label' => __( 'Page Parent',      'dynamic-fields-pro' ) ),
				array( 'value' => 'post_format',      'label' => __( 'Post Format',      'dynamic-fields-pro' ) ),
				array( 'value' => 'post_category',    'label' => __( 'Post Category',    'dynamic-fields-pro' ) ),
				array( 'value' => 'post_author',      'label' => __( 'Post Author',      'dynamic-fields-pro' ) ),
				array( 'value' => 'current_user',     'label' => __( 'Current User',     'dynamic-fields-pro' ) ),
				array( 'value' => 'current_user_role','label' => __( 'Current User Role','dynamic-fields-pro' ) ),
				array( 'value' => 'user_role',        'label' => __( 'User Role',        'dynamic-fields-pro' ) ),
			)
		);
	}
}
