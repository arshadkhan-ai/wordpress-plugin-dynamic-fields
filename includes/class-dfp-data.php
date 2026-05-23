<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access API — mirrors ACF's template-tag interface.
 */
class DFP_Data {

	/** @var array  Stack of repeater loop states. */
	private static $loop_stack = array();

	// ── Normalise post_id ────────────────────────────────────────────────────

	private static function normalise_post_id( $post_id ) {
		if ( $post_id === false || $post_id === null ) {
			global $post;
			return $post ? $post->ID : 0;
		}
		if ( $post_id === 'option' || $post_id === 'options' ) {
			return 'options';
		}
		return absint( $post_id );
	}

	// ── Field-object lookup ──────────────────────────────────────────────────

	/**
	 * Find the field definition array by key or name.
	 *
	 * @param string    $selector  Field key (field_xxx) or field name.
	 * @param int|false $post_id
	 * @return array|false
	 */
	public static function get_field_object( $selector, $post_id = false ) {
		$groups = DFP_Field_Group::get_field_groups();

		foreach ( $groups as $group_post ) {
			$group = DFP_Field_Group::get_field_group( $group_post->ID );
			if ( ! $group || empty( $group['fields'] ) ) {
				continue;
			}
			$found = self::search_fields( $group['fields'], $selector );
			if ( $found ) {
				return $found;
			}
		}
		return false;
	}

