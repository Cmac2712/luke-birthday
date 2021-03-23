<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/template" id="fusion-builder-inner-column-template">
	<div class="fusion-droppable fusion-droppable-vertical target-before fusion-nested-column-target"></div>
	<div class="fusion-builder-module-controls-container fusion-builder-module-controls-type-column fusion-builder-module-controls-type-column-nested">
		<div class="column-sizes">
			<h4>{{ fusionBuilderText.columns }}</h4>
			<div class="column-size column-size-1_6" data-column-size="1_6">1/6</div>
			<div class="column-size column-size-1_5" data-column-size="1_5">1/5</div>
			<div class="column-size column-size-1_4" data-column-size="1_4">1/4</div>
			<div class="column-size column-size-1_3" data-column-size="1_3">1/3</div>
			<div class="column-size column-size-2_5" data-column-size="2_5">2/5</div>
			<div class="column-size column-size-1_2" data-column-size="1_2">1/2</div>
			<div class="column-size column-size-3_5" data-column-size="3_5">3/5</div>
			<div class="column-size column-size-2_3" data-column-size="2_3">2/3</div>
			<div class="column-size column-size-3_4" data-column-size="3_4">3/4</div>
			<div class="column-size column-size-4_5" data-column-size="4_5">4/5</div>
			<div class="column-size column-size-5_6" data-column-size="5_6">5/6</div>
			<div class="column-size column-size-1_1" data-column-size="1_1">1/1</div>
		</div>
		<div class="fusion-builder-controls fusion-builder-module-controls fusion-builder-nested-column-controls">
			<a href="#" class="fusion-builder-settings-column fusion-builder-module-control"><span class="fusiona-pen"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Column Options', 'fusion-builder' ); ?></span></span></a>
			<a href="#" class="fusion-builder-row-add-child fusion-builder-module-control"><span class="fusiona-add-columns"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Add Columns', 'fusion-builder' ); ?></span></span></a>
			<a href="#" class="fusion-builder-column-size fusion-builder-module-control"><span class="fusion-column-size-label">{{{ layout }}}</span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Column Size', 'fusion-builder' ); ?></span></span></a>
			<a href="#" class="fusion-builder-column-clone fusion-builder-module-control"><span class="fusiona-file-add"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Clone Column', 'fusion-builder' ); ?></span></span></a>
			<a href="#" class="fusion-builder-column-remove fusion-builder-module-control"><span class="fusiona-trash-o"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Delete Column', 'fusion-builder' ); ?></span></span></a>
			<a href="#" class="fusion-builder-column-drag fusion-builder-module-control"><span class="fusiona-icon-move"></span><span class="fusion-column-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Drag Column', 'fusion-builder' ); ?></span></span></a>
		</div>
	</div>
	{{{ style }}}
	<#
	if ( ( 'none' === hover_type && '' === link ) || ( '' === hover_type && '' === link ) ) {
		// Background color fallback for IE and Edge.
		var additional_bg_image_div = '';
		if ( cssua.ua.ie || cssua.ua.edge ) {
			additional_bg_image_div = '<div class="' + wrapper_classes + '" style="content:\'\';z-index:-1;position:absolute;top:0;right:0;bottom:0;left:0;' + background_image_style + '" data-bg-url="' + background_image + '"></div>';
		}#>
		<div class="fusion-column-wrapper-live-{{{ cid }}} {{ wrapper_classes }}" style="{{{ wrapper_style_bg }}}" data-bg-url="{{{ background_image }}}">
			{{{ inner_content }}}
			{{{ additional_bg_image_div }}}
		</div>
		<a href="#" class="fusion-builder-add-element fusion-builder-module-control"><span class="fusiona-plus"></span><span class="fusion-column-tooltip">{{ fusionBuilderText.add_element }}</span></a>
	<#
	} else {

		// Background color fallback for IE and Edge.
		additional_bg_color_span = '';
		if ( '' !== background_color_style && ( cssua.ua.ie || cssua.ua.edge ) ) {
			additional_bg_color_span = '<span class="fusion-column-inner-bg-image" style="' + background_color_style + '"></span>';
		}#>
		<div class="fusion-column-wrapper-live-{{{ cid }}} {{ wrapper_classes }}" data-bg-url="{{{ background_image }}}">
			{{{ inner_content }}}
		</div>
		{{{ liftUpStyleTag }}}
		<span class="fusion-column-inner-bg hover-type-{{ hover_type }}" {{{ innerBgStyle }}}>
			<a {{{ href_link }}}>
				<span class="fusion-column-inner-bg-image" style="{{{ wrapper_style_bg }}}"></span>
				{{{ additional_bg_color_span }}}
			</a>
		</span>
		<a href="#" class="fusion-builder-add-element fusion-builder-module-control"><span class="fusiona-plus"></span><span class="fusion-column-tooltip">{{ fusionBuilderText.add_element }}</span></a>
	<#
	}
	#>

	<div class="fusion-column-margin-top fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>
	<div class="fusion-column-margin-bottom fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>

	<div class="fusion-column-padding-top fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>
	<div class="fusion-column-padding-right fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>
	<div class="fusion-column-padding-bottom fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>
	<div class="fusion-column-padding-left fusion-element-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>

	<div class="fusion-column-spacing">
		<div class="fusion-spacing-value">
			<div class="fusion-spacing-tooltip"></div>
		</div>
	</div>
	<div class="fusion-droppable fusion-droppable-vertical target-after fusion-nested-column-target"></div>
</script>
