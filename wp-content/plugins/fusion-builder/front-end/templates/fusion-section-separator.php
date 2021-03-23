<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_section_separator-shortcode">
<# if ( 'triangle' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}}>
		<# if ( '' !== values.icon ) { #>
			<div {{{ _.fusionGetAttributes( attrButton ) }}}></div>
		<# } #>
		<# if ( -1 !== values.divider_candy.indexOf( 'top' ) && -1 !== values.divider_candy.indexOf( 'bottom' ) ) { #>
			<div {{{ _.fusionGetAttributes( attrCandy ) }}}></div>
		<# } else { #>
			<div {{{ _.fusionGetAttributes( attrCandyArrow ) }}}></div>
			<div {{{ _.fusionGetAttributes( attrCandy ) }}}></div>
		<# } #>
	</div>
<# } else if ( 'bigtriangle' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-big-triangle-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 100 100" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
			<# if ( 'top' === values.divider_candy ) { #>
				<# if ( 'right' === values.divider_position ) { #>
					<path d="M0 100 L75 0 L100 100 Z"></path>
				<# } else if ( 'left' === values.divider_position ) { #>
					<path d="M0 100 L25 2 L100 100 Z"></path>
				<# } else { #>
					<path d="M0 100 L50 2 L100 100 Z"></path>
				<# } #>
			<# } else { #>
				<# if ( 'right' === values.divider_position ) { #>
					<path d="M-1 -1 L75 99 L101 -1 Z"></path>
				<# } else if ( 'left' === values.divider_position ) { #>
					<path d="M0 -1 L25 100 L101 -1 Z"></path>
				<# } else { #>
					<path d="M-1 -1 L50 99 L101 -1 Z"></path>
				<# } #>
			<# } #>
		</svg>
	</div>
<# } else if ( 'slant' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-slant-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 100 102" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
			<# if ( 'left' === values.divider_position && 'top' === values.divider_candy ) { #>
				<path d="M100 -1 L100 100 L0 0 Z"></path>
			<# } else if ( 'right' === values.divider_position && 'top' === values.divider_candy ) { #>
				<path d="M0 100 L0 -1 L100 0 Z"></path>
			<# } else if ( 'right' === values.divider_position && 'bottom' === values.divider_candy ) { #>
				<path d="M100 0 L-2 100 L101 100 Z"></path>
			<# } else { #>
				<path d="M0 0 L0 99 L100 99 Z"></path>
			<# } #>
		</svg>
	</div>
<# } else if ( 'rounded-split' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<div {{{ _.fusionGetAttributes( attrRoundedSplit ) }}}></div>
	</div>
<# } else if ( 'big-half-circle' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-big-half-circle-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 100 100" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
			<# if ( 'top' === values.divider_candy ) { #>
				<path d="M0 100 C40 0 60 0 100 100 Z"></path>
			<# } else { #>
				<path d="M0 0 C55 180 100 0 100 0 Z"></path>
			<# } #>
		</svg>
	</div>
<# } else if ( 'curved' === values.divider_type ) {	#>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-curved-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 100 100" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
			<# if ( 'left' === values.divider_position ) { #>
				<# if ( 'top' === values.divider_candy ) { #>
					<path d="M0 100 C 20 0 50 0 100 100 Z"></path>
				<# } else { #>
					<path d="M0 0 C 20 100 50 100 100 0 Z"></path>
				<# } #>
			<# } else { #>
				<# if ( 'top' === values.divider_candy ) { #>
					<path d="M0 100 C 60 0 75 0 100 100 Z"></path>
				<# } else { #>
					<path d="M0 0 C 50 100 80 100 100 0 Z"></path>
				<# } #>
			<# } #>
		</svg>
	</div>
