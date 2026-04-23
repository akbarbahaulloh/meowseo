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
		$schema_type         = (string) get_post_meta( $post->ID, '_meowseo_schema_type', true );
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
						value="<?php echo esc_attr( $focus_keyword ); ?>" />
				</div>

				<!-- Direct Answer -->
				<div class="meowseo-field">
					<label for="meowseo_direct_answer"><?php esc_html_e( 'Direct Answer (Featured Snippet)', 'meowseo' ); ?></label>
					<textarea id="meowseo_direct_answer" name="meowseo_direct_answer"
						placeholder="<?php esc_attr_e( 'One-sentence answer optimised for featured snippets…', 'meowseo' ); ?>"><?php echo esc_textarea( $direct_answer ); ?></textarea>
				</div>

				<!-- SEO Analysis -->
				<div class="meowseo-section-heading"><?php esc_html_e( 'SEO Analysis', 'meowseo' ); ?></div>
				<button type="button" class="button" id="meowseo-run-analysis"><?php esc_html_e( 'Run Analysis', 'meowseo' ); ?></button>
				<div id="meowseo-analysis-panel" style="margin-top:10px">
					<p style="color:#50575e;font-size:13px"><?php esc_html_e( 'Save the post, then click Run Analysis.', 'meowseo' ); ?></p>
				</div>

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

				<div class="meowseo-field">
					<label for="meowseo_schema_type"><?php esc_html_e( 'Schema Type', 'meowseo' ); ?></label>
					<select id="meowseo_schema_type" name="meowseo_schema_type">
						<option value=""><?php esc_html_e( '— None —', 'meowseo' ); ?></option>
						<option value="Article"       <?php selected( $schema_type, 'Article' ); ?>><?php esc_html_e( 'Article', 'meowseo' ); ?></option>
						<option value="FAQPage"       <?php selected( $schema_type, 'FAQPage' ); ?>><?php esc_html_e( 'FAQ Page', 'meowseo' ); ?></option>
						<option value="HowTo"         <?php selected( $schema_type, 'HowTo' ); ?>><?php esc_html_e( 'HowTo', 'meowseo' ); ?></option>
						<option value="LocalBusiness" <?php selected( $schema_type, 'LocalBusiness' ); ?>><?php esc_html_e( 'Local Business', 'meowseo' ); ?></option>
						<option value="Product"       <?php selected( $schema_type, 'Product' ); ?>><?php esc_html_e( 'Product', 'meowseo' ); ?></option>
					</select>
				</div>

				<!-- Article -->
				<div class="meowseo-schema-fields" data-type="Article" style="display:none">
					<div class="meowseo-section-heading"><?php esc_html_e( 'Article', 'meowseo' ); ?></div>
					<div class="meowseo-field">
						<label for="meowseo_schema_article_type"><?php esc_html_e( 'Article Type', 'meowseo' ); ?></label>
						<select id="meowseo_schema_article_type" name="meowseo_schema_article_type">
							<option value="Article"     <?php selected( $schema_config['article_type'] ?? '', 'Article' ); ?>>Article</option>
							<option value="NewsArticle" <?php selected( $schema_config['article_type'] ?? '', 'NewsArticle' ); ?>>NewsArticle</option>
							<option value="BlogPosting" <?php selected( $schema_config['article_type'] ?? '', 'BlogPosting' ); ?>>BlogPosting</option>
						</select>
					</div>
				</div>

				<!-- FAQ -->
				<div class="meowseo-schema-fields" data-type="FAQPage" style="display:none">
					<div class="meowseo-section-heading"><?php esc_html_e( 'FAQ Items', 'meowseo' ); ?></div>
					<div id="meowseo-faq-items">
						<?php
						$faq_items = $schema_config['faq_items'] ?? array();
						foreach ( $faq_items as $i => $item ) :
							?>
							<div class="meowseo-faq-item" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">
								<div class="meowseo-field">
									<label><?php esc_html_e( 'Question', 'meowseo' ); ?></label>
									<input type="text" name="meowseo_faq_question[]"
										value="<?php echo esc_attr( $item['question'] ?? '' ); ?>" />
								</div>
								<div class="meowseo-field">
									<label><?php esc_html_e( 'Answer', 'meowseo' ); ?></label>
									<textarea name="meowseo_faq_answer[]"><?php echo esc_textarea( $item['answer'] ?? '' ); ?></textarea>
								</div>
								<button type="button" class="button meowseo-remove-faq"><?php esc_html_e( 'Remove', 'meowseo' ); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button" id="meowseo-add-faq"><?php esc_html_e( '+ Add Question', 'meowseo' ); ?></button>
				</div>

				<!-- HowTo -->
				<div class="meowseo-schema-fields" data-type="HowTo" style="display:none">
					<div class="meowseo-section-heading"><?php esc_html_e( 'HowTo', 'meowseo' ); ?></div>
					<div class="meowseo-field">
						<label for="meowseo_schema_howto_name"><?php esc_html_e( 'Name', 'meowseo' ); ?></label>
						<input type="text" id="meowseo_schema_howto_name" name="meowseo_schema_howto_name"
							value="<?php echo esc_attr( $schema_config['howto_name'] ?? '' ); ?>" />
					</div>
					<div class="meowseo-field">
						<label for="meowseo_schema_howto_description"><?php esc_html_e( 'Description', 'meowseo' ); ?></label>
						<textarea id="meowseo_schema_howto_description" name="meowseo_schema_howto_description"><?php echo esc_textarea( $schema_config['howto_description'] ?? '' ); ?></textarea>
					</div>
					<div class="meowseo-section-heading"><?php esc_html_e( 'Steps', 'meowseo' ); ?></div>
					<div id="meowseo-howto-steps">
						<?php
						$steps = $schema_config['howto_steps'] ?? array();
						foreach ( $steps as $step ) :
							?>
							<div class="meowseo-howto-step" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">
								<div class="meowseo-field">
									<label><?php esc_html_e( 'Step Name', 'meowseo' ); ?></label>
									<input type="text" name="meowseo_howto_step_name[]"
										value="<?php echo esc_attr( $step['name'] ?? '' ); ?>" />
								</div>
								<div class="meowseo-field">
									<label><?php esc_html_e( 'Step Text', 'meowseo' ); ?></label>
									<textarea name="meowseo_howto_step_text[]"><?php echo esc_textarea( $step['text'] ?? '' ); ?></textarea>
								</div>
								<button type="button" class="button meowseo-remove-step"><?php esc_html_e( 'Remove', 'meowseo' ); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button" id="meowseo-add-step"><?php esc_html_e( '+ Add Step', 'meowseo' ); ?></button>
				</div>

				<!-- LocalBusiness -->
				<div class="meowseo-schema-fields" data-type="LocalBusiness" style="display:none">
					<div class="meowseo-section-heading"><?php esc_html_e( 'Local Business', 'meowseo' ); ?></div>
					<?php
					$lb_fields = array(
						'lb_name'    => __( 'Business Name', 'meowseo' ),
						'lb_type'    => __( 'Business Type', 'meowseo' ),
						'lb_address' => __( 'Address', 'meowseo' ),
						'lb_phone'   => __( 'Phone', 'meowseo' ),
						'lb_hours'   => __( 'Opening Hours (e.g. Mo-Fr 09:00-17:00)', 'meowseo' ),
					);
					foreach ( $lb_fields as $field_key => $label ) :
						?>
						<div class="meowseo-field">
							<label for="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $label ); ?></label>
							<input type="text" id="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"
								name="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"
								value="<?php echo esc_attr( $schema_config[ $field_key ] ?? '' ); ?>" />
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Product -->
				<div class="meowseo-schema-fields" data-type="Product" style="display:none">
					<div class="meowseo-section-heading"><?php esc_html_e( 'Product', 'meowseo' ); ?></div>
					<?php
					$product_fields = array(
						'product_name'         => __( 'Product Name', 'meowseo' ),
						'product_description'  => __( 'Description', 'meowseo' ),
						'product_sku'          => __( 'SKU', 'meowseo' ),
						'product_price'        => __( 'Price', 'meowseo' ),
						'product_currency'     => __( 'Currency (e.g. USD)', 'meowseo' ),
						'product_availability' => __( 'Availability', 'meowseo' ),
					);
					foreach ( $product_fields as $field_key => $label ) :
						?>
						<div class="meowseo-field">
							<label for="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $label ); ?></label>
							<input type="text" id="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"
								name="meowseo_schema_<?php echo esc_attr( $field_key ); ?>"
								value="<?php echo esc_attr( $schema_config[ $field_key ] ?? '' ); ?>" />
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Hidden JSON storage for schema_config -->
				<input type="hidden" id="meowseo_schema_config" name="meowseo_schema_config"
					value="<?php echo esc_attr( $schema_config_raw ); ?>" />

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

		<script>
		// FAQ/HowTo repeater logic (inline — runs before classic-editor.js)
		(function($){
			$(document).on('click', '#meowseo-add-faq', function(){
				$('#meowseo-faq-items').append(
					'<div class="meowseo-faq-item" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">' +
					'<div class="meowseo-field"><label><?php echo esc_js( __( 'Question', 'meowseo' ) ); ?></label>' +
					'<input type="text" name="meowseo_faq_question[]" /></div>' +
					'<div class="meowseo-field"><label><?php echo esc_js( __( 'Answer', 'meowseo' ) ); ?></label>' +
					'<textarea name="meowseo_faq_answer[]"></textarea></div>' +
					'<button type="button" class="button meowseo-remove-faq"><?php echo esc_js( __( 'Remove', 'meowseo' ) ); ?></button>' +
					'</div>'
				);
			});
			$(document).on('click', '.meowseo-remove-faq', function(){ $(this).closest('.meowseo-faq-item').remove(); });

			$(document).on('click', '#meowseo-add-step', function(){
				$('#meowseo-howto-steps').append(
					'<div class="meowseo-howto-step" style="border:1px solid #dcdcde;padding:10px;margin-bottom:8px;border-radius:4px">' +
					'<div class="meowseo-field"><label><?php echo esc_js( __( 'Step Name', 'meowseo' ) ); ?></label>' +
					'<input type="text" name="meowseo_howto_step_name[]" /></div>' +
					'<div class="meowseo-field"><label><?php echo esc_js( __( 'Step Text', 'meowseo' ) ); ?></label>' +
					'<textarea name="meowseo_howto_step_text[]"></textarea></div>' +
					'<button type="button" class="button meowseo-remove-step"><?php echo esc_js( __( 'Remove', 'meowseo' ) ); ?></button>' +
					'</div>'
				);
			});
			$(document).on('click', '.meowseo-remove-step', function(){ $(this).closest('.meowseo-howto-step').remove(); });

			// Build schema_config JSON before form submit
			$('#post').on('submit', function(){
				var type = $('#meowseo_schema_type').val();
				if (!type) { $('#meowseo_schema_config').val(''); return; }

				var config = {};
				if (type === 'Article') {
					config.article_type = $('#meowseo_schema_article_type').val();
				} else if (type === 'FAQPage') {
					config.faq_items = [];
					$('#meowseo-faq-items .meowseo-faq-item').each(function(){
						config.faq_items.push({
							question: $(this).find('[name="meowseo_faq_question[]"]').val(),
							answer:   $(this).find('[name="meowseo_faq_answer[]"]').val()
						});
					});
				} else if (type === 'HowTo') {
					config.howto_name        = $('#meowseo_schema_howto_name').val();
					config.howto_description = $('#meowseo_schema_howto_description').val();
					config.howto_steps = [];
					$('#meowseo-howto-steps .meowseo-howto-step').each(function(){
						config.howto_steps.push({
							name: $(this).find('[name="meowseo_howto_step_name[]"]').val(),
							text: $(this).find('[name="meowseo_howto_step_text[]"]').val()
						});
					});
				} else if (type === 'LocalBusiness') {
					['lb_name','lb_type','lb_address','lb_phone','lb_hours'].forEach(function(k){
						config[k] = $('#meowseo_schema_' + k).val();
					});
				} else if (type === 'Product') {
					['product_name','product_description','product_sku','product_price','product_currency','product_availability'].forEach(function(k){
						config[k] = $('#meowseo_schema_' + k).val();
					});
				}
				$('#meowseo_schema_config').val(JSON.stringify(config));
			});

			// Analysis button
			$(document).on('click', '#meowseo-run-analysis', function(){
				if (typeof meowseoClassic === 'undefined') return;
				var $panel = $('#meowseo-analysis-panel');
				$panel.html('<p style="color:#50575e">Running analysis…</p>');
				$.ajax({
					url: meowseoClassic.restUrl + '/analysis/' + meowseoClassic.postId,
					method: 'GET',
					beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', meowseoClassic.nonce); },
					success: function(data){
						var html = '';
						if (data.seo_score !== undefined) {
							html += '<div style="margin-bottom:10px"><strong>SEO Score: ' + data.seo_score + '</strong></div>';
						}
						if (data.checks && data.checks.length) {
							data.checks.forEach(function(check){
								var color = check.status === 'good' ? '#155724' : (check.status === 'ok' ? '#856404' : '#721c24');
								html += '<div style="margin-bottom:4px;color:' + color + '">&#9679; ' + $('<div>').text(check.message).html() + '</div>';
							});
						} else {
							html = '<p style="color:#50575e;font-size:13px">No analysis data. Save the post first.</p>';
						}
						$panel.html(html);
					},
					error: function(){
						$panel.html('<p style="color:#721c24">Analysis failed. Save the post first.</p>');
					}
				});
			});

			// Content Type fields toggle
			$('#meowseo_content_type').on('change', function(){
				var val = $(this).val();
				$('.meowseo-ct-field').hide();
				$('.meowseo-ct-field[data-ct="' + val + '"]').show();
			}).trigger('change');
		})(jQuery);
		</script>
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
			'meowseo_og_title'      => '_meowseo_og_title',
			'meowseo_twitter_title' => '_meowseo_twitter_title',
		);

		foreach ( $text_fields as $post_key => $meta_key ) {
			$value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
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

		// Schema type.
		$schema_type = isset( $_POST['meowseo_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['meowseo_schema_type'] ) ) : '';
		update_post_meta( $post_id, '_meowseo_schema_type', $schema_type );

		// Schema config JSON (built by JS before submit; stored as-is after decode/re-encode for safety).
		if ( isset( $_POST['meowseo_schema_config'] ) ) {
			$raw    = wp_unslash( $_POST['meowseo_schema_config'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$decoded = json_decode( $raw, true );
			$safe    = $decoded ? wp_json_encode( $decoded ) : '';
			update_post_meta( $post_id, '_meowseo_schema_config', $safe );
		}
	}
}
