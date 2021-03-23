<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-builder-block-module-pricing-table-preview-template">
	<h4 class="fusion_module_title"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
	<?php /* translators: The style. */ ?>
	<?php printf( esc_html__( 'style = %s', 'fusion-builder' ), '{{ params.type }}' ); ?>
	<br />
	<?php /* translators: The columns. */ ?>
	<?php printf( esc_html__( 'columns = %s', 'fusion-builder' ), '{{ params.columns }}' ); ?>

</script>