<# } else if ( 'clouds' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-clouds-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 100 100" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}} >
			<path d="M-5 100 Q 0 20 5 100 Z"></path>
			<path d="M0 100 Q 5 0 10 100"></path>
			<path d="M5 100 Q 10 30 15 100"></path>
			<path d="M10 100 Q 15 10 20 100"></path>
			<path d="M15 100 Q 20 30 25 100"></path>
			<path d="M20 100 Q 25 -10 30 100"></path>
			<path d="M25 100 Q 30 10 35 100"></path>
			<path d="M30 100 Q 35 30 40 100"></path>
			<path d="M35 100 Q 40 10 45 100"></path>
			<path d="M40 100 Q 45 50 50 100"></path>
			<path d="M45 100 Q 50 20 55 100"></path>
			<path d="M50 100 Q 55 40 60 100"></path>
			<path d="M55 100 Q 60 60 65 100"></path>
			<path d="M60 100 Q 65 50 70 100"></path>
			<path d="M65 100 Q 70 20 75 100"></path>
			<path d="M70 100 Q 75 45 80 100"></path>
			<path d="M75 100 Q 80 30 85 100"></path>
			<path d="M80 100 Q 85 20 90 100"></path>
			<path d="M85 100 Q 90 50 95 100"></path>
			<path d="M90 100 Q 95 25 100 100"></path>
			<path d="M95 100 Q 100 15 105 100 Z"></path>
		</svg>
	</div>
<# } else if ( 'horizon' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-horizon-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 {{{ values.yMin }}} 1024 178" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}} >
			<# if ( 'top' === values.divider_candy ) { #>
				<path class="st0" d="M1024 177.371H0V.219l507.699 133.939L1024 .219v177.152z"/>
				<path class="st1" d="M1024 177.781H0V39.438l507.699 94.925L1024 39.438v138.343z"/>
				<path class="st2" d="M1024 177.781H0v-67.892l507.699 24.474L1024 109.889v67.892z"/>
				<path class="st3" d="M1024 177.781H0v-3.891l507.699-39.526L1024 173.889v3.892z"/>
			<# } else { #>
				<path class="st0" d="M1024 177.193L507.699 43.254 0 177.193V.041h1024v177.152z"/>
				<path class="st1" d="M1024 138.076L507.699 43.152 0 138.076V-.266h1024v138.342z"/>
				<path class="st2" d="M1024 67.728L507.699 43.152 0 67.728V-.266h1024v67.994z"/>
				<path class="st3" d="M1024 3.625L507.699 43.152 0 3.625V-.266h1024v3.891z"/>
			<# } #>
		</svg>
	</div>
<# } else if ( 'hills' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<# if ( 'top' === values.divider_candy ) { #>
			<svg class="fusion-hills-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 74 1024 107" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
				<path class="st4" d="M0 182.086h1024v-77.312c-49.05 20.07-120.525 42.394-193.229 42.086-128.922-.512-159.846-72.294-255.795-72.294-89.088 0-134.656 80.179-245.043 82.022S169.063 99.346 49.971 97.401C32.768 97.094 16.077 99.244 0 103.135v78.951z"/>
			</svg>
		<# } else { #>
			<svg class="fusion-hills-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 1 1024 107" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
				<path class="st4" d="M0 0h1024v77.3c-49-20.1-120.5-42.4-193.2-42.1-128.9.5-159.8 72.3-255.8 72.3-89.1 0-134.7-80.2-245-82-110.4-1.8-160.9 57.2-280 59.2-17.2.3-33.9-1.8-50-5.7V0z"/>
			</svg>
		<# } #>
	</div>
