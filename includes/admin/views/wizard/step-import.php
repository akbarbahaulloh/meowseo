<?php
/**
 * Step 3: Import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$import_manager = $this->module_manager->get_module( 'import' )->get_import_manager();
$importers = $import_manager->get_available_importers();
$detected_importer = null;

foreach ( $importers as $importer ) {
	if ( $importer->is_plugin_installed() ) {
		$detected_importer = $importer;
		break;
	}
}

?>

<h2><?php esc_html_e( 'Import SEO Data', 'meowseo' ); ?></h2>
<p><?php esc_html_e( 'If you were using another SEO plugin, you can import your settings and metadata directly into MeowSEO.', 'meowseo' ); ?></p>

<?php if ( $detected_importer ) : ?>
	<div class="detected-importer" style="padding:25px; background:var(--meow-bg); border:1px solid var(--meow-primary); border-radius:12px; text-align:center;">
		<h3 style="margin-top:0;"><?php printf( __( 'Detected: %s', 'meowseo' ), $detected_importer->get_plugin_name() ); ?></h3>
		<p><?php esc_html_e( 'We found existing SEO data. Would you like to import it now? This will migrate your titles, descriptions, and basic settings.', 'meowseo' ); ?></p>
		
		<div class="import-actions" style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
			<button type="button" id="meowseo-start-import" class="button button-secondary" data-plugin="<?php echo esc_attr( $detected_importer->get_plugin_slug() ); ?>">
				<?php esc_html_e( 'Start Import Now', 'meowseo' ); ?>
			</button>
			<div id="import-progress" style="display:none; margin-top:15px;">
				<div class="progress-bar-container" style="height:10px; background:var(--meow-border); border-radius:5px; overflow:hidden;">
					<div id="import-bar" class="progress-bar" style="width:0%; height:100%; background:var(--meow-primary); transition:width 0.3s ease;"></div>
				</div>
				<p id="import-status" style="font-size:12px; margin-top:5px; color:var(--meow-text-muted);"></p>
			</div>
		</div>
	</div>
<?php else : ?>
	<div class="no-importer" style="padding:25px; background:rgba(148, 163, 184, 0.1); border:1px dashed var(--meow-border); border-radius:12px; text-align:center;">
		<p style="margin:0;"><?php esc_html_e( 'No other SEO plugins detected. You can proceed with a fresh configuration.', 'meowseo' ); ?></p>
	</div>
<?php endif; ?>
