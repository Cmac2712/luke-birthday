<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<#
var fieldId = 'undefined' === typeof param.param_name ? param.id : param.param_name,
	choices = 'undefined' === typeof param.param_name ? param.choices : param.value,
	icons   = 'undefined' !== typeof FusionApp && 'undefined' !== typeof param.icons ? param.icons : '';
#>
<div class="fusion-form-radio-button-set ui-buttonset fusion-option-{{ fieldId }}">
	<#
	var choice = option_value,
	index = 0;

	if ( ( 'undefined' === typeof choice || '' === choice ) && 'undefined' !== typeof param.default ) {
		choice = param.default;
	}
	#>
	<input type="hidden" id="{{ fieldId }}" name="{{ fieldId }}" value="{{ choice }}" class="button-set-value" />
	<# _.each( choices, function( name, value ) { #>
		<#
		index++;
		var selected  = ( value == choice ) ? ' ui-state-active' : '',
			icon      = ( 'undefined' !== typeof icons[ value ] && '' !== icons ) ? icons[ value ] : '',
			title     = name,
			iconClass = '' === icon ? '' : 'has-tooltip';

		if ( -1 !== icon.indexOf( 'svg' ) || -1 !== icon.indexOf( 'span' ) ) {
			title = icon;
		} else if ( '' !== icon ) {
			iconClass += ' ' + icon;
			title      = '';
		}

		#>
		<a href="#" class="ui-button buttonset-item{{ selected }} {{ iconClass }}" data-value="{{ value }}" aria-label="{{ name }}">{{{ title }}}</a>
	<# } ); #>
</div>
