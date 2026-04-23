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

			// Get current content from TinyMCE or textarea
			var content = '';
			if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
				content = tinyMCE.activeEditor.getContent();
			} else {
				content = $( '#content' ).val() || '';
			}

			$.ajax( {
				url: meowseoClassic.restUrl + '/analysis/' + meowseoClassic.postId,
				method: 'GET',
				data: {
					content: content,
					focus_keyword: $( '#meowseo_focus_keyword' ).val() || ''
				},
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

					addLog( '[Phase 4/4] Writing conclusion...', '#dcdcaa' );
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
			initAiWriter();
		} catch ( e ) {
			console.error( 'MeowSEO AI Writer Initialization Error:', e );
		}
	} );

} )( jQuery );
