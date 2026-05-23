<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ════════════════════════════════════════════════════════════════
// Abstract base class
// ════════════════════════════════════════════════════════════════

abstract class DFP_Field_Base {

	abstract public function get_type();
	abstract public function get_label();
	abstract public function get_defaults();

	/**
	 * Render the input HTML inside a meta box.
	 *
	 * @param array $field  Field definition array.
	 * @param mixed $value  Current (loaded) value.
	 */
	abstract public function render_field( $field, $value );

	/**
	 * Render the settings panel shown in the field builder.
	 *
	 * @param array $field  Field definition array.
	 */
	abstract public function render_field_settings( $field );

	/**
	 * Sanitize and persist the value to post meta.
	 *
	 * @param mixed $value
	 * @param int   $post_id
	 * @param array $field
	 * @return bool
	 */
	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], sanitize_text_field( (string) $value ) );
	}

	/**
	 * Transform the raw meta value before rendering or returning.
	 *
	 * @param mixed $value
	 * @param int   $post_id
	 * @param array $field
	 * @return mixed
	 */
	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false || $value === null ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : '';
		}
		return $value;
	}

	/**
	 * Validate the value.  Return true or an error string.
	 *
	 * @param bool|string $valid  Current validity (may already be an error message).
	 * @param mixed       $value
	 * @param array       $field
	 * @return bool|string
	 */
	public function validate_value( $valid, $value, $field ) {
		if ( $valid !== true ) {
			return $valid;
		}
		if ( ! empty( $field['required'] ) && ( $value === '' || $value === null || $value === false || $value === array() ) ) {
			return esc_html__( 'This field is required.', 'dynamic-fields-pro' );
		}
		return true;
	}

	// ── Helpers ────────────────────────────────────────────────────────────

	protected function field_key( $field ) {
		// Support _id_override so repeater sub-fields get a valid HTML id (no brackets).
		if ( isset( $field['_id_override'] ) ) {
			return esc_attr( $field['_id_override'] );
		}
		return esc_attr( $field['key'] );
	}

	protected function field_name( $field ) {
		// Support _name_override so repeater sub-fields get the proper bracket-notation name.
		if ( isset( $field['_name_override'] ) ) {
			return esc_attr( $field['_name_override'] );
		}
		return esc_attr( $field['key'] );
	}

	protected function row( $label, $content ) {
		echo '<tr class="dfp-settings-row"><th>' . esc_html( $label ) . '</th><td>' . $content . '</td></tr>';
	}

	protected function begin_settings( $field ) {
		echo '<table class="dfp-field-settings-table widefat">';
	}

	protected function end_settings() {
		echo '</table>';
	}

	protected function render_common_settings( $field ) {
		$this->row(
			__( 'Default Value', 'dynamic-fields-pro' ),
			'<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[default_value]" value="' . esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '">'
		);
		$this->row(
			__( 'Placeholder', 'dynamic-fields-pro' ),
			'<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[placeholder]" value="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '">'
		);
	}

	protected function prepend_append_row( $field ) {
		$prepend = isset( $field['prepend'] ) ? $field['prepend'] : '';
		$append  = isset( $field['append'] )  ? $field['append']  : '';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Prepend / Append', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="text" style="width:120px" placeholder="' . esc_attr__( 'Prepend', 'dynamic-fields-pro' ) . '" name="' . $this->field_key( $field ) . '[prepend]" value="' . esc_attr( $prepend ) . '"> ';
		echo '<input type="text" style="width:120px" placeholder="' . esc_attr__( 'Append', 'dynamic-fields-pro' ) . '" name="' . $this->field_key( $field ) . '[append]" value="' . esc_attr( $append ) . '">';
		echo '</td></tr>';
	}

	protected function choices_to_array( $raw ) {
		$choices = array();
		if ( is_array( $raw ) ) {
			return $raw;
		}
		foreach ( explode( "\n", (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( $line === '' ) {
				continue;
			}
			if ( strpos( $line, ':' ) !== false ) {
				list( $v, $l ) = explode( ':', $line, 2 );
				$choices[ trim( $v ) ] = trim( $l );
			} else {
				$choices[ $line ] = $line;
			}
		}
		return $choices;
	}

	protected function choices_to_textarea( $choices ) {
		$lines = array();
		if ( is_array( $choices ) ) {
			foreach ( $choices as $v => $l ) {
				$lines[] = ( $v === $l ) ? $v : $v . ' : ' . $l;
			}
		}
		return implode( "\n", $lines );
	}
}

// ════════════════════════════════════════════════════════════════
// 1. Text
// ════════════════════════════════════════════════════════════════
class DFP_Field_Text extends DFP_Field_Base {

	public function get_type()    { return 'text'; }
	public function get_label()   { return __( 'Text', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'default_value' => '',
			'placeholder'   => '',
			'maxlength'     => '',
			'prepend'       => '',
			'append'        => '',
		);
	}

	public function render_field( $field, $value ) {
		$prepend   = isset( $field['prepend'] ) && $field['prepend'] !== '' ? '<span class="dfp-input-prepend">' . esc_html( $field['prepend'] ) . '</span>' : '';
		$append    = isset( $field['append'] )  && $field['append']  !== '' ? '<span class="dfp-input-append">'  . esc_html( $field['append'] )  . '</span>' : '';
		$maxlength = isset( $field['maxlength'] ) && $field['maxlength'] !== '' ? ' maxlength="' . absint( $field['maxlength'] ) . '"' : '';

		echo '<div class="dfp-input-wrap' . ( $prepend ? ' has-prepend' : '' ) . ( $append ? ' has-append' : '' ) . '">';
		echo $prepend;
		echo '<input type="text" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '"' . $maxlength . ' class="widefat">';
		echo $append;
		echo '</div>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->render_common_settings( $field );
		$this->row(
			__( 'Max Length', 'dynamic-fields-pro' ),
			'<input type="number" min="0" class="small-text" name="' . $this->field_key( $field ) . '[maxlength]" value="' . esc_attr( isset( $field['maxlength'] ) ? $field['maxlength'] : '' ) . '">'
		);
		$this->prepend_append_row( $field );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], sanitize_text_field( (string) $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : '';
		}
		return (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		$max = isset( $field['maxlength'] ) ? absint( $field['maxlength'] ) : 0;
		if ( $max > 0 && mb_strlen( (string) $value ) > $max ) {
			return sprintf( esc_html__( 'Maximum length is %d characters.', 'dynamic-fields-pro' ), $max );
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 2. Textarea
// ════════════════════════════════════════════════════════════════
class DFP_Field_Textarea extends DFP_Field_Base {

	public function get_type()    { return 'textarea'; }
	public function get_label()   { return __( 'Textarea', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'default_value' => '',
			'placeholder'   => '',
			'rows'          => 8,
			'new_lines'     => 'wpautop',
		);
	}

	public function render_field( $field, $value ) {
		$rows = isset( $field['rows'] ) && $field['rows'] > 0 ? absint( $field['rows'] ) : 8;
		echo '<textarea id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" rows="' . $rows . '" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '" class="widefat">' . esc_textarea( $value ) . '</textarea>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row(
			__( 'Default Value', 'dynamic-fields-pro' ),
			'<textarea class="widefat" rows="4" name="' . $this->field_key( $field ) . '[default_value]">' . esc_textarea( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '</textarea>'
		);
		$this->row(
			__( 'Placeholder', 'dynamic-fields-pro' ),
			'<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[placeholder]" value="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '">'
		);
		$this->row(
			__( 'Rows', 'dynamic-fields-pro' ),
			'<input type="number" min="1" class="small-text" name="' . $this->field_key( $field ) . '[rows]" value="' . absint( isset( $field['rows'] ) ? $field['rows'] : 8 ) . '">'
		);
		$nl = isset( $field['new_lines'] ) ? $field['new_lines'] : 'wpautop';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'New Lines', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[new_lines]">';
		foreach ( array( 'wpautop' => __( 'Automatically add paragraphs', 'dynamic-fields-pro' ), 'br' => __( 'Automatically add &lt;br&gt;', 'dynamic-fields-pro' ), 'none' => __( 'No formatting', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $nl, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], sanitize_textarea_field( (string) $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : '';
		}
		return (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 3. Number
// ════════════════════════════════════════════════════════════════
class DFP_Field_Number extends DFP_Field_Base {

	public function get_type()    { return 'number'; }
	public function get_label()   { return __( 'Number', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'default_value' => '', 'min' => '', 'max' => '', 'step' => '', 'prepend' => '', 'append' => '' );
	}

	public function render_field( $field, $value ) {
		$attrs  = '';
		foreach ( array( 'min', 'max', 'step' ) as $a ) {
			if ( isset( $field[ $a ] ) && $field[ $a ] !== '' ) {
				$attrs .= ' ' . $a . '="' . esc_attr( $field[ $a ] ) . '"';
			}
		}
		$prepend = isset( $field['prepend'] ) && $field['prepend'] !== '' ? '<span class="dfp-input-prepend">' . esc_html( $field['prepend'] ) . '</span>' : '';
		$append  = isset( $field['append'] )  && $field['append']  !== '' ? '<span class="dfp-input-append">'  . esc_html( $field['append'] )  . '</span>' : '';
		echo '<div class="dfp-input-wrap' . ( $prepend ? ' has-prepend' : '' ) . ( $append ? ' has-append' : '' ) . '">';
		echo $prepend;
		echo '<input type="number" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $value ) . '"' . $attrs . ' style="max-width:200px">';
		echo $append;
		echo '</div>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row( __( 'Default Value', 'dynamic-fields-pro' ), '<input type="number" name="' . $this->field_key( $field ) . '[default_value]" value="' . esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '">' );
		$this->row( __( 'Min',  'dynamic-fields-pro' ), '<input type="number" name="' . $this->field_key( $field ) . '[min]" value="' . esc_attr( isset( $field['min'] )  ? $field['min']  : '' ) . '">' );
		$this->row( __( 'Max',  'dynamic-fields-pro' ), '<input type="number" name="' . $this->field_key( $field ) . '[max]" value="' . esc_attr( isset( $field['max'] )  ? $field['max']  : '' ) . '">' );
		$this->row( __( 'Step', 'dynamic-fields-pro' ), '<input type="number" name="' . $this->field_key( $field ) . '[step]" value="' . esc_attr( isset( $field['step'] ) ? $field['step'] : '' ) . '">' );
		$this->prepend_append_row( $field );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = ( isset( $field['step'] ) && strpos( (string) $field['step'], '.' ) !== false ) ? (float) $value : (int) $value;
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : '';
		}
		return $value;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( $value !== '' ) {
			$n = (float) $value;
			if ( isset( $field['min'] ) && $field['min'] !== '' && $n < (float) $field['min'] ) {
				return sprintf( esc_html__( 'Value must be at least %s.', 'dynamic-fields-pro' ), $field['min'] );
			}
			if ( isset( $field['max'] ) && $field['max'] !== '' && $n > (float) $field['max'] ) {
				return sprintf( esc_html__( 'Value must be at most %s.', 'dynamic-fields-pro' ), $field['max'] );
			}
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 4. Email
// ════════════════════════════════════════════════════════════════
class DFP_Field_Email extends DFP_Field_Base {

	public function get_type()    { return 'email'; }
	public function get_label()   { return __( 'Email', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'default_value' => '', 'placeholder' => '' );
	}

	public function render_field( $field, $value ) {
		echo '<input type="email" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '" class="widefat">';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->render_common_settings( $field );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = sanitize_email( (string) $value );
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		return $value === '' || $value === false ? ( isset( $field['default_value'] ) ? $field['default_value'] : '' ) : (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( $value !== '' && ! is_email( $value ) ) {
			return esc_html__( 'Please enter a valid email address.', 'dynamic-fields-pro' );
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 5. URL
// ════════════════════════════════════════════════════════════════
class DFP_Field_URL extends DFP_Field_Base {

	public function get_type()    { return 'url'; }
	public function get_label()   { return __( 'URL', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'default_value' => '', 'placeholder' => '' );
	}

	public function render_field( $field, $value ) {
		echo '<input type="url" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_url( $value ) . '" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '" class="widefat">';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->render_common_settings( $field );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], esc_url_raw( (string) $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		return $value === '' || $value === false ? ( isset( $field['default_value'] ) ? $field['default_value'] : '' ) : (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( $value !== '' && filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
			return esc_html__( 'Please enter a valid URL.', 'dynamic-fields-pro' );
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 6. Password
// ════════════════════════════════════════════════════════════════
class DFP_Field_Password extends DFP_Field_Base {

	public function get_type()    { return 'password'; }
	public function get_label()   { return __( 'Password', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'placeholder' => '' );
	}

	public function render_field( $field, $value ) {
		// Never render the saved value — always an empty password field.
		echo '<input type="password" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="" autocomplete="new-password" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '" class="widefat">';
		if ( $value !== '' ) {
			echo '<p class="description">' . esc_html__( 'A password is stored. Enter a new value to change it, or leave blank to keep the current password.', 'dynamic-fields-pro' ) . '</p>';
		}
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row(
			__( 'Placeholder', 'dynamic-fields-pro' ),
			'<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[placeholder]" value="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '">'
		);
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		// Only update if a new value was provided.
		if ( $value === '' || $value === null ) {
			return false;
		}
		return update_post_meta( $post_id, $field['key'], wp_hash_password( sanitize_text_field( (string) $value ) ) );
	}

	public function load_value( $value, $post_id, $field ) {
		return $value; // never expose the hash to render
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 7. Select
// ════════════════════════════════════════════════════════════════
class DFP_Field_Select extends DFP_Field_Base {

	public function get_type()    { return 'select'; }
	public function get_label()   { return __( 'Select', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'choices'       => array(),
			'default_value' => '',
			'allow_null'    => 0,
			'multiple'      => 0,
			'return_format' => 'value',
		);
	}

	public function render_field( $field, $value ) {
		$choices   = $this->choices_to_array( isset( $field['choices'] ) ? $field['choices'] : array() );
		$multiple  = ! empty( $field['multiple'] );
		$allow_null= ! empty( $field['allow_null'] );
		$selected  = is_array( $value ) ? $value : array( $value );
		$name      = $this->field_name( $field ) . ( $multiple ? '[]' : '' );
		$id        = $this->field_key( $field );

		echo '<select id="' . $id . '" name="' . esc_attr( $name ) . '"' . ( $multiple ? ' multiple size="5"' : '' ) . ' class="dfp-select widefat">';
		if ( $allow_null ) {
			echo '<option value="">' . esc_html__( '— Select —', 'dynamic-fields-pro' ) . '</option>';
		}
		foreach ( $choices as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . ( in_array( (string) $v, array_map( 'strval', $selected ), true ) ? ' selected' : '' ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$choices_text = $this->choices_to_textarea( isset( $field['choices'] ) ? $field['choices'] : array() );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Choices', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<textarea class="widefat" rows="8" name="' . $this->field_key( $field ) . '[choices]">' . esc_textarea( $choices_text ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'One choice per line. value : Label — or just value.', 'dynamic-fields-pro' ) . '</p>';
		echo '</td></tr>';
		$this->row(
			__( 'Default Value', 'dynamic-fields-pro' ),
			'<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[default_value]" value="' . esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '">'
		);
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Allow Null', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[allow_null]" value="1"' . checked( ! empty( $field['allow_null'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Multiple', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[multiple]" value="1"' . checked( ! empty( $field['multiple'] ), true, false ) . '></td></tr>';
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'value';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'value' => __( 'Value', 'dynamic-fields-pro' ), 'label' => __( 'Label', 'dynamic-fields-pro' ), 'array' => __( 'Both (Array)', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		if ( ! empty( $field['multiple'] ) ) {
			$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : array();
		} else {
			$value = sanitize_text_field( (string) $value );
		}
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false || $value === null ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : ( ! empty( $field['multiple'] ) ? array() : '' );
		}
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'value';
		if ( $rf === 'label' || $rf === 'array' ) {
			$choices = $this->choices_to_array( isset( $field['choices'] ) ? $field['choices'] : array() );
			if ( ! empty( $field['multiple'] ) && is_array( $value ) ) {
				return array_map( function( $v ) use ( $choices, $rf ) {
					return $rf === 'label' ? ( isset( $choices[ $v ] ) ? $choices[ $v ] : $v ) : array( 'value' => $v, 'label' => isset( $choices[ $v ] ) ? $choices[ $v ] : $v );
				}, $value );
			}
			return $rf === 'label' ? ( isset( $choices[ $value ] ) ? $choices[ $value ] : $value ) : array( 'value' => $value, 'label' => isset( $choices[ $value ] ) ? $choices[ $value ] : $value );
		}
		return $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 8. Checkbox
// ════════════════════════════════════════════════════════════════
class DFP_Field_Checkbox extends DFP_Field_Base {

	public function get_type()    { return 'checkbox'; }
	public function get_label()   { return __( 'Checkbox', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'choices' => array(), 'default_value' => '', 'return_format' => 'value', 'layout' => 'vertical', 'toggle' => 0 );
	}

	public function render_field( $field, $value ) {
		$choices = $this->choices_to_array( isset( $field['choices'] ) ? $field['choices'] : array() );
		$checked = is_array( $value ) ? $value : ( $value !== '' ? array( $value ) : array() );
		$layout  = isset( $field['layout'] ) && $field['layout'] === 'horizontal' ? 'dfp-checkbox-horizontal' : 'dfp-checkbox-vertical';

		echo '<input type="hidden" name="' . $this->field_name( $field ) . '[]" value="">';
		echo '<ul class="dfp-checkbox-list ' . $layout . '">';

		if ( ! empty( $field['toggle'] ) ) {
			echo '<li class="dfp-checkbox-toggle"><label><input type="checkbox" class="dfp-toggle-all" data-target="' . $this->field_key( $field ) . '"> ' . esc_html__( 'Toggle All', 'dynamic-fields-pro' ) . '</label></li>';
		}
		foreach ( $choices as $v => $l ) {
			$is_checked = in_array( (string) $v, array_map( 'strval', $checked ), true );
			echo '<li><label><input type="checkbox" name="' . $this->field_name( $field ) . '[]" value="' . esc_attr( $v ) . '" data-group="' . $this->field_key( $field ) . '"' . ( $is_checked ? ' checked' : '' ) . '> ' . esc_html( $l ) . '</label></li>';
		}
		echo '</ul>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$choices_text = $this->choices_to_textarea( isset( $field['choices'] ) ? $field['choices'] : array() );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Choices', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<textarea class="widefat" rows="8" name="' . $this->field_key( $field ) . '[choices]">' . esc_textarea( $choices_text ) . '</textarea>';
		echo '</td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Toggle', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[toggle]" value="1"' . checked( ! empty( $field['toggle'] ), true, false ) . '> ' . esc_html__( 'Add "Toggle all" checkbox', 'dynamic-fields-pro' );
		echo '</td></tr>';
		$layout = isset( $field['layout'] ) ? $field['layout'] : 'vertical';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Layout', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[layout]">';
		foreach ( array( 'vertical' => __( 'Vertical', 'dynamic-fields-pro' ), 'horizontal' => __( 'Horizontal', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $layout, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = is_array( $value ) ? array_filter( array_map( 'sanitize_text_field', $value ) ) : array();
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 9. Radio
// ════════════════════════════════════════════════════════════════
class DFP_Field_Radio extends DFP_Field_Base {

	public function get_type()    { return 'radio'; }
	public function get_label()   { return __( 'Radio', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'choices' => array(), 'default_value' => '', 'return_format' => 'value', 'layout' => 'vertical', 'allow_null' => 0 );
	}

	public function render_field( $field, $value ) {
		$choices    = $this->choices_to_array( isset( $field['choices'] ) ? $field['choices'] : array() );
		$allow_null = ! empty( $field['allow_null'] );
		$layout     = isset( $field['layout'] ) && $field['layout'] === 'horizontal' ? 'dfp-radio-horizontal' : 'dfp-radio-vertical';

		echo '<ul class="dfp-radio-list ' . $layout . '">';
		if ( $allow_null ) {
			echo '<li><label><input type="radio" name="' . $this->field_name( $field ) . '" value=""' . ( $value === '' ? ' checked' : '' ) . '> ' . esc_html__( '— None —', 'dynamic-fields-pro' ) . '</label></li>';
		}
		foreach ( $choices as $v => $l ) {
			echo '<li><label><input type="radio" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $v ) . '"' . checked( (string) $value, (string) $v, false ) . '> ' . esc_html( $l ) . '</label></li>';
		}
		echo '</ul>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Choices', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<textarea class="widefat" rows="8" name="' . $this->field_key( $field ) . '[choices]">' . esc_textarea( $this->choices_to_textarea( isset( $field['choices'] ) ? $field['choices'] : array() ) ) . '</textarea></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Allow Null', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[allow_null]" value="1"' . checked( ! empty( $field['allow_null'] ), true, false ) . '></td></tr>';
		$this->row( __( 'Default Value', 'dynamic-fields-pro' ), '<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[default_value]" value="' . esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '">' );
		$layout = isset( $field['layout'] ) ? $field['layout'] : 'vertical';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Layout', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[layout]">';
		foreach ( array( 'vertical' => __( 'Vertical', 'dynamic-fields-pro' ), 'horizontal' => __( 'Horizontal', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $layout, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], sanitize_text_field( (string) $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		return $value === false || $value === null ? ( isset( $field['default_value'] ) ? $field['default_value'] : '' ) : (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 10. True / False
// ════════════════════════════════════════════════════════════════
class DFP_Field_True_False extends DFP_Field_Base {

	public function get_type()    { return 'true_false'; }
	public function get_label()   { return __( 'True / False', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'message' => '', 'default_value' => 0, 'ui' => 0, 'ui_on_text' => 'Yes', 'ui_off_text' => 'No' );
	}

	public function render_field( $field, $value ) {
		$message  = isset( $field['message'] ) ? $field['message'] : '';
		$ui       = ! empty( $field['ui'] );
		$on_text  = isset( $field['ui_on_text'] )  ? $field['ui_on_text']  : __( 'Yes', 'dynamic-fields-pro' );
		$off_text = isset( $field['ui_off_text'] ) ? $field['ui_off_text'] : __( 'No', 'dynamic-fields-pro' );

		echo '<input type="hidden" name="' . $this->field_name( $field ) . '" value="0">';
		if ( $ui ) {
			$checked = $value ? ' dfp-toggle-on' : '';
			echo '<div class="dfp-toggle-ui' . $checked . '" data-key="' . $this->field_key( $field ) . '">';
			echo '<span class="dfp-toggle-off-text">' . esc_html( $off_text ) . '</span>';
			echo '<span class="dfp-toggle-switch"><span class="dfp-toggle-knob"></span></span>';
			echo '<span class="dfp-toggle-on-text">' . esc_html( $on_text ) . '</span>';
			echo '<input type="checkbox" name="' . $this->field_name( $field ) . '" value="1"' . checked( (bool) $value, true, false ) . ' style="display:none">';
			echo '</div>';
		} else {
			echo '<label><input type="checkbox" name="' . $this->field_name( $field ) . '" value="1"' . checked( (bool) $value, true, false ) . '>';
			if ( $message !== '' ) {
				echo ' ' . esc_html( $message );
			}
			echo '</label>';
		}
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row( __( 'Message', 'dynamic-fields-pro' ), '<input type="text" class="widefat" name="' . $this->field_key( $field ) . '[message]" value="' . esc_attr( isset( $field['message'] ) ? $field['message'] : '' ) . '">' );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Default Value', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[default_value]" value="1"' . checked( ! empty( $field['default_value'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Stylised UI', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[ui]" value="1"' . checked( ! empty( $field['ui'] ), true, false ) . '></td></tr>';
		$this->row( __( 'On Text',  'dynamic-fields-pro' ), '<input type="text" name="' . $this->field_key( $field ) . '[ui_on_text]" value="' . esc_attr( isset( $field['ui_on_text'] ) ? $field['ui_on_text'] : 'Yes' ) . '">' );
		$this->row( __( 'Off Text', 'dynamic-fields-pro' ), '<input type="text" name="' . $this->field_key( $field ) . '[ui_off_text]" value="' . esc_attr( isset( $field['ui_off_text'] ) ? $field['ui_off_text'] : 'No' ) . '">' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], $value ? 1 : 0 );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( $value === '' || $value === false || $value === null ) {
			return isset( $field['default_value'] ) ? (bool) $field['default_value'] : false;
		}
		return (bool) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 11. Post Object
// ════════════════════════════════════════════════════════════════
class DFP_Field_Post_Object extends DFP_Field_Base {

	public function get_type()    { return 'post_object'; }
	public function get_label()   { return __( 'Post Object', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'post_type' => array( 'post' ), 'taxonomy' => array(), 'allow_null' => 1, 'multiple' => 0, 'return_format' => 'object', 'ui' => 1 );
	}

	public function render_field( $field, $value ) {
		$post_types = isset( $field['post_type'] ) ? (array) $field['post_type'] : array( 'post' );
		$multiple   = ! empty( $field['multiple'] );
		$allow_null = ! empty( $field['allow_null'] );
		$selected   = is_array( $value ) ? array_map( 'intval', $value ) : ( $value ? array( (int) $value ) : array() );
		$name       = $this->field_name( $field ) . ( $multiple ? '[]' : '' );

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $field['taxonomy'] ) ) {
			$tax_query = array( 'relation' => 'OR' );
			foreach ( (array) $field['taxonomy'] as $tax_term ) {
				$parts = explode( ':', $tax_term );
				if ( count( $parts ) === 2 ) {
					$tax_query[] = array( 'taxonomy' => $parts[0], 'field' => 'term_id', 'terms' => (int) $parts[1] );
				}
			}
			$args['tax_query'] = $tax_query;
		}

		$posts = get_posts( $args );

		echo '<select id="' . $this->field_key( $field ) . '" name="' . esc_attr( $name ) . '"' . ( $multiple ? ' multiple size="8"' : '' ) . ' class="dfp-select widefat">';
		if ( $allow_null ) {
			echo '<option value="">' . esc_html__( '— Select —', 'dynamic-fields-pro' ) . '</option>';
		}
		foreach ( $posts as $p ) {
			echo '<option value="' . absint( $p->ID ) . '"' . ( in_array( $p->ID, $selected, true ) ? ' selected' : '' ) . '>' . esc_html( $p->post_title ) . ' (#' . absint( $p->ID ) . ')</option>';
		}
		echo '</select>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$post_types_all = get_post_types( array( 'show_ui' => true ), 'objects' );
		$selected_types = isset( $field['post_type'] ) ? (array) $field['post_type'] : array( 'post' );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Post Type', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[post_type][]" multiple>';
		foreach ( $post_types_all as $pt ) {
			if ( $pt->name === 'dfp_group' ) continue;
			echo '<option value="' . esc_attr( $pt->name ) . '"' . ( in_array( $pt->name, $selected_types, true ) ? ' selected' : '' ) . '>' . esc_html( $pt->label ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Allow Null', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[allow_null]" value="1"' . checked( ! empty( $field['allow_null'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Multiple', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[multiple]" value="1"' . checked( ! empty( $field['multiple'] ), true, false ) . '></td></tr>';
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'object' => __( 'Post Object', 'dynamic-fields-pro' ), 'id' => __( 'Post ID', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		if ( ! empty( $field['multiple'] ) ) {
			$value = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : array();
		} else {
			$value = $value ? absint( $value ) : '';
		}
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		if ( $rf === 'id' ) {
			return $value;
		}
		if ( ! empty( $field['multiple'] ) && is_array( $value ) ) {
			$posts = array();
			foreach ( $value as $id ) {
				$p = get_post( $id );
				if ( $p ) $posts[] = $p;
			}
			return $posts;
		}
		return $value ? get_post( absint( $value ) ) : null;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 12. Relationship
// ════════════════════════════════════════════════════════════════
class DFP_Field_Relationship extends DFP_Field_Base {

	public function get_type()    { return 'relationship'; }
	public function get_label()   { return __( 'Relationship', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'post_type' => array( 'post' ), 'taxonomy' => array(), 'filters' => array( 'search', 'post_type', 'taxonomy' ), 'min' => '', 'max' => '', 'return_format' => 'object' );
	}

	public function render_field( $field, $value ) {
		$post_types   = isset( $field['post_type'] ) ? (array) $field['post_type'] : array( 'post' );
		$selected_ids = is_array( $value ) ? array_map( 'intval', $value ) : array();
		$key          = $this->field_key( $field );

		$all_posts = get_posts( array( 'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );

		echo '<div class="dfp-relationship-wrap" id="' . $key . '-wrap">';
		echo '<div class="dfp-relationship-cols">';

		// Left column: available
		echo '<div class="dfp-relationship-available">';
		echo '<p class="dfp-relationship-label">' . esc_html__( 'Available', 'dynamic-fields-pro' ) . '</p>';
		echo '<input type="text" class="dfp-rel-search widefat" placeholder="' . esc_attr__( 'Search…', 'dynamic-fields-pro' ) . '">';
		echo '<ul class="dfp-relationship-list dfp-relationship-source">';
		foreach ( $all_posts as $p ) {
			if ( in_array( $p->ID, $selected_ids, true ) ) {
				continue;
			}
			echo '<li data-id="' . absint( $p->ID ) . '" data-type="' . esc_attr( $p->post_type ) . '">' . esc_html( $p->post_title ) . '</li>';
		}
		echo '</ul></div>';

		// Right column: selected
		echo '<div class="dfp-relationship-selected">';
		echo '<p class="dfp-relationship-label">' . esc_html__( 'Selected', 'dynamic-fields-pro' ) . '</p>';
		echo '<ul class="dfp-relationship-list dfp-relationship-target">';
		foreach ( $selected_ids as $sid ) {
			$p = get_post( $sid );
			if ( ! $p ) { continue; }
			echo '<li data-id="' . absint( $sid ) . '">' . esc_html( $p->post_title ) . '<span class="dfp-rel-remove">&times;</span></li>';
			echo '<input type="hidden" name="' . $this->field_name( $field ) . '[]" value="' . absint( $sid ) . '">';
		}
		echo '</ul></div>';
		echo '</div>'; // .dfp-relationship-cols
		echo '</div>'; // .dfp-relationship-wrap
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$post_types_all = get_post_types( array( 'show_ui' => true ), 'objects' );
		$selected_types = isset( $field['post_type'] ) ? (array) $field['post_type'] : array( 'post' );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Post Type', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[post_type][]" multiple>';
		foreach ( $post_types_all as $pt ) {
			if ( $pt->name === 'dfp_group' ) continue;
			echo '<option value="' . esc_attr( $pt->name ) . '"' . ( in_array( $pt->name, $selected_types, true ) ? ' selected' : '' ) . '>' . esc_html( $pt->label ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->row( __( 'Min Items', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[min]" value="' . esc_attr( isset( $field['min'] ) ? $field['min'] : '' ) . '">' );
		$this->row( __( 'Max Items', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[max]" value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : '' ) . '">' );
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'object' => __( 'Post Object', 'dynamic-fields-pro' ), 'id' => __( 'Post ID', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : array();
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return array();
		}
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		if ( $rf === 'id' ) {
			return $value;
		}
		$posts = array();
		foreach ( $value as $id ) {
			$p = get_post( absint( $id ) );
			if ( $p ) { $posts[] = $p; }
		}
		return $posts;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( is_array( $value ) ) {
			$count = count( $value );
			if ( isset( $field['min'] ) && $field['min'] !== '' && $count < absint( $field['min'] ) ) {
				return sprintf( esc_html__( 'Minimum %d items required.', 'dynamic-fields-pro' ), $field['min'] );
			}
			if ( isset( $field['max'] ) && $field['max'] !== '' && $count > absint( $field['max'] ) ) {
				return sprintf( esc_html__( 'Maximum %d items allowed.', 'dynamic-fields-pro' ), $field['max'] );
			}
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 13. Taxonomy
// ════════════════════════════════════════════════════════════════
class DFP_Field_Taxonomy extends DFP_Field_Base {

	public function get_type()    { return 'taxonomy'; }
	public function get_label()   { return __( 'Taxonomy', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'taxonomy' => 'category', 'field_type' => 'checkbox', 'allow_null' => 0, 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'return_format' => 'object', 'multiple' => 0 );
	}

	public function render_field( $field, $value ) {
		$taxonomy   = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'category';
		$field_type = isset( $field['field_type'] ) ? $field['field_type'] : 'checkbox';
		$allow_null = ! empty( $field['allow_null'] );
		$multiple   = ! empty( $field['multiple'] ) || in_array( $field_type, array( 'checkbox', 'multi_select' ), true );
		$selected   = is_array( $value ) ? array_map( 'intval', $value ) : ( $value ? array( (int) $value ) : array() );

		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { $terms = array(); }

		if ( in_array( $field_type, array( 'select', 'multi_select' ), true ) ) {
			$name = $this->field_name( $field ) . ( $multiple ? '[]' : '' );
			echo '<select id="' . $this->field_key( $field ) . '" name="' . esc_attr( $name ) . '"' . ( $multiple ? ' multiple size="6"' : '' ) . ' class="widefat dfp-select">';
			if ( $allow_null ) {
				echo '<option value="">' . esc_html__( '— Select —', 'dynamic-fields-pro' ) . '</option>';
			}
			foreach ( $terms as $term ) {
				echo '<option value="' . absint( $term->term_id ) . '"' . ( in_array( $term->term_id, $selected, true ) ? ' selected' : '' ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
		} elseif ( $field_type === 'radio' ) {
			echo '<ul class="dfp-radio-list">';
			if ( $allow_null ) {
				echo '<li><label><input type="radio" name="' . $this->field_name( $field ) . '" value=""> ' . esc_html__( '— None —', 'dynamic-fields-pro' ) . '</label></li>';
			}
			foreach ( $terms as $term ) {
				echo '<li><label><input type="radio" name="' . $this->field_name( $field ) . '" value="' . absint( $term->term_id ) . '"' . ( in_array( $term->term_id, $selected, true ) ? ' checked' : '' ) . '> ' . esc_html( $term->name ) . '</label></li>';
			}
			echo '</ul>';
		} else {
			// checkbox
			echo '<input type="hidden" name="' . $this->field_name( $field ) . '[]" value="">';
			echo '<ul class="dfp-checkbox-list">';
			foreach ( $terms as $term ) {
				echo '<li><label><input type="checkbox" name="' . $this->field_name( $field ) . '[]" value="' . absint( $term->term_id ) . '"' . ( in_array( $term->term_id, $selected, true ) ? ' checked' : '' ) . '> ' . esc_html( $term->name ) . '</label></li>';
			}
			echo '</ul>';
		}

		if ( ! empty( $field['add_term'] ) ) {
			echo '<div class="dfp-add-term-wrap"><input type="text" class="dfp-add-term-input" placeholder="' . esc_attr__( 'New term name…', 'dynamic-fields-pro' ) . '"><button type="button" class="button dfp-add-term-btn" data-taxonomy="' . esc_attr( $taxonomy ) . '" data-field="' . $this->field_key( $field ) . '">' . esc_html__( 'Add Term', 'dynamic-fields-pro' ) . '</button></div>';
		}
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'objects' );
		$tax        = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'category';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Taxonomy', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[taxonomy]">';
		foreach ( $taxonomies as $t ) {
			echo '<option value="' . esc_attr( $t->name ) . '"' . selected( $tax, $t->name, false ) . '>' . esc_html( $t->label ) . '</option>';
		}
		echo '</select></td></tr>';
		$ft = isset( $field['field_type'] ) ? $field['field_type'] : 'checkbox';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Field Type', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[field_type]">';
		foreach ( array( 'checkbox' => __( 'Checkbox', 'dynamic-fields-pro' ), 'radio' => __( 'Radio', 'dynamic-fields-pro' ), 'select' => __( 'Select', 'dynamic-fields-pro' ), 'multi_select' => __( 'Multi-Select', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $ft, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Allow Null', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[allow_null]" value="1"' . checked( ! empty( $field['allow_null'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Add Term', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[add_term]" value="1"' . checked( ! empty( $field['add_term'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Save Terms', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[save_terms]" value="1"' . checked( ! empty( $field['save_terms'] ), true, false ) . '><p class="description">' . esc_html__( 'Connect saved terms to the post.', 'dynamic-fields-pro' ) . '</p></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Load Terms', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[load_terms]" value="1"' . checked( ! empty( $field['load_terms'] ), true, false ) . '><p class="description">' . esc_html__( 'Load value from post\'s taxonomy terms.', 'dynamic-fields-pro' ) . '</p></td></tr>';
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'object' => __( 'Term Object', 'dynamic-fields-pro' ), 'id' => __( 'Term ID', 'dynamic-fields-pro' ), 'slug' => __( 'Slug', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : ( $value ? array( absint( $value ) ) : array() );
		update_post_meta( $post_id, $field['key'], $value );

		if ( ! empty( $field['save_terms'] ) ) {
			$tax  = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'category';
			wp_set_object_terms( $post_id, $value, $tax );
		}
		return true;
	}

	public function load_value( $value, $post_id, $field ) {
		if ( ! empty( $field['load_terms'] ) ) {
			$tax   = isset( $field['taxonomy'] ) ? $field['taxonomy'] : 'category';
			$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			$value = is_wp_error( $terms ) ? array() : $terms;
		}
		if ( ! is_array( $value ) ) {
			$value = $value ? array( (int) $value ) : array();
		}
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		if ( $rf === 'id' ) { return $value; }
		$result = array();
		foreach ( $value as $tid ) {
			$term = get_term( absint( $tid ) );
			if ( ! $term || is_wp_error( $term ) ) { continue; }
			$result[] = $rf === 'slug' ? $term->slug : $term;
		}
		return $result;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 14. User
// ════════════════════════════════════════════════════════════════
class DFP_Field_User extends DFP_Field_Base {

	public function get_type()    { return 'user'; }
	public function get_label()   { return __( 'User', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'role' => '', 'allow_null' => 1, 'multiple' => 0, 'return_format' => 'object' );
	}

	public function render_field( $field, $value ) {
		$role       = isset( $field['role'] ) ? $field['role'] : '';
		$multiple   = ! empty( $field['multiple'] );
		$allow_null = ! empty( $field['allow_null'] );
		$selected   = is_array( $value ) ? array_map( 'intval', $value ) : ( $value ? array( (int) $value ) : array() );
		$name       = $this->field_name( $field ) . ( $multiple ? '[]' : '' );

		$args = array( 'fields' => array( 'ID', 'display_name' ) );
		if ( $role !== '' ) { $args['role'] = $role; }
		$users = get_users( $args );

		echo '<select id="' . $this->field_key( $field ) . '" name="' . esc_attr( $name ) . '"' . ( $multiple ? ' multiple size="6"' : '' ) . ' class="widefat dfp-select">';
		if ( $allow_null ) {
			echo '<option value="">' . esc_html__( '— Select —', 'dynamic-fields-pro' ) . '</option>';
		}
		foreach ( $users as $u ) {
			echo '<option value="' . absint( $u->ID ) . '"' . ( in_array( (int) $u->ID, $selected, true ) ? ' selected' : '' ) . '>' . esc_html( $u->display_name ) . ' (#' . absint( $u->ID ) . ')</option>';
		}
		echo '</select>';
	}

	public function render_field_settings( $field ) {
		global $wp_roles;
		$this->begin_settings( $field );
		$role = isset( $field['role'] ) ? $field['role'] : '';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Filter by Role', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[role]">';
		echo '<option value="">' . esc_html__( 'All Roles', 'dynamic-fields-pro' ) . '</option>';
		foreach ( $wp_roles->roles as $slug => $data ) {
			echo '<option value="' . esc_attr( $slug ) . '"' . selected( $role, $slug, false ) . '>' . esc_html( $data['name'] ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Allow Null', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[allow_null]" value="1"' . checked( ! empty( $field['allow_null'] ), true, false ) . '></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Multiple', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[multiple]" value="1"' . checked( ! empty( $field['multiple'] ), true, false ) . '></td></tr>';
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'object' => __( 'User Object', 'dynamic-fields-pro' ), 'id' => __( 'User ID', 'dynamic-fields-pro' ), 'array' => __( 'Array', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		if ( ! empty( $field['multiple'] ) ) {
			$value = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : array();
		} else {
			$value = $value ? absint( $value ) : '';
		}
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'object';
		if ( $rf === 'id' ) { return $value; }
		if ( ! empty( $field['multiple'] ) && is_array( $value ) ) {
			return array_map( function( $id ) use ( $rf ) {
				$u = get_user_by( 'id', $id );
				if ( ! $u ) { return $id; }
				return $rf === 'array' ? array( 'ID' => $u->ID, 'display_name' => $u->display_name, 'user_email' => $u->user_email ) : $u;
			}, $value );
		}
		if ( ! $value ) { return null; }
		$u = get_user_by( 'id', absint( $value ) );
		if ( ! $u ) { return null; }
		return $rf === 'array' ? array( 'ID' => $u->ID, 'display_name' => $u->display_name, 'user_email' => $u->user_email ) : $u;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 15. Date Picker
// ════════════════════════════════════════════════════════════════
class DFP_Field_Date_Picker extends DFP_Field_Base {

	public function get_type()    { return 'date_picker'; }
	public function get_label()   { return __( 'Date Picker', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'display_format' => 'dd/mm/yy', 'return_format' => 'Ymd', 'first_day' => 1 );
	}

	public function render_field( $field, $value ) {
		$display_format = isset( $field['display_format'] ) ? $field['display_format'] : 'dd/mm/yy';
		$return_format  = isset( $field['return_format'] )  ? $field['return_format']  : 'Ymd';
		$first_day      = isset( $field['first_day'] )      ? absint( $field['first_day'] ) : 1;

		// Convert stored value to display format.
		$display_value = '';
		if ( $value ) {
			$ts = strtotime( $value );
			if ( $ts ) {
				$display_value = date_i18n( $display_format, $ts );
			}
		}

		echo '<div class="dfp-date-picker-wrap">';
		echo '<input type="text" class="dfp-date-picker-display" value="' . esc_attr( $display_value ) . '" placeholder="' . esc_attr( $display_format ) . '" autocomplete="off" data-date-format="' . esc_attr( $display_format ) . '" data-return-format="' . esc_attr( $return_format ) . '" data-first-day="' . $first_day . '">';
		echo '<input type="hidden" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $value ) . '">';
		echo '</div>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row( __( 'Display Format', 'dynamic-fields-pro' ), '<input type="text" name="' . $this->field_key( $field ) . '[display_format]" value="' . esc_attr( isset( $field['display_format'] ) ? $field['display_format'] : 'dd/mm/yy' ) . '"><p class="description">' . esc_html__( 'jQuery UI datepicker format.', 'dynamic-fields-pro' ) . '</p>' );
		$this->row( __( 'Return Format', 'dynamic-fields-pro' ), '<input type="text" name="' . $this->field_key( $field ) . '[return_format]" value="' . esc_attr( isset( $field['return_format'] ) ? $field['return_format'] : 'Ymd' ) . '"><p class="description">' . esc_html__( 'PHP date() format for stored value.', 'dynamic-fields-pro' ) . '</p>' );
		$this->row( __( 'Week Starts On', 'dynamic-fields-pro' ), '<select name="' . $this->field_key( $field ) . '[first_day]"><option value="0"' . selected( 0, isset( $field['first_day'] ) ? (int) $field['first_day'] : 1, false ) . '>' . esc_html__( 'Sunday', 'dynamic-fields-pro' ) . '</option><option value="1"' . selected( 1, isset( $field['first_day'] ) ? (int) $field['first_day'] : 1, false ) . '>' . esc_html__( 'Monday', 'dynamic-fields-pro' ) . '</option></select>' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = sanitize_text_field( (string) $value );
		// Validate date format.
		if ( $value && ! strtotime( $value ) ) {
			$value = '';
		}
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		return (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 16. Color Picker
// ════════════════════════════════════════════════════════════════
class DFP_Field_Color_Picker extends DFP_Field_Base {

	public function get_type()    { return 'color_picker'; }
	public function get_label()   { return __( 'Color Picker', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'default_value' => '', 'enable_opacity' => 0, 'return_format' => 'string' );
	}

	public function render_field( $field, $value ) {
		$enable_opacity = ! empty( $field['enable_opacity'] );
		echo '<input type="text" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . esc_attr( $value ) . '" class="dfp-color-picker"';
		if ( $enable_opacity ) {
			echo ' data-alpha-enabled="true"';
		}
		if ( isset( $field['default_value'] ) && $field['default_value'] !== '' ) {
			echo ' data-default-color="' . esc_attr( $field['default_value'] ) . '"';
		}
		echo '>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$this->row( __( 'Default Value', 'dynamic-fields-pro' ), '<input type="text" class="dfp-color-picker-settings" name="' . $this->field_key( $field ) . '[default_value]" value="' . esc_attr( isset( $field['default_value'] ) ? $field['default_value'] : '' ) . '">' );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Enable Opacity', 'dynamic-fields-pro' ) . '</th><td><input type="checkbox" name="' . $this->field_key( $field ) . '[enable_opacity]" value="1"' . checked( ! empty( $field['enable_opacity'] ), true, false ) . '></td></tr>';
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'string';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'string' => __( 'Hex string', 'dynamic-fields-pro' ), 'array' => __( 'Array (r,g,b,a)', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = sanitize_hex_color( (string) $value );
		if ( ! $value ) {
			// Could be rgba – allow through after basic sanitization.
			$value = sanitize_text_field( (string) $value );
		}
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( ! $value ) {
			return isset( $field['default_value'] ) ? $field['default_value'] : '';
		}
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'string';
		if ( $rf === 'array' ) {
			$hex = ltrim( $value, '#' );
			if ( strlen( $hex ) === 6 ) {
				return array(
					'r' => hexdec( substr( $hex, 0, 2 ) ),
					'g' => hexdec( substr( $hex, 2, 2 ) ),
					'b' => hexdec( substr( $hex, 4, 2 ) ),
					'a' => 1,
				);
			}
		}
		return (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 17. Image
// ════════════════════════════════════════════════════════════════
class DFP_Field_Image extends DFP_Field_Base {

	public function get_type()    { return 'image'; }
	public function get_label()   { return __( 'Image', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'return_format' => 'array', 'preview_size' => 'medium', 'library' => 'all', 'min_width' => '', 'min_height' => '', 'max_width' => '', 'max_height' => '' );
	}

	public function render_field( $field, $value ) {
		$preview_size = isset( $field['preview_size'] ) ? $field['preview_size'] : 'medium';
		$attachment_id = is_array( $value ) ? ( isset( $value['id'] ) ? $value['id'] : 0 ) : (int) $value;
		$preview_url  = $attachment_id ? wp_get_attachment_image_url( $attachment_id, $preview_size ) : '';

		echo '<div class="dfp-image-wrap" id="' . $this->field_key( $field ) . '-wrap">';
		echo '<div class="dfp-image-preview" style="' . ( $preview_url ? '' : 'display:none' ) . '">';
		if ( $preview_url ) {
			echo '<img src="' . esc_url( $preview_url ) . '" alt="" style="max-width:300px;height:auto">';
		}
		echo '</div>';
		echo '<input type="hidden" id="' . $this->field_key( $field ) . '" name="' . $this->field_name( $field ) . '" value="' . absint( $attachment_id ) . '" class="dfp-image-id">';
		echo '<button type="button" class="button dfp-image-select" data-field="' . $this->field_key( $field ) . '" data-preview-size="' . esc_attr( $preview_size ) . '">';
		echo $preview_url ? esc_html__( 'Change Image', 'dynamic-fields-pro' ) : esc_html__( 'Add Image', 'dynamic-fields-pro' );
		echo '</button> ';
		echo '<button type="button" class="button dfp-image-remove"' . ( $preview_url ? '' : ' style="display:none"' ) . ' data-field="' . $this->field_key( $field ) . '">' . esc_html__( 'Remove', 'dynamic-fields-pro' ) . '</button>';
		echo '</div>';
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'array' => __( 'Image Array', 'dynamic-fields-pro' ), 'url' => __( 'Image URL', 'dynamic-fields-pro' ), 'id' => __( 'Image ID', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$sizes = get_intermediate_image_sizes();
		$ps    = isset( $field['preview_size'] ) ? $field['preview_size'] : 'medium';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Preview Size', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[preview_size]">';
		foreach ( $sizes as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $ps, $s, false ) . '>' . esc_html( $s ) . '</option>';
		}
		echo '</select></td></tr>';
		$lib = isset( $field['library'] ) ? $field['library'] : 'all';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Library', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[library]">';
		foreach ( array( 'all' => __( 'All', 'dynamic-fields-pro' ), 'uploadedTo' => __( 'Uploaded to post', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $lib, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->row( __( 'Min Width',  'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[min_width]" value="' . esc_attr( isset( $field['min_width'] ) ? $field['min_width'] : '' ) . '"> px' );
		$this->row( __( 'Min Height', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[min_height]" value="' . esc_attr( isset( $field['min_height'] ) ? $field['min_height'] : '' ) . '"> px' );
		$this->row( __( 'Max Width',  'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[max_width]" value="' . esc_attr( isset( $field['max_width'] ) ? $field['max_width'] : '' ) . '"> px' );
		$this->row( __( 'Max Height', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[max_height]" value="' . esc_attr( isset( $field['max_height'] ) ? $field['max_height'] : '' ) . '"> px' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], absint( $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		$id = absint( $value );
		if ( ! $id ) { return false; }
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		if ( $rf === 'id' )  { return $id; }
		if ( $rf === 'url' ) { return wp_get_attachment_url( $id ); }
		// array
		$src  = wp_get_attachment_image_src( $id, 'full' );
		$meta = wp_get_attachment_metadata( $id );
		return array(
			'id'     => $id,
			'url'    => $src ? $src[0] : '',
			'width'  => $src ? $src[1] : 0,
			'height' => $src ? $src[2] : 0,
			'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'title'  => get_the_title( $id ),
			'caption'=> wp_get_attachment_caption( $id ),
			'sizes'  => isset( $meta['sizes'] ) ? $meta['sizes'] : array(),
		);
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( ! $value ) { return true; }
		$meta = wp_get_attachment_metadata( absint( $value ) );
		if ( $meta ) {
			foreach ( array( 'min_width' => 'width', 'min_height' => 'height' ) as $setting => $dim ) {
				if ( isset( $field[ $setting ] ) && $field[ $setting ] !== '' && isset( $meta[ $dim ] ) && $meta[ $dim ] < (int) $field[ $setting ] ) {
					return sprintf( esc_html__( 'Image %s must be at least %dpx.', 'dynamic-fields-pro' ), $dim, $field[ $setting ] );
				}
			}
			foreach ( array( 'max_width' => 'width', 'max_height' => 'height' ) as $setting => $dim ) {
				if ( isset( $field[ $setting ] ) && $field[ $setting ] !== '' && isset( $meta[ $dim ] ) && $meta[ $dim ] > (int) $field[ $setting ] ) {
					return sprintf( esc_html__( 'Image %s must be at most %dpx.', 'dynamic-fields-pro' ), $dim, $field[ $setting ] );
				}
			}
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 18. Repeater
// ════════════════════════════════════════════════════════════════
class DFP_Field_Repeater extends DFP_Field_Base {

	public function get_type()    { return 'repeater'; }
	public function get_label()   { return __( 'Repeater', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array( 'sub_fields' => array(), 'min' => '', 'max' => '', 'layout' => 'block', 'button_label' => 'Add Row', 'collapsed' => '' );
	}

	public function render_field( $field, $value ) {
		$rows         = is_array( $value ) ? array_values( $value ) : array();
		$sub_fields   = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
		$layout       = isset( $field['layout'] ) ? $field['layout'] : 'block';
		$button_label = isset( $field['button_label'] ) && $field['button_label'] !== '' ? $field['button_label'] : __( 'Add Row', 'dynamic-fields-pro' );
		$key          = $this->field_key( $field );
		$min          = isset( $field['min'] ) && $field['min'] !== '' ? absint( $field['min'] ) : 0;
		$max          = isset( $field['max'] ) && $field['max'] !== '' ? absint( $field['max'] ) : 0;

		echo '<div class="dfp-repeater-wrap dfp-layout-' . esc_attr( $layout ) . '" id="' . $key . '-repeater" data-min="' . $min . '" data-max="' . $max . '" data-base-name="' . esc_attr( $field['key'] ) . '">';
		echo '<input type="hidden" class="dfp-row-count" name="' . esc_attr( $key ) . '_count" value="' . count( $rows ) . '">';

		// Header row (for table layout)
		if ( $layout === 'table' && ! empty( $sub_fields ) ) {
			echo '<div class="dfp-repeater-header dfp-row">';
			echo '<div class="dfp-row-handle-col"></div>';
			foreach ( $sub_fields as $sf ) {
				echo '<div class="dfp-col">' . esc_html( $sf['label'] ) . '</div>';
			}
			echo '<div class="dfp-row-actions-col"></div>';
			echo '</div>';
		}

		echo '<div class="dfp-rows-list">';
		foreach ( $rows as $i => $row_data ) {
			$this->render_repeater_row( $field, $sub_fields, $row_data, $i, $layout );
		}
		echo '</div>'; // .dfp-rows-list

		// Clone template (hidden)
		echo '<div class="dfp-row-clone" style="display:none">';
		$this->render_repeater_row( $field, $sub_fields, array(), 'clone', $layout );
		echo '</div>';

		echo '<div class="dfp-repeater-footer">';
		echo '<button type="button" class="button dfp-add-row" data-repeater="' . $key . '-repeater">' . esc_html( $button_label ) . '</button>';
		echo '</div>';
		echo '</div>'; // .dfp-repeater-wrap
	}

	private function render_repeater_row( $field, $sub_fields, $row_data, $index, $layout ) {
		$key        = $this->field_key( $field );
		$is_clone   = $index === 'clone';
		$collapsed  = ! $is_clone && isset( $field['collapsed'] ) && $field['collapsed'] !== '' ? '' : '';

		echo '<div class="dfp-row' . ( $collapsed ? ' dfp-row-collapsed' : '' ) . '" data-index="' . ( $is_clone ? 'clone' : absint( $index ) ) . '">';
		echo '<div class="dfp-row-header">';
		echo '<span class="dfp-row-handle" draggable="true" title="' . esc_attr__( 'Drag to reorder', 'dynamic-fields-pro' ) . '">&#9776;</span>';
		echo '<span class="dfp-row-title">' . ( $is_clone ? '' : esc_html( sprintf( __( 'Row %d', 'dynamic-fields-pro' ), $index + 1 ) ) ) . '</span>';
		echo '<span class="dfp-row-actions">';
		echo '<button type="button" class="dfp-row-collapse" title="' . esc_attr__( 'Collapse', 'dynamic-fields-pro' ) . '">&#9650;</button>';
		echo '<button type="button" class="dfp-row-delete button-link-delete" title="' . esc_attr__( 'Remove row', 'dynamic-fields-pro' ) . '">&times;</button>';
		echo '</span>';
		echo '</div>'; // .dfp-row-header

		echo '<div class="dfp-row-fields dfp-layout-' . esc_attr( $layout ) . '">';
		foreach ( $sub_fields as $sf ) {
			$sf_type  = isset( $sf['type'] ) ? $sf['type'] : 'text';
			$sf_sname = isset( $sf['name'] ) ? $sf['name'] : '';
			$sf_value = ( ! $is_clone && isset( $row_data[ $sf_sname ] ) ) ? $row_data[ $sf_sname ] : ( isset( $sf['default_value'] ) ? $sf['default_value'] : '' );

			// HTML name uses bracket notation so PHP parses it as $repeater_key[0][subfield_name].
			$html_name = $field['key'] . '[' . ( $is_clone ? 'clone' : absint( $index ) ) . '][' . $sf_sname . ']';
			// HTML id must be valid (no brackets) — use underscores.
			$html_id   = sanitize_key( $field['key'] ) . '_' . ( $is_clone ? 'clone' : absint( $index ) ) . '_' . sanitize_key( $sf_sname );

			$sf_copy                  = $sf;
			$sf_copy['_name_override'] = $html_name;
			$sf_copy['_id_override']   = $html_id;
			// Keep original key for field-object lookups; do NOT change it.

			$field_type = DFP_Fields::get_field_type( $sf_type );

			$req_mark = ! empty( $sf['required'] ) ? ' <span class="dfp-required">*</span>' : '';
			echo '<div class="dfp-sub-field dfp-sub-field-' . esc_attr( $sf_type ) . '">';
			if ( $layout !== 'table' ) {
				echo '<div class="dfp-sub-label"><label for="' . esc_attr( $html_id ) . '">' . esc_html( $sf['label'] ) . $req_mark . '</label></div>';
			}
			echo '<div class="dfp-sub-input">';
			if ( $field_type ) {
				$field_type->render_field( $sf_copy, $sf_value );
			}
			echo '</div>';
			echo '</div>';
		}
		echo '</div>'; // .dfp-row-fields
		echo '</div>'; // .dfp-row
	}

	public function render_field_settings( $field ) {
		$sub_fields   = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
		$layout       = isset( $field['layout'] ) ? $field['layout'] : 'block';
		$button_label = isset( $field['button_label'] ) ? $field['button_label'] : __( 'Add Row', 'dynamic-fields-pro' );

		$this->begin_settings( $field );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Sub Fields', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<div class="dfp-sub-fields-builder" data-parent-key="' . $this->field_key( $field ) . '">';
		echo '<div class="dfp-sub-fields-header"><span></span><span>' . esc_html__( 'Label', 'dynamic-fields-pro' ) . '</span><span>' . esc_html__( 'Name', 'dynamic-fields-pro' ) . '</span><span>' . esc_html__( 'Type', 'dynamic-fields-pro' ) . '</span><span></span></div>';
		foreach ( $sub_fields as $sf ) {
			echo '<div class="dfp-sub-field-row">';
			echo '<span class="dfp-row-handle">&#9776;</span>';
			echo '<input type="text" placeholder="' . esc_attr__( 'Label', 'dynamic-fields-pro' ) . '" name="' . $this->field_key( $field ) . '[sub_fields][][label]" value="' . esc_attr( $sf['label'] ) . '">';
			echo '<input type="text" placeholder="' . esc_attr__( 'Name', 'dynamic-fields-pro' ) . '" name="' . $this->field_key( $field ) . '[sub_fields][][name]" value="' . esc_attr( $sf['name'] ) . '">';
			echo '<select name="' . $this->field_key( $field ) . '[sub_fields][][type]">';
			foreach ( DFP_Fields::get_all_types() as $type_slug => $type_label ) {
				if ( $type_slug === 'repeater' ) { continue; } // no nested repeaters in builder
				echo '<option value="' . esc_attr( $type_slug ) . '"' . selected( $sf['type'], $type_slug, false ) . '>' . esc_html( $type_label ) . '</option>';
			}
			echo '</select>';
			echo '<button type="button" class="button button-small dfp-remove-sub-field">&times;</button>';
			echo '</div>';
		}
		echo '<button type="button" class="button dfp-add-sub-field" data-parent="' . $this->field_key( $field ) . '">' . esc_html__( '+ Add Sub-Field', 'dynamic-fields-pro' ) . '</button>';
		echo '</div>';
		echo '</td></tr>';
		$this->row( __( 'Min Rows', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[min]" value="' . esc_attr( isset( $field['min'] ) ? $field['min'] : '' ) . '">' );
		$this->row( __( 'Max Rows', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[max]" value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : '' ) . '">' );
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Layout', 'dynamic-fields-pro' ) . '</th><td><select name="' . $this->field_key( $field ) . '[layout]">';
		foreach ( array( 'table' => __( 'Table', 'dynamic-fields-pro' ), 'block' => __( 'Block', 'dynamic-fields-pro' ), 'row' => __( 'Row', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $layout, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->row( __( 'Button Label', 'dynamic-fields-pro' ), '<input type="text" name="' . $this->field_key( $field ) . '[button_label]" value="' . esc_attr( $button_label ) . '">' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) ) {
			return update_post_meta( $post_id, $field['key'], array() );
		}
		$sub_fields = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
		$rows       = array();

		// Remove the sentinel "clone" key.
		unset( $value['clone'] );

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$clean_row = array();
			foreach ( $sub_fields as $sf ) {
				$sf_name     = $sf['name'];
				$sf_value    = isset( $row[ $sf_name ] ) ? $row[ $sf_name ] : '';
				$sf_type_obj = DFP_Fields::get_field_type( $sf['type'] );
				if ( $sf_type_obj ) {
					// Sanitize inline without writing to DB (store result in row).
					$clean_row[ $sf_name ] = _dfp_sanitize_sub_field( $sf_value, $sf, $sf_type_obj );
				} else {
					$clean_row[ $sf_name ] = sanitize_text_field( (string) $sf_value );
				}
			}
			$rows[] = $clean_row;
		}

		return update_post_meta( $post_id, $field['key'], $rows );
	}

	public function load_value( $value, $post_id, $field ) {
		return is_array( $value ) ? array_values( $value ) : array();
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		if ( is_array( $value ) ) {
			$count = count( $value );
			if ( isset( $field['min'] ) && $field['min'] !== '' && $count < absint( $field['min'] ) ) {
				return sprintf( esc_html__( 'Minimum %d rows required.', 'dynamic-fields-pro' ), $field['min'] );
			}
			if ( isset( $field['max'] ) && $field['max'] !== '' && $count > absint( $field['max'] ) ) {
				return sprintf( esc_html__( 'Maximum %d rows allowed.', 'dynamic-fields-pro' ), $field['max'] );
			}
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 19. WYSIWYG (rich-text editor)
// ════════════════════════════════════════════════════════════════
class DFP_Field_WYSIWYG extends DFP_Field_Base {

	public function get_type()    { return 'wysiwyg'; }
	public function get_label()   { return __( 'WYSIWYG Editor', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'tabs'         => 'all',
			'toolbar'      => 'full',
			'media_upload' => 1,
			'delay'        => 0,
			'rows'         => 10,
		);
	}

	public function render_field( $field, $value ) {
		$key   = $field['key'];
		// Build a valid HTML id: replace anything that isn't alphanumeric/underscore with _.
		// Also use _id_override if set (for sub-field context).
		$html_id   = $this->field_key( $field );
		$html_id   = preg_replace( '/[^a-z0-9_]/', '_', strtolower( $html_id ) );
		$html_name = $this->field_name( $field );
		$rows      = isset( $field['rows'] ) && $field['rows'] > 0 ? absint( $field['rows'] ) : 10;
		$teeny     = isset( $field['toolbar'] ) && $field['toolbar'] === 'basic';

		// Inside a clone template (id contains "clone") we cannot call wp_editor().
		// Render a styled textarea instead; JS will upgrade it when a row is added.
		if ( strpos( $html_id, 'clone' ) !== false || strpos( $html_name, '[clone]' ) !== false ) {
			echo '<textarea class="dfp-wysiwyg-textarea widefat" name="' . esc_attr( $html_name ) . '" id="' . esc_attr( $html_id ) . '" rows="' . $rows . '">' . esc_textarea( $value ) . '</textarea>';
			return;
		}

		$settings = array(
			'textarea_name' => $html_name,
			'media_buttons' => ! empty( $field['media_upload'] ),
			'textarea_rows' => $rows,
			'teeny'         => $teeny,
			'tinymce'       => ! $teeny,
			'quicktags'     => true,
		);

		wp_editor( wp_kses_post( $value ), $html_id, $settings );
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$tabs = isset( $field['tabs'] ) ? $field['tabs'] : 'all';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Tabs', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[tabs]">';
		foreach ( array( 'all' => __( 'Visual &amp; Text', 'dynamic-fields-pro' ), 'visual' => __( 'Visual Only', 'dynamic-fields-pro' ), 'text' => __( 'Text Only', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $tabs, $v, false ) . '>' . wp_kses_post( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$toolbar = isset( $field['toolbar'] ) ? $field['toolbar'] : 'full';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Toolbar', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[toolbar]">';
		foreach ( array( 'full' => __( 'Full', 'dynamic-fields-pro' ), 'basic' => __( 'Basic (teeny)', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $toolbar, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Media Upload', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<input type="checkbox" name="' . $this->field_key( $field ) . '[media_upload]" value="1"' . checked( ! empty( $field['media_upload'] ), true, false ) . '></td></tr>';
		$this->row( __( 'Height (rows)', 'dynamic-fields-pro' ), '<input type="number" min="2" class="small-text" name="' . $this->field_key( $field ) . '[rows]" value="' . absint( isset( $field['rows'] ) ? $field['rows'] : 10 ) . '">' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], wp_kses_post( $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		return (string) $value;
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// 20. Gallery (multiple images)
// ════════════════════════════════════════════════════════════════
class DFP_Field_Gallery extends DFP_Field_Base {

	public function get_type()    { return 'gallery'; }
	public function get_label()   { return __( 'Gallery', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'return_format' => 'array',
			'preview_size'  => 'thumbnail',
			'insert'        => 'append',
			'library'       => 'all',
			'min'           => '',
			'max'           => '',
		);
	}

	public function render_field( $field, $value ) {
		$ids          = is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : array();
		$preview_size = isset( $field['preview_size'] ) ? $field['preview_size'] : 'thumbnail';
		$key          = $this->field_key( $field );
		$name         = $this->field_name( $field );
		$min          = isset( $field['min'] ) && $field['min'] !== '' ? absint( $field['min'] ) : 0;
		$max          = isset( $field['max'] ) && $field['max'] !== '' ? absint( $field['max'] ) : 0;

		echo '<div class="dfp-gallery-wrap" id="' . $key . '-gallery" data-field="' . $key . '" data-field-name="' . esc_attr( $name ) . '" data-min="' . $min . '" data-max="' . $max . '">';
		echo '<div class="dfp-gallery-grid" id="' . $key . '-grid">';

		foreach ( $ids as $id ) {
			$img_url = wp_get_attachment_image_url( $id, $preview_size );
			if ( ! $img_url ) { continue; }
			echo '<div class="dfp-gallery-item" data-id="' . absint( $id ) . '">';
			echo '<img src="' . esc_url( $img_url ) . '" alt="">';
			echo '<input type="hidden" class="dfp-gallery-id" name="' . esc_attr( $name ) . '[]" value="' . absint( $id ) . '">';
			echo '<button type="button" class="dfp-gallery-item-remove" title="' . esc_attr__( 'Remove', 'dynamic-fields-pro' ) . '">&times;</button>';
			echo '</div>';
		}

		echo '</div>'; // .dfp-gallery-grid

		echo '<div class="dfp-gallery-actions">';
		echo '<button type="button" class="button dfp-gallery-add" data-preview-size="' . esc_attr( $preview_size ) . '">' . esc_html__( 'Add Images', 'dynamic-fields-pro' ) . '</button>';
		echo ' <button type="button" class="button dfp-gallery-clear">' . esc_html__( 'Clear Gallery', 'dynamic-fields-pro' ) . '</button>';
		echo ' <span class="dfp-gallery-count">';
		printf( esc_html( _n( '%d image', '%d images', count( $ids ), 'dynamic-fields-pro' ) ), count( $ids ) );
		if ( $min || $max ) {
			echo ' ';
			if ( $min && $max ) {
				printf( esc_html__( '(min: %d, max: %d)', 'dynamic-fields-pro' ), $min, $max );
			} elseif ( $min ) {
				printf( esc_html__( '(min: %d)', 'dynamic-fields-pro' ), $min );
			} else {
				printf( esc_html__( '(max: %d)', 'dynamic-fields-pro' ), $max );
			}
		}
		echo '</span>';
		echo '</div>';

		echo '</div>'; // .dfp-gallery-wrap
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'array' => __( 'Image Array', 'dynamic-fields-pro' ), 'url' => __( 'URL', 'dynamic-fields-pro' ), 'id' => __( 'ID', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$sizes = get_intermediate_image_sizes();
		$ps    = isset( $field['preview_size'] ) ? $field['preview_size'] : 'thumbnail';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Preview Size', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[preview_size]">';
		foreach ( $sizes as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $ps, $s, false ) . '>' . esc_html( $s ) . '</option>';
		}
		echo '</select></td></tr>';
		$lib = isset( $field['library'] ) ? $field['library'] : 'all';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Library', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[library]">';
		foreach ( array( 'all' => __( 'All', 'dynamic-fields-pro' ), 'uploadedTo' => __( 'Uploaded to post', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $lib, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->row( __( 'Min Items', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[min]" value="' . esc_attr( isset( $field['min'] ) ? $field['min'] : '' ) . '">' );
		$this->row( __( 'Max Items', 'dynamic-fields-pro' ), '<input type="number" min="0" name="' . $this->field_key( $field ) . '[max]" value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : '' ) . '">' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		$value = is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array();
		return update_post_meta( $post_id, $field['key'], $value );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return array();
		}
		$rf    = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		$ids   = array_filter( array_map( 'absint', $value ) );
		if ( $rf === 'id' ) {
			return array_values( $ids );
		}
		$result = array();
		foreach ( $ids as $id ) {
			if ( $rf === 'url' ) {
				$url = wp_get_attachment_url( $id );
				if ( $url ) { $result[] = $url; }
			} else {
				// array
				$src  = wp_get_attachment_image_src( $id, 'full' );
				$meta = wp_get_attachment_metadata( $id );
				$result[] = array(
					'id'      => $id,
					'url'     => $src ? $src[0] : '',
					'width'   => $src ? $src[1] : 0,
					'height'  => $src ? $src[2] : 0,
					'alt'     => get_post_meta( $id, '_wp_attachment_image_alt', true ),
					'title'   => get_the_title( $id ),
					'caption' => wp_get_attachment_caption( $id ),
					'sizes'   => isset( $meta['sizes'] ) ? $meta['sizes'] : array(),
				);
			}
		}
		return $result;
	}

	public function validate_value( $valid, $value, $field ) {
		$valid = parent::validate_value( $valid, $value, $field );
		if ( $valid !== true ) { return $valid; }
		$count = is_array( $value ) ? count( array_filter( $value ) ) : 0;
		if ( isset( $field['min'] ) && $field['min'] !== '' && $count < absint( $field['min'] ) ) {
			return sprintf( esc_html__( 'Please select at least %d images.', 'dynamic-fields-pro' ), $field['min'] );
		}
		if ( isset( $field['max'] ) && $field['max'] !== '' && $count > absint( $field['max'] ) ) {
			return sprintf( esc_html__( 'Maximum %d images allowed.', 'dynamic-fields-pro' ), $field['max'] );
		}
		return true;
	}
}

// ════════════════════════════════════════════════════════════════
// 21. File
// ════════════════════════════════════════════════════════════════
class DFP_Field_File extends DFP_Field_Base {

	public function get_type()    { return 'file'; }
	public function get_label()   { return __( 'File', 'dynamic-fields-pro' ); }
	public function get_defaults() {
		return array(
			'return_format' => 'array',
			'library'       => 'all',
			'min_size'      => '',
			'max_size'      => '',
			'mime_types'    => '',
		);
	}

	public function render_field( $field, $value ) {
		$attachment_id = is_array( $value ) ? ( isset( $value['id'] ) ? $value['id'] : 0 ) : absint( $value );
		$attachment    = $attachment_id ? get_post( $attachment_id ) : null;
		$key           = $this->field_key( $field );
		$name          = $this->field_name( $field );
		$has_file      = (bool) $attachment;

		$mime_types = isset( $field['mime_types'] ) ? trim( $field['mime_types'] ) : '';
		echo '<div class="dfp-file-wrap" id="' . $key . '-wrap">';
		echo '<input type="hidden" id="' . $key . '" name="' . esc_attr( $name ) . '" value="' . absint( $attachment_id ) . '" class="dfp-file-id">';
		echo '<div class="dfp-file-info"' . ( $has_file ? '' : ' style="display:none"' ) . '>';
		if ( $has_file ) {
			$url      = wp_get_attachment_url( $attachment_id );
			$filename = basename( get_attached_file( $attachment_id ) );
			$raw_file = get_attached_file( $attachment_id );
			$file_size = $raw_file && file_exists( $raw_file ) ? size_format( filesize( $raw_file ) ) : '';
			echo '<span class="dfp-file-name"><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $filename ) . '</a></span>';
			if ( $file_size ) {
				echo '<span class="dfp-file-size">(' . esc_html( $file_size ) . ')</span>';
			}
		} else {
			echo '<span class="dfp-file-name"></span><span class="dfp-file-size"></span>';
		}
		echo '</div>';
		echo '<button type="button" class="button dfp-file-select"' . ( $mime_types ? ' data-mime-types="' . esc_attr( $mime_types ) . '"' : '' ) . '>' . ( $has_file ? esc_html__( 'Change File', 'dynamic-fields-pro' ) : esc_html__( 'Select File', 'dynamic-fields-pro' ) ) . '</button>';
		echo ' <button type="button" class="dfp-file-remove"' . ( $has_file ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Remove', 'dynamic-fields-pro' ) . '</button>';
		echo '</div>'; // .dfp-file-wrap
	}

	public function render_field_settings( $field ) {
		$this->begin_settings( $field );
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Return Format', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[return_format]">';
		foreach ( array( 'array' => __( 'File Array', 'dynamic-fields-pro' ), 'url' => __( 'URL', 'dynamic-fields-pro' ), 'id' => __( 'ID', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $rf, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$lib = isset( $field['library'] ) ? $field['library'] : 'all';
		echo '<tr class="dfp-settings-row"><th>' . esc_html__( 'Library', 'dynamic-fields-pro' ) . '</th><td>';
		echo '<select name="' . $this->field_key( $field ) . '[library]">';
		foreach ( array( 'all' => __( 'All', 'dynamic-fields-pro' ), 'uploadedTo' => __( 'Uploaded to post', 'dynamic-fields-pro' ) ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $lib, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		$this->row( __( 'Allowed File Types', 'dynamic-fields-pro' ), '<input type="text" class="widefat" placeholder="' . esc_attr__( 'e.g. pdf,doc,zip', 'dynamic-fields-pro' ) . '" name="' . $this->field_key( $field ) . '[mime_types]" value="' . esc_attr( isset( $field['mime_types'] ) ? $field['mime_types'] : '' ) . '"><p class="description">' . esc_html__( 'Comma separated. Leave blank for all types.', 'dynamic-fields-pro' ) . '</p>' );
		$this->end_settings();
	}

	public function update_value( $value, $post_id, $field ) {
		return update_post_meta( $post_id, $field['key'], absint( $value ) );
	}

	public function load_value( $value, $post_id, $field ) {
		$id = absint( $value );
		if ( ! $id ) { return false; }
		$rf = isset( $field['return_format'] ) ? $field['return_format'] : 'array';
		if ( $rf === 'id' )  { return $id; }
		$url = wp_get_attachment_url( $id );
		if ( ! $url ) { return false; }
		if ( $rf === 'url' ) { return $url; }
		$post = get_post( $id );
		$file = get_attached_file( $id );
		return array(
			'id'        => $id,
			'url'       => $url,
			'title'     => $post ? $post->post_title : '',
			'filename'  => $file ? basename( $file ) : '',
			'filesize'  => $file && file_exists( $file ) ? filesize( $file ) : 0,
			'mime_type' => $post ? $post->post_mime_type : '',
		);
	}

	public function validate_value( $valid, $value, $field ) {
		return parent::validate_value( $valid, $value, $field );
	}
}

// ════════════════════════════════════════════════════════════════
// Container / Registry
// ════════════════════════════════════════════════════════════════

class DFP_Fields {

	private static $types = array();

	public function __construct() {
		// Register all built-in types.
		$instances = array(
			new DFP_Field_Text(),
			new DFP_Field_Textarea(),
			new DFP_Field_Number(),
			new DFP_Field_Email(),
			new DFP_Field_URL(),
			new DFP_Field_Password(),
			new DFP_Field_Select(),
			new DFP_Field_Checkbox(),
			new DFP_Field_Radio(),
			new DFP_Field_True_False(),
			new DFP_Field_Post_Object(),
			new DFP_Field_Relationship(),
			new DFP_Field_Taxonomy(),
			new DFP_Field_User(),
			new DFP_Field_Date_Picker(),
			new DFP_Field_Color_Picker(),
			new DFP_Field_Image(),
			new DFP_Field_WYSIWYG(),
			new DFP_Field_Gallery(),
			new DFP_Field_File(),
			new DFP_Field_Repeater(),
		);
		foreach ( $instances as $instance ) {
			self::$types[ $instance->get_type() ] = $instance;
		}

		do_action( 'dfp/register_fields', $this );
	}

	/**
	 * Register a custom field type.
	 *
	 * @param DFP_Field_Base $instance
	 */
	public function register_field_type( DFP_Field_Base $instance ) {
		self::$types[ $instance->get_type() ] = $instance;
	}

	/**
	 * @param string $type
	 * @return DFP_Field_Base|null
	 */
	public static function get_field_type( $type ) {
		return isset( self::$types[ $type ] ) ? self::$types[ $type ] : null;
	}

	/**
	 * @return array  slug => label
	 */
	public static function get_all_types() {
		$out = array();
		foreach ( self::$types as $slug => $instance ) {
			$out[ $slug ] = $instance->get_label();
		}
		return $out;
	}

	/**
	 * Field types grouped for the field-type picker UI.
	 *
	 * @return array  group_label => [ slug => label ]
	 */
	public static function get_types_grouped() {
		return array(
			__( 'Basic', 'dynamic-fields-pro' )      => array( 'text' => __( 'Text', 'dynamic-fields-pro' ), 'textarea' => __( 'Textarea', 'dynamic-fields-pro' ), 'number' => __( 'Number', 'dynamic-fields-pro' ), 'email' => __( 'Email', 'dynamic-fields-pro' ), 'url' => __( 'URL', 'dynamic-fields-pro' ), 'password' => __( 'Password', 'dynamic-fields-pro' ) ),
			__( 'Content', 'dynamic-fields-pro' )    => array( 'image' => __( 'Image', 'dynamic-fields-pro' ), 'gallery' => __( 'Gallery', 'dynamic-fields-pro' ), 'file' => __( 'File', 'dynamic-fields-pro' ), 'wysiwyg' => __( 'WYSIWYG Editor', 'dynamic-fields-pro' ) ),
			__( 'Choice', 'dynamic-fields-pro' )     => array( 'select' => __( 'Select', 'dynamic-fields-pro' ), 'checkbox' => __( 'Checkbox', 'dynamic-fields-pro' ), 'radio' => __( 'Radio', 'dynamic-fields-pro' ), 'true_false' => __( 'True / False', 'dynamic-fields-pro' ) ),
			__( 'Relational', 'dynamic-fields-pro' ) => array( 'post_object' => __( 'Post Object', 'dynamic-fields-pro' ), 'relationship' => __( 'Relationship', 'dynamic-fields-pro' ), 'taxonomy' => __( 'Taxonomy', 'dynamic-fields-pro' ), 'user' => __( 'User', 'dynamic-fields-pro' ) ),
			__( 'jQuery', 'dynamic-fields-pro' )     => array( 'date_picker' => __( 'Date Picker', 'dynamic-fields-pro' ), 'color_picker' => __( 'Color Picker', 'dynamic-fields-pro' ) ),
			__( 'Layout', 'dynamic-fields-pro' )     => array( 'repeater' => __( 'Repeater', 'dynamic-fields-pro' ) ),
		);
	}
}

/**
 * Sanitize a repeater sub-field value without persisting to the database.
 * Called by DFP_Field_Repeater::update_value() for each sub-field.
 *
 * @param mixed          $value
 * @param array          $field     Sub-field definition.
 * @param DFP_Field_Base $type_obj  The field-type instance for that sub-field.
 * @return mixed  Sanitized value ready to be stored in the repeater row array.
 */
function _dfp_sanitize_sub_field( $value, array $field, DFP_Field_Base $type_obj ) {
	switch ( $type_obj->get_type() ) {
		case 'text':
		case 'radio':
		case 'select':
		case 'password':
		case 'color_picker':
		case 'date_picker':
			return sanitize_text_field( (string) $value );

		case 'textarea':
			return sanitize_textarea_field( (string) $value );

		case 'number':
			return is_numeric( $value ) ? $value + 0 : '';

		case 'email':
			return sanitize_email( (string) $value );

		case 'url':
			return esc_url_raw( (string) $value );

		case 'wysiwyg':
			return wp_kses_post( (string) $value );

		case 'file':
		case 'image':
		case 'post_object':
		case 'user':
			if ( is_array( $value ) ) {
				return array_filter( array_map( 'absint', $value ) );
			}
			return $value ? absint( $value ) : '';

		case 'gallery':
			if ( is_array( $value ) ) {
				return array_values( array_filter( array_map( 'absint', $value ) ) );
			}
			return array();

		case 'checkbox':
		case 'relationship':
		case 'taxonomy':
			return is_array( $value )
				? array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) )
				: array();

		case 'true_false':
			return $value ? 1 : 0;

		default:
			return sanitize_text_field( (string) $value );
	}
}
