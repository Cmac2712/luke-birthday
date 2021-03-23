<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/template" id="fusion-builder-container-library-template">
	<div class="fusion-builder-modal-top-container">
		<div class="fusion-builder-modal-search">
			<label for="fusion-modal-search" class="fusiona-search"><span><?php esc_html_e( 'Search', 'fusion-builder' ); ?></span></label>
			<input type="text" id="fusion-modal-search" class="fusion-elements-filter" placeholder="{{ fusionBuilderText.search_containers }}" />
		</div>

		<ul class="fusion-tabs-menu">
			<li><a href="#default-columns">{{ fusionBuilderText.builder_sections }}</a></li>
			<li><a href="#custom-sections">{{ fusionBuilderText.library_sections }}</a></li>
			<li><a href="#misc">{{ fusionBuilderText.library_misc }}</a></li>
		</ul>
	</div>

	<div class="fusion-builder-main-settings fusion-builder-main-settings-full">
		<div class="fusion-builder-column-layouts-container">
			<div class="fusion-tabs">
				<div id="default-columns" class="fusion-tab-content">
					<?php echo fusion_builder_column_layouts( 'container' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>

				<div id="custom-sections" class="fusion-tab-content">
					<div id="fusion-loader"><span class="fusion-builder-loader"></span></div>
				</div>
				<div id="misc" class="fusion-tab-content">
					<div class="fusion-builder-layouts-header">
						<div class="fusion-builder-layouts-header-info">
							<h2>{{ fusionBuilderText.special_title }}</h2>
							<span class="fusion-builder-layout-info">{{ fusionBuilderText.special_description }}</span>
						</div>
					</div>
					<ul class="fusion-builder-all-modules fusion-builder-special-list">
						<li class="fusion-builder-section-next-page">
							<h4 class="fusion_module_title">{{ fusionBuilderText.nextpage }}</h4>
						</li>
						<# for ( var i = 0; i < 16; i++ ) { #>
							<li class="spacer fusion-builder-element"></li>
						<# } #>
					</ul>
				</div>

			</div>
		</div>
	</div>

</script>
