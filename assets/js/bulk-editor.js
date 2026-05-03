( function ( $ ) {
	'use strict';

	$( document ).ready( function () {

		function initBulkAi() {
			$( '#doaction, #doaction2' ).on( 'click', function ( e ) {
				var actionId = $( this ).attr( 'id' ) === 'doaction' ? 'bulk-action-selector-top' : 'bulk-action-selector-bottom';
				var action = $( '#' + actionId ).val();

				if ( action === 'meowseo_generate_ai_meta' ) {
					e.preventDefault();

					var postIds = [];
					$( 'input[name="post[]"]:checked' ).each( function () {
						postIds.push( $( this ).val() );
					} );

					if ( postIds.length === 0 ) {
						alert( 'Please select at least one post.' );
						return;
					}

					if ( ! confirm( 'Are you sure you want to generate AI meta (Title & Description) for ' + postIds.length + ' post(s)? This may take some time and consume API credits.' ) ) {
						return;
					}

					startBulkAiGeneration( postIds );
				}
			} );
		}

		function startBulkAiGeneration( postIds ) {
			// Create overlay UI
			var $overlay = $( '<div id="meowseo-bulk-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;display:flex;align-items:center;justify-content:center;"></div>' );
			var $modal = $( '<div style="background:#fff;padding:30px;border-radius:8px;max-width:500px;width:100%;text-align:center;box-shadow:0 4px 15px rgba(0,0,0,0.2);"></div>' );
			
			var $title = $( '<h2 style="margin-top:0;margin-bottom:15px;color:#2271b1;">Generating AI Meta...</h2>' );
			var $desc = $( '<p style="margin-bottom:20px;color:#50575e;">' + meowseoBulk.strings.processing + '</p>' );
			
			var $progressContainer = $( '<div style="width:100%;background:#f0f0f1;height:24px;border-radius:12px;overflow:hidden;margin-bottom:15px;position:relative;"></div>' );
			var $progressBar = $( '<div style="width:0%;background:#2271b1;height:100%;transition:width 0.3s ease;"></div>' );
			var $progressText = $( '<div style="position:absolute;top:0;left:0;width:100%;height:100%;line-height:24px;color:#000;font-size:12px;font-weight:bold;">0 / ' + postIds.length + '</div>' );
			
			var $log = $( '<div style="text-align:left;height:100px;overflow-y:auto;background:#f0f0f1;padding:10px;font-family:monospace;font-size:12px;border-radius:4px;color:#3c434a;margin-bottom:20px;"></div>' );
			var $closeBtn = $( '<button type="button" class="button button-primary" style="display:none;">Close & Reload</button>' );

			$progressContainer.append( $progressBar ).append( $progressText );
			$modal.append( $title ).append( $desc ).append( $progressContainer ).append( $log ).append( $closeBtn );
			$overlay.append( $modal );
			$( 'body' ).append( $overlay );

			var total = postIds.length;
			var current = 0;
			var successCount = 0;
			var failCount = 0;

			function processNext() {
				if ( current >= total ) {
					// Done
					$title.text( meowseoBulk.strings.completed ).css( 'color', '#00a32a' );
					$desc.text( 'Successfully generated for ' + successCount + ' posts. Failed: ' + failCount + '.' );
					$closeBtn.show().on( 'click', function() {
						window.location.reload();
					} );
					return;
				}

				var postId = postIds[current];
				var postTitle = $( '#post-' + postId + ' .row-title' ).text() || 'Post ID ' + postId;
				
				$log.append( '<div>[' + (current+1) + '/' + total + '] Processing: ' + postTitle + '...</div>' );
				$log.scrollTop( $log[0].scrollHeight );

				$.ajax( {
					url: meowseoBulk.restUrl + '/ai/generate-all',
					method: 'POST',
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', meowseoBulk.nonce );
						xhr.setRequestHeader( 'Content-Type', 'application/json' );
					},
					data: JSON.stringify( { post_id: parseInt( postId, 10 ) } ),
					success: function ( response ) {
						if ( response && response.success ) {
							$log.append( '<div style="color:green;">✓ Success!</div>' );
							successCount++;
						} else {
							$log.append( '<div style="color:red;">✗ Failed: ' + (response.message || 'Unknown error') + '</div>' );
							failCount++;
						}
					},
					error: function ( xhr, status, error ) {
						var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
						$log.append( '<div style="color:red;">✗ Error: ' + msg + '</div>' );
						failCount++;
					},
					complete: function () {
						current++;
						var percentage = Math.round( ( current / total ) * 100 );
						$progressBar.css( 'width', percentage + '%' );
						$progressText.text( current + ' / ' + total );
						$log.scrollTop( $log[0].scrollHeight );

						// Small delay to avoid hammering server too fast
						setTimeout( processNext, 500 );
					}
				} );
			}

			// Start processing
			processNext();
		}

		initBulkAi();
	} );

} )( jQuery );
