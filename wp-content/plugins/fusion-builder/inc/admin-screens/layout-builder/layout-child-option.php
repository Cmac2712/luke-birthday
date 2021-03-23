<?php
/**
 * An underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-layout-child-option">
	<div class="layout-child-option">
		<input id="{{ id }}-include" data-parent="{{ parent }}"  data-type="{{ type }}" data-label="{{ label }}" type="checkbox" name="{{ id }}" <# print( 'include' === checked ? 'checked' : '' ) #> value="include">
		<label for="{{ id }}-include" class="option-include">
			<i class="fusiona-checkmark"></i>
		</label>
		<input id="{{ id }}-exclude" data-parent="{{ parent }}"  data-type="{{ type }}" data-label="{{ label }}" type="checkbox" name="{{ id }}" <# print( 'exclude' === checked ? 'checked' : '' ) #> value="exclude">
		<label for="{{ id }}-exclude" class="option-exclude">
			<i class="fusiona-cross"></i>
		</label>
		<span id="{{ id }}" class="layout-option-label">{{{ label }}}</span>
	</div>
</script>