<# } else if ( 'hills_opacity' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-hills-opacity-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 {{{ values.yMin }}} 1024 182" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>
		<# if ( 'top' === values.divider_candy ) { #>
			<path class="st0" d="M0 182.086h1024V41.593c-28.058-21.504-60.109-37.581-97.075-37.581-112.845 0-198.144 93.798-289.792 93.798S437.658 6.777 351.846 6.777s-142.234 82.125-238.49 82.125c-63.078 0-75.776-31.744-113.357-53.658L0 182.086z"/>
			<path class="st1" d="M1024 181.062v-75.878c-39.731 15.872-80.794 27.341-117.658 25.805-110.387-4.506-191.795-109.773-325.53-116.224-109.158-5.12-344.166 120.115-429.466 166.298H1024v-.001z"/>
			<path class="st2" d="M0 182.086h1024V90.028C966.451 59.103 907.059 16.3 824.115 15.071 690.278 13.023 665.19 102.93 482.099 102.93S202.138-1.62 74.24.019C46.49.326 21.811 4.217 0 9.849v172.237z"/>
			<path class="st3" d="M0 182.086h1024V80.505c-37.171 19.558-80.691 35.328-139.571 36.25-151.142 2.355-141.619-28.57-298.496-29.184s-138.854 47.002-305.459 43.725C132.813 128.428 91.238 44.563 0 28.179v153.907z"/>
			<path class="st4" d="M0 182.086h1024v-77.312c-49.05 20.07-120.525 42.394-193.229 42.086-128.922-.512-159.846-72.294-255.795-72.294-89.088 0-134.656 80.179-245.043 82.022S169.063 99.346 49.971 97.401C32.768 97.094 16.077 99.244 0 103.135v78.951z"/>
			</svg>
		<# } else { #>
			<path class="st0" d="M0 0h1024v140.5C995.9 162 963.9 178 926.9 178c-112.8 0-198.1-93.8-289.8-93.8s-199.5 91-285.3 91-142.2-82.1-238.5-82.1c-63.1 0-75.7 31.6-113.3 53.6V0z"/>
			<path class="st1" d="M1024 0v75.9C984.3 60 942.2 48.6 905.3 50.1c-110.4 4.5-191.8 109.8-325.5 116.2C470.6 171.5 235.6 46.1 150.3 0H1024z"/>
			<path class="st2" d="M0 0h1024v92c-57.5 30.9-116.9 73.7-199.9 75-133.8 2-158.9-87.9-342-87.9S202.1 183.7 74.2 182c-27.8-.3-52.4-4.2-74.2-9.7V0z"/>
			<path class="st3" d="M0 0h1024v101.6C986.8 82 943.3 66.3 884.4 65.4 733.3 63 742.8 94 585.9 94.6S447 47.6 280.4 50.9C132.8 53.6 91.2 137.5 0 154V0z"/>
			<path class="st4" d="M0 0h1024v77.3c-49-20.1-120.5-42.4-193.2-42.1-128.9.5-159.8 72.3-255.8 72.3-89.1 0-134.7-80.2-245-82-110.4-1.8-160.9 57.2-280 59.2-17.2.3-33.9-1.8-50-5.7V0z"/>
		<# } #>
		</svg>
	</div>
<# } else if ( 'waves' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-waves-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 {{{ values.yMin }}} 1024 162" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>

		<# if ( 'left' === values.divider_position ) { #>
			<# if ( 'top' === values.divider_candy ) { #>
				<path class="st3" d="M0 216.312h1024v-3.044c-50.8-17.1-108.7-30.7-172.7-37.9-178.6-19.8-220 36.8-404.9 21.3-206.6-17.2-228-126.5-434.5-141.6-3.9-.3-7.9-.5-11.9-.7v161.944z"/>
			<# } else { #>
				<path class="st3" d="M0 162.1c4-.2 8-.4 11.9-.7C218.4 146.3 239.8 37 446.4 19.8 631.3 4.3 672.7 60.9 851.3 41.1c64-7.2 121.9-20.8 172.7-37.9V.156H0V162.1z"/>
			<# } #>
		<# } else { #>
			<# if ( 'top' === values.divider_candy ) { #>
				<path class="st3" d="M1024.1 54.368c-4 .2-8 .4-11.9.7-206.5 15.1-227.9 124.4-434.5 141.6-184.9 15.5-226.3-41.1-404.9-21.3-64 7.2-121.9 20.8-172.7 37.9v3.044h1024V54.368z"/>
			<# } else { #>
				<path class="st3" d="M1024.1.156H.1V3.2c50.8 17.1 108.7 30.7 172.7 37.9 178.6 19.8 220-36.8 404.9-21.3 206.6 17.2 228 126.5 434.5 141.6 3.9.3 7.9.5 11.9.7V.156z"/>
			<# } #>
		<# } #>
		</svg>
	</div>
