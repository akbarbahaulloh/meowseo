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

		// Auto-trigger analysis on SEO input changes
		$( '#meowseo_focus_keyword, #meowseo_secondary_keyword_1, #meowseo_secondary_keyword_2, #meowseo_secondary_keyword_3, #meowseo_lsi_keywords, #meowseo_direct_answer' ).on( 'input', function() {
			runAnalysis();
		} );

		// Auto-trigger analysis on content changes
		$( '#content' ).on( 'input', function() {
			runAnalysis();
		} );

		// Hook into TinyMCE if available
		if ( typeof tinyMCE !== 'undefined' ) {
			tinyMCE.on( 'AddEditor', function( e ) {
				e.editor.on( 'change keyup', function() {
					runAnalysis();
				} );
			} );
		}

		// Initial analysis run
		setTimeout( function() {
			runAnalysis();
		}, 500 );

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
		var $select = $( '#meowseo_schema_page_type' );
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
	// FAQ / HowTo Repeaters
	// -------------------------------------------------------------------------
	function initRepeaters() {
		var labels = meowseoClassic.labels || {};

		$( document ).on( 'click', '#meowseo-add-faq', function () {
			$( '#meowseo-faq-items' ).append(
				'<div class="meowseo-faq-item" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">' +
				'<div class="meowseo-field"><label>' + escHtml( labels.question || 'Question' ) + '</label>' +
				'<input type="text" name="meowseo_faq_question[]" /></div>' +
				'<div class="meowseo-field"><label>' + escHtml( labels.answer || 'Answer' ) + '</label>' +
				'<textarea name="meowseo_faq_answer[]"></textarea></div>' +
				'<button type="button" class="button meowseo-remove-faq">' + escHtml( labels.remove || 'Remove' ) + '</button>' +
				'</div>'
			);
		} );

		$( document ).on( 'click', '.meowseo-remove-faq', function () {
			$( this ).closest( '.meowseo-faq-item' ).remove();
		} );

		$( document ).on( 'click', '#meowseo-add-step', function () {
			$( '#meowseo-howto-steps' ).append(
				'<div class="meowseo-howto-step" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">' +
				'<div class="meowseo-field"><label>' + escHtml( labels.stepName || 'Step Name' ) + '</label>' +
				'<input type="text" name="meowseo_howto_step_name[]" /></div>' +
				'<div class="meowseo-field"><label>' + escHtml( labels.stepText || 'Step Text' ) + '</label>' +
				'<textarea name="meowseo_howto_step_text[]"></textarea></div>' +
				'<button type="button" class="button meowseo-remove-step">' + escHtml( labels.remove || 'Remove' ) + '</button>' +
				'</div>'
			);
		} );

		$( document ).on( 'click', '.meowseo-remove-step', function () {
			$( this ).closest( '.meowseo-howto-step' ).remove();
		} );

		// Build schema_config JSON before form submit
		$( '#post' ).on( 'submit', function () {
			var type = $( '#meowseo_schema_page_type' ).val();
			if ( ! type ) {
				$( '#meowseo_schema_config' ).val( '' );
				return;
			}

			var config = {};
			if ( type === 'FAQPage' ) {
				config.faq_items = [];
				$( '#meowseo-faq-items .meowseo-faq-item' ).each( function () {
					config.faq_items.push( {
						question: $( this ).find( '[name="meowseo_faq_question[]"]' ).val(),
						answer: $( this ).find( '[name="meowseo_faq_answer[]"]' ).val()
					} );
				} );
			} else if ( type === 'HowTo' ) {
				config.howto_name        = $( '#meowseo_schema_howto_name' ).val();
				config.howto_description = $( '#meowseo_schema_howto_description' ).val();
				config.howto_steps = [];
				$( '#meowseo-howto-steps .meowseo-howto-step' ).each( function () {
					config.howto_steps.push( {
						name: $( this ).find( '[name="meowseo_howto_step_name[]"]' ).val(),
						text: $( this ).find( '[name="meowseo_howto_step_text[]"]' ).val()
					} );
				} );
			} else if ( type === 'LocalBusiness' ) {
				[ 'lb_name', 'lb_type', 'lb_address', 'lb_phone', 'lb_hours' ].forEach( function ( k ) {
					config[ k ] = $( '#meowseo_schema_' + k ).val();
				} );
			} else if ( type === 'Product' ) {
				[ 'product_name', 'product_description', 'product_sku', 'product_price', 'product_currency', 'product_availability' ].forEach( function ( k ) {
					config[ k ] = $( '#meowseo_schema_' + k ).val();
				} );
			}
			$( '#meowseo_schema_config' ).val( JSON.stringify( config ) );
		} );
	}

	// -------------------------------------------------------------------------
	// Content Type toggle
	// -------------------------------------------------------------------------
	function initContentTypeToggle() {
		$( '#meowseo_content_type' ).on( 'change', function () {
			var val = $( this ).val();
			$( '.meowseo-ct-field' ).hide();
			$( '.meowseo-ct-field[data-ct="' + val + '"]' ).show();
		} ).trigger( 'change' );
	}

	// -------------------------------------------------------------------------
	// Analysis via REST
	// -------------------------------------------------------------------------
	var analysisTimer = null;
	var lastAnalysisResult = null;

	function runAnalysis() {
		clearTimeout( analysisTimer );
		analysisTimer = setTimeout( function () {
			var $panel = $( '#meowseo-analysis-panel' );
			var labels = meowseoClassic.labels || {};
			$panel.html( '<p style="color:#50575e">' + escHtml( labels.analyzing || 'Running analysis…' ) + '</p>' );

			// Get current content from TinyMCE or textarea
			var content = '';
			if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
				content = tinyMCE.activeEditor.getContent();
			} else {
				content = $( '#content' ).val() || '';
			}

			var postId = meowseoClassic.postId || $( '#post_ID' ).val();
			if ( ! postId || postId === '0' ) {
				$panel.html( '<p style="color:#721c24">Post ID not found. Please save the post as draft first.</p>' );
				return;
			}

			// Gather secondary keywords
			var secondaryKeywords = [];
			[1, 2, 3].forEach(function(i) {
				var val = $( '#meowseo_secondary_keyword_' + i ).val();
				if ( val ) secondaryKeywords.push( val );
			});

			$.ajax( {
				url: meowseoClassic.restUrl + '/analysis/' + postId,
				method: 'POST',
				data: JSON.stringify( {
					content: content,
					focus_keyword: $( '#meowseo_focus_keyword' ).val() || '',
					secondary_keywords: secondaryKeywords,
					lsi_keywords: $( '#meowseo_lsi_keywords' ).val() || '',
					direct_answer: $( '#meowseo_direct_answer' ).val() || ''
				} ),
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
				},
				success: function ( data ) {
					try {
						lastAnalysisResult = data;
						renderAnalysis( $panel, data );
					} catch ( e ) {
						console.error( 'MeowSEO Analysis Render Error:', e );
						$panel.html( '<p style="color:#721c24">Failed to render analysis results. Please try again.</p>' );
					}
				},
				error: function ( xhr, status, error ) {
					var errorMsg = 'Analysis failed (Code: ' + xhr.status + '). ';
					
					// Handle authentication errors
					if ( xhr.status === 401 || xhr.status === 403 ) {
						errorMsg += 'Authentication failed. Please refresh the page.';
						console.error( 'MeowSEO Analysis Authentication Error:', xhr.status, error );
					} else if ( xhr.status === 404 ) {
						errorMsg += 'Route not found. Please check your permalink settings.';
						console.error( 'MeowSEO Analysis 404 Error:', error );
					} else {
						errorMsg += 'Please ensure the post is saved and try again.';
						console.error( 'MeowSEO Analysis Error:', xhr.status, error, xhr.responseText );
					}
					
					$panel.html( '<p style="color:#721c24">' + escHtml( errorMsg ) + '</p>' );
				},
			} );
		}, 1000 );
	}

	function renderAnalysis( $panel, data ) {
		var html = '';

		// ── SEO Score ──────────────────────────────────────────────────────────
		if ( data.seo ) {
			var score     = data.seo.score || 0;
			var scoreColor = score >= 80 ? '#46b450' : ( score >= 50 ? '#f0b849' : '#d63638' );
			var scoreBg   = score >= 80 ? '#edfaee' : ( score >= 50 ? '#fcf9e8' : '#fce8e8' );
			var scoreLabel = score >= 80 ? 'Baik' : ( score >= 50 ? 'Perlu Ditingkatkan' : 'Buruk' );

			// Circular score indicator
			html += '<div class="meowseo-score-panel">';
			html += '<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">';
			html += '<div style="width:60px;height:60px;border-radius:50%;background:' + scoreBg + ';border:3px solid ' + scoreColor + ';display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">';
			html += '<span style="font-size:18px;font-weight:700;color:' + scoreColor + ';line-height:1;">' + score + '</span>';
			html += '<span style="font-size:9px;color:' + scoreColor + ';line-height:1;margin-top:2px;">/ 100</span>';
			html += '</div>';
			html += '<div>';
			html += '<strong style="font-size:13px;display:block;">SEO Score</strong>';
			html += '<span style="font-size:12px;color:' + scoreColor + ';font-weight:600;">' + escHtml( scoreLabel ) + '</span>';
			// Score progress bar
			html += '<div style="width:120px;height:5px;background:#e0e0e0;border-radius:3px;margin-top:5px;">';
			html += '<div style="width:' + score + '%;height:5px;background:' + scoreColor + ';border-radius:3px;transition:width .3s;"></div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';

			// Checklist items
			if ( data.seo.checks && data.seo.checks.length ) {
				html += '<div style="display:grid;gap:5px;">';
				data.seo.checks.forEach( function ( check ) {
					var icon   = check.pass ? '✓' : '✕';
					var color  = check.pass ? '#46b450' : '#d63638';
					var bgChk  = check.pass ? '#edfaee' : '#fce8e8';
					html += '<div style="display:flex;align-items:flex-start;gap:8px;padding:6px 8px;background:' + bgChk + ';border-radius:4px;font-size:12px;">';
					html += '<span style="color:' + color + ';font-weight:700;flex-shrink:0;width:14px;">' + icon + '</span>';
					html += '<span style="color:#1e1e1e;">' + escHtml( check.label ) + '</span>';
					html += '</div>';
				} );
				html += '</div>';
			}
			if ( score < 100 ) {
				html += '<div style="margin-top:15px;padding-top:10px;border-top:1px dashed #c3c4c7;">';
				html += '<button type="button" class="button button-secondary meowseo-ai-explain-btn">';
				html += '<span class="dashicons dashicons-lightbulb" style="color:#f0b849;margin-top:4px;"></span> Tanya AI Cara Memperbaiki';
				html += '</button>';
				html += '<div class="meowseo-ai-explain-result" style="display:none;margin-top:10px;padding:12px;background:#fff;border-left:4px solid #f0b849;border-radius:4px;font-size:13px;color:#3c434a;box-shadow:0 1px 3px rgba(0,0,0,0.05);"></div>';
				html += '</div>';
			}

			html += '</div>'; // .meowseo-score-panel

			// Divider
			html += '<hr style="margin:14px 0;border-top:1px solid #e0e0e0;">';
		}

		// ── Readability Score ──────────────────────────────────────────────────
		if ( data.readability ) {
			var rScore     = data.readability.score || 0;
			var rColor     = rScore >= 70 ? '#46b450' : ( rScore >= 40 ? '#f0b849' : '#d63638' );
			var rBg        = rScore >= 70 ? '#edfaee' : ( rScore >= 40 ? '#fcf9e8' : '#fce8e8' );
			var rLabel     = rScore >= 70 ? 'Mudah Dibaca' : ( rScore >= 40 ? 'Cukup' : 'Sulit Dibaca' );

			html += '<div class="meowseo-score-panel">';
			html += '<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">';
			html += '<div style="width:60px;height:60px;border-radius:50%;background:' + rBg + ';border:3px solid ' + rColor + ';display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">';
			html += '<span style="font-size:18px;font-weight:700;color:' + rColor + ';line-height:1;">' + rScore + '</span>';
			html += '<span style="font-size:9px;color:' + rColor + ';line-height:1;margin-top:2px;">/ 100</span>';
			html += '</div>';
			html += '<div>';
			html += '<strong style="font-size:13px;display:block;">Keterbacaan</strong>';
			html += '<span style="font-size:12px;color:' + rColor + ';font-weight:600;">' + escHtml( rLabel ) + '</span>';
			html += '<div style="width:120px;height:5px;background:#e0e0e0;border-radius:3px;margin-top:5px;">';
			html += '<div style="width:' + rScore + '%;height:5px;background:' + rColor + ';border-radius:3px;"></div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';

			if ( data.readability.checks && data.readability.checks.length ) {
				html += '<div style="display:grid;gap:5px;">';
				data.readability.checks.forEach( function ( check ) {
					var icon  = check.pass ? '✓' : '✕';
					var color = check.pass ? '#46b450' : '#d63638';
					var bgChk = check.pass ? '#edfaee' : '#fce8e8';
					html += '<div style="display:flex;align-items:flex-start;gap:8px;padding:6px 8px;background:' + bgChk + ';border-radius:4px;font-size:12px;">';
					html += '<span style="color:' + color + ';font-weight:700;flex-shrink:0;width:14px;">' + icon + '</span>';
					html += '<span style="color:#1e1e1e;">' + escHtml( check.label ) + '</span>';
					html += '</div>';
				} );
				html += '</div>';
			}
			html += '</div>'; // .meowseo-score-panel
		}

		if ( ! html ) {
			html = '<p style="color:#50575e;font-size:13px;">Tidak ada data analisis. Simpan post terlebih dahulu.</p>';
		}

		$panel.html( html );
	}

	function renderScoreBadge( score, color ) {
		var bgColor   = color === 'green' ? '#edfaee' : ( color === 'orange' ? '#fcf9e8' : '#fce8e8' );
		var textColor = color === 'green' ? '#46b450' : ( color === 'orange' ? '#f0b849' : '#d63638' );
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
					style_id: $( '#meowseo-bulk-ai-style' ).val(),
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

		// Handle AI Explainer
		$( document ).on( 'click', '.meowseo-ai-explain-btn', function() {
			var $btn = $( this );
			var $result = $btn.siblings( '.meowseo-ai-explain-result' );
			
			// Gather failed checks
			var failedChecks = [];
			if ( lastAnalysisResult && lastAnalysisResult.seo && lastAnalysisResult.seo.checks ) {
				lastAnalysisResult.seo.checks.forEach( function( check ) {
					if ( ! check.pass ) {
						failedChecks.push( check.label );
					}
				} );
			}

			var content = '';
			if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
				content = tinyMCE.activeEditor.getContent();
			} else {
				content = $( '#content' ).val() || '';
			}

			$btn.prop( 'disabled', true ).html( '<span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span> Menganalisis...' );
			$result.html( '<em>AI sedang membaca konten Anda dan menyusun saran...</em>' ).slideDown();

			$.ajax( {
				url: meowseoClassic.restUrl + '/ai/explain-seo-score',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
				},
				data: JSON.stringify( {
					post_id: meowseoClassic.postId || $( '#post_ID' ).val(),
					failed_checks: failedChecks,
					focus_keyword: $( '#meowseo_focus_keyword' ).val() || '',
					content: content
				} ),
				success: function ( response ) {
					if ( response && response.success && response.data ) {
						// Simple markdown to HTML conversion for the response
						var formattedHtml = response.data
							.replace( /\n\n/g, '</p><p>' )
							.replace( /\n/g, '<br>' )
							.replace( /\*\*(.*?)\*\*/g, '<strong>$1</strong>' )
							.replace( /\*(.*?)\*/g, '<em>$1</em>' )
							.replace( /^- (.*)$/gm, '<li>$1</li>' )
							.replace( /<\/li><br><li>/g, '</li><li>' );
							
						// Wrap lists
						if ( formattedHtml.indexOf( '<li>' ) !== -1 ) {
							formattedHtml = formattedHtml.replace( /(<li>.*<\/li>)/g, '<ul style="margin-left:20px;list-style-type:disc;">$1</ul>' );
						}

						$result.html( '<p>' + formattedHtml + '</p>' );
					} else {
						$result.html( '<span style="color:#d63638;">Gagal mendapatkan saran. Silakan coba lagi.</span>' );
					}
				},
				error: function ( xhr, status, error ) {
					var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
					$result.html( '<span style="color:#d63638;">Error: ' + escHtml( msg ) + '</span>' );
					console.error( 'MeowSEO AI Explainer Error:', msg );
				},
				complete: function() {
					$btn.prop( 'disabled', false ).html( '<span class="dashicons dashicons-lightbulb" style="color:#f0b849;margin-top:4px;"></span> Tanya AI Cara Memperbaiki' );
				}
			} );
		} );
	}

	function initBulkAi() {
		var $logArea = $( '#meowseo-bulk-ai-log' );

		function addLog( message, color ) {
			var timestamp = new Date().toLocaleTimeString();
			var $log = $( '<div>' ).css( 'margin-bottom', '2px' );
			if ( color ) $log.css( 'color', color );
			$log.html( '<span style="color:#888">[' + timestamp + ']</span> ' + message );
			$logArea.append( $log ).show();
			$logArea.scrollTop( $logArea[ 0 ].scrollHeight );
		}

		$( '#meowseo-bulk-ai-btn' ).on( 'click', function () {
			var $btn = $( this );
			var profileId = $( '#meowseo-bulk-ai-profile' ).val();
			var styleId = $( '#meowseo-bulk-ai-style' ).val();
			var imageStyleId = $( '#meowseo-bulk-ai-image-style' ).val();
			var origText = $btn.html();

			$logArea.empty().append( '<div style="color:#6a9955">// MeowSEO AI Progress Log</div>' );
			$btn.prop( 'disabled', true ).html( '&#10024; Processing…' );

			addLog( 'Initializing bulk SEO generation...', '#569cd6' );
			addLog( 'Analyzing post content and context...', '#dcdcaa' );

			var profileName = profileId ? $( '#meowseo-bulk-ai-profile option:selected' ).text() : 'Auto (Default)';
			var styleName = styleId ? $( '#meowseo-bulk-ai-style option:selected' ).text() : 'Standard MeowSEO';
			var imageStyleName = imageStyleId ? $( '#meowseo-bulk-ai-image-style option:selected' ).text() : 'Standard MeowSEO';
			addLog( 'AI Profile: ' + profileName, '#ce9178' );
			addLog( 'Writing Style: ' + styleName, '#ce9178' );
			addLog( 'Image Style: ' + imageStyleName, '#ce9178' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/ai/generate-all',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
					xhr.setRequestHeader( 'Content-Type', 'application/json' );
					addLog( 'Request sent to backend...', '#888' );
				},
				data: JSON.stringify( {
					post_id: meowseoClassic.postId,
					profile_id: profileId,
					style_id: styleId,
					image_style_id: imageStyleId,
				} ),
				success: function ( data ) {
					if ( data.success && data.data && data.data.text ) {
						addLog( 'AI Response received successfully.', '#4ec9b0' );
						addLog( 'Parsing SEO package...', '#dcdcaa' );

						var res = data.data.text;
						var updatedCount = 0;
						
						if ( res.seo_title ) {
							$( '#meowseo_title' ).val( res.seo_title ).trigger( 'input' );
							addLog( '✓ Updated SEO Title', '#b5cea8' );
							updatedCount++;
						}
						if ( res.seo_description ) {
							$( '#meowseo_description' ).val( res.seo_description ).trigger( 'input' );
							addLog( '✓ Updated Meta Description', '#b5cea8' );
							updatedCount++;
						}
						if ( res.focus_keyword ) {
							$( '#meowseo_focus_keyword' ).val( res.focus_keyword ).trigger( 'input' );
							addLog( '✓ Updated Focus Keyword', '#b5cea8' );
							updatedCount++;
						}
						if ( res.direct_answer ) {
							$( '#meowseo_direct_answer' ).val( res.direct_answer ).trigger( 'input' );
							addLog( '✓ Updated Featured Snippet', '#b5cea8' );
							updatedCount++;
						}

						if ( data.data.image && data.data.image.attachment_id ) {
							var attId = data.data.image.attachment_id;
							var imgUrl = data.data.image.url;
							$( '#_thumbnail_id' ).val( attId );
							$( '#postimagediv .inside' ).html( '<p class="hide-if-no-js"><a href="#" id="set-post-thumbnail" aria-describedby="set-post-thumbnail-desc"><img src="' + imgUrl + '" alt="" style="max-width:100%;height:auto;" /></a></p><p class="hide-if-no-js howto" id="set-post-thumbnail-desc">Click the image to edit or update</p><p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">Remove featured image</a></p>' );
							addLog( '✓ Generated and set Featured Image', '#b5cea8' );
							updatedCount++;
						}
						
						addLog( 'Success! ' + updatedCount + ' fields updated.', '#4ec9b0' );
					} else {
						var errorMsg = data.message || 'Unknown error occurred.';
						addLog( 'ERROR: ' + errorMsg, '#f44747' );
					}
				},
				error: function ( xhr ) {
					var error = 'AI generation failed.';
					if ( xhr.responseJSON && xhr.responseJSON.message ) {
						error = xhr.responseJSON.message;
					} else if ( xhr.statusText ) {
						error = xhr.statusText;
					}
					addLog( 'FATAL ERROR: ' + error, '#f44747' );
					
					// Show detailed provider errors if available
					if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.errors ) {
						$.each( xhr.responseJSON.data.errors, function( slug, msg ) {
							addLog( '→ ' + slug.toUpperCase() + ': ' + msg, '#f44747' );
						} );
					}

					addLog( 'Check your AI API key and connection settings.', '#888' );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).html( origText );
					addLog( 'Process finished.', '#569cd6' );
				},
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Schema Preview
	// -------------------------------------------------------------------------
	function initSchemaPreview() {
		$( '#meowseo-schema-preview-btn' ).on( 'click', function () {
			var $btn = $( this );
			var $container = $( '#meowseo-schema-preview-container' );
			var $code = $( '#meowseo-schema-preview-code' );
			var postId = meowseoClassic.postId || $( '#post_ID' ).val();

			if ( ! postId || postId === '0' ) {
				alert( 'Please save the post first before previewing schema.' );
				return;
			}

			$btn.prop( 'disabled', true ).text( 'Loading Preview…' );
			$container.show();
			$code.text( 'Loading...' );

			$.ajax( {
				url: meowseoClassic.restUrl + '/schema/preview/' + postId,
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
				},
				success: function ( response ) {
					if ( response && response.success && response.data ) {
						// Format the JSON with 2 spaces indentation
						var formattedJson = JSON.stringify( response.data, null, 2 );
						$code.text( formattedJson );
					} else {
						$code.text( 'No schema data generated or error occurred.' );
					}
				},
				error: function ( xhr, status, error ) {
					console.error( 'Schema Preview Error:', error );
					$code.text( 'Failed to load schema preview. Error: ' + status );
				},
				complete: function () {
					$btn.prop( 'disabled', false ).html( '<span class="dashicons dashicons-visibility" style="margin-top:4px"></span> Preview JSON-LD Schema' );
				}
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
	// AI Writer
	// -------------------------------------------------------------------------
	function initAiWriter() {
		var $logArea = $( '#meowseo-writer-log' );

		function addLog( message, color ) {
			var timestamp = new Date().toLocaleTimeString();
			var $log = $( '<div>' ).css( 'margin-bottom', '2px' );
			if ( color ) $log.css( 'color', color );
			$log.html( '<span style="color:#888">[' + timestamp + ']</span> ' + message );
			$logArea.append( $log ).show();
			$logArea.scrollTop( $logArea[ 0 ].scrollHeight );
		}

		$( '#meowseo-writer-btn' ).on( 'click', async function () {
			var $btn = $( this );
			var styleId = $( '#meowseo_writer_style' ).val();
			var mode = $( '#meowseo_writer_style option:selected' ).data( 'mode' ) || 'advance';
			var topic = $( '#meowseo_writer_topic' ).val().trim();
			var origText = $btn.html();

			if ( ! topic ) {
				alert( 'Please enter a topic or prompt first.' );
				return;
			}

			if ( typeof tinyMCE === 'undefined' || ! tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden() ) {
				alert( 'TinyMCE editor is not active. Please ensure you are in the Visual editor mode.' );
				return;
			}

			$logArea.empty().append( '<div style="color:#6a9955">// MeowSEO AI Writer Log (' + mode.toUpperCase() + ' Mode)</div>' );
			$btn.prop( 'disabled', true ).html( '&#10024; Writing…' );

			addLog( 'Initializing AI Writer for topic: ' + topic, '#569cd6' );

			try {
				if ( mode === 'simple' ) {
					addLog( 'Sending prompt for single-pass generation...', '#dcdcaa' );
					
					const res = await $.ajax( {
						url: meowseoClassic.restUrl + '/ai/write/simple',
						method: 'POST',
						beforeSend: function ( xhr ) {
							xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce );
							xhr.setRequestHeader( 'Content-Type', 'application/json' );
						},
						data: JSON.stringify( { topic: topic, style_id: styleId } )
					} );

					if ( res && res.success && res.data && res.data.content ) {
						addLog( '✓ Article generated successfully.', '#b5cea8' );
						tinyMCE.activeEditor.setContent( res.data.content );
						addLog( '✓ Content inserted into editor.', '#b5cea8' );
					} else {
						throw new Error( 'Invalid response from server.' );
					}
				} else {
					// Advance Mode
					addLog( '[Phase 1/4] Requesting structured outline...', '#dcdcaa' );
					const outlineRes = await $.ajax( {
						url: meowseoClassic.restUrl + '/ai/write/outline',
						method: 'POST',
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce ); xhr.setRequestHeader( 'Content-Type', 'application/json' ); },
						data: JSON.stringify( { topic: topic, style_id: styleId } )
					} );

					if ( ! outlineRes || ! outlineRes.success || ! outlineRes.data || ! outlineRes.data.outline ) {
						throw new Error( 'Failed to generate outline.' );
					}

					var outline = outlineRes.data.outline;
					addLog( '✓ Outline generated with ' + outline.length + ' main sections.', '#b5cea8' );

					addLog( '[Phase 2/4] Writing introduction and hook...', '#dcdcaa' );
					const introRes = await $.ajax( {
						url: meowseoClassic.restUrl + '/ai/write/intro',
						method: 'POST',
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce ); xhr.setRequestHeader( 'Content-Type', 'application/json' ); },
						data: JSON.stringify( { topic: topic, outline: outline, style_id: styleId } )
					} );

					var fullContent = introRes.data.content + '\n\n';
					addLog( '✓ Introduction written.', '#b5cea8' );

					addLog( '[Phase 3/4] Writing body sections (processing chunks)...', '#dcdcaa' );
					
					// Process in chunks of 2 to balance speed and timeout risk
					var chunkSize = 2;
					for ( var i = 0; i < outline.length; i += chunkSize ) {
						var chunk = outline.slice( i, i + chunkSize );
						addLog( '→ Processing sections ' + (i + 1) + ' to ' + Math.min(i + chunkSize, outline.length) + '...', '#ce9178' );
						
						// We process them sequentially in JS to avoid overwhelming the server or hitting limits
						for ( var j = 0; j < chunk.length; j++ ) {
							const sectionRes = await $.ajax( {
								url: meowseoClassic.restUrl + '/ai/write/section',
								method: 'POST',
								beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce ); xhr.setRequestHeader( 'Content-Type', 'application/json' ); },
								data: JSON.stringify( { topic: topic, section: chunk[j], style_id: styleId } )
							} );
							
							fullContent += sectionRes.data.content + '\n\n';
							addLog( '  ✓ Section completed: ' + chunk[j].heading, '#b5cea8' );
						}
					}

					addLog( '[Phase 4/5] Generating FAQs for AI Overviews & Schema...', '#dcdcaa' );
					const faqRes = await $.ajax( {
						url: meowseoClassic.restUrl + '/ai/write/faq',
						method: 'POST',
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce ); xhr.setRequestHeader( 'Content-Type', 'application/json' ); },
						data: JSON.stringify( { topic: topic, outline: outline, style_id: styleId, post_id: meowseoClassic.postId || 0 } )
					} );

					if ( faqRes && faqRes.success && faqRes.data && faqRes.data.html ) {
						fullContent += faqRes.data.html + '\n\n';
						var faqCount = ( faqRes.data.items && faqRes.data.items.length ) ? faqRes.data.items.length : '?';
						addLog( '✓ ' + faqCount + ' FAQs generated & saved to Schema.', '#b5cea8' );
					} else {
						addLog( '⚠ FAQ generation returned no content. Skipping.', '#ce9178' );
					}

					addLog( '[Phase 5/5] Writing conclusion...', '#dcdcaa' );
					const concRes = await $.ajax( {
						url: meowseoClassic.restUrl + '/ai/write/conclusion',
						method: 'POST',
						beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce ); xhr.setRequestHeader( 'Content-Type', 'application/json' ); },
						data: JSON.stringify( { topic: topic, outline: outline, style_id: styleId } )
					} );

					fullContent += concRes.data.content + '\n\n';
					addLog( '✓ Conclusion written.', '#b5cea8' );

					addLog( 'Inserting full article into editor...', '#dcdcaa' );
					tinyMCE.activeEditor.setContent( fullContent );
					addLog( 'Success! Article is ready.', '#4ec9b0' );
				}
			} catch ( error ) {
				var errorMsg = 'An error occurred.';
				if ( error.responseJSON && error.responseJSON.message ) {
					errorMsg = error.responseJSON.message;
				} else if ( error.statusText ) {
					errorMsg = error.statusText;
				} else if ( error.message ) {
					errorMsg = error.message;
				}
				addLog( 'FATAL ERROR: ' + errorMsg, '#f44747' );
			} finally {
				$btn.prop( 'disabled', false ).html( origText );
				addLog( 'Process finished.', '#569cd6' );
			}
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

		try {
			initSchemaPreview();
		} catch ( e ) {
			console.error( 'MeowSEO Schema Preview Initialization Error:', e );
		}

		try {
			initAiWriter();
		} catch ( e ) {
			console.error( 'MeowSEO AI Writer Initialization Error:', e );
		}

		try {
			initRepeaters();
		} catch ( e ) {
			console.error( 'MeowSEO Repeaters Initialization Error:', e );
		}

		try {
			initContentTypeToggle();
		} catch ( e ) {
			console.error( 'MeowSEO Content Type Toggle Initialization Error:', e );
		}

	} );

} )( jQuery );
