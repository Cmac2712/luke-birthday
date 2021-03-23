<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/template" id="fusion-builder-tab-template">
	<div class="fusion-panel-section-header-wrapper">
		<a href="#" class="fusion-builder-go-back" title="<?php esc_attr_e( 'Back', 'Avada' ); ?>" aria-label="<?php esc_attr_e( 'Back', 'Avada' ); ?>">
			<svg version="1.1" width="18" height="18" viewBox="0 0 32 32"><path d="M12.586 27.414l-10-10c-0.781-0.781-0.781-2.047 0-2.828l10-10c0.781-0.781 2.047-0.781 2.828 0s0.781 2.047 0 2.828l-6.586 6.586h19.172c1.105 0 2 0.895 2 2s-0.895 2-2 2h-19.172l6.586 6.586c0.39 0.39 0.586 0.902 0.586 1.414s-0.195 1.024-0.586 1.414c-0.781 0.781-2.047 0.781-2.828 0z"></path></svg>
		</a>
		<span class="fusion-builder-tab-section-title">{{{ label }}}</span>
	</div>
	<?php fusion_customizer_front_options_loop( 'fields' ); ?>
</script>
