<?php
/**
 * Step 5: SEO Defaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sitemap_enabled = $this->options->get( 'sitemap_enabled', true );
$index_categories = $this->options->get( 'robots_category', array( 'noindex' => false ) )['noindex'] === false;

?>

<h2><?php esc_html_e( 'SEO Defaults', 'meowseo' ); ?></h2>
<p><?php esc_html_e( 'Configure the foundational SEO settings for your content.', 'meowseo' ); ?></p>

<div class="seo-defaults-form" style="display:flex; flex-direction:column; gap:25px;">
	<div class="form-group" style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:var(--meow-bg); border-radius:8px;">
		<div>
			<h4 style="margin:0;"><?php esc_html_e( 'XML Sitemaps', 'meowseo' ); ?></h4>
			<p style="margin:5px 0 0 0; font-size:12px;"><?php esc_html_e( 'Help search engines find and index your content.', 'meowseo' ); ?></p>
		</div>
		<input type="checkbox" name="sitemap_enabled" value="1" <?php checked( $sitemap_enabled ); ?>>
	</div>

	<div class="form-group" style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:var(--meow-bg); border-radius:8px;">
		<div>
			<h4 style="margin:0;"><?php esc_html_e( 'Index Categories', 'meowseo' ); ?></h4>
			<p style="margin:5px 0 0 0; font-size:12px;"><?php esc_html_e( 'Allow Google to index your category pages.', 'meowseo' ); ?></p>
		</div>
		<input type="checkbox" name="index_categories" value="1" <?php checked( $index_categories ); ?>>
	</div>

	<div class="form-group">
		<label for="separator" style="display:block; margin-bottom:8px;"><?php esc_html_e( 'Title Separator:', 'meowseo' ); ?></label>
		<div class="separator-options" style="display:flex; gap:10px;">
			<?php 
			$separators = array( '-', '–', '—', '|', '•', '»' );
			$current_sep = $this->options->get( 'title_separator', '-' );
			foreach ( $separators as $sep ) : ?>
				<label class="sep-option <?php echo $sep === $current_sep ? 'active' : ''; ?>" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; background:var(--meow-bg); border:1px solid var(--meow-border); border-radius:4px; cursor:pointer;">
					<input type="radio" name="title_separator" value="<?php echo esc_attr( $sep ); ?>" <?php checked( $sep === $current_sep ); ?> style="display:none;">
					<?php echo esc_html( $sep ); ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('.sep-option').on('click', function() {
		$('.sep-option').removeClass('active');
		$(this).addClass('active');
		$(this).find('input').prop('checked', true);
	});
});
</script>
