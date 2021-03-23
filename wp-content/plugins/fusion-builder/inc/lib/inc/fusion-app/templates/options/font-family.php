<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<div class="wrapper fusion-builder-font-family fusion-builder-typography">
	<#
	var familyId      = 'fusion_font_family_' + param.param_name,
		familyDefault = 'object' === typeof param.default && 'undefined' !== typeof param.default['font-family'] ? param.default['font-family'] : '';
		familyValue   = 'undefined' !== typeof atts && 'undefined' !== typeof atts.params[ familyId ] ? atts.params[ familyId ] : familyDefault;
	#>
	<div class="font-family">
		<# if ( 'undefined' !== typeof FusionApp ) { #>
			<div class="fusion-skip-init fusion-select-field<?php echo ( is_rtl() ) ? ' fusion-select-field-rtl' : ''; ?>">
				<div class="fusion-select-preview-wrap">
					<span class="fusion-select-preview">
						<# if ( '' !== familyValue ) { #>
							{{ familyValue }}
						<# } else { #>
							<span class="fusion-select-placeholder"><?php esc_attr_e( 'Select Font Family', 'fusion-builder' ); ?></span>
						<# } #>
					</span>
					<div class="fusiona-arrow-down"></div>
				</div>
				<div class="fusion-select-dropdown">
					<div class="fusion-select-search">
						<input type="text" class="fusion-hide-from-atts fusion-dont-update" placeholder="<?php esc_attr_e( 'Search Font Families', 'fusion-builder' ); ?>" />
					</div>
					<div class="fusion-select-options"></div>
				</div>
				<input type="hidden" id="{{{ familyId }}}" name="{{{ familyId }}}" value="{{ familyValue }}" data-default="{{ familyDefault }}" class="input-font_family fusion-select-option-value">
			</div>
		<# } else { #>
			<div class="fusion-loader">
				<div class="fusion-builder-loader">
				</div>
			</div>
			<div class="select_arrow"></div>
			<select id="{{ familyId }}" name="{{ familyId }}" class="input-font_family fusion-select-field fusion-skip-init<?php echo ( is_rtl() ) ? ' fusion-select-field-rtl' : ''; ?>" data-default="{{ familyDefault }}" data-value="{{ familyValue }}"></select>
		<# } #>
	</div>

	<#
	var subsetId      = 'fusion_font_subset_' + param.param_name,
		subsetDefault = 'object' === typeof param.default && 'undefined' !== typeof param.default['font-subset'] ? param.default['font-subset'] : '';
		subsetValue   = 'undefined' !== typeof atts && 'undefined' !== typeof atts.params[ subsetId ] ? atts.params[ subsetId ] : subsetDefault;
	#>
	<div class="subsets hide-on-standard-fonts fusion-subsets-wrapper" style="display:none">
		<h5><?php esc_html_e( 'Subset', 'fusion-builder' ); ?></h5>
		<#
			var subsetLabel = '<?php esc_attr_e( 'Select Font Subset', 'fusion-builder' ); ?>';

			if ( 'string' === typeof subsetValue && '' !== subsetValue ) {
				subsetLabel = subsetValue.replace( 'ext', 'Extended' ).replace( '-', ' ' ).replace( /\w\S*/g, function( txt ) {
					return txt.charAt( 0 ).toUpperCase() + txt.substr( 1 ).toLowerCase();
				} );
			}
		#>
		<div class="fusion-typography-select-wrapper">
			<# if ( 'undefined' !== typeof FusionApp ) { #>
				<select name="{{ subsetId }}" class="input-subsets subset" id="{{ subsetId }}" data-default="{{ subsetDefault }}">
					<option value="" selected disabled hidden>{{ subsetLabel }}</option>
				</select>
				<div class="fusiona-arrow-down"></div>
			<# } else { #>
				<div class="select_arrow"></div>
				<select id="{{ subsetId }}" name="{{ subsetId }}" class="input-subsets fusion-select-field< fusion-skip-init?php echo ( is_rtl() ) ? ' fusion-select-field-rtl' : ''; ?>" data-default="{{ subsetDefault }}" data-value="{{ subsetValue }}"></select>
			<# } #>
		</div>
	</div>

	<#
	var variantId      = 'fusion_font_variant_' + param.param_name,
		variantDefault = 'object' === typeof param.default && 'undefined' !== typeof param.default['font-variant'] ? param.default['font-variant'] : '';
		variantValue   = 'undefined' !== typeof atts && 'undefined' !== typeof atts.params[ variantId ] ? atts.params[ variantId ] : variantDefault;
	#>
	<div class="variant fusion-variant-wrapper" style="display:none">
		<h5><?php esc_html_e( 'Variant', 'fusion-builder' ); ?></h5>
		<div class="fusion-typography-select-wrapper">
			<# if ( 'undefined' !== typeof FusionApp ) { #>
				<select name="{{ variantId }}" class="input-variant variant" id="{{ variantId }}" data-default="{{ variantDefault }}"></select>
				<div class="fusiona-arrow-down"></div>
			<# } else { #>
				<div class="select_arrow"></div>
				<select id="{{ variantId }}" name="{{ variantId }}" class="input-variant fusion-select-field fusion-skip-init<?php echo ( is_rtl() ) ? ' fusion-select-field-rtl' : ''; ?>" data-default="{{ variantDefault }}" data-value="{{ variantValue }}" ></select>
			<# } #>
		</div>
	</div>

</div>
