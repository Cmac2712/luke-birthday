<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/template" id="fusion-builder-container-template">
	<div class="fusion-droppable fusion-droppable-horizontal target-before fusion-container-target"></div>
	<# if ( '' !== menu_anchor ) { #> <div id="{{ menu_anchor }}"> <# } #>

	<div class="fusion-builder-section-header">
		<input type="text" class="fusion-builder-section-name" name="admin_label" placeholder="Container" value="{{ admin_label }}">
		<div class="fusion-builder-module-controls-container fusion-builder-module-controls-type-container">
			<div class="fusion-builder-controls fusion-builder-module-controls">
				<a href="#" class="fusion-builder-container-settings fusion-builder-module-control"><span class="fusiona-pen"></span></a>
				<a href="#" class="fusion-builder-container-clone fusion-builder-module-control"><span class="fusiona-file-add"></span></a>
				<a href="#" class="fusion-builder-container-save fusion-builder-module-control" data-focus="#fusion-builder-save-element-input" data-target="#fusion-builder-layouts-sections" ><span class="fusiona-drive"></span></a>
				<a href="#" class="fusion-builder-container-remove fusion-builder-module-control"><span class="fusiona-trash-o"></span></a>
				<a href="#" class="fusion-builder-container-drag fusion-builder-module-control"><span class="fusiona-icon-move"></span></a>
				<a href="#" class="fusion-builder-toggle fusion-builder-module-control" title="{{ fusionBuilderText.click_to_toggle }}"><span class="fusiona-caret-up"></span></a>
			</div>
		</div>
	</div>
	<div class="fusion-builder-wireframe-utility-toolbar">
	<# if ( 'undefined' !== typeof isGlobal && 'yes' === isGlobal ) { #>
		<a href="#" class="fusion-builder-container-global fusion-builder-module-control fusion-builder-unglobal-tooltip" data-cid={{cid}}><span class="fusiona-globe"></span><span class="fusion-container-tooltip">{{{ fusionBuilderText.global_container }}}</span></a>
	<# } #>
	<# if ( 'published_until' === status || 'publish_after' === status ) { #>
		<a href="#" class="fusion-builder-container-scheduled fusion-builder-module-control fusion-builder-publish-tooltip" data-cid={{cid}}><span class="fusiona-calendar-plus-o"></span><span class="fusion-container-tooltip">{{ fusionBuilderText.container_scheduled }}<br>{{ fusionBuilderText.container_publish }}</span></a>
	<# } #>
	<# if ( 'draft' === status) { #>
		<a href="#" class="fusion-builder-container-draft fusion-builder-module-control fusion-builder-publish-tooltip" data-cid={{cid}}><span class="fusiona-calendar-alt-regular"></span><span class="fusion-container-tooltip">{{ fusionBuilderText.container_draft }}<br>{{ fusionBuilderText.container_publish }}</span></a>
	<# } #>
	</div>

	<div class="fusion-spacing-wrapper">
		<div class="fusion-container-margin-top fusion-container-spacing {{{ topOverlap }}}">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
		<div class="fusion-container-margin-bottom fusion-container-spacing {{{ bottomOverlap }}}">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
		<div class="fusion-container-padding-top fusion-container-spacing {{{ topOverlap }}}">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
		<div class="fusion-container-padding-right fusion-container-spacing">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
		<div class="fusion-container-padding-bottom fusion-container-spacing {{{ bottomOverlap }}}">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
		<div class="fusion-container-padding-left fusion-container-spacing">
			<div class="fusion-spacing-value">
				<div class="fusion-spacing-tooltip"></div>
			</div>
		</div>
	</div>

	<div class="fusion-builder-module-controls-container-wrapper">
		<div class="fusion-builder-module-controls-container fusion-builder-module-controls-type-container">
			<div class="fusion-builder-controls fusion-builder-module-controls fusion-builder-container-controls">
				<a href="#" class="fusion-builder-container-drag fusion-builder-module-control"><span class="fusiona-icon-move"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Drag Container', 'fusion-builder' ); ?></span></span></a>
				<a href="#" class="fusion-builder-container-remove fusion-builder-module-control"><span class="fusiona-trash-o"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Delete Container', 'fusion-builder' ); ?></span></span></a>
				<a href="#" class="fusion-builder-container-save fusion-builder-module-control" data-focus="#fusion-builder-save-element-input" data-target="#fusion-builder-layouts-sections" ><span class="fusiona-drive"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Save Container', 'fusion-builder' ); ?></span></span></a>
				<a href="#" class="fusion-builder-container-clone fusion-builder-module-control"><span class="fusiona-file-add"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Clone Container', 'fusion-builder' ); ?></span></span></a>
				<a href="#" class="fusion-builder-container-settings fusion-builder-module-control"><span class="fusiona-pen"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Container Options', 'fusion-builder' ); ?></span></span></a>
				<a href="#" class="fusion-builder-container-add fusion-builder-module-control"><span class="fusiona-add-container"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text"><?php esc_html_e( 'Add Container', 'fusion-builder' ); ?></span></span></a>
			</div>
			<# if ( 'undefined' !== typeof isGlobal && 'yes' === isGlobal ) { #>
				<a href="#" class="fusion-builder-container-global fusion-builder-module-control fusion-builder-unglobal-tooltip" data-cid={{cid}}><span class="fusiona-globe"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text">{{{ fusionBuilderText.global_container }}}</span></span></a>
			<# } #>
			<# if ( 'published_until' === status || 'publish_after' === status ) { #>
				<a href="#" class="fusion-builder-container-scheduled fusion-builder-module-control fusion-builder-publish-tooltip" data-cid={{cid}}><span class="fusiona-calendar-plus-o"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text">{{ fusionBuilderText.container_scheduled }}<br>{{ fusionBuilderText.container_publish }}</span></span></a>
			<# } #>
			<# if ( 'draft' === status) { #>
				<a href="#" class="fusion-builder-container-draft fusion-builder-module-control fusion-builder-publish-tooltip" data-cid={{cid}}><span class="fusiona-calendar-alt-regular"></span><span class="fusion-container-tooltip"><span class="fusion-tooltip-text">{{ fusionBuilderText.container_draft }}<br>{{ fusionBuilderText.container_publish }}</span></span></a>
			<# } #>
		</div>
	</div>

	<# if ( '' !== styleBlock ) { #>
	{{{ styleBlock }}}
	<# } #>

	{{{ parallax_helper }}}
	<div id="{{ id }}" class="{{ classes }}" {{{ style }}}>
		{{{ outer_html }}}

		<# if ( 'yes' == hundred_percent_height && 'yes' == hundred_percent_height_center_content ) {
			#> <div class="fusion-fullwidth-center-content {{ centerContentClass }}"> <#
		} #>
		<div class="fusion-builder-container-content" style="{{{ content_styles }}}"></div>
		<div class="clearfix"></div>
		<a href="#" class="fusion-builder-container-add fusion-builder-module-control"><span class="fusiona-plus"></span> <?php esc_html_e( 'Add Container', 'fusion-builder' ); ?></a>

		<# if ( 'yes' == hundred_percent_height && 'yes' == hundred_percent_height_center_content ) { #> </div> <# } #>
		<# if ( 'yes' == hundred_percent_height ) { #>
			<div class="hundred-percent-height fusion-outline-helper<# if ( 'yes' == hundred_percent_height_center_content ) { #> fusion-centered-content<# } #>" style="{{ contentStyle }}"></div>
			<# if ( 'yes' == hundred_percent_height_scroll ) { #>
				<nav class="fusion-scroll-section-nav {{ scrollPosition }}">
					<ul>
						<li><a href="#" class="fusion-scroll-section-link"><span class="fusion-scroll-section-link-bullet"></span></a></li>
					</ul>
					<span class="fusion-panel-shortcut" data-fusion-option="container_scroll_nav_bg_color"><span class="fusiona-cog"></span></span>
				</nav>
			<# } #>
		<# } #>
	</div>

	<# if ( '' !== menu_anchor ) {#> </div> <# } #>
	<div class="fusion-droppable fusion-droppable-horizontal target-after fusion-container-target"></div>
</script>
