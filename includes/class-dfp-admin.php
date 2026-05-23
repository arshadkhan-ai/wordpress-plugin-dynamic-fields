<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DFP_Admin {

	public function __construct() {
		add_action( 'admin_menu',             array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
	}

	// ── Menu ─────────────────────────────────────────────────────────────────

	public function admin_menu() {
		add_menu_page(
			esc_html__( 'Dynamic Fields Pro', 'dynamic-fields-pro' ),
			esc_html__( 'Dynamic Fields', 'dynamic-fields-pro' ),
			'manage_options',
			'dfp-field-groups',
			array( $this, 'dispatch_page' ),
			'dashicons-editor-table',
			25
		);
		add_submenu_page(
			'dfp-field-groups',
			esc_html__( 'Field Groups', 'dynamic-fields-pro' ),
			esc_html__( 'Field Groups', 'dynamic-fields-pro' ),
			'manage_options',
			'dfp-field-groups',
			array( $this, 'dispatch_page' )
		);
	}

	/** Route requests to the correct page renderer. */
	public function dispatch_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		if ( $action === 'edit' || $action === 'new' ) {
			$this->render_builder_page();
		} else {
			$this->render_groups_list_page();
		}
	}

	// ── Assets ───────────────────────────────────────────────────────────────

	public function enqueue_assets( $hook ) {
		global $pagenow;

		$on_dfp_page = ( strpos( $hook, 'dfp-field-groups' ) !== false );
		$on_edit_page= in_array( $pagenow, array( 'post.php', 'post-new.php' ), true );

		if ( ! $on_dfp_page && ! $on_edit_page ) {
			return;
		}

		wp_enqueue_style(
			'dfp-admin',
			DFP_URL . 'assets/css/dfp-admin.css',
			array( 'wp-color-picker' ),
			DFP_VERSION
		);

		wp_enqueue_script(
			'dfp-repeater',
			DFP_URL . 'assets/js/dfp-repeater.js',
			array( 'jquery' ),
			DFP_VERSION,
			true
		);

		wp_enqueue_script(
			'dfp-admin',
			DFP_URL . 'assets/js/dfp-admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'wp-color-picker', 'dfp-repeater' ),
			DFP_VERSION,
			true
		);

		wp_localize_script(
			'dfp-admin',
			'dfpSettings',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'dfp_admin' ),
				'saveNonce'       => wp_create_nonce( 'dfp_save_field_group' ),
				'toggleNonce'     => wp_create_nonce( 'dfp_toggle_group' ),
				'fieldTypes'      => DFP_Fields::get_types_grouped(),
				'locationParams'  => DFP_Location::get_rule_params(),
				'i18n'            => array(
					'confirmDelete'   => __( 'Are you sure you want to delete this field group?', 'dynamic-fields-pro' ),
					'confirmDuplicate'=> __( 'Duplicate this field group?', 'dynamic-fields-pro' ),
					'saved'           => __( 'Saved!', 'dynamic-fields-pro' ),
					'saving'          => __( 'Saving…', 'dynamic-fields-pro' ),
					'error'           => __( 'An error occurred. Please try again.', 'dynamic-fields-pro' ),
					'addField'        => __( '+ Add Field', 'dynamic-fields-pro' ),
					'addSubField'     => __( '+ Add Sub-Field', 'dynamic-fields-pro' ),
					'duplicateField'  => __( 'Duplicate Field', 'dynamic-fields-pro' ),
					'deleteField'     => __( 'Delete Field', 'dynamic-fields-pro' ),
					'noFields'        => __( 'No fields yet. Click "+ Add Field" to get started.', 'dynamic-fields-pro' ),
				),
			)
		);

		wp_enqueue_style( 'wp-color-picker' );

		if ( $on_dfp_page ) {
			wp_enqueue_media();
		}
	}

	// ── Field Groups list page ────────────────────────────────────────────────

	private function render_groups_list_page() {
		$groups = DFP_Field_Group::get_field_groups();
		?>
		<div class="wrap dfp-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Field Groups', 'dynamic-fields-pro' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfp-field-groups&action=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'dynamic-fields-pro' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( empty( $groups ) ) : ?>
				<div class="dfp-empty-state">
					<span class="dashicons dashicons-editor-table dfp-empty-icon"></span>
					<h2><?php esc_html_e( 'No field groups yet.', 'dynamic-fields-pro' ); ?></h2>
					<p><?php esc_html_e( 'Create a field group to start adding custom fields to your posts, pages, and other content types.', 'dynamic-fields-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfp-field-groups&action=new' ) ); ?>" class="button button-primary button-large">
						<?php esc_html_e( 'Create your first Field Group', 'dynamic-fields-pro' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped dfp-groups-table">
					<thead>
						<tr>
							<th class="column-title"><?php esc_html_e( 'Title', 'dynamic-fields-pro' ); ?></th>
							<th class="column-fields"><?php esc_html_e( 'Fields', 'dynamic-fields-pro' ); ?></th>
							<th class="column-location"><?php esc_html_e( 'Location', 'dynamic-fields-pro' ); ?></th>
							<th class="column-status"><?php esc_html_e( 'Status', 'dynamic-fields-pro' ); ?></th>
							<th class="column-order"><?php esc_html_e( 'Order', 'dynamic-fields-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups as $group_post ) : ?>
							<?php
							$group_data = DFP_Field_Group::get_field_group( $group_post->ID );
							if ( ! $group_data ) { continue; }
							$field_count    = count( isset( $group_data['fields'] ) ? $group_data['fields'] : array() );
							$settings       = isset( $group_data['settings'] ) ? $group_data['settings'] : array();
							$is_active      = ! isset( $settings['active'] ) || $settings['active'];
							$menu_order     = isset( $settings['menu_order'] ) ? $settings['menu_order'] : 0;
							$location_desc  = $this->describe_location( $group_data['location'] );
							$edit_url       = admin_url( 'admin.php?page=dfp-field-groups&action=edit&group_id=' . $group_post->ID );
							$duplicate_url  = wp_nonce_url( admin_url( 'admin-ajax.php?action=dfp_duplicate_group&group_id=' . $group_post->ID ), 'dfp_duplicate_group_' . $group_post->ID );
							$export_url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=dfp_export_group&group_id=' . $group_post->ID ), 'dfp_export_group_' . $group_post->ID );
							?>
							<tr data-group-id="<?php echo absint( $group_post->ID ); ?>">
								<td class="column-title">
									<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $group_data['title'] ); ?></a></strong>
									<div class="row-actions">
										<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'dynamic-fields-pro' ); ?></a> | </span>
										<span class="duplicate"><a href="#" class="dfp-js-duplicate" data-group-id="<?php echo absint( $group_post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dfp_admin' ) ); ?>"><?php esc_html_e( 'Duplicate', 'dynamic-fields-pro' ); ?></a> | </span>
										<span class="export"><a href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export', 'dynamic-fields-pro' ); ?></a> | </span>
										<span class="delete"><a href="#" class="dfp-js-delete button-link-delete" data-group-id="<?php echo absint( $group_post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dfp_admin' ) ); ?>"><?php esc_html_e( 'Delete', 'dynamic-fields-pro' ); ?></a></span>
									</div>
								</td>
								<td class="column-fields"><?php echo absint( $field_count ); ?></td>
								<td class="column-location"><?php echo wp_kses_post( $location_desc ); ?></td>
								<td class="column-status">
									<label class="dfp-toggle-active" title="<?php esc_attr_e( 'Toggle active state', 'dynamic-fields-pro' ); ?>">
										<input type="checkbox" class="dfp-js-toggle-active" data-group-id="<?php echo absint( $group_post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dfp_admin' ) ); ?>" <?php checked( $is_active ); ?>>
										<span class="dfp-toggle-slider"></span>
									</label>
									<span class="dfp-status-label"><?php echo $is_active ? esc_html__( 'Active', 'dynamic-fields-pro' ) : esc_html__( 'Inactive', 'dynamic-fields-pro' ); ?></span>
								</td>
								<td class="column-order"><?php echo absint( $menu_order ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="dfp-import-link">
					<a href="#" id="dfp-open-import"><?php esc_html_e( 'Import Field Group (JSON)', 'dynamic-fields-pro' ); ?></a>
				</p>
				<div id="dfp-import-panel" style="display:none">
					<h3><?php esc_html_e( 'Import Field Group', 'dynamic-fields-pro' ); ?></h3>
					<textarea id="dfp-import-json" class="widefat" rows="8" placeholder="<?php esc_attr_e( 'Paste exported JSON here…', 'dynamic-fields-pro' ); ?>"></textarea>
					<button class="button button-primary" id="dfp-do-import" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dfp_admin' ) ); ?>"><?php esc_html_e( 'Import', 'dynamic-fields-pro' ); ?></button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build a short human-readable description of location rules.
	 *
	 * @param array $location
	 * @return string  HTML
	 */
	private function describe_location( $location ) {
		if ( empty( $location ) ) {
			return '<em>' . esc_html__( 'No rules', 'dynamic-fields-pro' ) . '</em>';
		}
		$parts = array();
		foreach ( $location as $group ) {
			$and_parts = array();
			foreach ( $group as $rule ) {
				$param  = isset( $rule['param'] )    ? $rule['param']    : '';
				$op     = isset( $rule['operator'] ) ? $rule['operator'] : '==';
				$value  = isset( $rule['value'] )    ? $rule['value']    : '';
				$and_parts[] = '<code>' . esc_html( $param ) . ' ' . esc_html( $op ) . ' ' . esc_html( $value ) . '</code>';
			}
			$parts[] = implode( ' <strong>&amp;</strong> ', $and_parts );
		}
		return implode( '<br><em>' . esc_html__( 'or', 'dynamic-fields-pro' ) . '</em><br>', $parts );
	}

	// ── Field Builder page ────────────────────────────────────────────────────

	private function render_builder_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

		$group_data = $group_id ? DFP_Field_Group::get_field_group( $group_id ) : null;

		$title    = $group_data ? $group_data['title']    : '';
		$fields   = $group_data ? $group_data['fields']   : array();
		$location = $group_data ? $group_data['location'] : array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) );
		$settings = $group_data ? $group_data['settings'] : array( 'position' => 'normal', 'style' => 'default', 'label_placement' => 'top', 'instruction_placement' => 'label', 'menu_order' => 0, 'active' => true );

		$back_url = admin_url( 'admin.php?page=dfp-field-groups' );

		echo '<div class="wrap dfp-wrap dfp-builder-wrap">';
		echo '<a href="' . esc_url( $back_url ) . '" class="dfp-back-link">&larr; ' . esc_html__( 'Back to Field Groups', 'dynamic-fields-pro' ) . '</a>';
		echo '<h1>' . ( $group_id ? esc_html__( 'Edit Field Group', 'dynamic-fields-pro' ) : esc_html__( 'Add New Field Group', 'dynamic-fields-pro' ) ) . '</h1>';

		echo '<div class="dfp-builder-top">';
		echo '<input type="text" id="dfp-group-title" class="dfp-group-title-input" placeholder="' . esc_attr__( 'Field Group Title', 'dynamic-fields-pro' ) . '" value="' . esc_attr( $title ) . '">';
		echo '<button id="dfp-save-group" class="button button-primary button-large" data-group-id="' . absint( $group_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'dfp_save_field_group' ) ) . '">' . esc_html__( 'Save Field Group', 'dynamic-fields-pro' ) . '</button>';
		echo '<span class="dfp-save-status" id="dfp-save-status"></span>';
		echo '</div>';

		// Tabs
		echo '<div class="dfp-builder-tabs">';
		echo '<nav class="dfp-tabs-nav"><ul>';
		foreach ( array( 'fields' => __( 'Fields', 'dynamic-fields-pro' ), 'location' => __( 'Location', 'dynamic-fields-pro' ), 'settings' => __( 'Settings', 'dynamic-fields-pro' ) ) as $tab => $label ) {
			echo '<li><a href="#dfp-tab-' . esc_attr( $tab ) . '" class="dfp-tab-link" data-tab="' . esc_attr( $tab ) . '">' . esc_html( $label ) . '</a></li>';
		}
		echo '</ul></nav>';

		// ── Fields tab ──
		echo '<div id="dfp-tab-fields" class="dfp-tab-pane dfp-tab-active">';
		$this->render_fields_tab( $fields );
		echo '</div>';

		// ── Location tab ──
		echo '<div id="dfp-tab-location" class="dfp-tab-pane" style="display:none">';
		$this->render_location_tab( $location );
		echo '</div>';

		// ── Settings tab ──
		echo '<div id="dfp-tab-settings" class="dfp-tab-pane" style="display:none">';
		$this->render_settings_tab( $settings );
		echo '</div>';

		echo '</div>'; // .dfp-builder-tabs

		// Field type picker (hidden, shown via JS)
		$this->render_field_type_picker();

		echo '</div>'; // .wrap
	}

	// ── Fields tab ───────────────────────────────────────────────────────────

	private function render_fields_tab( $fields ) {
		echo '<div class="dfp-fields-section">';
		echo '<div class="dfp-fields-header">';
		echo '<h2>' . esc_html__( 'Fields', 'dynamic-fields-pro' ) . '</h2>';
		echo '<button type="button" class="button dfp-add-field-btn" id="dfp-add-field">' . esc_html__( '+ Add Field', 'dynamic-fields-pro' ) . '</button>';
		echo '</div>';

		echo '<div id="dfp-fields-list" class="dfp-fields-list' . ( empty( $fields ) ? ' dfp-empty' : '' ) . '">';

		if ( empty( $fields ) ) {
			echo '<div class="dfp-no-fields" id="dfp-no-fields">' . esc_html__( 'No fields yet. Click "+ Add Field" to get started.', 'dynamic-fields-pro' ) . '</div>';
		}

		foreach ( $fields as $field ) {
			$this->render_field_row( $field );
		}

		echo '</div>'; // #dfp-fields-list
		echo '</div>'; // .dfp-fields-section
	}

	/**
	 * Render a single field row in the builder (collapsed header + settings panel).
	 *
	 * @param array $field
	 */
	public function render_field_row( $field ) {
		$type_label = '';
		$type_obj   = DFP_Fields::get_field_type( isset( $field['type'] ) ? $field['type'] : 'text' );
		if ( $type_obj ) {
			$type_label = $type_obj->get_label();
		}

		$key  = isset( $field['key'] )  ? $field['key']  : DFP_Field_Group::generate_key();
		$name = isset( $field['name'] ) ? $field['name'] : '';
		$label= isset( $field['label'])? $field['label'] : '';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		?>
		<div class="dfp-field-row" data-key="<?php echo esc_attr( $key ); ?>" data-type="<?php echo esc_attr( $type ); ?>" draggable="true">
			<div class="dfp-field-row-header">
				<span class="dfp-field-handle dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'dynamic-fields-pro' ); ?>"></span>
				<span class="dfp-field-label-display"><?php echo esc_html( $label ?: __( '(no label)', 'dynamic-fields-pro' ) ); ?></span>
				<span class="dfp-field-name-display dfp-muted"><?php echo esc_html( $name ); ?></span>
				<span class="dfp-field-type-badge"><?php echo esc_html( $type_label ); ?></span>
				<span class="dfp-field-row-actions">
					<button type="button" class="dfp-toggle-field-settings button-link" title="<?php esc_attr_e( 'Edit', 'dynamic-fields-pro' ); ?>">&#9650;</button>
					<button type="button" class="dfp-duplicate-field button-link" title="<?php esc_attr_e( 'Duplicate Field', 'dynamic-fields-pro' ); ?>">&#9633;</button>
					<button type="button" class="dfp-delete-field button-link-delete" title="<?php esc_attr_e( 'Delete Field', 'dynamic-fields-pro' ); ?>">&times;</button>
				</span>
			</div>

			<div class="dfp-field-settings" style="display:none">
				<table class="dfp-field-meta-table">
					<tr>
						<th><label><?php esc_html_e( 'Field Label', 'dynamic-fields-pro' ); ?></label></th>
						<td><input type="text" class="dfp-field-label widefat" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'e.g. Hero Title', 'dynamic-fields-pro' ); ?>"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Field Name', 'dynamic-fields-pro' ); ?></label></th>
						<td><input type="text" class="dfp-field-name widefat" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'e.g. hero_title', 'dynamic-fields-pro' ); ?>">
						<p class="description"><?php esc_html_e( 'Single word, no spaces, underscores allowed.', 'dynamic-fields-pro' ); ?></p></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Field Key', 'dynamic-fields-pro' ); ?></label></th>
						<td><input type="text" class="dfp-field-key" value="<?php echo esc_attr( $key ); ?>" readonly></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Field Type', 'dynamic-fields-pro' ); ?></label></th>
						<td>
							<select class="dfp-field-type-select">
								<?php foreach ( DFP_Fields::get_types_grouped() as $group_label => $types ) : ?>
									<optgroup label="<?php echo esc_attr( $group_label ); ?>">
										<?php foreach ( $types as $type_slug => $type_lbl ) : ?>
											<option value="<?php echo esc_attr( $type_slug ); ?>"<?php selected( $type, $type_slug ); ?>><?php echo esc_html( $type_lbl ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Required', 'dynamic-fields-pro' ); ?></label></th>
						<td><label><input type="checkbox" class="dfp-field-required" value="1"<?php checked( ! empty( $field['required'] ) ); ?>> <?php esc_html_e( 'Yes', 'dynamic-fields-pro' ); ?></label></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Instructions', 'dynamic-fields-pro' ); ?></label></th>
						<td><textarea class="dfp-field-instructions widefat" rows="2" placeholder="<?php esc_attr_e( 'Instructions for the content editor', 'dynamic-fields-pro' ); ?>"><?php echo esc_textarea( isset( $field['instructions'] ) ? $field['instructions'] : '' ); ?></textarea></td>
					</tr>
				</table>

				<!-- Type-specific settings panels (shown/hidden by JS based on type) -->
				<?php foreach ( DFP_Fields::get_all_types() as $type_slug => $type_lbl ) : ?>
					<?php $ft = DFP_Fields::get_field_type( $type_slug ); ?>
					<?php if ( ! $ft ) { continue; } ?>
					<div class="dfp-type-settings" data-type="<?php echo esc_attr( $type_slug ); ?>"<?php echo ( $type !== $type_slug ? ' style="display:none"' : '' ); ?>>
						<?php $ft->render_field_settings( $field ); ?>
					</div>
				<?php endforeach; ?>

				<div class="dfp-field-actions-footer">
					<a href="#" class="dfp-duplicate-field"><?php esc_html_e( 'Duplicate Field', 'dynamic-fields-pro' ); ?></a>
					&nbsp;|&nbsp;
					<a href="#" class="dfp-delete-field button-link-delete"><?php esc_html_e( 'Delete Field', 'dynamic-fields-pro' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	// ── Location tab ─────────────────────────────────────────────────────────

	private function render_location_tab( $location ) {
		if ( empty( $location ) ) {
			$location = array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) );
		}

		$params = DFP_Location::get_rule_params();

		echo '<div class="dfp-location-wrap" id="dfp-location-wrap">';
		echo '<div class="dfp-location-header"><h2>' . esc_html__( 'Location', 'dynamic-fields-pro' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Show this field group if', 'dynamic-fields-pro' ) . '</p></div>';

		foreach ( $location as $gi => $rule_group ) :
			echo '<div class="dfp-location-group" data-group-index="' . absint( $gi ) . '">';
			foreach ( $rule_group as $ri => $rule ) :
				$this->render_location_rule( $rule, $params, $gi, $ri );
			endforeach;
			echo '<button type="button" class="button dfp-add-rule" data-group="' . absint( $gi ) . '">' . esc_html__( '+ Add Rule', 'dynamic-fields-pro' ) . '</button>';
			echo '</div>'; // .dfp-location-group

			if ( $gi < count( $location ) - 1 ) {
				echo '<div class="dfp-or-separator"><span>' . esc_html__( 'or', 'dynamic-fields-pro' ) . '</span></div>';
			}
		endforeach;

		echo '<div class="dfp-or-separator"><span>' . esc_html__( 'or', 'dynamic-fields-pro' ) . '</span></div>';
		echo '<button type="button" class="button dfp-add-rule-group">' . esc_html__( '+ Add Rule Group', 'dynamic-fields-pro' ) . '</button>';

		// Hidden templates for JS cloning.
		echo '<div class="dfp-location-templates" style="display:none">';
		echo '<div class="dfp-location-group-template dfp-location-group">';
		$this->render_location_rule( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ), $params, '__GROUP__', 0 );
		echo '<button type="button" class="button dfp-add-rule" data-group="__GROUP__">' . esc_html__( '+ Add Rule', 'dynamic-fields-pro' ) . '</button>';
		echo '</div>';
		echo '<div class="dfp-location-rule-template">';
		$this->render_location_rule( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ), $params, '__GROUP__', '__RULE__' );
		echo '</div>';
		echo '</div>';

		echo '</div>'; // .dfp-location-wrap
	}

	private function render_location_rule( $rule, $params, $gi, $ri ) {
		$param    = isset( $rule['param'] )    ? $rule['param']    : 'post_type';
		$operator = isset( $rule['operator'] ) ? $rule['operator'] : '==';
		$value    = isset( $rule['value'] )    ? $rule['value']    : '';

		echo '<div class="dfp-location-rule" data-rule-index="' . esc_attr( $ri ) . '">';

		// Param select
		echo '<select class="dfp-rule-param" name="dfp_location[' . esc_attr( $gi ) . '][' . esc_attr( $ri ) . '][param]">';
		foreach ( $params as $p ) {
			echo '<option value="' . esc_attr( $p['value'] ) . '"' . selected( $param, $p['value'], false ) . '>' . esc_html( $p['label'] ) . '</option>';
		}
		echo '</select>';

		// Operator select
		echo '<select class="dfp-rule-operator" name="dfp_location[' . esc_attr( $gi ) . '][' . esc_attr( $ri ) . '][operator]">';
		echo '<option value="=="' . selected( $operator, '==', false ) . '>' . esc_html__( '==', 'dynamic-fields-pro' ) . '</option>';
		echo '<option value="!="' . selected( $operator, '!=', false ) . '>' . esc_html__( '!=', 'dynamic-fields-pro' ) . '</option>';
		echo '</select>';

		// Value select (populated by AJAX on param change, pre-populated here)
		$values = DFP_Location::get_rule_values( $param );
		echo '<select class="dfp-rule-value" name="dfp_location[' . esc_attr( $gi ) . '][' . esc_attr( $ri ) . '][value]">';
		foreach ( $values as $v ) {
			echo '<option value="' . esc_attr( $v['value'] ) . '"' . selected( (string) $value, (string) $v['value'], false ) . '>' . esc_html( $v['label'] ) . '</option>';
		}
		echo '</select>';

		echo '<button type="button" class="dfp-remove-rule button-link-delete" title="' . esc_attr__( 'Remove rule', 'dynamic-fields-pro' ) . '">&times;</button>';
		echo '</div>';
	}

	// ── Settings tab ─────────────────────────────────────────────────────────

	private function render_settings_tab( $settings ) {
		$position   = isset( $settings['position'] )             ? $settings['position']             : 'normal';
		$style      = isset( $settings['style'] )                ? $settings['style']                : 'default';
		$label_pl   = isset( $settings['label_placement'] )      ? $settings['label_placement']      : 'top';
		$inst_pl    = isset( $settings['instruction_placement'] ) ? $settings['instruction_placement'] : 'label';
		$menu_order = isset( $settings['menu_order'] )           ? $settings['menu_order']           : 0;
		$active     = ! isset( $settings['active'] ) || $settings['active'];
		$desc       = isset( $settings['description'] )          ? $settings['description']          : '';
		?>
		<div class="dfp-settings-section">
			<h2><?php esc_html_e( 'Settings', 'dynamic-fields-pro' ); ?></h2>
			<table class="form-table dfp-group-settings-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'dynamic-fields-pro' ); ?></th>
					<td>
						<select id="dfp-setting-position" name="dfp_settings[position]">
							<option value="normal" <?php selected( $position, 'normal' ); ?>><?php esc_html_e( 'Normal (after content)', 'dynamic-fields-pro' ); ?></option>
							<option value="side"   <?php selected( $position, 'side' ); ?>><?php esc_html_e( 'Side', 'dynamic-fields-pro' ); ?></option>
							<option value="acf_after_title" <?php selected( $position, 'acf_after_title' ); ?>><?php esc_html_e( 'After Title', 'dynamic-fields-pro' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Style', 'dynamic-fields-pro' ); ?></th>
					<td>
						<select id="dfp-setting-style" name="dfp_settings[style]">
							<option value="default"  <?php selected( $style, 'default' ); ?>><?php esc_html_e( 'WP MetaBox', 'dynamic-fields-pro' ); ?></option>
							<option value="seamless" <?php selected( $style, 'seamless' ); ?>><?php esc_html_e( 'Seamless (no box)', 'dynamic-fields-pro' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Label Position', 'dynamic-fields-pro' ); ?></th>
					<td>
						<select id="dfp-setting-label-placement" name="dfp_settings[label_placement]">
							<option value="top"  <?php selected( $label_pl, 'top' ); ?>><?php esc_html_e( 'Top', 'dynamic-fields-pro' ); ?></option>
							<option value="left" <?php selected( $label_pl, 'left' ); ?>><?php esc_html_e( 'Left', 'dynamic-fields-pro' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Instruction Position', 'dynamic-fields-pro' ); ?></th>
					<td>
						<select id="dfp-setting-instruction-placement" name="dfp_settings[instruction_placement]">
							<option value="label" <?php selected( $inst_pl, 'label' ); ?>><?php esc_html_e( 'Below Label', 'dynamic-fields-pro' ); ?></option>
							<option value="field" <?php selected( $inst_pl, 'field' ); ?>><?php esc_html_e( 'Below Field', 'dynamic-fields-pro' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Menu Order', 'dynamic-fields-pro' ); ?></th>
					<td><input type="number" id="dfp-setting-menu-order" name="dfp_settings[menu_order]" value="<?php echo absint( $menu_order ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Active', 'dynamic-fields-pro' ); ?></th>
					<td><label><input type="checkbox" id="dfp-setting-active" name="dfp_settings[active]" value="1"<?php checked( $active ); ?>> <?php esc_html_e( 'Yes', 'dynamic-fields-pro' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Description', 'dynamic-fields-pro' ); ?></th>
					<td><textarea id="dfp-setting-description" name="dfp_settings[description]" class="widefat" rows="3"><?php echo esc_textarea( $desc ); ?></textarea></td>
				</tr>
			</table>
		</div>
		<?php
	}

	// ── Field type picker ─────────────────────────────────────────────────────

	private function render_field_type_picker() {
		echo '<div id="dfp-field-type-picker" class="dfp-type-picker" style="display:none">';
		echo '<div class="dfp-type-picker-inner">';
		echo '<h3>' . esc_html__( 'Select Field Type', 'dynamic-fields-pro' ) . '</h3>';
		echo '<button type="button" class="dfp-type-picker-close button-link">&times;</button>';
		echo '<div class="dfp-type-groups">';
		foreach ( DFP_Fields::get_types_grouped() as $group_label => $types ) {
			echo '<div class="dfp-type-group">';
			echo '<h4 class="dfp-type-group-label">' . esc_html( $group_label ) . '</h4>';
			foreach ( $types as $slug => $label ) {
				echo '<button type="button" class="dfp-type-pick-btn" data-type="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</button>';
			}
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
}
