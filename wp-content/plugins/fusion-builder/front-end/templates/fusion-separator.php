<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_separator-shortcode">
<div class="fusion-sep-clear"></div>

<div {{{ _.fusionGetAttributes( attr ) }}}>
	<# if ( '' !== values.icon && 'none' !== values.style_type ) { #>
	<span {{{ _.fusionGetAttributes( iconWrapperAttr ) }}}><i {{{ _.fusionGetAttributes( iconAttr ) }}}></i></span>
	<# } #>
</div>

<# if ( 'right' === values.alignment || '' !== values.bottom_margin ) { #>
<div class="fusion-sep-clear"></div>
<# } #>
</script>
