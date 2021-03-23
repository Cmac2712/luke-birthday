<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_tb_pagination-shortcode">
	{{{styles}}}
	<div {{{ _.fusionGetAttributes( wrapperAttr ) }}}>
		<a href="#" rel="prev">{{ fusionBuilderText.previous }}</a>
		<a href="#" rel="next">{{ fusionBuilderText.next }}</a>
	</div>
</script>
