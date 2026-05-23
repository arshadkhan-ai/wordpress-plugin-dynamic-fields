/* global dfpSettings, wp, jQuery */
( function ( $ ) {
	'use strict';

	var DFP_Admin = {

		fieldIndex: 0,
		dragSrcEl: null,

		// ── Boot ────────────────────────────────────────────────────────────

		init: function () {
			this.initFieldBuilder();
			this.initTabs();
			this.initFieldTypeSwitcher();
			this.initSortableFields();
			this.initLocationRules();
			this.initLocationValueUpdate();
			this.initSave();
			this.initGroupListActions();
			this.initImageFields();
			this.initGalleryFields();
			this.initFileFields();
			this.initColorPickers();
			this.initDatePickers();
			this.initTrueFalseToggle();
			this.initRelationshipFields();
			this.initImportPanel();
			this.initToggleAllCheckbox();
		},

		// ── Tab switching ────────────────────────────────────────────────────

		initTabs: function () {
			var $nav   = $( '.dfp-tabs-nav' );
			var $panes = $( '.dfp-tab-pane' );

			if ( ! $nav.length ) { return; }

			$nav.on( 'click', '.dfp-tab-link', function ( e ) {
				e.preventDefault();
				var tab = $( this ).data( 'tab' );
				$nav.find( '.dfp-tab-link' ).removeClass( 'dfp-active' );
				$( this ).addClass( 'dfp-active' );
				$panes.hide();
				$( '#dfp-tab-' + tab ).show();
			} );

			// Activate first tab.
			$nav.find( '.dfp-tab-link' ).first().trigger( 'click' );
		},

		// ── Field builder ────────────────────────────────────────────────────

		initFieldBuilder: function () {
			var self = this;

			// Open type picker.
			$( '#dfp-add-field' ).on( 'click', function () {
				self.openFieldTypePicker();
			} );

			// Handle type pick.
			$( '#dfp-field-type-picker' ).on( 'click', '.dfp-type-pick-btn', function () {
				var type = $( this ).data( 'type' );
				$( '#dfp-field-type-picker' ).hide();
				self.addField( type );
			} );

			// Close picker on overlay click or close button.
			$( '#dfp-field-type-picker' ).on( 'click', function ( e ) {
				if ( $( e.target ).is( '#dfp-field-type-picker' ) ) {
					$( '#dfp-field-type-picker' ).hide();
				}
			} );
			$( '#dfp-field-type-picker' ).on( 'click', '.dfp-type-picker-close', function () {
				$( '#dfp-field-type-picker' ).hide();
			} );

			// Toggle settings.
			$( '#dfp-fields-list' ).on( 'click', '.dfp-toggle-field-settings', function () {
				var $row = $( this ).closest( '.dfp-field-row' );
				$row.toggleClass( 'settings-open' );
				$row.find( '.dfp-field-settings' ).toggle();
			} );

			// Click on label display also toggles settings.
			$( '#dfp-fields-list' ).on( 'click', '.dfp-field-label-display', function () {
				var $row = $( this ).closest( '.dfp-field-row' );
				$row.toggleClass( 'settings-open' );
				$row.find( '.dfp-field-settings' ).toggle();
			} );

			// Live update label display.
			$( '#dfp-fields-list' ).on( 'input', '.dfp-field-label', function () {
				var $row = $( this ).closest( '.dfp-field-row' );
				var val  = $( this ).val();
				$row.find( '.dfp-field-label-display' ).text( val || '(no label)' );
				// Auto-generate name from label if name is still untouched.
				if ( $row.data( 'name-auto' ) !== false ) {
					var slug = val.toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' );
					$row.find( '.dfp-field-name' ).val( slug );
					$row.find( '.dfp-field-name-display' ).text( slug );
				}
			} );

			// Manual name edit — stop auto-generate.
			$( '#dfp-fields-list' ).on( 'input', '.dfp-field-name', function () {
				var $row = $( this ).closest( '.dfp-field-row' );
				$row.data( 'name-auto', false );
				var val = $( this ).val();
				$row.find( '.dfp-field-name-display' ).text( val );
			} );

			// Delete field.
			$( '#dfp-fields-list' ).on( 'click', '.dfp-delete-field', function ( e ) {
				e.preventDefault();
				if ( ! confirm( 'Delete this field?' ) ) { return; }
				$( this ).closest( '.dfp-field-row' ).remove();
				if ( ! $( '#dfp-fields-list .dfp-field-row' ).length ) {
					$( '#dfp-no-fields' ).show();
				}
			} );

			// Duplicate field.
			$( '#dfp-fields-list' ).on( 'click', '.dfp-duplicate-field', function ( e ) {
				e.preventDefault();
				var $row  = $( this ).closest( '.dfp-field-row' );
				var $copy = $row.clone( true );
				var newKey = 'field_' + self.generateKey();
				$copy.attr( 'data-key', newKey );
				$copy.find( '.dfp-field-key' ).val( newKey );
				$copy.find( '.dfp-field-settings' ).hide();
				$copy.removeClass( 'settings-open' );
				$row.after( $copy );
			} );

			// Sub-field add / remove in repeater settings.
			$( '#dfp-fields-list' ).on( 'click', '.dfp-add-sub-field', function () {
				var $wrap = $( this ).closest( '.dfp-sub-fields-builder' );
				self.addSubFieldRow( $wrap );
			} );
			$( '#dfp-fields-list' ).on( 'click', '.dfp-remove-sub-field', function () {
				$( this ).closest( '.dfp-sub-field-row' ).remove();
			} );
		},

		openFieldTypePicker: function () {
			$( '#dfp-field-type-picker' ).show();
		},

		addField: function ( type ) {
			var self   = this;
			var key    = 'field_' + self.generateKey();
			var $list  = $( '#dfp-fields-list' );
			var $empty = $( '#dfp-no-fields' );

			// Build row via AJAX (ask server to render the HTML for this field type).
			var emptyField = {
				key:  key,
				name: '',
				label:'',
				type: type,
				instructions: '',
				required: 0
			};

			$.ajax( {
				url:  dfpSettings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dfp_render_field_row',
					nonce:  dfpSettings.nonce,
					field:  JSON.stringify( emptyField )
				},
				success: function ( response ) {
					if ( response.success ) {
						var $row = $( response.data.html );
						$row.data( 'name-auto', true );
						$list.append( $row );
						$empty.hide();
						// Open settings immediately.
						$row.find( '.dfp-field-settings' ).show();
						$row.addClass( 'settings-open' );
						self.initColorPickers( $row );
					} else {
						// Fallback: client-side minimal row.
						self.appendMinimalRow( type, key, $list );
						$empty.hide();
					}
				},
				error: function () {
					self.appendMinimalRow( type, key, $list );
					$empty.hide();
				}
			} );
		},

		appendMinimalRow: function ( type, key, $list ) {
			var label = ( dfpSettings.fieldTypes && this.flatTypes( dfpSettings.fieldTypes )[ type ] ) || type;
			var $row  = $( '<div class="dfp-field-row settings-open" data-key="' + key + '" data-type="' + type + '" draggable="true">' +
				'<div class="dfp-field-row-header">' +
				'<span class="dfp-field-handle dashicons dashicons-move"></span>' +
				'<span class="dfp-field-label-display">(no label)</span>' +
				'<span class="dfp-field-name-display dfp-muted"></span>' +
				'<span class="dfp-field-type-badge">' + label + '</span>' +
				'<span class="dfp-field-row-actions">' +
				'<button type="button" class="dfp-toggle-field-settings button-link">&#9650;</button>' +
				'<button type="button" class="dfp-duplicate-field button-link">&#9633;</button>' +
				'<button type="button" class="dfp-delete-field button-link-delete">&times;</button>' +
				'</span></div>' +
				'<div class="dfp-field-settings">' +
				'<table class="dfp-field-meta-table"><tr><th>Label</th><td><input type="text" class="dfp-field-label widefat" value="" placeholder="e.g. Hero Title"></td></tr>' +
				'<tr><th>Name</th><td><input type="text" class="dfp-field-name widefat" value="" placeholder="e.g. hero_title"></td></tr>' +
				'<tr><th>Key</th><td><input type="text" class="dfp-field-key" value="' + key + '" readonly></td></tr>' +
				'<tr><th>Type</th><td><span>' + label + '</span><input type="hidden" class="dfp-field-type-hidden" value="' + type + '"></td></tr>' +
				'<tr><th>Required</th><td><label><input type="checkbox" class="dfp-field-required" value="1"> Yes</label></td></tr>' +
				'<tr><th>Instructions</th><td><textarea class="dfp-field-instructions widefat" rows="2"></textarea></td></tr>' +
				'</table></div></div>' );
			$row.data( 'name-auto', true );
			$list.append( $row );
		},

		flatTypes: function ( grouped ) {
			var flat = {};
			$.each( grouped, function ( group, types ) {
				$.each( types, function ( slug, label ) {
					flat[ slug ] = label;
				} );
			} );
			return flat;
		},

		addSubFieldRow: function ( $wrap ) {
			var types = dfpSettings.fieldTypes;
			var opts  = '';
			$.each( types, function ( group, groupTypes ) {
				$.each( groupTypes, function ( slug, label ) {
					if ( slug !== 'repeater' ) {
						opts += '<option value="' + slug + '">' + label + '</option>';
					}
				} );
			} );
			var $row = $( '<div class="dfp-sub-field-row">' +
				'<span class="dfp-row-handle">&#9776;</span>' +
				'<input type="text" placeholder="Label" class="dfp-sf-label">' +
				'<input type="text" placeholder="Name" class="dfp-sf-name">' +
				'<select class="dfp-sf-type">' + opts + '</select>' +
				'<button type="button" class="button button-small dfp-remove-sub-field">&times;</button>' +
				'</div>' );

			$row.find( '.dfp-sf-label' ).on( 'input', function () {
				var slug = $( this ).val().toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' );
				$row.find( '.dfp-sf-name' ).val( slug );
			} );

			// Insert before the "Add Sub-Field" button so new rows appear above it.
			var $addBtn = $wrap.find( '.dfp-add-sub-field' );
			if ( $addBtn.length ) {
				$addBtn.before( $row );
			} else {
				$wrap.append( $row );
			}
		},

		// ── Field type switcher ──────────────────────────────────────────────

		initFieldTypeSwitcher: function () {
			$( '#dfp-fields-list' ).on( 'change', '.dfp-field-type-select', function () {
				var type    = $( this ).val();
				var $row    = $( this ).closest( '.dfp-field-row' );
				var $panels = $row.find( '.dfp-type-settings' );

				$row.attr( 'data-type', type );
				$row.find( '.dfp-field-type-badge' ).text( $( this ).find( ':selected' ).text() );
				$panels.hide();
				$row.find( '.dfp-type-settings[data-type="' + type + '"]' ).show();
			} );
		},

		// ── Sortable fields (HTML5 drag) ─────────────────────────────────────

		initSortableFields: function () {
			var self = this;
			var $list = $( '#dfp-fields-list' );

			$list.on( 'dragstart', '.dfp-field-row', function ( e ) {
				self.dragSrcEl = this;
				e.originalEvent.dataTransfer.effectAllowed = 'move';
				e.originalEvent.dataTransfer.setData( 'text/html', this.outerHTML );
				$( this ).addClass( 'dfp-dragging' );
			} );

			$list.on( 'dragend', '.dfp-field-row', function () {
				$( '.dfp-field-row' ).removeClass( 'dfp-dragging dfp-drag-over' );
			} );

			$list.on( 'dragover', '.dfp-field-row', function ( e ) {
				e.preventDefault();
				e.originalEvent.dataTransfer.dropEffect = 'move';
				$( '.dfp-field-row' ).removeClass( 'dfp-drag-over' );
				if ( this !== self.dragSrcEl ) {
					$( this ).addClass( 'dfp-drag-over' );
				}
				return false;
			} );

			$list.on( 'drop', '.dfp-field-row', function ( e ) {
				e.stopPropagation();
				if ( this !== self.dragSrcEl ) {
					$( '.dfp-field-row' ).removeClass( 'dfp-drag-over' );
					var $src  = $( self.dragSrcEl );
					var $dest = $( this );
					// Determine position relative to dest midpoint.
					var destY  = e.originalEvent.clientY;
					var rect   = this.getBoundingClientRect();
					var midY   = rect.top + rect.height / 2;
					if ( destY < midY ) {
						$dest.before( $src );
					} else {
						$dest.after( $src );
					}
				}
				return false;
			} );
		},

		// ── Location rules ───────────────────────────────────────────────────

		initLocationRules: function () {
			var self = this;
			var $wrap = $( '#dfp-location-wrap' );

			if ( ! $wrap.length ) { return; }

			// Add Rule (within existing group)
			$wrap.on( 'click', '.dfp-add-rule', function () {
				var $group    = $( this ).closest( '.dfp-location-group' );
				var groupIdx  = $group.data( 'group-index' );
				var ruleCount = $group.find( '.dfp-location-rule' ).length;
				var $tmpl     = $( '.dfp-location-rule-template .dfp-location-rule' ).clone();

				// Update name attributes with correct indices.
				$tmpl.find( '[name]' ).each( function () {
					var name = $( this ).attr( 'name' )
						.replace( '__GROUP__', groupIdx )
						.replace( '__RULE__',  ruleCount );
					$( this ).attr( 'name', name );
				} );

				$( this ).before( $tmpl );
				self.refreshRuleValues( $tmpl.find( '.dfp-rule-param' ) );
			} );

			// Add Rule Group
			$wrap.on( 'click', '.dfp-add-rule-group', function () {
				var $groups   = $wrap.find( '.dfp-location-group' );
				var newGi     = $groups.length;
				var $tmpl     = $( '.dfp-location-group-template' ).clone().removeClass( 'dfp-location-group-template' );
				$tmpl.attr( 'data-group-index', newGi );
				$tmpl.find( '[name]' ).each( function () {
					var name = $( this ).attr( 'name' )
						.replace( '__GROUP__', newGi )
						.replace( '__RULE__',  0 );
					$( this ).attr( 'name', name );
				} );

				var $orDiv = $( '<div class="dfp-or-separator"><span>or</span></div>' );
				$( this ).before( $orDiv );
				$orDiv.before( $tmpl );
				self.refreshRuleValues( $tmpl.find( '.dfp-rule-param' ) );
			} );

			// Remove Rule
			$wrap.on( 'click', '.dfp-remove-rule', function () {
				var $group = $( this ).closest( '.dfp-location-group' );
				$( this ).closest( '.dfp-location-rule' ).remove();
				if ( ! $group.find( '.dfp-location-rule' ).length ) {
					// Remove entire group and preceding OR separator.
					$group.prev( '.dfp-or-separator' ).remove();
					$group.remove();
				}
			} );

			// Re-index names on any structural change.
			$wrap.on( 'click', '.dfp-add-rule, .dfp-add-rule-group, .dfp-remove-rule', function () {
				self.reindexLocationRules();
			} );
		},

		reindexLocationRules: function () {
			$( '#dfp-location-wrap .dfp-location-group' ).each( function ( gi ) {
				$( this ).attr( 'data-group-index', gi ).data( 'group-index', gi );
				$( this ).find( '.dfp-location-rule' ).each( function ( ri ) {
					$( this ).find( '[name]' ).each( function () {
						var name = $( this ).attr( 'name' ).replace( /\[\d+\]\[(\d+)\]/g, function ( match, ri2 ) {
							return '[' + gi + '][' + ri + ']';
						} );
						$( this ).attr( 'name', 'dfp_location[' + gi + '][' + ri + '][' + ( $( this ).attr( 'name' ).split( '][' ).pop().replace( ']', '' ) ) + ']' );
					} );
				} );
			} );
		},

		// ── Location value AJAX update ───────────────────────────────────────

		initLocationValueUpdate: function () {
			var self = this;
			$( '#dfp-location-wrap' ).on( 'change', '.dfp-rule-param', function () {
				self.refreshRuleValues( $( this ) );
			} );
		},

		refreshRuleValues: function ( $paramSelect ) {
			var param     = $paramSelect.val();
			var $rule     = $paramSelect.closest( '.dfp-location-rule' );
			var $valSel   = $rule.find( '.dfp-rule-value' );
			var nameParts = $valSel.attr( 'name' );

			$valSel.prop( 'disabled', true ).html( '<option>' + 'Loading…' + '</option>' );

			$.ajax( {
				url:  dfpSettings.ajaxUrl,
				type: 'GET',
				data: { action: 'dfp_get_rule_values', nonce: dfpSettings.nonce, param: param },
				success: function ( res ) {
					if ( res.success && res.data ) {
						var opts = '';
						$.each( res.data, function ( i, item ) {
							opts += '<option value="' + item.value + '">' + item.label + '</option>';
						} );
						$valSel.html( opts );
					}
					$valSel.prop( 'disabled', false );
				},
				error: function () {
					$valSel.html( '<option>—</option>' ).prop( 'disabled', false );
				}
			} );
		},

		// ── Save ─────────────────────────────────────────────────────────────

		initSave: function () {
			var self = this;
			$( '#dfp-save-group' ).on( 'click', function () {
				self.saveGroup();
			} );

			// Ctrl/Cmd+S shortcut.
			$( document ).on( 'keydown', function ( e ) {
				if ( ( e.ctrlKey || e.metaKey ) && e.key === 's' ) {
					e.preventDefault();
					self.saveGroup();
				}
			} );
		},

		saveGroup: function () {
			var self      = this;
			var $btn      = $( '#dfp-save-group' );
			var $status   = $( '#dfp-save-status' );
			var group_id  = $btn.data( 'group-id' );
			var nonce     = $btn.data( 'nonce' );

			$btn.prop( 'disabled', true );
			$status.text( dfpSettings.i18n.saving );

			var groupData = self.collectGroupData( group_id );

			$.ajax( {
				url:  dfpSettings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dfp_save_field_group',
					nonce:  nonce,
					group:  JSON.stringify( groupData )
				},
				success: function ( res ) {
					$btn.prop( 'disabled', false );
					if ( res.success ) {
						$status.text( dfpSettings.i18n.saved );
						// Update group ID after first save.
						if ( ! group_id && res.data.group_id ) {
							$btn.data( 'group-id', res.data.group_id );
							// Update URL without reload.
							if ( window.history && window.history.pushState ) {
								window.history.pushState( {}, '', res.data.edit_url );
							}
						}
						setTimeout( function () { $status.text( '' ); }, 3000 );
					} else {
						$status.css( 'color', '#a00' ).text( res.data && res.data.message ? res.data.message : dfpSettings.i18n.error );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false );
					$status.css( 'color', '#a00' ).text( dfpSettings.i18n.error );
				}
			} );
		},

		collectGroupData: function ( group_id ) {
			var self     = this;
			var title    = $( '#dfp-group-title' ).val();
			var fields   = self.collectFields();
			var location = self.collectLocationRules();
			var settings = self.collectSettings();

			return {
				id:       group_id || 0,
				title:    title,
				fields:   fields,
				location: location,
				settings: settings
			};
		},

		collectFields: function () {
			var fields = [];
			$( '#dfp-fields-list .dfp-field-row' ).each( function () {
				var $row  = $( this );
				var type  = $row.attr( 'data-type' );
				var key   = $row.find( '.dfp-field-key' ).val() || $row.attr( 'data-key' );
				var label = $row.find( '.dfp-field-label' ).val();
				var name  = $row.find( '.dfp-field-name' ).val();
				var req   = $row.find( '.dfp-field-required' ).is( ':checked' ) ? 1 : 0;
				var instr = $row.find( '.dfp-field-instructions' ).val();

				// Collect type-specific settings from the active settings panel.
				var typeSettings = {};
				$row.find( '.dfp-type-settings[data-type="' + type + '"] :input' ).each( function () {
					var n = $( this ).attr( 'name' ) || '';
					var v;
					if ( $( this ).is( ':checkbox' ) ) {
						v = $( this ).is( ':checked' ) ? 1 : 0;
					} else if ( $( this ).is( 'select[multiple]' ) ) {
						v = $( this ).val() || [];
					} else {
						v = $( this ).val();
					}
					// Extract the setting key from the name attribute.
					var match = n.match( /\[([^\]]+)\]$/ );
					if ( match ) {
						typeSettings[ match[1] ] = v;
					}
				} );

				// Sub-fields (for repeater).
				var subFields = [];
				$row.find( '.dfp-sub-field-row' ).each( function () {
					var $sf = $( this );
					subFields.push( {
						label: $sf.find( '.dfp-sf-label, [name*="[label]"]' ).val() || '',
						name:  $sf.find( '.dfp-sf-name, [name*="[name]"]' ).val() || '',
						type:  $sf.find( '.dfp-sf-type, [name*="[type]"]' ).val() || 'text'
					} );
				} );

				var field = $.extend( {}, typeSettings, {
					key:          key,
					label:        label,
					name:         name,
					type:         type,
					required:     req,
					instructions: instr
				} );

				if ( subFields.length ) {
					field.sub_fields = subFields;
				}

				fields.push( field );
			} );
			return fields;
		},

		collectLocationRules: function () {
			var location = [];
			$( '#dfp-location-wrap .dfp-location-group' ).each( function () {
				var group = [];
				$( this ).find( '.dfp-location-rule' ).each( function () {
					group.push( {
						param:    $( this ).find( '.dfp-rule-param' ).val(),
						operator: $( this ).find( '.dfp-rule-operator' ).val(),
						value:    $( this ).find( '.dfp-rule-value' ).val()
					} );
				} );
				if ( group.length ) {
					location.push( group );
				}
			} );
			return location;
		},

		collectSettings: function () {
			return {
				position:              $( '#dfp-setting-position' ).val()              || 'normal',
				style:                 $( '#dfp-setting-style' ).val()                 || 'default',
				label_placement:       $( '#dfp-setting-label-placement' ).val()       || 'top',
				instruction_placement: $( '#dfp-setting-instruction-placement' ).val() || 'label',
				menu_order:            parseInt( $( '#dfp-setting-menu-order' ).val(), 10 ) || 0,
				active:                $( '#dfp-setting-active' ).is( ':checked' ),
				description:           $( '#dfp-setting-description' ).val()           || ''
			};
		},

		// ── Groups list actions ──────────────────────────────────────────────

		initGroupListActions: function () {
			var self = this;

			// Delete
			$( '.dfp-js-delete' ).on( 'click', function ( e ) {
				e.preventDefault();
				if ( ! confirm( dfpSettings.i18n.confirmDelete ) ) { return; }
				var $btn     = $( this );
				var groupId  = $btn.data( 'group-id' );
				var nonce    = $btn.data( 'nonce' );

				$.post( dfpSettings.ajaxUrl, { action: 'dfp_delete_group', group_id: groupId, nonce: nonce }, function ( res ) {
					if ( res.success ) {
						$btn.closest( 'tr' ).fadeOut( 300, function () { $( this ).remove(); } );
					}
				} );
			} );

			// Duplicate
			$( '.dfp-js-duplicate' ).on( 'click', function ( e ) {
				e.preventDefault();
				if ( ! confirm( dfpSettings.i18n.confirmDuplicate ) ) { return; }
				var groupId = $( this ).data( 'group-id' );
				var nonce   = $( this ).data( 'nonce' );

				$.post( dfpSettings.ajaxUrl, { action: 'dfp_duplicate_group', group_id: groupId, nonce: nonce }, function ( res ) {
					if ( res.success ) {
						window.location.href = res.data.redirect_url;
					}
				} );
			} );

			// Toggle active
			$( '.dfp-js-toggle-active' ).on( 'change', function () {
				var groupId  = $( this ).data( 'group-id' );
				var nonce    = $( this ).data( 'nonce' );
				var active   = $( this ).is( ':checked' );
				var $label   = $( this ).closest( 'td' ).find( '.dfp-status-label' );

				$.post( dfpSettings.ajaxUrl, { action: 'dfp_toggle_group', group_id: groupId, nonce: nonce, active: active ? 1 : 0 }, function ( res ) {
					if ( res.success ) {
						$label.text( active ? 'Active' : 'Inactive' );
					}
				} );
			} );
		},

		// ── Image fields ─────────────────────────────────────────────────────

		initImageFields: function ( $context ) {
			var $ctx = $context || $( document );

			$ctx.on( 'click', '.dfp-image-select', function ( e ) {
				e.preventDefault();
				var $btn   = $( this );
				var fkey   = $btn.data( 'field' );
				var prevsz = $btn.data( 'preview-size' ) || 'medium';
				var $wrap  = $( '#' + fkey + '-wrap' );
				var $input = $wrap.find( '.dfp-image-id' );
				var $prev  = $wrap.find( '.dfp-image-preview' );
				var $rem   = $wrap.find( '.dfp-image-remove' );

				if ( typeof wp === 'undefined' || ! wp.media ) { return; }

				var frame = wp.media( {
					title:    'Select Image',
					button:   { text: 'Use this image' },
					multiple: false,
					library:  { type: 'image' }
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$input.val( attachment.id );
					var previewUrl = attachment.sizes && attachment.sizes[ prevsz ] ? attachment.sizes[ prevsz ].url : attachment.url;
					$prev.html( '<img src="' + previewUrl + '" alt="" style="max-width:300px;height:auto">' ).show();
					$btn.text( 'Change Image' );
					$rem.show();
				} );

				frame.open();
			} );

			$ctx.on( 'click', '.dfp-image-remove', function ( e ) {
				e.preventDefault();
				var fkey  = $( this ).data( 'field' );
				var $wrap = $( '#' + fkey + '-wrap' );
				$wrap.find( '.dfp-image-id' ).val( '' );
				$wrap.find( '.dfp-image-preview' ).hide().html( '' );
				$wrap.find( '.dfp-image-select' ).text( 'Add Image' );
				$( this ).hide();
			} );
		},

		// ── Color pickers ────────────────────────────────────────────────────

		initColorPickers: function ( $context ) {
			var $ctx = $context || $( document );
			$ctx.find( '.dfp-color-picker' ).each( function () {
				if ( $( this ).data( 'wpColorPicker' ) ) { return; }
				var opts = { defaultColor: $( this ).data( 'default-color' ) || '' };
				if ( $( this ).data( 'alpha-enabled' ) ) {
					opts.change = function ( event, ui ) {};
				}
				$( this ).wpColorPicker( opts );
			} );
		},

		// ── Date pickers ─────────────────────────────────────────────────────

		initDatePickers: function ( $context ) {
			var $ctx = $context || $( document );
			$ctx.find( '.dfp-date-picker-display' ).each( function () {
				var $display  = $( this );
				var $hidden   = $display.next( 'input[type="hidden"]' );
				var fmt       = $display.data( 'date-format' ) || 'dd/mm/yy';
				var retFmt    = $display.data( 'return-format' ) || 'Ymd';
				var firstDay  = parseInt( $display.data( 'first-day' ), 10 ) || 1;

				$display.datepicker( {
					dateFormat: fmt,
					firstDay:   firstDay,
					onSelect: function ( dateStr, picker ) {
						var d    = picker.selectedDay;
						var m    = picker.selectedMonth + 1;
						var y    = picker.selectedYear;
						var padded = function ( n ) { return n < 10 ? '0' + n : '' + n; };
						var stored = retFmt
							.replace( 'Y', y )
							.replace( 'y', String( y ).slice( -2 ) )
							.replace( 'm', padded( m ) )
							.replace( 'n', m )
							.replace( 'd', padded( d ) )
							.replace( 'j', d );
						$hidden.val( stored );
					}
				} );
			} );
		},

		// ── True/False toggle UI ─────────────────────────────────────────────

		initTrueFalseToggle: function () {
			$( document ).on( 'click', '.dfp-toggle-ui', function () {
				var $toggle = $( this );
				var $cb     = $toggle.find( 'input[type="checkbox"]' );
				var checked = ! $cb.is( ':checked' );
				$cb.prop( 'checked', checked );
				$toggle.toggleClass( 'dfp-toggle-on', checked );
			} );
		},

		// ── Relationship fields ──────────────────────────────────────────────

		initRelationshipFields: function ( $context ) {
			var $ctx = $context || $( document );

			// Move item from source to target on click.
			$ctx.on( 'click', '.dfp-relationship-source li', function () {
				var $li    = $( this );
				var id     = $li.data( 'id' );
				var label  = $li.text();
				var $target= $li.closest( '.dfp-relationship-cols' ).find( '.dfp-relationship-target' );
				var $wrap  = $li.closest( '.dfp-relationship-wrap' );
				var fname  = $wrap.attr( 'id' ).replace( '-wrap', '' );

				$li.hide();
				$target.append(
					$( '<li data-id="' + id + '">' + label + '<span class="dfp-rel-remove">&times;</span></li>' )
				);
				$target.after(
					$( '<input type="hidden" name="' + fname + '[]" value="' + id + '" class="dfp-rel-hidden" data-id="' + id + '">' )
				);
			} );

			// Remove from target.
			$ctx.on( 'click', '.dfp-relationship-target .dfp-rel-remove', function () {
				var $li   = $( this ).closest( 'li' );
				var id    = $li.data( 'id' );
				var $wrap = $li.closest( '.dfp-relationship-wrap' );
				// Show in source again.
				$wrap.find( '.dfp-relationship-source li[data-id="' + id + '"]' ).show();
				// Remove hidden input.
				$wrap.siblings( '.dfp-rel-hidden[data-id="' + id + '"]' ).remove();
				$li.remove();
			} );

			// Search filter.
			$ctx.on( 'input', '.dfp-rel-search', function () {
				var q     = $( this ).val().toLowerCase();
				var $list = $( this ).closest( '.dfp-relationship-available' ).find( '.dfp-relationship-source li' );
				$list.each( function () {
					$( this ).toggle( $( this ).text().toLowerCase().indexOf( q ) !== -1 );
				} );
			} );
		},

		// ── Toggle-all checkbox ──────────────────────────────────────────────

		initToggleAllCheckbox: function () {
			$( document ).on( 'change', '.dfp-toggle-all', function () {
				var target  = $( this ).data( 'target' );
				var checked = $( this ).is( ':checked' );
				$( 'input[type="checkbox"][data-group="' + target + '"]' ).prop( 'checked', checked );
			} );
		},

		// ── Import panel (groups list page) ─────────────────────────────────

		initImportPanel: function () {
			$( '#dfp-open-import' ).on( 'click', function ( e ) {
				e.preventDefault();
				$( '#dfp-import-panel' ).slideToggle();
			} );

			$( '#dfp-do-import' ).on( 'click', function () {
				var json  = $( '#dfp-import-json' ).val();
				var nonce = $( this ).data( 'nonce' );

				if ( ! json.trim() ) { return; }

				$.post( dfpSettings.ajaxUrl, { action: 'dfp_import_group', json: json, nonce: nonce }, function ( res ) {
					if ( res.success ) {
						window.location.href = res.data.redirect_url;
					} else {
						alert( res.data && res.data.message ? res.data.message : 'Import failed.' );
					}
				} );
			} );
		},

		// ── Gallery fields ───────────────────────────────────────────────────

		initGalleryFields: function ( $context ) {
			var $ctx = $context || $( document );

			// Add images button.
			$ctx.on( 'click', '.dfp-gallery-add', function ( e ) {
				e.preventDefault();
				if ( typeof wp === 'undefined' || ! wp.media ) { return; }
				var $btn  = $( this );
				var $wrap = $btn.closest( '.dfp-gallery-wrap' );
				var $grid = $wrap.find( '.dfp-gallery-grid' );
				var $count= $wrap.find( '.dfp-gallery-count' );
				var max   = parseInt( $wrap.data( 'max' ), 10 ) || 0;
				var name  = $wrap.data( 'field-name' );

				var frame = wp.media( {
					title:    'Select Gallery Images',
					button:   { text: 'Add to Gallery' },
					multiple: true,
					library:  { type: 'image' }
				} );

				// Pre-select already chosen items.
				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );
					$wrap.find( 'input.dfp-gallery-id' ).each( function () {
						var id = parseInt( $( this ).val(), 10 );
						if ( id ) {
							var attachment = wp.media.attachment( id );
							attachment.fetch();
							selection.add( attachment );
						}
					} );
				} );

				frame.on( 'select', function () {
					var attachments = frame.state().get( 'selection' ).toJSON();
					// Clear existing items.
					$grid.empty();
					attachments.forEach( function ( att ) {
						if ( max > 0 && $grid.children().length >= max ) { return; }
						var thumbUrl = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
						var $item = $(
							'<div class="dfp-gallery-item">' +
							'<img src="' + thumbUrl + '" alt="">' +
							'<input type="hidden" class="dfp-gallery-id" name="' + name + '[]" value="' + att.id + '">' +
							'<button type="button" class="dfp-gallery-item-remove" title="Remove">&times;</button>' +
							'</div>'
						);
						$grid.append( $item );
					} );
					$count.text( $grid.children().length + ' image(s)' );
				} );

				frame.open();
			} );

			// Remove single image.
			$ctx.on( 'click', '.dfp-gallery-item-remove', function ( e ) {
				e.preventDefault();
				var $item = $( this ).closest( '.dfp-gallery-item' );
				var $wrap = $item.closest( '.dfp-gallery-wrap' );
				$item.remove();
				$wrap.find( '.dfp-gallery-count' ).text( $wrap.find( '.dfp-gallery-item' ).length + ' image(s)' );
			} );

			// Clear all.
			$ctx.on( 'click', '.dfp-gallery-clear', function ( e ) {
				e.preventDefault();
				var $wrap = $( this ).closest( '.dfp-gallery-wrap' );
				$wrap.find( '.dfp-gallery-grid' ).empty();
				$wrap.find( '.dfp-gallery-count' ).text( '0 image(s)' );
			} );
		},

		// ── File fields ──────────────────────────────────────────────────────

		initFileFields: function ( $context ) {
			var $ctx = $context || $( document );

			$ctx.on( 'click', '.dfp-file-select', function ( e ) {
				e.preventDefault();
				if ( typeof wp === 'undefined' || ! wp.media ) { return; }
				var $btn      = $( this );
				var $wrap     = $btn.closest( '.dfp-file-wrap' );
				var $idInp    = $wrap.find( '.dfp-file-id' );
				var $urlInp   = $wrap.find( '.dfp-file-url' );
				var $nameSpan = $wrap.find( '.dfp-file-name' );
				var $sizeSpan = $wrap.find( '.dfp-file-size' );
				var $info     = $wrap.find( '.dfp-file-info' );
				var $rem      = $wrap.find( '.dfp-file-remove' );
				var mimeTypes = $btn.data( 'mime-types' ) || '';

				var frameArgs = {
					title:    'Select File',
					button:   { text: 'Use this file' },
					multiple: false
				};
				if ( mimeTypes ) {
					frameArgs.library = { type: mimeTypes.split( ',' ) };
				}

				var frame = wp.media( frameArgs );

				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					$idInp.val( att.id );
					if ( $urlInp.length ) { $urlInp.val( att.url ); }
					$nameSpan.text( att.filename || att.title || 'file' );
					var sizeKb = att.filesizeHumanReadable || ( att.filesizeInBytes ? Math.round( att.filesizeInBytes / 1024 ) + ' KB' : '' );
					$sizeSpan.text( sizeKb ? '(' + sizeKb + ')' : '' );
					$info.show();
					$rem.show();
					$btn.text( 'Change File' );
				} );

				frame.open();
			} );

			$ctx.on( 'click', '.dfp-file-remove', function ( e ) {
				e.preventDefault();
				var $wrap = $( this ).closest( '.dfp-file-wrap' );
				$wrap.find( '.dfp-file-id' ).val( '' );
				$wrap.find( '.dfp-file-url' ).val( '' );
				$wrap.find( '.dfp-file-name' ).text( '' );
				$wrap.find( '.dfp-file-size' ).text( '' );
				$wrap.find( '.dfp-file-info' ).hide();
				$( this ).hide();
				$wrap.find( '.dfp-file-select' ).text( 'Select File' );
			} );
		},

		// ── Utility ──────────────────────────────────────────────────────────

		generateKey: function () {
			var hex = '';
			for ( var i = 0; i < 16; i++ ) {
				hex += Math.floor( Math.random() * 16 ).toString( 16 );
			}
			return hex;
		}
	};

	$( document ).ready( function () {
		DFP_Admin.init();
	} );

} )( jQuery );
