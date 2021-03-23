<?php
/**
 * Admin Screen markup (What's New page).
 *
 * @package fusion-builder
 */

?>
<div class="wrap about-wrap fusion-builder-wrap">

	<?php Fusion_Builder_Admin::header(); ?>
	<?php if ( ! class_exists( 'Avada' ) ) { ?>
		<div class="fusion-builder-important-notice">
			<p class="about-description">
				<?php
				printf( // phpcs:ignore WordPress.Security.EscapeOutput
					/* translators: Link attributes. */
					__( 'Fusion Builder will soon be available to be used with any WordPress theme. <a %1$s>Subscribe to our newsletter</a> to find out when it will be sold separately. In the meantime, check out the <a %2$s>Add-ons</a> tab for available Add-ons that can be used with the Avada WordPress theme.', 'fusion-builder' ), // phpcs:ignore WordPress.Security.EscapeOutput
					'href="https://theme-fusion.us2.list-manage2.com/subscribe?u=4345c7e8c4f2826cc52bb84cd&id=af30829ace" target="_blank"',
					'href="' . esc_url_raw( admin_url( 'admin.php?page=fusion-builder-addons' ) ) . '" target="_self"'
				);
				?>
			</p>
		</div>
	<?php } else { ?>
		<div class="fusion-builder-registration-steps">
			<?php
			$welcome_html = '<iframe width="1120" height="630" src="https://www.youtube.com/embed/83cp_MoZuAw?rel=0" frameborder="0" allowfullscreen></iframe>

			<div class="col three-col">

				<div class="col">
					<h3>' . esc_attr__( 'The All New Fusion Builder', 'fusion-builder' ) . '</h3>
					<p>' . esc_attr__( 'Fusion Builder has been recreated in every way, making it the easiest and fastest way to build beautiful, professional layouts. Fusion Builder is intuitive, user friendly and loaded with features. It\'s a joy to use and will change your outlook on what a page builder can do.', 'fusion-builder' ) . '</p>
				</div>

				<div class="col">
					<h3>' . esc_attr__( 'Fusion Builder Library', 'fusion-builder' ) . '</h3>
					<p>' . esc_attr__( 'No, we\'re not talking about books! This Library allows you to save any type of custom content including containers, columns, elements or full page layouts. The user interface makes it easy to reuse any of this content at any time, and you can import and export it all to share.', 'fusion-builder' ) . '</p>
				</div>

				<div class="col last-feature last">
					<h3>' . esc_attr__( 'Built For The Future', 'fusion-builder' ) . '</h3>
					<p>' . esc_attr__( 'Fusion Builder plays a major role in site creation and is a vital part of the Avada ecosystem. But that\'s not all, it\'s packed with useful features and has been built for extendability. This is only the beginning, and the sky is the limit for the future of Fusion Builder.', 'fusion-builder' ) . '</p>
				</div>

			</div>';

			echo apply_filters( 'fusion_builder_admin_welcome_screen_content', $welcome_html ); // phpcs:ignore WordPress.Security.EscapeOutput

			require_once wp_normalize_path( dirname( __FILE__ ) . '/register.php' );
			?>

		</div>
		<?php } ?>
	<?php Fusion_Builder_Admin::footer(); ?>
</div>
