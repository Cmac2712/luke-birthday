<script type="text/html" id="tmpl-fusion_portfolio-shortcode">
<#
// If Query Data is set, use it and continue.  If not, echo HTML.
if ( portfolio_posts ) {

	if ( 'carousel' === layout ) { #>
		<div {{{ _.fusionGetAttributes( portfolioShortcode ) }}}>
			<div {{{ _.fusionGetAttributes( portfolioShortcodeCarousel ) }}}>
				<div class="fusion-carousel-positioner">
					<ul class="fusion-carousel-holder">{{{ portfolio_posts }}}</ul>

					<# if ( 'yes' === show_nav ) { #>
						<div class="fusion-carousel-nav"><span class="fusion-nav-prev"></span><span class="fusion-nav-next"></span></div>
					<# } #>

				</div>
			</div>
		</div>
	<# } else { #>
		{{{ alignPaddingStyle }}}
		<div {{{ _.fusionGetAttributes( portfolioShortcode ) }}}>
			{{{ filters }}}
			{{{ columnSpacingStyle }}}
			<div {{{ _.fusionGetAttributes( portfolioShortcodePortfolioWrapper ) }}}>
				{{{ portfolio_posts }}}
			</div>
			{{{ pagination }}}
		</div>
	<# } #>

<# } else if ( placeholder ) { #>
	{{{ placeholder }}}
<# } #>
</script>
