/* global meowseoClassic, wp */
( function ( $ ) {
	'use strict';

	var STORAGE_KEY = 'meowseo_active_tab';

	// -------------------------------------------------------------------------
	// Tab switching
	// -------------------------------------------------------------------------
	function initTabs() {
		var $nav    = $( '#meowseo-tab-nav' );
		var $panels = $( '.meowseo-tab-panel' );

		var saved = localStorage.getItem( STORAGE_KEY ) || 'general';

		function activate( tab ) {
			$nav.find( 'button' ).removeClass( 'meowseo-active' );
			$panels.removeClass( 'meowseo-active' );

			$nav.find( 'button[data-tab="' + tab + '"]' ).addClass( 'meowseo-active' );
			$( '#meowseo-tab-' + tab ).addClass( 'meowseo-active' );

			localStorage.setItem( STORAGE_KEY, tab );
		}

		activate( saved );

		$nav.on( 'click', 'button', function () {
			activate( $( this ).data( 'tab' ) );
		} );
	}

	// -------------------------------------------------------------------------
	// Character counters
	// -------------------------------------------------------------------------
	var TITLE_THRESHOLDS = { ok: [ 30, 60 ], warn: [ 0, 70 ] };
	var DESC_THRESHOLDS  = { ok: [ 120, 155 ], warn: [ 0, 170 ] };

	function getCounterClass( len, thresholds ) {
		if ( len >= thresholds.ok[ 0 ] && len <= thresholds.ok[ 1 ] ) {
			return 'meowseo-ok';
		}
		if ( len <= thresholds.warn[ 1 ] ) {
			return 'meowseo-warn';
		}
		return 'meowseo-bad';
	}

	function updateCounter( $input, $counter, thresholds ) {
		var len = $input.val().length;
		$counter
			.text( len + ' / ' + thresholds.ok[ 1 ] )
			.removeClass( 'meowseo-ok meowseo-warn meowseo-bad' )
			.addClass( len > 0 ? getCounterClass( len, thresholds ) : '' );
	}

	function initCounters() {
		var $titleInput   = $( '#meowseo_title' );
		var $titleCounter = $( '#meowseo-title-counter' );
		var $descInput    = $( '#meowseo_description' );
		var $descCounter  = $( '#meowseo-desc-counter' );

		$titleInput.on( 'input', function () {
			updateCounter( $titleInput, $titleCounter, TITLE_THRESHOLDS );
			updateSerpPreview();
		} );

		$descInput.on( 'input', function () {
			updateCounter( $descInput, $descCounter, DESC_THRESHOLDS );
			updateSerpPreview();
		} );

		// Init on load.
		updateCounter( $titleInput, $titleCounter, TITLE_THRESHOLDS );
		updateCounter( $descInput, $descCounter, DESC_THRESHOLDS );
	}

	// -------------------------------------------------------------------------
	// SERP Preview
	// -------------------------------------------------------------------------
	function truncate( str, max ) {
		if ( ! str ) return '';
		return str.length > max ? str.substring( 0, max ) + '…' : str;
	}

	function updateSerpPreview() {
		var title = $( '#meowseo_title' ).val() || meowseoClassic.postTitle || '';
		var desc  = $( '#meowseo_description' ).val() || '';

		$( '#meowseo-serp-title' ).text( truncate( title, 60 ) || meowseoClassic.postTitle );
		$( '#meowseo-serp-desc' ).text( truncate( desc, 155 ) || meowseoClassic.postExcerpt || '' );
	}

	function initSerpPreview() {
		updateSerpPreview();
	}

	// -------------------------------------------------------------------------
	// Media picker (OG + Twitter image)
	// -------------------------------------------------------------------------
	function initMediaPickers() {
		$( '.meowseo-pick-image' ).on( 'click', function () {
			var $btn      = $( this );
			var target    = $btn.data( 'target' );
			var $input    = $( '#' + target );
			var $preview  = $( '#' + target + '-preview' );

			if ( typeof wp === 'undefined' || ! wp.media ) {
				return;
			}

			var frame = wp.media( {
				title: 'Select Image',
				button: { text: 'Use this image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$input.val( attachment.id );
				$preview.attr( 'src', attachment.url ).addClass( 'has-image' );
			} );

			frame.open();
		} );

		$( '.meowseo-remove-image' ).on( 'click', function () {
			var target   = $( this ).data( 'target' );
			$( '#' + target ).val( '' );
			$( '#' + target + '-preview' ).removeClass( 'has-image' ).attr( 'src', '' );
		} );
	}

	// -------------------------------------------------------------------------
	// Twitter "use OG data" toggle
	// -------------------------------------------------------------------------
	function initOgTwitterToggle() {
		var $toggle = $( '#meowseo_use_og_for_twitter' );
		var $fields = $( '#meowseo-twitter-fields' );

		function syncToggle() {
			$fields.find( 'input, textarea' ).prop( 'disabled', $toggle.is( ':checked' ) );
		}

		$toggle.on( 'change', syncToggle );
		syncToggle();
	}

	// -------------------------------------------------------------------------
	// Schema conditional fields
	// -------------------------------------------------------------------------
	function initSchemaFields() {
		var $select = $( '#meowseo_schema_type' );
		var $groups = $( '.meowseo-schema-fields' );

		function syncSchema() {
			var val = $select.val();
			$groups.hide();
			if ( val ) {
				$groups.filter( '[data-type="' + val + '"]' ).show();
			}
		}

		$select.on( 'change', syncSchema );
		syncSchema();
	}

	// -------------------------------------------------------------------------
	// Analysis via REST
	// -------------------------------------------------------------------------
	var analysisTimer = null;

	function runAnalysis() {
		clearTimeout( analysisTimer );
		analysisTimer = setTimeout( function () {
			var $panel = $( '#meowseo-analysis-panel' );
			$panel.html( '<p style="color:#50575e">Running analysis…</p>' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/analysis/' + meowseoClassic.postId,
				method: 'GET',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
				},
				success: function ( data ) {
					renderAnalysis( $panel, data );
				},
				error: function () {
					$panel.html( '<p style="color:#721c24">Analysis failed. Save the post first, then try again.</p>' );
				},
			} );
		}, 800 );
	}

	function renderAnalysis( $panel, data ) {
		var html = '';

		if ( data.seo_score !== undefined ) {
			html += '<div style="margin-bottom:10px"><strong>SEO Score: ' + data.seo_score + '</strong></div>';
		}

		if ( data.checks && data.checks.length ) {
			data.checks.forEach( function ( check ) {
				var color = check.status === 'good' ? '#155724' : ( check.status === 'ok' ? '#856404' : '#721c24' );
				var dot   = '●';
				html += '<div style="margin-bottom:4px;color:' + color + '">' + dot + ' ' + escHtml( check.message ) + '</div>';
			} );
		} else {
			html += '<p style="color:#50575e">No analysis data available. Save the post first.</p>';
		}

		$panel.html( html );
	}

	function escHtml( str ) {
		return $( '<div>' ).text( str ).html();
	}

	// -------------------------------------------------------------------------
	// AI generation
	// -------------------------------------------------------------------------
	function initAiButtons() {
		$( '.meowseo-ai-btn' ).on( 'click', function () {
			var $btn    = $( this );
			var action  = $btn.data( 'action' );
			var target  = $btn.data( 'target' );
			var $input  = $( '#' + target );
			var origText = $btn.text();

			$btn.prop( 'disabled', true ).text( 'Generating…' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/ai/generate',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
				},
				data: JSON.stringify( {
					post_id: meowseoClassic.postId,
					type: action,
				} ),
				success: function ( data ) {
					if ( data.result ) {
						$input.val( data.result ).trigger( 'input' );
					}
				},
				error: function () {
					alert( 'AI generation failed. Check your AI settings.' );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).text( origText );
				},
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// GSC Submit
	// -------------------------------------------------------------------------
	function initGscSubmit() {
		$( '#meowseo-gsc-submit' ).on( 'click', function () {
			var $btn    = $( this );
			var $status = $( '#meowseo-gsc-status' );
			$btn.prop( 'disabled', true ).text( 'Submitting…' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/gsc/submit',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
				},
				data: JSON.stringify( { post_id: meowseoClassic.postId } ),
				success: function ( data ) {
					var msg = data.message || 'Submitted to Google.';
					$status.text( 'Last submitted: just now' );
					alert( msg );
				},
				error: function () {
					alert( 'GSC submission failed. Check your Google Search Console settings.' );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).text( 'Submit to Google' );
				},
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------
	$( function () {
		initTabs();
		initCounters();
		initSerpPreview();
		initMediaPickers();
		initOgTwitterToggle();
		initSchemaFields();
		initAiButtons();
		initGscSubmit();
	} );

} )( jQuery );
