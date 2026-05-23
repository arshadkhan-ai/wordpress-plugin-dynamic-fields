/* global jQuery */
/**
 * Dynamic Fields Pro — Repeater / Flexible Content handler.
 *
 * Works on both:
 *  - Admin meta boxes (data entry on post-edit screen)
 *  - Field Builder sub-field rows
 */
( function ( $ ) {
	'use strict';

	var dfpRepeater = {

		// ── Boot ────────────────────────────────────────────────────────────

		init: function () {
			var self = this;

			$( '.dfp-repeater-wrap' ).each( function () {
				self.initRepeater( $( this ) );
			} );

			// Listen for future repeaters added via flexible content.
			$( document ).on( 'dfp/repeater/init', function ( e, repeaterWrap ) {
				self.initRepeater( $( repeaterWrap ) );
			} );
		},

		initRepeater: function ( $wrap ) {
			var self = this;

			// Add row.
			$wrap.on( 'click', '.dfp-add-row', function ( e ) {
				e.preventDefault();
				self.addRow( $wrap );
			} );

			// Delete row.
			$wrap.on( 'click', '.dfp-row-delete', function ( e ) {
				e.preventDefault();
				self.deleteRow( $( this ).closest( '.dfp-row' ), $wrap );
			} );

			// Collapse/expand.
			$wrap.on( 'click', '.dfp-row-collapse', function ( e ) {
				e.preventDefault();
				self.collapseRow( $( this ).closest( '.dfp-row' ) );
			} );

			// Click on header to toggle.
			$wrap.on( 'click', '.dfp-row-header', function ( e ) {
				if ( $( e.target ).is( 'button, .dfp-row-handle' ) ) { return; }
				self.collapseRow( $( this ).closest( '.dfp-row' ) );
			} );

			// Update row title when input changes.
			$wrap.on( 'change input', '.dfp-row-fields input, .dfp-row-fields textarea, .dfp-row-fields select', function () {
				self.updateRowTitle( $( this ).closest( '.dfp-row' ) );
			} );

			// Enforce min/max on add.
			self.enforceMinMax( $wrap );

			// Init sortable.
			self.initSortable( $wrap );
		},

		// ── Add Row ─────────────────────────────────────────────────────────

		addRow: function ( $wrap ) {
			var self      = this;
			var $clone    = $wrap.find( '.dfp-row-clone' );
			var $list     = $wrap.find( '.dfp-rows-list' );
			var $countInp = $wrap.find( '.dfp-row-count' );
			var max       = parseInt( $wrap.data( 'max' ), 10 ) || 0;
			var current   = $list.find( '.dfp-row' ).length;

			if ( max > 0 && current >= max ) {
				return;
			}

			// Clone the template row.
			var $newRow = $clone.find( '.dfp-row' ).clone();
			var rowIndex = current; // 0-based

			// Update all [name] attributes: replace [clone] with [rowIndex].
			$newRow.find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' ).replace( /\[clone\]/g, '[' + rowIndex + ']' );
				$( this ).attr( 'name', name );
			} );

			// Update IDs/fors similarly.
			$newRow.find( '[id]' ).each( function () {
				var id = $( this ).attr( 'id' ).replace( /clone/g, rowIndex );
				$( this ).attr( 'id', id );
			} );
			$newRow.find( '[for]' ).each( function () {
				var f = $( this ).attr( 'for' ).replace( /clone/g, rowIndex );
				$( this ).attr( 'for', f );
			} );

			// Set row title.
			$newRow.find( '.dfp-row-title' ).text( self.rowLabel( $wrap, rowIndex ) );
			$newRow.attr( 'data-index', rowIndex );
			$newRow.removeClass( 'dfp-row-collapsed' );

			// Animate in.
			$newRow.css( 'opacity', 0 );
			$list.append( $newRow );
			$newRow.animate( { opacity: 1 }, 200 );

			// Update count.
			$countInp.val( rowIndex + 1 );

			// Init any color pickers / date pickers / media fields in new row.
			if ( typeof window.DFP_Admin !== 'undefined' ) {
				window.DFP_Admin.initColorPickers( $newRow );
				window.DFP_Admin.initDatePickers( $newRow );
				window.DFP_Admin.initImageFields( $newRow );
				window.DFP_Admin.initGalleryFields( $newRow );
				window.DFP_Admin.initFileFields( $newRow );
			}

			self.enforceMinMax( $wrap );

			// Fire event.
			$wrap[ 0 ].dispatchEvent( new CustomEvent( 'dfp/repeater/add_row', {
				bubbles: true,
				detail:  { row: $newRow[ 0 ], repeaterWrap: $wrap[ 0 ] }
			} ) );

			return $newRow;
		},

		// ── Delete Row ──────────────────────────────────────────────────────

		deleteRow: function ( $row, $wrap ) {
			var self  = this;
			var $list = $wrap.find( '.dfp-rows-list' );
			var min   = parseInt( $wrap.data( 'min' ), 10 ) || 0;
			var count = $list.find( '.dfp-row' ).length;

			if ( min > 0 && count <= min ) {
				return;
			}

			// Fade out then remove.
			$row.animate( { opacity: 0 }, 200, function () {
				$row.remove();
				self.reindexRows( $wrap );
				self.enforceMinMax( $wrap );
			} );
		},

		// ── Re-index Rows ───────────────────────────────────────────────────

		reindexRows: function ( $wrap ) {
			var self = this;
			var $list = $wrap.find( '.dfp-rows-list' );
			var baseName = $wrap.data( 'base-name' );

			$list.find( '.dfp-row' ).each( function ( i ) {
				var $row = $( this );
				$row.attr( 'data-index', i );
				$row.find( '.dfp-row-title' ).text( self.rowLabel( $wrap, i ) );

				$row.find( '[name]' ).each( function () {
					// Replace the index segment: baseName[OLD_INDEX][subfield] -> baseName[i][subfield]
					var name = $( this ).attr( 'name' );
					// Pattern: base_name[N][anything]
					var escaped = baseName.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
					var re      = new RegExp( '^(' + escaped + ')\\[\\d+\\]' );
					name = name.replace( re, '$1[' + i + ']' );
					$( this ).attr( 'name', name );
				} );

				$row.find( '[id]' ).each( function () {
					var id  = $( this ).attr( 'id' );
					var escaped = baseName.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
					var re  = new RegExp( '(' + escaped + ')_(\\d+)' );
					$( this ).attr( 'id', id.replace( re, '$1_' + i ) );
				} );
			} );

			$wrap.find( '.dfp-row-count' ).val( $list.find( '.dfp-row' ).length );
		},

		rowLabel: function ( $wrap, index ) {
			var label = $wrap.data( 'row-label' ) || 'Row';
			return label + ' ' + ( index + 1 );
		},

		// ── Sortable (HTML5 drag) ────────────────────────────────────────────

		initSortable: function ( $wrap ) {
			var self     = this;
			var $list    = $wrap.find( '.dfp-rows-list' );
			var dragSrc  = null;

			$list.on( 'dragstart', '.dfp-row', function ( e ) {
				dragSrc = this;
				e.originalEvent.dataTransfer.effectAllowed = 'move';
				e.originalEvent.dataTransfer.setData( 'text/html', this.outerHTML );
				$( this ).addClass( 'dfp-row-dragging' );
			} );

			$list.on( 'dragend', '.dfp-row', function () {
				$( '.dfp-row' ).removeClass( 'dfp-row-dragging dfp-row-drag-over' );
				dragSrc = null;
			} );

			$list.on( 'dragover', '.dfp-row', function ( e ) {
				e.preventDefault();
				e.originalEvent.dataTransfer.dropEffect = 'move';
				$( '.dfp-row' ).removeClass( 'dfp-row-drag-over' );
				if ( this !== dragSrc ) {
					$( this ).addClass( 'dfp-row-drag-over' );
				}
				return false;
			} );

			$list.on( 'drop', '.dfp-row', function ( e ) {
				e.stopPropagation();
				if ( dragSrc && this !== dragSrc ) {
					$( '.dfp-row' ).removeClass( 'dfp-row-drag-over' );
					var $src  = $( dragSrc );
					var $dest = $( this );
					var destY  = e.originalEvent.clientY;
					var rect   = this.getBoundingClientRect();
					var midY   = rect.top + rect.height / 2;
					if ( destY < midY ) {
						$dest.before( $src );
					} else {
						$dest.after( $src );
					}
					self.reindexRows( $wrap );
				}
				return false;
			} );

			// Make rows draggable.
			$list.on( 'mousedown', '.dfp-row-handle', function () {
				$( this ).closest( '.dfp-row' ).attr( 'draggable', 'true' );
			} );
			$list.on( 'mouseup', '.dfp-row', function () {
				$( this ).attr( 'draggable', 'false' );
			} );
		},

		// ── Collapse / Expand ────────────────────────────────────────────────

		collapseRow: function ( $row ) {
			var isCollapsed = $row.hasClass( 'dfp-row-collapsed' );
			$row.toggleClass( 'dfp-row-collapsed', ! isCollapsed );
			$row.find( '.dfp-row-fields' ).toggle( isCollapsed );
			if ( ! isCollapsed ) {
				this.updateRowTitle( $row );
			}
		},

		// ── Row title summary ─────────────────────────────────────────────────

		updateRowTitle: function ( $row ) {
			var $firstInput = $row.find( '.dfp-row-fields input[type="text"], .dfp-row-fields input[type="email"], .dfp-row-fields input[type="url"], .dfp-row-fields select, .dfp-row-fields textarea' ).first();
			if ( $firstInput.length ) {
				var val = $firstInput.val();
				if ( val && val.trim() ) {
					$row.find( '.dfp-row-title' ).text( val.trim().substring( 0, 60 ) );
				}
			}
		},

		// ── Min / Max enforcement ────────────────────────────────────────────

		enforceMinMax: function ( $wrap ) {
			var $list   = $wrap.find( '.dfp-rows-list' );
			var count   = $list.find( '.dfp-row' ).length;
			var min     = parseInt( $wrap.data( 'min' ), 10 ) || 0;
			var max     = parseInt( $wrap.data( 'max' ), 10 ) || 0;
			var $addBtn = $wrap.find( '.dfp-add-row' );

			// Disable add button if max reached.
			if ( max > 0 && count >= max ) {
				$addBtn.prop( 'disabled', true ).attr( 'title', 'Maximum ' + max + ' rows reached.' );
			} else {
				$addBtn.prop( 'disabled', false ).removeAttr( 'title' );
			}

			// Hide delete buttons if at min.
			$wrap.find( '.dfp-row-delete' ).toggle( min <= 0 || count > min );
		},

		// ════════════════════════════════════════════════════════════════════
		// Flexible Content
		// ════════════════════════════════════════════════════════════════════

		/**
		 * Show a layout chooser popup for a flexible content field.
		 *
		 * @param {jQuery} $flexWrap  The .dfp-repeater-wrap element for this flex field.
		 */
		showLayoutChooser: function ( $flexWrap ) {
			var self = this;

			// Remove any existing chooser.
			$( '.dfp-fc-chooser-overlay' ).remove();

			var layouts = $flexWrap.data( 'layouts' );
			if ( ! layouts || ! layouts.length ) { return; }

			var $overlay = $( '<div class="dfp-fc-chooser-overlay"></div>' );
			var $chooser = $( '<div class="dfp-fc-chooser"></div>' );
			$chooser.append( '<h3>Choose a Layout</h3>' );
			$chooser.append( '<button class="dfp-fc-chooser-cancel button-link" style="float:right;margin-top:-34px">&times;</button>' );

			$.each( layouts, function ( i, layout ) {
				var $btn = $( '<button type="button" class="dfp-fc-layout-btn">' + layout.label + '</button>' );
				$btn.on( 'click', function () {
					$overlay.remove();
					self.addLayout( $flexWrap, layout.name );
				} );
				$chooser.append( $btn );
			} );

			$overlay.append( $chooser );
			$( 'body' ).append( $overlay );

			$overlay.on( 'click', function ( e ) {
				if ( $( e.target ).is( $overlay ) ) { $overlay.remove(); }
			} );
			$chooser.find( '.dfp-fc-chooser-cancel' ).on( 'click', function () { $overlay.remove(); } );
		},

		/**
		 * Add a layout to a flexible content field.
		 *
		 * @param {jQuery} $flexWrap
		 * @param {string} layoutName
		 */
		addLayout: function ( $flexWrap, layoutName ) {
			var self    = this;
			var $list   = $flexWrap.find( '.dfp-rows-list' );
			var baseName= $flexWrap.data( 'base-name' );
			var rowIndex= $list.find( '.dfp-row' ).length;

			// Find the template for this layout.
			var $tmpl = $flexWrap.find( '.dfp-fc-layout-template[data-layout="' + layoutName + '"] .dfp-row' );
			if ( ! $tmpl.length ) { return; }

			var $newRow = $tmpl.clone();

			// Re-index name attributes.
			$newRow.find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' )
					.replace( /\[clone\]/g, '[' + rowIndex + ']' );
				$( this ).attr( 'name', name );
			} );

			$newRow.attr( 'data-index', rowIndex );
			$newRow.find( '.dfp-row-title' ).text( layoutName + ' ' + ( rowIndex + 1 ) );

			$newRow.css( 'opacity', 0 );
			$list.append( $newRow );
			$newRow.animate( { opacity: 1 }, 200 );

			$flexWrap.find( '.dfp-row-count' ).val( rowIndex + 1 );

			self.enforceMinMax( $flexWrap );

			$flexWrap[ 0 ].dispatchEvent( new CustomEvent( 'dfp/repeater/add_row', {
				bubbles: true,
				detail:  { row: $newRow[ 0 ], repeaterWrap: $flexWrap[ 0 ], layout: layoutName }
			} ) );

			return $newRow;
		}
	};

	// Expose globally so DFP_Admin can reference.
	window.dfpRepeater = dfpRepeater;

	$( document ).ready( function () {
		dfpRepeater.init();
	} );

} )( jQuery );
