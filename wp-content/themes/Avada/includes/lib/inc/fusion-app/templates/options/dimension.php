<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<#
var valueCheck = 'undefined' === typeof param.param_name ? option_value : param.value,
	isFrontEnd = jQuery( 'body' ).hasClass( 'fusion-builder-live' ),
	topLabel, rightLabel, bottomLabel, leftLabel, numberOfDimensions,
	borderRadius = 'border_radius' === param.param_name || 'border_radius' === param.type ? true : false;

if ( 'object' === typeof param.value && 'string' === typeof valueCheck ) {
	valueCheck = param.value;
}

if ( borderRadius ) {
	topLabel     = fusionBuilderText.fusion_dimension_top_left_label;
	rightLabel   = fusionBuilderText.fusion_dimension_top_right_label;
	bottomLabel  = fusionBuilderText.fusion_dimension_bottom_left_label;
	leftLabel    = fusionBuilderText.fusion_dimension_bottom_right_label;
} else {
	topLabel     = fusionBuilderText.fusion_dimension_top_label
	rightLabel   = fusionBuilderText.fusion_dimension_right_label
	bottomLabel = fusionBuilderText.fusion_dimension_bottom_label;
	leftLabel    = fusionBuilderText.fusion_dimension_left_label;
}

if ( 'object' == typeof valueCheck ) { #>
	<# if ( 'undefined' !== typeof valueCheck.width && 'undefined' !== typeof valueCheck.height ) { #>
		<div class="multi-builder-dimension dimension-width-height" id="{{ param.param_name }}">
	<# } else { #>
		<div class="multi-builder-dimension" id="{{ param.param_name }}">
	<# } #>
	<# numberOfDimensions = Object.keys( valueCheck ).length; #>
	<# _.each( valueCheck, function( sub_value, sub_param ) { #>
		<#
		var dimension_value = ( 'undefined' !== typeof atts && 'undefined' !== typeof atts.params[ sub_param ] ) ? atts.params[ sub_param ] : sub_value,
			values = 'string' === typeof option_value ? option_value.split(' ') : '',
			inputClass,
			content_text;

			dimension_value = ( 'undefined' !== typeof atts && 'undefined' !== atts.params[ sub_param ] ) ? atts.params[ sub_param ] : sub_value;
			inputClass = '';

		content_text = isFrontEnd ? fusionBuilderText.fusion_dimension_width_label : 'fusiona-expand';
		if ( sub_param.indexOf( 'height' ) > -1 || sub_param.indexOf( 'horizontal' ) > -1  ) {
			content_text = isFrontEnd ? fusionBuilderText.fusion_dimension_height_label : 'fusiona-expand fusion-rotate-315';
		}
		if ( ! borderRadius && sub_param.indexOf( 'top' ) > -1 || sub_param.indexOf( 'top_left' ) > -1 ) {
			content_text = isFrontEnd ? topLabel : 'dashicons dashicons-arrow-up-alt';
			if ( 4 == values.length ) {
				dimension_value = values[0];
			}
		}
		if ( ! borderRadius && sub_param.indexOf( 'right' ) > -1 || sub_param.indexOf( 'top_right' ) > -1 ) {
			content_text = isFrontEnd ? rightLabel : 'dashicons dashicons-arrow-right-alt';
			if ( 4 == values.length ) {
				dimension_value = values[1];
			}
		}
		if ( ! borderRadius && sub_param.indexOf( 'bottom' ) > -1 || sub_param.indexOf( 'bottom_left' ) > -1 ) {
			content_text = isFrontEnd ? bottomLabel : 'dashicons dashicons-arrow-down-alt';
			if ( 4 == values.length ) {
				dimension_value = values[2];
			}
		}
		if ( ! borderRadius && sub_param.indexOf( 'left' ) > -1 || sub_param.indexOf( 'bottom_right' ) > -1 ) {
			content_text = isFrontEnd ? leftLabel : 'dashicons dashicons-arrow-left-alt';
			if ( 4 == values.length ) {
				dimension_value = values[3];
			}
		}
		if ( sub_param.indexOf( 'all' ) > -1 ) {
			content_text = isFrontEnd ? fusionBuilderText.fusion_dimension_all_label : 'fa fa-arrows';
			if ( 'object' == typeof dimension_value ) {
				dimension_value = dimension_value.value[ sub_param ];
			}
		}
		#>
		<div class="fusion-builder-dimension">
			<# if ( isFrontEnd && 1 < numberOfDimensions ) { #>
				<label>{{content_text}}</label>
			<# } else { #>
				<span class="add-on"><i class="{{ content_text }}"></i></span>
			<# } #>
			<input type="text" name="{{ sub_param }}" id="{{ sub_param }}" value="{{ dimension_value }}" {{{ inputClass }}} />
		</div>
	<# } ); #>
	</div>
<# } else { #>
	<#
	values = option_value.split(' ');
	if ( 1 == values.length ) {
		var dimension_top = values[0];
		var dimension_bottom = values[0];
		var dimension_left = values[0];
		var dimension_right = values[0];
	}
	if ( 2 == values.length ) {
		var dimension_top = values[0];
		var dimension_bottom = values[0];
		var dimension_left = values[1];
		var dimension_right = values[1];
	}
	if ( 3 == values.length ) {
		var dimension_top = values[0];
		var dimension_left = values[1];
		var dimension_right = values[1];
		var dimension_bottom = values[2];
	}
	if ( 4 == values.length ) {
		var dimension_top = values[0];
		var dimension_left = values[3];
		var dimension_right = values[1];
		var dimension_bottom = values[2];
	}
	#>
	<div class="single-builder-dimension">
		<div class="fusion-builder-dimension">
			<# if ( isFrontEnd ) { #>
				<label>{{topLabel}}</label>
			<# } else { #>
				<span class="add-on"><i class="dashicons dashicons-arrow-up"></i></span>
			<# } #>
			<input type="text" name="{{ param.param_name }}_top" id="{{ param.param_name }}_top" value="{{ dimension_top }}" />
		</div>
		<div class="fusion-builder-dimension">
			<# if ( isFrontEnd ) { #>
				<label>{{rightLabel}}</label>
			<# } else { #>
				<span class="add-on"><i class="dashicons dashicons-arrow-right"></i></span>
			<# } #>
			<input type="text" name="{{ param.param_name }}_right" id="{{ param.param_name }}_right" value="{{ dimension_right }}" />
		</div>
		<div class="fusion-builder-dimension">
			<# if ( isFrontEnd ) { #>
				<label>{{bottomLabel}}</label>
			<# } else { #>
				<span class="add-on"><i class="dashicons dashicons-arrow-down"></i></span>
			<# } #>
			<input type="text" name="{{ param.param_name }}_bottom" id="{{ param.param_name }}_bottom" value="{{ dimension_bottom }}" />
		</div>
		<div class="fusion-builder-dimension">
			<# if ( isFrontEnd ) { #>
				<label>{{leftLabel}}</label>
			<# } else { #>
				<span class="add-on"><i class="dashicons dashicons-arrow-left"></i></span>
			<# } #>
			<input type="text" name="{{ param.param_name }}_left" id="{{ param.param_name }}_left" value="{{ dimension_left }}" />
		</div>
		<input type="hidden" name="{{ param.param_name }}" id="{{ param.param_name }}" value="{{ option_value }}" />
	</div>
<# } #>
