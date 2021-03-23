<?php
	/**
	 * The template for the header sticky bar.
	 * Override this template by specifying the path where it is stored (templates_path) in your FusionRedux config.
	 *
	 * @author        FusionRedux Framework
	 * @package       FusionReduxFramework/Templates
	 * @version:      3.5.7.8
	 */
?>
<div id="fusionredux-sticky">
	<div id="info_bar">

		<a href="javascript:void(0);" class="expand_options<?php echo esc_attr(( $this->parent->args['open_expanded'] ) ? ' expanded' : ''); ?>"<?php echo $this->parent->args['hide_expand'] ? ' style="display: none;"' : '' ?>>
			<?php esc_html_e( 'Expand', 'Avada' ); ?>
		</a>

		<div class="fusionredux-action_bar">
			<span class="spinner"></span>
			<?php if ( false === $this->parent->args['hide_save'] ) { ?>
				<?php submit_button( esc_html__( 'Save Changes', 'Avada' ), 'primary', 'fusionredux_save', false ); ?>
			<?php } ?>

			<?php if ( false === $this->parent->args['hide_reset'] ) { ?>
				<?php submit_button( esc_html__( 'Reset Section', 'Avada' ), 'secondary', $this->parent->args['opt_name'] . '[defaults-section]', false, array( 'id' => 'fusionredux-defaults-section' ) ); ?>
				<?php submit_button( esc_html__( 'Reset All', 'Avada' ), 'secondary', $this->parent->args['opt_name'] . '[defaults]', false, array( 'id' => 'fusionredux-defaults' ) ); ?>
			<?php } ?>
		</div>
		<div class="fusionredux-ajax-loading" alt="<?php esc_attr_e( 'Working...', 'Avada' ) ?>">&nbsp;</div>
		<div class="clear"></div>
	</div>

	<!-- Notification bar -->
	<div id="fusionredux_notification_bar">
		<?php $this->notification_bar(); ?>
	</div>


</div>
