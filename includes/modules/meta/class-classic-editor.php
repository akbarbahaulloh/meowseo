<?php
/**
 * Classic Editor Meta Box
 *
 * Renders and saves MeowSEO fields in the WordPress Classic Editor.
 *
 * @package MeowSEO
 * @subpackage Modules\Meta
 */

namespace MeowSEO\Modules\Meta;

/**
 * Class Classic_Editor
 *
 * Adds a MeowSEO meta box to the classic post editor with tabbed UI,
 * character counters, SERP preview, social fields, schema fields, and
 * AI generation support.
 */
class Classic_Editor {

	const NONCE_ACTION = 'meowseo_classic_editor_save';
	const NONCE_FIELD  = 'meowseo_classic_editor_nonce';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Enqueue classic editor JS and CSS on post edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_editor_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'meowseo-classic-editor',
			MEOWSEO_URL . 'assets/css/classic-editor.css',
			array(),
			MEOWSEO_VERSION
		);

		wp_enqueue_script(
			'meowseo-classic-editor',
			MEOWSEO_URL . 'assets/js/classic-editor.js',
			array( 'jquery' ),
			MEOWSEO_VERSION,
			true
		);

		$post_id = get_the_ID();

		wp_localize_script(
			'meowseo-classic-editor',
			'meowseoClassic',
			array(
				'postId'      => $post_id,
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'restUrl'     => rest_url( 'meowseo/v1' ),
				'postTitle'   => $post_id ? get_the_title( $post_id ) : '',
				'postExcerpt' => $post_id ? get_the_excerpt( $post_id ) : '',
				'siteUrl'     => home_url(),
				'labels'      => array(
					'question'  => __( 'Question', 'meowseo' ),
					'answer'    => __( 'Answer', 'meowseo' ),
					'stepName'  => __( 'Step Name', 'meowseo' ),
					'stepText'  => __( 'Step Text', 'meowseo' ),
					'remove'    => __( 'Remove', 'meowseo' ),
					'analyzing' => __( 'Running analysis…', 'meowseo' ),
				),
			)
		);
	}

	/**
	 * Register the meta box for all public post types.
	 */
	public function register_meta_box(): void {
		$post_types = get_post_types( array( 'public' => true ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'meowseo-meta-box',
				'MeowSEO',
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		// Fetch all meta.
		$title               = (string) get_post_meta( $post->ID, '_meowseo_title', true );
		$description         = (string) get_post_meta( $post->ID, '_meowseo_description', true );
		$focus_keyword       = (string) get_post_meta( $post->ID, '_meowseo_focus_keyword', true );
		$secondary_keywords_raw = (string) get_post_meta( $post->ID, '_meowseo_secondary_keywords', true );
		$secondary_keywords  = $secondary_keywords_raw ? json_decode( $secondary_keywords_raw, true ) : array();
		if ( ! is_array( $secondary_keywords ) ) {
			$secondary_keywords = array();
		}
		$sk_1 = $secondary_keywords[0] ?? '';
		$sk_2 = $secondary_keywords[1] ?? '';
		$sk_3 = $secondary_keywords[2] ?? '';
		$lsi_keywords        = (string) get_post_meta( $post->ID, '_meowseo_lsi_keywords', true );
		$direct_answer       = (string) get_post_meta( $post->ID, '_meowseo_direct_answer', true );
		$canonical           = (string) get_post_meta( $post->ID, '_meowseo_canonical', true );
		$noindex             = (bool) get_post_meta( $post->ID, '_meowseo_robots_noindex', true );
		$nofollow            = (bool) get_post_meta( $post->ID, '_meowseo_robots_nofollow', true );
		$og_title            = (string) get_post_meta( $post->ID, '_meowseo_og_title', true );
		$og_desc             = (string) get_post_meta( $post->ID, '_meowseo_og_description', true );
		$og_image_id         = (int) get_post_meta( $post->ID, '_meowseo_og_image_id', true );
		$twitter_title       = (string) get_post_meta( $post->ID, '_meowseo_twitter_title', true );
		$twitter_desc        = (string) get_post_meta( $post->ID, '_meowseo_twitter_description', true );
		$twitter_image_id    = (int) get_post_meta( $post->ID, '_meowseo_twitter_image_id', true );
		$use_og_for_twitter  = (bool) get_post_meta( $post->ID, '_meowseo_use_og_for_twitter', true );
		$schema_page_type    = (string) get_post_meta( $post->ID, '_meowseo_schema_page_type', true );
		$schema_article_type = (string) get_post_meta( $post->ID, '_meowseo_schema_article_type', true );
		$schema_config_raw   = (string) get_post_meta( $post->ID, '_meowseo_schema_config', true );
		$schema_config       = $schema_config_raw ? json_decode( $schema_config_raw, true ) : array();
		$gsc_last_submit     = (int) get_post_meta( $post->ID, '_meowseo_gsc_last_submit', true );

		$content_type        = (string) get_post_meta( $post->ID, '_meowseo_content_type', true ) ?: 'article';
		$phone_number        = (string) get_post_meta( $post->ID, '_meowseo_phone_number', true );
		$review_rating       = (string) get_post_meta( $post->ID, '_meowseo_review_rating', true );
		$product_price       = (string) get_post_meta( $post->ID, '_meowseo_product_price', true );

		$og_image_url     = $og_image_id ? wp_get_attachment_image_url( $og_image_id, 'thumbnail' ) : '';
		$twitter_image_url = $twitter_image_id ? wp_get_attachment_image_url( $twitter_image_id, 'thumbnail' ) : '';
		$gsc_date         = $gsc_last_submit ? gmdate( 'Y-m-d H:i', $gsc_last_submit ) : '';

		$post_permalink = get_permalink( $post->ID );
		$parsed_url     = wp_parse_url( $post_permalink );
		$host           = $parsed_url['host'] ?? '';
		$path           = $parsed_url['path'] ?? '';
		// Format: "domain.com › slug" (breadcrumb style)
		$path_parts     = array_filter( explode( '/', trim( $path, '/' ) ) );
		$slug           = ! empty( $path_parts ) ? end( $path_parts ) : '';
		$display_url    = $host . ( $slug ? ' › ' . $slug : '' );
		?>
		<div id="meowseo-tabs">

			<div id="meowseo-tab-nav">
				<button type="button" data-tab="general">General</button>
				<button type="button" data-tab="social">Social</button>
				<button type="button" data-tab="schema">Schema</button>
				<button type="button" data-tab="writer">AI Writer</button>
				<button type="button" data-tab="advanced">Advanced</button>
			</div>

			<!-- ============================================================ -->
			<!-- TAB: General                                                  -->
			<!-- ============================================================ -->
			<div id="meowseo-tab-general" class="meowseo-tab-panel">
				
				<!-- Bulk AI Generation Section -->
				<div class="meowseo-bulk-ai-section" style="background:#f0f6fb;padding:15px;border-radius:6px;margin-bottom:20px;border:1px solid #c3d9ef">
					<div style="margin-bottom:12px">
						<strong style="display:block;margin-bottom:4px;font-size:14px"><?php esc_html_e( 'Bulk AI SEO Generation', 'meowseo' ); ?></strong>
						<p class="description" style="margin:0"><?php esc_html_e( 'Generate all SEO metadata at once using your preferred AI profile and writing style.', 'meowseo' ); ?></p>
					</div>
					
					<div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
						<div>
							<label style="display:block;margin-bottom:5px;font-size:12px;font-weight:600"><?php esc_html_e( 'AI Profile', 'meowseo' ); ?></label>
							<select id="meowseo-bulk-ai-profile" style="width:100%">
								<option value=""><?php esc_html_e( 'Auto (Default)', 'meowseo' ); ?></option>
								<?php
								$meowseo_opt = get_option( 'meowseo_options', array() );
								$ai_profiles = $meowseo_opt['ai_profiles'] ?? array();
								foreach ( $ai_profiles as $profile ) {
									if ( ! empty( $profile['active'] ) ) {
										printf( '<option value="%s">%s (%s)</option>', esc_attr( $profile['id'] ), esc_html( $profile['label'] ), esc_html( $profile['provider'] ) );
									}
								}
								?>
							</select>
						</div>
						<div>
							<label style="display:block;margin-bottom:5px;font-size:12px;font-weight:600"><?php esc_html_e( 'Writing Style', 'meowseo' ); ?></label>
							<select id="meowseo-bulk-ai-style" style="width:100%">
								<option value=""><?php esc_html_e( 'Standard MeowSEO', 'meowseo' ); ?></option>
								<?php
								$writing_styles = $meowseo_opt['writing_styles'] ?? array();
								foreach ( $writing_styles as $style ) {
									printf( '<option value="%s">%s</option>', esc_attr( $style['id'] ), esc_html( $style['label'] ) );
								}
								?>
							</select>
						</div>
						<div>
							<label style="display:block;margin-bottom:5px;font-size:12px;font-weight:600"><?php esc_html_e( 'Image Style', 'meowseo' ); ?></label>
							<select id="meowseo-bulk-ai-image-style" style="width:100%">
								<option value=""><?php esc_html_e( 'Standard MeowSEO', 'meowseo' ); ?></option>
								<?php
								$image_styles = $meowseo_opt['image_styles'] ?? array();
								foreach ( $image_styles as $style ) {
									printf( '<option value="%s">%s</option>', esc_attr( $style['id'] ), esc_html( $style['label'] ) );
								}
								?>
							</select>
						</div>
						<button type="button" class="button button-primary" id="meowseo-bulk-ai-btn" style="height:30px">
							&#10024; <?php esc_html_e( 'Generate All', 'meowseo' ); ?>
						</button>
					</div>

					<div id="meowseo-bulk-ai-log" style="width:100%;background:#1e1e1e;color:#d4d4d4;padding:10px;border-radius:4px;font-family:monospace;font-size:11px;margin-top:10px;display:none;max-height:150px;overflow-y:auto;border:1px solid #333">
						<div style="color:#6a9955">// MeowSEO AI Progress Log</div>
					</div>
				</div>

				<!-- SERP Preview -->
				<div class="meowseo-serp-preview">
					<div class="serp-label">Search Preview</div>
					<div class="serp-url"><span><?php echo esc_html( $display_url ); ?></span></div>
					<div class="serp-title" id="meowseo-serp-title"><?php echo esc_html( $title ?: get_the_title( $post ) ); ?></div>
					<div class="serp-desc"  id="meowseo-serp-desc"><?php echo esc_html( $description ?: get_the_excerpt( $post ) ); ?></div>
				</div>

				<!-- Content Type -->
				<div class="meowseo-field">
					<label for="meowseo_content_type"><?php esc_html_e( 'Content Type', 'meowseo' ); ?></label>
					<select id="meowseo_content_type" name="meowseo_content_type" style="width:100%">
						<option value="article" <?php selected( $content_type, 'article' ); ?>><?php esc_html_e( 'Article', 'meowseo' ); ?></option>
						<option value="promotion" <?php selected( $content_type, 'promotion' ); ?>><?php esc_html_e( 'Promotion', 'meowseo' ); ?></option>
						<option value="review" <?php selected( $content_type, 'review' ); ?>><?php esc_html_e( 'Review', 'meowseo' ); ?></option>
						<option value="news" <?php selected( $content_type, 'news' ); ?>><?php esc_html_e( 'News', 'meowseo' ); ?></option>
						<option value="journal" <?php selected( $content_type, 'journal' ); ?>><?php esc_html_e( 'Journal', 'meowseo' ); ?></option>
						<option value="education" <?php selected( $content_type, 'education' ); ?>><?php esc_html_e( 'Education', 'meowseo' ); ?></option>
					</select>
				</div>

				<!-- Promotion: Phone Number -->
				<div class="meowseo-field meowseo-ct-field" data-ct="promotion" style="display:none">
					<label for="meowseo_phone_number"><?php esc_html_e( 'Phone Number (Will be added to Title)', 'meowseo' ); ?></label>
					<input type="text" id="meowseo_phone_number" name="meowseo_phone_number" value="<?php echo esc_attr( $phone_number ); ?>" placeholder="e.g. 08123456789" />
				</div>

				<!-- Review: Rating & Price -->
				<div class="meowseo-field meowseo-ct-field" data-ct="review" style="display:none">
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
						<div>
							<label for="meowseo_review_rating"><?php esc_html_e( 'Rating (1-5)', 'meowseo' ); ?></label>
							<input type="number" id="meowseo_review_rating" name="meowseo_review_rating" value="<?php echo esc_attr( $review_rating ); ?>" min="1" max="5" step="0.1" />
						</div>
						<div>
							<label for="meowseo_product_price"><?php esc_html_e( 'Product Price', 'meowseo' ); ?></label>
							<input type="text" id="meowseo_product_price" name="meowseo_product_price" value="<?php echo esc_attr( $product_price ); ?>" placeholder="e.g. 50000" />
						</div>
					</div>
				</div>

				<!-- SEO Title -->
				<div class="meowseo-field">
					<label for="meowseo_title">
						<?php esc_html_e( 'SEO Title', 'meowseo' ); ?>
						<span class="meowseo-counter" id="meowseo-title-counter">0 / 60</span>
						<button type="button" class="button button-small meowseo-ai-btn"
							data-action="title" data-target="meowseo_title"
							style="margin-left:auto">&#10024; Generate</button>
					</label>
					<input type="text" id="meowseo_title" name="meowseo_title"
						value="<?php echo esc_attr( $title ); ?>"
						placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>" />
				</div>

				<!-- Meta Description -->
				<div class="meowseo-field">
					<label for="meowseo_description">
						<?php esc_html_e( 'Meta Description', 'meowseo' ); ?>
						<span class="meowseo-counter" id="meowseo-desc-counter">0 / 155</span>
						<button type="button" class="button button-small meowseo-ai-btn"
							data-action="description" data-target="meowseo_description"
							style="margin-left:auto">&#10024; Generate</button>
					</label>
					<textarea id="meowseo_description" name="meowseo_description"
						placeholder="<?php esc_attr_e( 'Write a short description of this page…', 'meowseo' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
				</div>

				<!-- Focus Keyword -->
				<div class="meowseo-field">
					<label for="meowseo_focus_keyword"><?php esc_html_e( 'Focus Keyword', 'meowseo' ); ?></label>
					<input type="text" id="meowseo_focus_keyword" name="meowseo_focus_keyword"
						value="<?php echo esc_attr( $focus_keyword ); ?>" placeholder="<?php esc_attr_e( 'Primary focus keyword...', 'meowseo' ); ?>" />
				</div>

				<!-- Secondary Keywords -->
				<div class="meowseo-field">
					<label><?php esc_html_e( 'Secondary Keywords', 'meowseo' ); ?></label>
					<div style="display:flex;gap:10px;margin-top:5px;">
						<input type="text" id="meowseo_secondary_keyword_1" name="meowseo_secondary_keyword[]"
							value="<?php echo esc_attr( $sk_1 ); ?>" placeholder="<?php esc_attr_e( 'Secondary keyword 1...', 'meowseo' ); ?>" />
						<input type="text" id="meowseo_secondary_keyword_2" name="meowseo_secondary_keyword[]"
							value="<?php echo esc_attr( $sk_2 ); ?>" placeholder="<?php esc_attr_e( 'Secondary keyword 2...', 'meowseo' ); ?>" />
						<input type="text" id="meowseo_secondary_keyword_3" name="meowseo_secondary_keyword[]"
							value="<?php echo esc_attr( $sk_3 ); ?>" placeholder="<?php esc_attr_e( 'Secondary keyword 3...', 'meowseo' ); ?>" />
					</div>
					<p class="description" style="margin-top:5px;font-size:12px;color:#646970;"><?php esc_html_e( 'Target up to 4 keywords per post. Included for free!', 'meowseo' ); ?></p>
				</div>

				<!-- LSI Keywords -->
				<div class="meowseo-field">
					<label for="meowseo_lsi_keywords"><?php esc_html_e( 'LSI Keywords / Synonyms', 'meowseo' ); ?></label>
					<input type="text" id="meowseo_lsi_keywords" name="meowseo_lsi_keywords"
						value="<?php echo esc_attr( $lsi_keywords ); ?>" placeholder="e.g. synonym 1, synonym 2" />
					<p class="description" style="margin-top:5px;font-size:12px;color:#646970;"><?php esc_html_e( 'Separate multiple keywords with commas. Helps with semantic SEO scoring.', 'meowseo' ); ?></p>
				</div>

				<!-- Direct Answer -->
				<div class="meowseo-field">
					<label for="meowseo_direct_answer"><?php esc_html_e( 'Direct Answer (Featured Snippet)', 'meowseo' ); ?></label>
					<textarea id="meowseo_direct_answer" name="meowseo_direct_answer"
						placeholder="<?php esc_attr_e( 'One-sentence answer optimised for featured snippets…', 'meowseo' ); ?>"><?php echo esc_textarea( $direct_answer ); ?></textarea>
				</div>

				<!-- SEO Analysis -->
				<div class="meowseo-section-heading"><?php esc_html_e( 'SEO Analysis', 'meowseo' ); ?></div>
				<div id="meowseo-analysis-panel" style="margin-top:10px">
					<p style="color:#50575e;font-size:13px"><?php esc_html_e( 'Initializing analysis...', 'meowseo' ); ?></p>
				</div>

			</div> <!-- END meowseo-tab-general -->

			<!-- ============================================================ -->
			<!-- TAB: Social                                                   -->
			<!-- ============================================================ -->
			<div id="meowseo-tab-social" class="meowseo-tab-panel">

				<div class="meowseo-section-heading"><?php esc_html_e( 'Facebook / Open Graph', 'meowseo' ); ?></div>

				<div class="meowseo-field">
					<label for="meowseo_og_title"><?php esc_html_e( 'OG Title', 'meowseo' ); ?></label>
					<input type="text" id="meowseo_og_title" name="meowseo_og_title"
						value="<?php echo esc_attr( $og_title ); ?>"
						placeholder="<?php echo esc_attr( $title ?: get_the_title( $post ) ); ?>" />
				</div>

				<div class="meowseo-field">
					<label for="meowseo_og_description"><?php esc_html_e( 'OG Description', 'meowseo' ); ?></label>
					<textarea id="meowseo_og_description" name="meowseo_og_description"
						placeholder="<?php echo esc_attr( $description ); ?>"><?php echo esc_textarea( $og_desc ); ?></textarea>
				</div>

				<div class="meowseo-field">
					<label><?php esc_html_e( 'OG Image', 'meowseo' ); ?></label>
					<div class="meowseo-image-picker">
						<img id="meowseo_og_image-preview"
							src="<?php echo esc_url( $og_image_url ); ?>"
							class="meowseo-image-preview<?php echo $og_image_url ? ' has-image' : ''; ?>" />
						<div class="meowseo-image-actions">
							<input type="hidden" id="meowseo_og_image" name="meowseo_og_image"
								value="<?php echo esc_attr( $og_image_id ?: '' ); ?>" />
							<button type="button" class="button meowseo-pick-image"
								data-target="meowseo_og_image"><?php esc_html_e( 'Select Image', 'meowseo' ); ?></button>
							<button type="button" class="button meowseo-remove-image"
								data-target="meowseo_og_image"><?php esc_html_e( 'Remove', 'meowseo' ); ?></button>
						</div>
					</div>
				</div>

				<div class="meowseo-section-heading"><?php esc_html_e( 'Twitter / X Card', 'meowseo' ); ?></div>

				<div class="meowseo-field">
					<label>
						<input type="checkbox" id="meowseo_use_og_for_twitter" name="meowseo_use_og_for_twitter"
							value="1" <?php checked( $use_og_for_twitter ); ?> />
						<?php esc_html_e( 'Use same data as Facebook', 'meowseo' ); ?>
					</label>
				</div>

				<div id="meowseo-twitter-fields">
					<div class="meowseo-field">
						<label for="meowseo_twitter_title"><?php esc_html_e( 'Twitter Title', 'meowseo' ); ?></label>
						<input type="text" id="meowseo_twitter_title" name="meowseo_twitter_title"
							value="<?php echo esc_attr( $twitter_title ); ?>"
							placeholder="<?php echo esc_attr( $og_title ?: $title ?: get_the_title( $post ) ); ?>" />
					</div>

					<div class="meowseo-field">
						<label for="meowseo_twitter_description"><?php esc_html_e( 'Twitter Description', 'meowseo' ); ?></label>
						<textarea id="meowseo_twitter_description" name="meowseo_twitter_description"
							placeholder="<?php echo esc_attr( $og_desc ?: $description ); ?>"><?php echo esc_textarea( $twitter_desc ); ?></textarea>
					</div>

					<div class="meowseo-field">
						<label><?php esc_html_e( 'Twitter Image', 'meowseo' ); ?></label>
						<div class="meowseo-image-picker">
							<img id="meowseo_twitter_image-preview"
								src="<?php echo esc_url( $twitter_image_url ); ?>"
								class="meowseo-image-preview<?php echo $twitter_image_url ? ' has-image' : ''; ?>" />
							<div class="meowseo-image-actions">
								<input type="hidden" id="meowseo_twitter_image" name="meowseo_twitter_image"
									value="<?php echo esc_attr( $twitter_image_id ?: '' ); ?>" />
								<button type="button" class="button meowseo-pick-image"
									data-target="meowseo_twitter_image"><?php esc_html_e( 'Select Image', 'meowseo' ); ?></button>
								<button type="button" class="button meowseo-remove-image"
									data-target="meowseo_twitter_image"><?php esc_html_e( 'Remove', 'meowseo' ); ?></button>
							</div>
						</div>
					</div>
				</div>

			</div>

			<!-- ============================================================ -->
			<!-- TAB: Schema                                                   -->
			<!-- ============================================================ -->
			<div id="meowseo-tab-schema" class="meowseo-tab-panel">
				<?php wp_nonce_field( 'meowseo_schema_metabox', 'meowseo_schema_nonce' ); ?>
				<div id="meowseo-schema-builder" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
					<div class="meowseo-loading-spinner" style="padding:40px;text-align:center">
						<span class="dashicons dashicons-update spin"></span>
						<p><?php esc_html_e( 'Loading Schema Builder...', 'meowseo' ); ?></p>
					</div>
				</div>
				<p class="description" style="margin-top:15px">
					<?php esc_html_e( 'The visual Schema Builder allows you to add multiple structured data types to your content.', 'meowseo' ); ?>
				</p>
			</div>

			<!-- ============================================================ -->
			<!-- TAB: AI Writer                                                -->
			<!-- ============================================================ -->
			<div id="meowseo-tab-writer" class="meowseo-tab-panel">
				<div class="meowseo-section-heading"><?php esc_html_e( 'Article Writer', 'meowseo' ); ?></div>
				<p class="description"><?php esc_html_e( 'Generate full articles based on your configured Writing Styles.', 'meowseo' ); ?></p>
				
				<div class="meowseo-field">
					<label for="meowseo_writer_style"><?php esc_html_e( 'Writing Style', 'meowseo' ); ?></label>
					<select id="meowseo_writer_style" name="meowseo_writer_style" style="width:100%">
						<?php
						$writing_styles = $meowseo_opt['writing_styles'] ?? array();
						foreach ( $writing_styles as $style ) {
							$mode = $style['mode'] ?? 'advance';
							$mode_label = 'advance' === $mode ? 'Advance' : 'Simple';
							printf( '<option value="%s" data-mode="%s">%s (%s Mode)</option>', esc_attr( $style['id'] ), esc_attr( $mode ), esc_html( $style['label'] ), esc_html( $mode_label ) );
						}
						?>
					</select>
				</div>

				<div class="meowseo-field">
					<label for="meowseo_writer_topic"><?php esc_html_e( 'Topic or Prompt', 'meowseo' ); ?></label>
					<textarea id="meowseo_writer_topic" rows="4" placeholder="<?php esc_attr_e( 'What should the article be about?', 'meowseo' ); ?>"></textarea>
				</div>

				<div class="meowseo-field">
					<button type="button" class="button button-primary" id="meowseo-writer-btn">
						&#10024; <?php esc_html_e( 'Generate Article', 'meowseo' ); ?>
					</button>
				</div>

				<div id="meowseo-writer-log" style="width:100%;background:#1e1e1e;color:#d4d4d4;padding:10px;border-radius:4px;font-family:monospace;font-size:11px;margin-top:10px;display:none;max-height:200px;overflow-y:auto;border:1px solid #333">
					<div style="color:#6a9955">// MeowSEO AI Writer Log</div>
				</div>
			</div>

			<!-- ============================================================ -->
			<!-- TAB: Advanced                                                 -->
			<!-- ============================================================ -->
			<div id="meowseo-tab-advanced" class="meowseo-tab-panel">

				<div class="meowseo-field">
					<label for="meowseo_canonical"><?php esc_html_e( 'Canonical URL', 'meowseo' ); ?></label>
					<input type="url" id="meowseo_canonical" name="meowseo_canonical"
						value="<?php echo esc_attr( $canonical ); ?>"
						placeholder="<?php echo esc_attr( (string) $post_permalink ); ?>" />
				</div>

				<div class="meowseo-field">
					<span style="display:block;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#50575e;margin-bottom:5px"><?php esc_html_e( 'Robots', 'meowseo' ); ?></span>
					<div class="meowseo-robots">
						<label>
							<input type="checkbox" name="meowseo_robots_noindex" value="1" <?php checked( $noindex ); ?> />
							<?php esc_html_e( 'No Index', 'meowseo' ); ?>
						</label>
						<label>
							<input type="checkbox" name="meowseo_robots_nofollow" value="1" <?php checked( $nofollow ); ?> />
							<?php esc_html_e( 'No Follow', 'meowseo' ); ?>
						</label>
					</div>
				</div>

				<div class="meowseo-field" style="margin-top:20px">
					<span style="display:block;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#50575e;margin-bottom:8px"><?php esc_html_e( 'Google Search Console', 'meowseo' ); ?></span>
					<button type="button" class="button" id="meowseo-gsc-submit"><?php esc_html_e( 'Submit to Google', 'meowseo' ); ?></button>
					<span id="meowseo-gsc-status" style="margin-left:10px;font-size:13px;color:#50575e">
						<?php
						if ( $gsc_date ) {
							/* translators: %s: date string */
							printf( esc_html__( 'Last submitted: %s', 'meowseo' ), esc_html( $gsc_date ) );
						} else {
							esc_html_e( 'Never submitted', 'meowseo' );
						}
						?>
					</span>
				</div>

			</div>

		</div><!-- #meowseo-tabs -->

		<?php
	}

	/**
	 * Save meta box data on post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// String fields (text inputs).
		$text_fields = array(
			'meowseo_title'         => '_meowseo_title',
			'meowseo_focus_keyword' => '_meowseo_focus_keyword',
			'meowseo_lsi_keywords'  => '_meowseo_lsi_keywords',
			'meowseo_og_title'      => '_meowseo_og_title',
			'meowseo_twitter_title' => '_meowseo_twitter_title',
		);

		foreach ( $text_fields as $post_key => $meta_key ) {
			$value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}

		// Secondary keywords
		if ( isset( $_POST['meowseo_secondary_keyword'] ) && is_array( $_POST['meowseo_secondary_keyword'] ) ) {
			$s_kws = array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['meowseo_secondary_keyword'] ) ) );
			update_post_meta( $post_id, '_meowseo_secondary_keywords', wp_json_encode( array_values( $s_kws ) ) );
		} else {
			delete_post_meta( $post_id, '_meowseo_secondary_keywords' );
		}

		// New Content Type fields
		$ct_fields = array(
			'meowseo_content_type'  => '_meowseo_content_type',
			'meowseo_phone_number'  => '_meowseo_phone_number',
			'meowseo_review_rating' => '_meowseo_review_rating',
			'meowseo_product_price' => '_meowseo_product_price',
		);
		foreach ( $ct_fields as $post_key => $meta_key ) {
			$value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}

		// Textarea fields.
		$textarea_fields = array(
			'meowseo_description'         => '_meowseo_description',
			'meowseo_direct_answer'       => '_meowseo_direct_answer',
			'meowseo_og_description'      => '_meowseo_og_description',
			'meowseo_twitter_description' => '_meowseo_twitter_description',
		);

		foreach ( $textarea_fields as $post_key => $meta_key ) {
			$value = isset( $_POST[ $post_key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}

		// URL field.
		$canonical = isset( $_POST['meowseo_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['meowseo_canonical'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_canonical', $canonical );

		// Boolean checkboxes.
		update_post_meta( $post_id, '_meowseo_robots_noindex', isset( $_POST['meowseo_robots_noindex'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_meowseo_robots_nofollow', isset( $_POST['meowseo_robots_nofollow'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_meowseo_use_og_for_twitter', isset( $_POST['meowseo_use_og_for_twitter'] ) ? 1 : 0 );

		// Image ID fields (absint).
		$og_image_id      = isset( $_POST['meowseo_og_image'] ) ? absint( $_POST['meowseo_og_image'] ) : 0;
		$twitter_image_id = isset( $_POST['meowseo_twitter_image'] ) ? absint( $_POST['meowseo_twitter_image'] ) : 0;
		update_post_meta( $post_id, '_meowseo_og_image_id', $og_image_id );
		update_post_meta( $post_id, '_meowseo_twitter_image_id', $twitter_image_id );

		// Schema types.
		$schema_page_type = isset( $_POST['meowseo_schema_page_type'] ) ? sanitize_text_field( wp_unslash( $_POST['meowseo_schema_page_type'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_schema_page_type', $schema_page_type );

		$schema_article_type = isset( $_POST['meowseo_schema_article_type'] ) ? sanitize_text_field( wp_unslash( $_POST['meowseo_schema_article_type'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_schema_article_type', $schema_article_type );

		// Schema config JSON (built by JS before submit; stored as-is after decode/re-encode for safety).
		if ( isset( $_POST['meowseo_schema_config'] ) ) {
			$raw    = wp_unslash( $_POST['meowseo_schema_config'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$decoded = json_decode( $raw, true );
			$safe    = $decoded ? wp_json_encode( $decoded ) : '';
			update_post_meta( $post_id, '_meowseo_schema_config', $safe );
		}

		// Review / AggregateRating fields.
		$review_product_name = isset( $_POST['meowseo_review_product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['meowseo_review_product_name'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_review_product_name', $review_product_name );

		$review_rating = isset( $_POST['meowseo_review_rating'] ) ? absint( $_POST['meowseo_review_rating'] ) : 0;
		if ( $review_rating >= 1 && $review_rating <= 5 ) {
			update_post_meta( $post_id, '_meowseo_review_rating', $review_rating );
		} else {
			delete_post_meta( $post_id, '_meowseo_review_rating' );
		}

		$review_count = isset( $_POST['meowseo_review_count'] ) ? absint( $_POST['meowseo_review_count'] ) : 1;
		update_post_meta( $post_id, '_meowseo_review_count', max( 1, $review_count ) );
	}
}
