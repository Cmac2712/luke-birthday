<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-builder-block-module-settings-chart-template">
<#
	var sidebarEditing = 'dialog' !== FusionApp.preferencesData.editing_mode && 'generated_element' !== atts.type ? true : false;
#>
	<div class="fusion-builder-modal-top-container">
	<# if ( sidebarEditing ) { #>
		<div class="ui-dialog-titlebar">

			<h2>
				{{{ atts.title }}}
			</h2>

			<div class="fusion-utility-menu-wrap">
				<span class="fusion-utility-menu fusiona-ellipsis"></span>
			</div>
			<button id="fusion-close-element-settings" type="button" class="fusiona-close-fb" aria-label="Close" role="button" title="Close">
		</div>
	<# } #>
		<ul class="fusion-tabs-menu">
			<li class=""><a href="#table">{{ fusionBuilderText.chart }}</a></li>
			<li class=""><a href="#table-options">{{ fusionBuilderText.chart_options }}</a></li>
		</ul>
	</div>

	<div class="fusion-builder-main-settings <# if ( sidebarEditing ) { #>fusion-builder-customizer-settings<# } #> fusion-builder-main-settings-full has-group-options">
		<div class="fusion-tabs">

			<div id="table" class="fusion-tab-content">

				<div class="fusion-table-builder-chart">
					<ul class="fusion-builder-module-settings">
						<li class="fusion-builder-option custom">
							<div class="important-description">
								{{{ fusionBuilderText.chart_bars_note }}}
							</div>
						</li>
					</ul>
				</div>

				<div class="fusion-table-builder-chart">

					<?php fusion_element_options_loop( 'atts.frontOptions' ); ?>

				</div>

				<div id="fusion-table-builder-chart-edit-table" class="fusion-table-builder-chart">
					<ul class="fusion-builder-module-settings">
						<li class="fusion-builder-option">
							{{ fusionBuilderText.chart_table_button_desc }}
							<a href="#" class="fusion-builder-chart-button fusion-chart-edit-table">{{ fusionBuilderText.chart_table_button_text }}</a>
						</li>
					</ul>
				</div>

			</div>

			<div id="table-options" class="fusion-tab-content">

				<?php fusion_element_options_loop( 'atts.chartOptions' ); ?>

			</div>

		</div>

	</div>

</script>