<# } else if ( 'waves_opacity' === values.divider_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}} >
		<svg class="fusion-waves-opacity-candy" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 {{{ values.yMin }}} 1024 216" preserveAspectRatio="none" {{{ _.fusionGetAttributes( attrSVG ) }}}>

		<# if ( 'left' === values.divider_position ) { #>
			<# if ( 'top' === values.divider_candy ) { #>
				<path class="st0" d="M0 216.068h1024l.1-105.2c-14.6-3.2-30.2-5.8-47.1-7.6-178.6-19.6-279.5 56.8-464.3 41.3-206.5-17.2-248.4-128.8-455-143.8-19-1.3-38.3-.2-57.7.3v215z"/>
				<path class="st1" d="M0 20.068v196.144h1024v-79.744c-22.7-6.4-47.9-11.4-76.2-14.6-178.6-19.8-272.2 53.9-457.1 38.4-206.6-17.2-197.3-124.7-403.9-139.8-27.2-2-56.6-2-86.8-.4z"/>
				<path class="st2" d="M0 216.212h1024v-35.744c-45.1-15.4-95.2-27.7-150-33.7-178.6-19.8-220.6 46.8-405.4 31.3-206.6-17.2-197.8-114.7-404.4-129.7-20.4-1.5-42-2-64.2-1.7v169.544z"/>
				<path class="st3" d="M0 216.312h1024v-3.044c-50.8-17.1-108.7-30.7-172.7-37.9-178.6-19.8-220 36.8-404.9 21.3-206.6-17.2-228-126.5-434.5-141.6-3.9-.3-7.9-.5-11.9-.7v161.944z"/>
			<# } else { #>
				<path class="st0" d="M0 215.4c19.4.5 38.7 1.6 57.7.3 206.6-15 248.5-126.6 455-143.8 184.8-15.5 285.7 60.9 464.3 41.3 16.9-1.8 32.5-4.4 47.1-7.6L1024 .4H0v215z"/>
				<path class="st1" d="M0 196.4c30.2 1.6 59.6 1.6 86.8-.4C293.4 180.9 284.1 73.4 490.7 56.2c184.9-15.5 278.5 58.2 457.1 38.4 28.3-3.2 53.5-8.2 76.2-14.6V.256H0V196.4z"/>
				<path class="st2" d="M0 169.8c22.2.3 43.8-.2 64.2-1.7C270.8 153.1 262 55.6 468.6 38.4 653.4 22.9 695.4 89.5 874 69.7c54.8-6 104.9-18.3 150-33.7V.256H0V169.8z"/>
				<path class="st3" d="M0 162.1c4-.2 8-.4 11.9-.7C218.4 146.3 239.8 37 446.4 19.8 631.3 4.3 672.7 60.9 851.3 41.1c64-7.2 121.9-20.8 172.7-37.9V.156H0V162.1z"/>
			<# } #>
		<# } else { #>
			<# if ( 'top' === values.divider_candy ) { #>
				<path class="st0" d="M1024.1 1.068c-19.4-.5-38.7-1.6-57.7-.3-206.6 15-248.5 126.6-455 143.8-184.8 15.5-285.7-60.9-464.3-41.3-16.9 1.8-32.5 4.4-47.1 7.6l.1 105.2h1024v-215z"/>
				<path class="st1" d="M1024.1 20.068c-30.2-1.6-59.6-1.6-86.8.4-206.6 15.1-197.3 122.6-403.9 139.8-184.9 15.5-278.5-58.2-457.1-38.4-28.3 3.2-53.5 8.2-76.2 14.6v79.744h1024V20.068z"/>
				<path class="st2" d="M1024.1 46.668c-22.2-.3-43.8.2-64.2 1.7-206.6 15-197.8 112.5-404.4 129.7-184.8 15.5-226.8-51.1-405.4-31.3-54.8 6-104.9 18.3-150 33.7v35.744h1024V46.668z"/>
				<path class="st3" d="M1024.1 54.368c-4 .2-8 .4-11.9.7-206.5 15.1-227.9 124.4-434.5 141.6-184.9 15.5-226.3-41.1-404.9-21.3-64 7.2-121.9 20.8-172.7 37.9v3.044h1024V54.368z"/>
			<# } else { #>
				<path class="st0" d="M1024.1.4H.1L0 105.6c14.6 3.2 30.2 5.8 47.1 7.6 178.6 19.6 279.5-56.8 464.3-41.3 206.5 17.2 248.4 128.8 455 143.8 19 1.3 38.3.2 57.7-.3V.4z"/>
				<path class="st1" d="M1024.1 196.4V.256H.1V80C22.8 86.4 48 91.4 76.3 94.6c178.6 19.8 272.2-53.9 457.1-38.4C740 73.4 730.7 180.9 937.3 196c27.2 2 56.6 2 86.8.4z"/>
				<path class="st2" d="M1024.1.256H.1V36c45.1 15.4 95.2 27.7 150 33.7 178.6 19.8 220.6-46.8 405.4-31.3 206.6 17.2 197.8 114.7 404.4 129.7 20.4 1.5 42 2 64.2 1.7V.256z"/>
				<path class="st3" d="M1024.1.156H.1V3.2c50.8 17.1 108.7 30.7 172.7 37.9 178.6 19.8 220-36.8 404.9-21.3 206.6 17.2 228 126.5 434.5 141.6 3.9.3 7.9.5 11.9.7V.156z"/>
			<# } #>
		<# } #>
		</svg>
	</div>
<# } #>
</script>