	/**
	 * Recursively search fields (and sub_fields) for a matching key or name.
	 *
	 * @param array  $fields
	 * @param string $selector
	 * @return array|false
	 */
	private static function search_fields( $fields, $selector ) {
		foreach ( $fields as $field ) {
			if ( isset( $field['key'] ) && $field['key'] === $selector ) {
				return $field;
			}
			if ( isset( $field['name'] ) && $field['name'] === $selector ) {
				return $field;
			}
			if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$found = self::search_fields( $field['sub_fields'], $selector );
				if ( $found ) {
					return $found;
				}
			}
		}
		return false;
	}

	// ── get_field ────────────────────────────────────────────────────────────

	/**
	 * Get a field's value for a post.
	 *
	 * @param string    $selector
	 * @param int|false $post_id
	 * @param bool      $format_value
	 * @return mixed
	 */
	public static function get_field( $selector, $post_id = false, $format_value = true ) {
		$post_id = self::normalise_post_id( $post_id );

		if ( $post_id === 'options' ) {
			return self::get_option( $selector, $format_value );
		}

		$field = self::get_field_object( $selector, $post_id );

		if ( ! $field ) {
			// Fall back to raw meta.
			return get_post_meta( $post_id, $selector, true );
		}

		$value = get_post_meta( $post_id, $field['key'], true );

		if ( $format_value ) {
			$field_type = DFP_Fields::get_field_type( $field['type'] );
			if ( $field_type ) {
				$value = $field_type->load_value( $value, $post_id, $field );
			}
		}

		return $value;
	}

	// ── get_fields ───────────────────────────────────────────────────────────

	/**
	 * Get all fields for a post as key => value pairs.
	 *
	 * @param int|false $post_id
	 * @param bool      $format_value
	 * @return array|false
	 */
	public static function get_fields( $post_id = false, $format_value = true ) {
		$post_id = self::normalise_post_id( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		$post   = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$groups  = DFP_Location::get_groups_for_post( $post_id, $post );
		$result  = array();

		foreach ( $groups as $group_data ) {
			if ( empty( $group_data['fields'] ) ) {
				continue;
			}
			foreach ( $group_data['fields'] as $field ) {
				$value = self::get_field( $field['name'], $post_id, $format_value );
				$result[ $field['name'] ] = $value;
			}
		}

		return $result;
	}

	// ── update_field / delete_field ──────────────────────────────────────────

	/**
	 * Update a field value.
	 *
	 * @param string $selector
	 * @param mixed  $value
	 * @param int    $post_id
	 * @return bool
	 */
	public static function update_field( $selector, $value, $post_id = false ) {
		$post_id = self::normalise_post_id( $post_id );
		$field   = self::get_field_object( $selector, $post_id );

		if ( ! $field ) {
			return (bool) update_post_meta( $post_id, $selector, $value );
		}

		$field_type = DFP_Fields::get_field_type( $field['type'] );
		if ( $field_type ) {
			return (bool) $field_type->update_value( $value, $post_id, $field );
		}

		return (bool) update_post_meta( $post_id, $field['key'], $value );
	}

	/**
	 * Delete a field value.
	 *
	 * @param string $selector
	 * @param int    $post_id
	 * @return bool
	 */
	public static function delete_field( $selector, $post_id = false ) {
		$post_id = self::normalise_post_id( $post_id );
		$field   = self::get_field_object( $selector, $post_id );
		$key     = $field ? $field['key'] : $selector;
		return delete_post_meta( $post_id, $key );
	}

	// ── Repeater loop ────────────────────────────────────────────────────────

	/**
	 * Check if there are more rows in a repeater loop.
	 *
	 * Designed to be called as the while() condition:
	 *   while ( have_rows( 'repeater' ) ) { the_row(); ... }
	 *
	 * @param string    $selector
	 * @param int|false $post_id
	 * @return bool
	 */
	public static function have_rows( $selector, $post_id = false ) {
		$post_id = self::normalise_post_id( $post_id );

		// If the top of the stack is already this loop, check for more rows.
		if ( ! empty( self::$loop_stack ) ) {
			$top = &self::$loop_stack[ count( self::$loop_stack ) - 1 ];
			if ( $top['selector'] === $selector && $top['post_id'] === $post_id ) {
				$next = $top['i'] + 1;
				if ( $next < count( $top['rows'] ) ) {
					return true;
				}
				array_pop( self::$loop_stack );
				return false;
			}
		}

		// New loop — fetch rows.
		$rows = null;

		// If we're inside a parent repeater, look in the current row first.
		if ( ! empty( self::$loop_stack ) ) {
			$parent = &self::$loop_stack[ count( self::$loop_stack ) - 1 ];
			if ( $parent['row'] !== null && isset( $parent['row'][ $selector ] ) ) {
				$rows = $parent['row'][ $selector ];
			}
		}

		if ( $rows === null ) {
			$rows = get_post_meta( $post_id, $selector, true );
		}

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return false;
		}

		self::$loop_stack[] = array(
			'selector' => $selector,
			'post_id'  => $post_id,
			'rows'     => array_values( $rows ),
			'i'        => -1,
			'row'      => null,
		);

		return true;
	}

	/**
	 * Advance the internal pointer to the next row.
	 *
	 * @return array|false  The current row data, or false when exhausted.
	 */
	public static function the_row() {
		if ( empty( self::$loop_stack ) ) {
			return false;
		}

		$top = &self::$loop_stack[ count( self::$loop_stack ) - 1 ];
		$top['i']++;

		if ( $top['i'] >= count( $top['rows'] ) ) {
			array_pop( self::$loop_stack );
			return false;
		}

		$top['row'] = $top['rows'][ $top['i'] ];
		return $top['row'];
	}

	/**
	 * Return the entire current row.
	 *
	 * @param bool $format_value  Unused — included for API parity.
	 * @return array|false
	 */
	public static function get_row( $format_value = true ) {
		if ( empty( self::$loop_stack ) ) {
			return false;
		}
		$top = self::$loop_stack[ count( self::$loop_stack ) - 1 ];
		return $top['row'];
	}

	/**
	 * Return the 0-based index of the current row.
	 *
	 * @return int
	 */
	public static function get_row_index() {
		if ( empty( self::$loop_stack ) ) {
			return 0;
		}
		return self::$loop_stack[ count( self::$loop_stack ) - 1 ]['i'];
	}

	/**
	 * Get a sub-field value from the current row.
	 *
	 * @param string $selector
	 * @param bool   $format_value
	 * @return mixed|false
	 */
	public static function get_sub_field( $selector, $format_value = true ) {
		if ( empty( self::$loop_stack ) ) {
			return false;
		}

		$top = self::$loop_stack[ count( self::$loop_stack ) - 1 ];
		if ( ! is_array( $top['row'] ) ) {
			return false;
		}

		if ( ! array_key_exists( $selector, $top['row'] ) ) {
			return false;
		}

		return $top['row'][ $selector ];
	}

	/**
	 * Reset the loop stack, or a specific loop.
	 *
	 * @param string|false    $selector
	 * @param int|false       $post_id
	 * @return bool
	 */
	public static function reset_rows( $selector = false, $post_id = false ) {
		if ( $selector === false ) {
			self::$loop_stack = array();
			return true;
		}

		$post_id = self::normalise_post_id( $post_id );

		foreach ( self::$loop_stack as $k => $loop ) {
			if ( $loop['selector'] === $selector && $loop['post_id'] === $post_id ) {
				array_splice( self::$loop_stack, $k );
				return true;
			}
		}

		return false;
	}

	// ── Options API ──────────────────────────────────────────────────────────

	private static function get_option( $key, $format_value = true ) {
		return get_option( 'dfp_option_' . $key, '' );
	}

	// ── save_post hook ───────────────────────────────────────────────────────

	/**
	 * Hooked to save_post — persists field values from $_POST.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save_fields( $post_id, $post ) {
		// Skip autosaves, revisions, and non-standard save conditions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'revision' === $post->post_type ) {
			return;
		}
		if ( 'dfp_group' === $post->post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Get groups that apply to this post.
		$groups = DFP_Location::get_groups_for_post( $post_id, $post );

		foreach ( $groups as $group_data ) {
			$group_id = absint( $group_data['id'] );

			// Verify the nonce for this group.
			$nonce_key = '_dfp_nonce_' . $group_id;
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), 'dfp_save_fields_' . $group_id ) ) {
				continue;
			}

			if ( empty( $group_data['fields'] ) ) {
				continue;
			}

			foreach ( $group_data['fields'] as $field ) {
				$key        = $field['key'];
				$field_type = DFP_Fields::get_field_type( $field['type'] );

				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$raw_value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : null;

				if ( $raw_value === null && ! in_array( $field['type'], array( 'checkbox', 'relationship', 'taxonomy', 'repeater' ), true ) ) {
					// For non-multi fields not present in POST (e.g. unchecked true_false).
					$raw_value = '';
				}
				if ( $raw_value === null ) {
					$raw_value = array();
				}

				if ( $field_type ) {
					// Run validate_value — errors are logged but do not block saving.
					$valid = $field_type->validate_value( true, $raw_value, $field );
					if ( $valid !== true ) {
						// Store validation error in a transient so the UI can show it.
						set_transient( 'dfp_validation_error_' . $post_id . '_' . $key, $valid, 30 );
					}
					$field_type->update_value( $raw_value, $post_id, $field );
				} else {
					update_post_meta( $post_id, $key, sanitize_text_field( (string) $raw_value ) );
				}
			}
		}
	}
}
