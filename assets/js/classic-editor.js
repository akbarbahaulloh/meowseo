/* global meowseoClassic, wp */
( function ( $ ) {
	'use strict';

	var STORAGE_KEY = 'meowseo_active_tab';

	// -------------------------------------------------------------------------
	// Global error handling
	// -------------------------------------------------------------------------
	window.addEventListener( 'error', function ( event ) {
		console.error( 'MeowSEO JavaScript Error:', event.error );
	} );

	// Handle unhandled promise rejections
	window.addEventListener( 'unhandledrejection', function ( event ) {
		console.error( 'MeowSEO Unhandled Promise Rejection:', event.reason );
	} );

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
		if ( ( len > 0 && len < thresholds.ok[ 0 ] ) || ( len > thresholds.ok[ 1 ] && len <= thresholds.warn[ 1 ] ) ) {
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
			runAnalysis();
		} );

		$descInput.on( 'input', function () {
			updateCounter( $descInput, $descCounter, DESC_THRESHOLDS );
			updateSerpPreview();
			runAnalysis();
		} );

		// Init on load.
		updateCounter( $titleInput, $titleCounter, TITLE_THRESHOLDS );
		updateCounter( $descInput, $descCounter, DESC_THRESHOLDS );
	}

	// -------------------------------------------------------------------------
	// SERP Preview
	// -------------------------------------------------------------------------
	var serpPreviewTimer = null;

	function truncate( str, max ) {
		if ( ! str ) return '';
		return str.length > max ? str.substring( 0, max ) + '…' : str;
	}

	function updateSerpPreview() {
		clearTimeout( serpPreviewTimer );
		serpPreviewTimer = setTimeout( function () {
			var title = $( '#meowseo_title' ).val() || meowseoClassic.postTitle || '';
			var desc  = $( '#meowseo_description' ).val() || '';

			$( '#meowseo-serp-title' ).text( truncate( title, 60 ) || meowseoClassic.postTitle );
			$( '#meowseo-serp-desc' ).text( truncate( desc, 155 ) || meowseoClassic.postExcerpt || '' );
		}, 100 );
	}

	function initSerpPreview() {
		// Immediate update on page load (no debounce)
		var title = $( '#meowseo_title' ).val() || meowseoClassic.postTitle || '';
		var desc  = $( '#meowseo_description' ).val() || '';
		$( '#meowseo-serp-title' ).text( truncate( title, 60 ) || meowseoClassic.postTitle );
		$( '#meowseo-serp-desc' ).text( truncate( desc, 155 ) || meowseoClassic.postExcerpt || '' );
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

			// Error handling: Check if media library is available
			if ( typeof wp === 'undefined' || ! wp.media ) {
				var errorMsg = 'Media library is not available. Please refresh the page and try again.';
				console.error( 'MeowSEO Media Picker Error:', errorMsg );
				alert( errorMsg );
				return;
			}

			try {
				var frame = wp.media( {
					title: 'Select Image',
					button: { text: 'Use this image' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					try {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						if ( ! attachment || ! attachment.id ) {
							console.error( 'MeowSEO Media Picker Error: Invalid attachment data' );
							alert( 'Failed to select image. Please try again.' );
							return;
						}
						$input.val( attachment.id );
						$preview.attr( 'src', attachment.url ).addClass( 'has-image' );
					} catch ( e ) {
						console.error( 'MeowSEO Media Picker Error:', e );
						alert( 'Failed to process selected image. Please try again.' );
					}
				} );

				frame.open();
			} catch ( e ) {
				console.error( 'MeowSEO Media Picker Error:', e );
				alert( 'Failed to open media library. Please refresh the page and try again.' );
			}
		} );

		$( '.meowseo-remove-image' ).on( 'click', function () {
			try {
				var target   = $( this ).data( 'target' );
				$( '#' + target ).val( '' );
				$( '#' + target + '-preview' ).removeClass( 'has-image' ).attr( 'src', '' );
			} catch ( e ) {
				console.error( 'MeowSEO Media Picker Error:', e );
			}
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
			try {
				var val = $select.val();
				$groups.hide();
				if ( val ) {
					$groups.filter( '[data-type="' + val + '"]' ).show();
				}
			} catch ( e ) {
				console.error( 'MeowSEO Schema Field Error:', e );
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
					try {
						renderAnalysis( $panel, data );
					} catch ( e ) {
						console.error( 'MeowSEO Analysis Render Error:', e );
						$panel.html( '<p style="color:#721c24">Failed to render analysis results. Please try again.</p>' );
					}
				},
				error: function ( xhr, status, error ) {
					var errorMsg = 'Analysis failed. ';
					
					// Handle authentication errors
					if ( xhr.status === 401 || xhr.status === 403 ) {
						errorMsg += 'Authentication failed. Please refresh the page and try again.';
						console.error( 'MeowSEO Analysis Authentication Error:', xhr.status, error );
					} else if ( xhr.status === 0 ) {
						errorMsg += 'Network error. Please check your connection and try again.';
						console.error( 'MeowSEO Analysis Network Error:', error );
					} else {
						errorMsg += 'Save the post first, then try again.';
						console.error( 'MeowSEO Analysis Error:', status, error, xhr.responseText );
					}
					
					$panel.html( '<p style="color:#721c24">' + escHtml( errorMsg ) + '</p>' );
				},
			} );
		}, 1000 );
	}

	function renderAnalysis( $panel, data ) {
		var html = '';

		// SEO Analysis Section
		if ( data.seo ) {
			html += '<div style="margin-bottom:16px">';
			html += '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">';
			html += '<strong style="font-size:14px">SEO Analysis</strong>';
			html += renderScoreBadge( data.seo.score, data.seo.color );
			html += '</div>';

			if ( data.seo.checks && data.seo.checks.length ) {
				html += '<div style="margin-left:0">';
				data.seo.checks.forEach( function ( check ) {
					var color = check.pass ? '#155724' : '#721c24';
					var dot   = check.pass ? '✓' : '✕';
					html += '<div style="margin-bottom:6px;color:' + color + ';font-size:13px">' + dot + ' ' + escHtml( check.label ) + '</div>';
				} );
				html += '</div>';
			}
			html += '</div>';
		}

		// Readability Analysis Section
		if ( data.readability ) {
			html += '<div style="margin-bottom:16px">';
			html += '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">';
			html += '<strong style="font-size:14px">Readability Analysis</strong>';
			html += renderScoreBadge( data.readability.score, data.readability.color );
			html += '</div>';

			if ( data.readability.checks && data.readability.checks.length ) {
				html += '<div style="margin-left:0">';
				data.readability.checks.forEach( function ( check ) {
					var color = check.pass ? '#155724' : '#721c24';
					var dot   = check.pass ? '✓' : '✕';
					html += '<div style="margin-bottom:6px;color:' + color + ';font-size:13px">' + dot + ' ' + escHtml( check.label ) + '</div>';
				} );
				html += '</div>';
			}
			html += '</div>';
		}

		if ( ! html ) {
			html = '<p style="color:#50575e;font-size:13px">No analysis data available. Save the post first.</p>';
		}

		$panel.html( html );
	}

	function renderScoreBadge( score, color ) {
		var bgColor = color === 'green' ? '#d4edda' : ( color === 'orange' ? '#fff3cd' : '#f8d7da' );
		var textColor = color === 'green' ? '#155724' : ( color === 'orange' ? '#856404' : '#721c24' );
		return '<span style="background:' + bgColor + ';color:' + textColor + ';padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600">' + score + '</span>';
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

			if ( ! action || ! target ) {
				console.error( 'MeowSEO AI Button Error: Missing action or target data attribute' );
				alert( 'AI button configuration error. Please refresh the page.' );
				return;
			}

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
					try {
						if ( data.result ) {
							$input.val( data.result ).trigger( 'input' );
						} else {
							console.warn( 'MeowSEO AI Generation: No result in response' );
							alert( 'AI generation returned no content. Please try again.' );
						}
					} catch ( e ) {
						console.error( 'MeowSEO AI Generation Error:', e );
						alert( 'Failed to process AI generation result. Please try again.' );
					}
				},
				error: function ( xhr, status, error ) {
					var errorMsg = 'AI generation failed. ';
					
					// Handle authentication errors
					if ( xhr.status === 401 || xhr.status === 403 ) {
						errorMsg += 'Authentication failed. Please refresh the page and try again.';
						console.error( 'MeowSEO AI Authentication Error:', xhr.status, error );
					} else if ( xhr.status === 0 ) {
						errorMsg += 'Network error. Please check your connection and try again.';
						console.error( 'MeowSEO AI Network Error:', error );
					} else {
						errorMsg += 'Check your AI settings and try again.';
						console.error( 'MeowSEO AI Generation Error:', status, error, xhr.responseText );
					}
					
					alert( errorMsg );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).text( origText );
				},
			} );
		} );
	}

	function initBulkAi() {
		$( '#meowseo-bulk-ai-btn' ).on( 'click', function () {
			var $btn = $( this );
			var provider = $( '#meowseo-bulk-ai-provider' ).val();
			var origText = $btn.html();

			$btn.prop( 'disabled', true ).html( '&#10024; Generating…' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/ai/generate-all',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
				},
				data: JSON.stringify( {
					post_id: meowseoClassic.postId,
					provider: provider,
				} ),
				success: function ( data ) {
					if ( data.success && data.data && data.data.text ) {
						var res = data.data.text;
						
						if ( res.seo_title ) {
							$( '#meowseo_title' ).val( res.seo_title ).trigger( 'input' );
						}
						if ( res.seo_description ) {
							$( '#meowseo_description' ).val( res.seo_description ).trigger( 'input' );
						}
						if ( res.focus_keyword ) {
							$( '#meowseo_focus_keyword' ).val( res.focus_keyword ).trigger( 'input' );
						}
						if ( res.direct_answer ) {
							$( '#meowseo_direct_answer' ).val( res.direct_answer ).trigger( 'input' );
						}
						
						// Show a success message
						alert( 'SEO content generated successfully!' );
					} else {
						alert( 'Failed to generate content. Please try again.' );
					}
				},
				error: function ( xhr ) {
					var error = 'AI generation failed.';
					if ( xhr.responseJSON && xhr.responseJSON.message ) {
						error += ' ' + xhr.responseJSON.message;
					}
					alert( error );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).html( origText );
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
					try {
						var msg = data.message || 'Submitted to Google.';
						$status.text( 'Last submitted: just now' );
						console.log( 'MeowSEO GSC Submit Success:', msg );
						alert( msg );
					} catch ( e ) {
						console.error( 'MeowSEO GSC Submit Error:', e );
						alert( 'Failed to process GSC submission response. Please try again.' );
					}
				},
				error: function ( xhr, status, error ) {
					var errorMsg = 'GSC submission failed. ';
					
					// Handle authentication errors
					if ( xhr.status === 401 || xhr.status === 403 ) {
						errorMsg += 'Authentication failed. Please refresh the page and try again.';
						console.error( 'MeowSEO GSC Authentication Error:', xhr.status, error );
					} else if ( xhr.status === 0 ) {
						errorMsg += 'Network error. Please check your connection and try again.';
						console.error( 'MeowSEO GSC Network Error:', error );
					} else {
						errorMsg += 'Check your Google Search Console settings and try again.';
						console.error( 'MeowSEO GSC Submit Error:', status, error, xhr.responseText );
					}
					
					alert( errorMsg );
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
		try {
			initTabs();
		} catch ( e ) {
			console.error( 'MeowSEO Tab Initialization Error:', e );
		}

		try {
			initCounters();
		} catch ( e ) {
			console.error( 'MeowSEO Counter Initialization Error:', e );
		}

		try {
			initSerpPreview();
		} catch ( e ) {
			console.error( 'MeowSEO SERP Preview Initialization Error:', e );
		}

		try {
			initMediaPickers();
		} catch ( e ) {
			console.error( 'MeowSEO Media Picker Initialization Error:', e );
		}

		try {
			initOgTwitterToggle();
		} catch ( e ) {
			console.error( 'MeowSEO OG/Twitter Toggle Initialization Error:', e );
		}

		try {
			initSchemaFields();
		} catch ( e ) {
			console.error( 'MeowSEO Schema Fields Initialization Error:', e );
		}

		try {
			initAiButtons();
		} catch ( e ) {
			console.error( 'MeowSEO AI Buttons Initialization Error:', e );
		}

		try {
			initBulkAi();
		} catch ( e ) {
			console.error( 'MeowSEO Bulk AI Initialization Error:', e );
		}

		try {
			initGscSubmit();
		} catch ( e ) {
			console.error( 'MeowSEO GSC Submit Initialization Error:', e );
		}
	} );

} )( jQuery );
