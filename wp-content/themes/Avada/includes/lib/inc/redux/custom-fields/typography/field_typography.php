<?php

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - render()
 * - enqueue()
 * - makeGoogleWebfontLink()
 * - makeGoogleWebfontString()
 * - output()
 * - getGoogleArray()
 * - getSubsets()
 * - getVariants()
 * Classes list:
 * - FusionReduxFramework_typography
 */

if ( ! class_exists( 'FusionReduxFramework_typography' ) ) {
	class FusionReduxFramework_typography {

		private $std_fonts = array(
			"Arial, Helvetica, sans-serif"                         => "Arial, Helvetica, sans-serif",
			"'Arial Black', Gadget, sans-serif"                    => "'Arial Black', Gadget, sans-serif",
			"'Bookman Old Style', serif"                           => "'Bookman Old Style', serif",
			"'Comic Sans MS', cursive"                             => "'Comic Sans MS', cursive",
			"Courier, monospace"                                   => "Courier, monospace",
			"Garamond, serif"                                      => "Garamond, serif",
			"Georgia, serif"                                       => "Georgia, serif",
			"Impact, Charcoal, sans-serif"                         => "Impact, Charcoal, sans-serif",
			"'Lucida Console', Monaco, monospace"                  => "'Lucida Console', Monaco, monospace",
			"'Lucida Sans Unicode', 'Lucida Grande', sans-serif"   => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
			"'MS Sans Serif', Geneva, sans-serif"                  => "'MS Sans Serif', Geneva, sans-serif",
			"'MS Serif', 'New York', sans-serif"                   => "'MS Serif', 'New York', sans-serif",
			"'Palatino Linotype', 'Book Antiqua', Palatino, serif" => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
			"Tahoma,Geneva, sans-serif"                            => "Tahoma, Geneva, sans-serif",
			"'Times New Roman', Times,serif"                       => "'Times New Roman', Times, serif",
			"'Trebuchet MS', Helvetica, sans-serif"                => "'Trebuchet MS', Helvetica, sans-serif",
			"Verdana, Geneva, sans-serif"                          => "Verdana, Geneva, sans-serif",
		);

		private $user_fonts = true;

		protected $parent;
		protected $field;
		protected $value;

		/**
		 * Field Constructor.
		 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
		 *
		 * @since FusionReduxFramework 1.0.0
		 */
		function __construct( $field = array(), $value = '', $parent ) {
			$this->parent = $parent;
			$this->field  = $field;
			$this->value  = $value;

			// Shim out old arg to new
			if ( isset( $this->field['all_styles'] ) && ! empty( $this->field['all_styles'] ) ) {
				$this->field['all-styles'] = $this->field['all_styles'];
				unset ( $this->field['all_styles'] );
			}

			// Set field array defaults.  No errors please
			$defaults    = array(
				'font-family'     => true,
				'font-size'       => true,
				'font-weight'     => true,
				'font-style'      => true,
				'font-backup'     => false,
				'subsets'         => true,
				'custom_fonts'    => true,
				'text-align'      => true,
				'text-transform'  => false,
				'font-variant'    => false,
				'text-decoration' => false,
				'color'           => true,
				'preview'         => true,
				'line-height'     => true,
				'multi' => array(
					'subset' => false,
					'weight' => false,
				),
				'word-spacing'    => false,
				'letter-spacing'  => false,
				'google'          => true,
				'update_weekly'   => false,    // Enable to force updates of Google Fonts to be weekly
				'font_family_clear' => false,
				'margin-top' => false,
				'margin-bottom' => false,
			);
			$this->field = wp_parse_args( $this->field, $defaults );

			// Set value defaults.
			$defaults    = array(
				'font-family'     => '',
				'font-options'    => '',
				'font-backup'     => '',
				'text-align'      => '',
				'text-transform'  => '',
				'font-variant'    => '',
				'text-decoration' => '',
				'line-height'     => '',
				'word-spacing'    => '',
				'letter-spacing'  => '',
				'subsets'         => '',
				'google'          => false,
				'font-script'     => '',
				'font-weight'     => '',
				'font-style'      => '',
				'color'           => '',
				'font-size'       => '',
				'margin-top'      => '',
				'margin-bottom'   => '',
			);
			$this->value = wp_parse_args( $this->value, $defaults );
			if ( ! $this->value['font-weight'] || 400 === $this->value['font-weight'] || '400' === $this->value['font-weight'] ) {
				$this->value['font-weight'] = '400';
			}

			// Get the google array
			$this->getGoogleArray();

			if ( empty( $this->field['fonts'] ) ) {
				$this->user_fonts     = false;
				$this->field['fonts'] = $this->std_fonts;
			}

			// Localize std fonts
			$this->localizeStdFonts();

		}

		function localize( $field, $value = "" ) {
			$params = array();

			if ( true == $this->user_fonts && ! empty( $this->field['fonts'] ) ) {
				$params['std_font'] = $this->field['fonts'];
			}

			return $params;
		}


		/**
		 * Field Render Function.
		 * Takes the vars and outputs the HTML for the field in the settings
		 *
		 * @since FusionReduxFramework 1.0.0
		 */
		function render() {
			// Since fonts declared is CSS (@font-face) are not rendered in the preview,
			// they can be declared in a CSS file and passed here so they DO display in
			// font preview.  Do NOT pass style.css in your theme, as that will mess up
			// admin page styling.  It's recommended to pass a CSS file with ONLY font
			// declarations.
			// If field is set and not blank, then enqueue field
			if ( isset( $this->field['ext-font-css'] ) && $this->field['ext-font-css'] != '' ) {
				wp_register_style( 'fusionredux-external-fonts', $this->field['ext-font-css'] );
				wp_enqueue_style( 'fusionredux-external-fonts' );
			}

			$isGoogleFont = false;

			echo '<div id="' . $this->field['id'] . '" class="fusionredux-typography-container" data-id="' . $this->field['id'] . '">';

			if ( isset( $this->field['select3'] ) ) { // if there are any let's pass them to js
				$select3_params = json_encode( $this->field['select3'] );
				$select3_params = htmlspecialchars( $select3_params, ENT_QUOTES );

				echo '<input type="hidden" class="select3_params" value="' . $select3_params . '">';
			}

			/* Font Family */
			if ( $this->field['font-family'] === true ) {

				// font family clear
				echo '<input type="hidden" class="fusionredux-font-clear" value="' . $this->field['font_family_clear'] . '">';

				//if (filter_var($this->value['google'], FILTER_VALIDATE_BOOLEAN)) {
				if ( filter_var( $this->value['google'], FILTER_VALIDATE_BOOLEAN ) ) {

					// Divide and conquer
					$fontFamily = explode( ', ', $this->value['font-family'], 2 );

					// If array 0 is empty and array 1 is not
					if ( empty( $fontFamily[0] ) && ! empty( $fontFamily[1] ) ) {

						// Make array 0 = array 1
						$fontFamily[0] = $fontFamily[1];

						// Clear array 1
						$fontFamily[1] = "";
					}
				}

				// If no fontFamily array exists, create one and set array 0
				// with font value
				if ( ! isset( $fontFamily ) ) {
					$fontFamily    = array();
					$fontFamily[0] = $this->value['font-family'];
					$fontFamily[1] = "";
				}

				// Is selected font a Google font
				$isGoogleFont = '0';
				if ( isset( $this->parent->fonts['google'][ $fontFamily[0] ] ) ) {
					$isGoogleFont = '1';
				}

				// If not a Google font, show all font families
				if ( $isGoogleFont != '1' ) {
					$fontFamily[0] = $this->value['font-family'];
				}

				$userFonts = '0';
				if ( true == $this->user_fonts ) {
					$userFonts = '1';
				}

				echo '<input type="hidden" class="fusionredux-typography-font-family ' . $this->field['class'] . '" data-user-fonts="' . $userFonts . '" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-family]' . '" value="' . $this->value['font-family'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '<input type="hidden" class="fusionredux-typography-font-options ' . $this->field['class'] . '" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-options]' . '" value="' . $this->value['font-options'] . '" data-id="' . $this->field['id'] . '"  />';

				echo '<input type="hidden" class="fusionredux-typography-google-font" value="' . $isGoogleFont . '" id="' . $this->field['id'] . '-google-font">';

				echo '<div class="select_wrapper typography-family" style="width: 220px; margin-right: 5px;">';
				echo '<label>' . __( 'Font Family', 'Avada' ) . '</label>';
				$placeholder = $fontFamily[0] ? $fontFamily[0] : __( 'Font family', 'Avada' );

				echo '<div class=" fusionredux-typography fusionredux-typography-family select3-container ' . $this->field['class'] . '" id="' . $this->field['id'] . '-family" placeholder="' . $placeholder . '" data-id="' . $this->field['id'] . '" data-value="' . $fontFamily[0] . '">';

				echo '</div>';
				echo '</div>';

				$googleSet = false;
				if ( $this->field['google'] === true ) {

					// Set a flag so we know to set a header style or not
					echo '<input type="hidden" class="fusionredux-typography-google ' . $this->field['class'] . '" id="' . $this->field['id'] . '-google" name="' . $this->field['name'] . $this->field['name_suffix'] . '[google]' . '" type="text" value="' . $this->field['google'] . '" data-id="' . $this->field['id'] . '" />';
					$googleSet = true;
				}
			}

			/* Backup Font */
			if ( $this->field['font-family'] === true && $this->field['google'] === true ) {

				if ( isset( $googleSet ) && false == $googleSet ) {
					// Set a flag so we know to set a header style or not
					echo '<input type="hidden" class="fusionredux-typography-google ' . $this->field['class'] . '" id="' . $this->field['id'] . '-google" name="' . $this->field['name'] . $this->field['name_suffix'] . '[google]' . '" type="text" value="' . $this->field['google'] . '" data-id="' . $this->field['id'] . '"  />';
				}

				if ( $this->field['font-backup'] === true ) {
					echo '<div class="select_wrapper typography-family-backup" style="width: 220px; margin-right: 5px;">';
					echo '<label>' . __( 'Backup Font Family', 'Avada' ) . '</label>';
					echo '<select data-placeholder="' . __( 'Backup Font Family', 'Avada' ) . '" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-backup]' . '" class="fusionredux-typography fusionredux-typography-family-backup ' . $this->field['class'] . '" id="' . $this->field['id'] . '-family-backup" data-id="' . $this->field['id'] . '" data-value="' . $this->value['font-backup'] . '">';
					echo '<option data-google="false" data-details="" value=""></option>';

					foreach ( $this->field['fonts'] as $i => $family ) {
						echo '<option data-google="true" value="' . $i . '"' . selected( $this->value['font-backup'], $i, false ) . '>' . $family . '</option>';
					}

					echo '</select></div>';
				}
			}

			/* Font Style/Weight */
			if ( $this->field['font-style'] === true || $this->field['font-weight'] === true ) {

				echo '<div class="select_wrapper typography-style" original-title="' . __( 'Font style', 'Avada' ) . '">';
				echo '<label>' . __( 'Font Weight &amp; Style', 'Avada' ) . '</label>';

				$style = $this->value['font-weight'] . $this->value['font-style'];

				echo '<input type="hidden" class="typography-font-weight" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-weight]' . '" value="' . $this->value['font-weight'] . '" data-id="' . $this->field['id'] . '"  /> ';
				echo '<input type="hidden" class="typography-font-style" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-style]' . '" value="' . $this->value['font-style'] . '" data-id="' . $this->field['id'] . '"  /> ';
				$multi = ( isset( $this->field['multi']['weight'] ) && $this->field['multi']['weight'] ) ? ' multiple="multiple"' : "";
				echo '<select' . $multi . ' data-placeholder="' . __( 'Style', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-style select ' . $this->field['class'] . '" original-title="' . __( 'Font style', 'Avada' ) . '" id="' . $this->field['id'] . '_style" data-id="' . $this->field['id'] . '" data-value="' . $style . '">';

				if ( empty( $this->value['subsets'] ) || empty( $this->value['font-weight'] ) ) {
					echo '<option value=""></option>';
				}

				$nonGStyles = array(
					'200' => 'Lighter',
					'400' => 'Normal',
					'700' => 'Bold',
					'900' => 'Bolder'
				);

				if ( ! isset( $this->value['font-weight'] ) && isset( $this->value['subsets'] ) ) {
					$this->value['font-weight'] = $this->value['subsets'];
				}

				foreach ( $nonGStyles as $i => $style ) {
					if ( ! isset( $this->value['font-weight'] ) ) {
						$this->value['font-weight'] = false;
					}

					if ( ! isset( $this->value['subsets'] ) ) {
						$this->value['subsets'] = false;
					}

					echo '<option value="' . $i . '" ' . selected( $this->value['font-weight'], $i, false ) . '>' . $style . '</option>';
				}

				echo '</select></div>';
			}

			/* Font Script */
			if ( $this->field['font-family'] == true && $this->field['subsets'] == true && $this->field['google'] == true ) {
				echo '<div class="select_wrapper typography-script tooltip" original-title="' . __( 'Font subsets', 'Avada' ) . '">';
				echo '<input type="hidden" class="typography-subsets" name="' . $this->field['name'] . $this->field['name_suffix'] . '[subsets]' . '" value="' . $this->value['subsets'] . '" data-id="' . $this->field['id'] . '"  /> ';
				echo '<label>' . __( 'Font Subsets', 'Avada' ) . '</label>';
				$multi = ( isset( $this->field['multi']['subset'] ) && $this->field['multi']['subset'] ) ? ' multiple="multiple"' : "";
				echo '<select'.$multi.' data-placeholder="' . __( 'Subsets', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-subsets ' . $this->field['class'] . '" original-title="' . __( 'Font script', 'Avada' ) . '"  id="' . $this->field['id'] . '-subsets" data-value="' . $this->value['subsets'] . '" data-id="' . $this->field['id'] . '" >';

				if ( empty( $this->value['subsets'] ) ) {
					echo '<option value=""></option>';
				}

				echo '</select></div>';
			}

			/* Font Align */
			if ( $this->field['text-align'] === true ) {
				echo '<div class="select_wrapper typography-align tooltip" original-title="' . __( 'Text Align', 'Avada' ) . '">';
				echo '<label>' . __( 'Text Align', 'Avada' ) . '</label>';
				echo '<select data-placeholder="' . __( 'Text Align', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-align ' . $this->field['class'] . '" original-title="' . __( 'Text Align', 'Avada' ) . '"  id="' . $this->field['id'] . '-align" name="' . $this->field['name'] . $this->field['name_suffix'] . '[text-align]' . '" data-value="' . $this->value['text-align'] . '" data-id="' . $this->field['id'] . '" >';
				echo '<option value=""></option>';

				$align = array(
					'inherit',
					'left',
					'right',
					'center',
					'justify',
					'initial'
				);

				foreach ( $align as $v ) {
					echo '<option value="' . $v . '" ' . selected( $this->value['text-align'], $v, false ) . '>' . ucfirst( $v ) . '</option>';
				}

				echo '</select></div>';
			}

			/* Text Transform */
			if ( $this->field['text-transform'] === true ) {
				echo '<div class="select_wrapper typography-transform tooltip" original-title="' . __( 'Text Transform', 'Avada' ) . '">';
				echo '<label>' . __( 'Text Transform', 'Avada' ) . '</label>';
				echo '<select data-placeholder="' . __( 'Text Transform', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-transform ' . $this->field['class'] . '" original-title="' . __( 'Text Transform', 'Avada' ) . '"  id="' . $this->field['id'] . '-transform" name="' . $this->field['name'] . $this->field['name_suffix'] . '[text-transform]' . '" data-value="' . $this->value['text-transform'] . '" data-id="' . $this->field['id'] . '" >';
				echo '<option value=""></option>';

				$values = array(
					'none',
					'capitalize',
					'uppercase',
					'lowercase',
					'initial',
					'inherit'
				);

				foreach ( $values as $v ) {
					echo '<option value="' . $v . '" ' . selected( $this->value['text-transform'], $v, false ) . '>' . ucfirst( $v ) . '</option>';
				}

				echo '</select></div>';
			}

			/* Font Variant */
			if ( $this->field['font-variant'] === true ) {
				echo '<div class="select_wrapper typography-font-variant tooltip" original-title="' . __( 'Font Variant', 'Avada' ) . '">';
				echo '<label>' . __( 'Font Variant', 'Avada' ) . '</label>';
				echo '<select data-placeholder="' . __( 'Font Variant', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-font-variant ' . $this->field['class'] . '" original-title="' . __( 'Font Variant', 'Avada' ) . '"  id="' . $this->field['id'] . '-font-variant" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-variant]' . '" data-value="' . $this->value['font-variant'] . '" data-id="' . $this->field['id'] . '" >';
				echo '<option value=""></option>';

				$values = array(
					'inherit',
					'normal',
					'small-caps'
				);

				foreach ( $values as $v ) {
					echo '<option value="' . $v . '" ' . selected( $this->value['font-variant'], $v, false ) . '>' . ucfirst( $v ) . '</option>';
				}

				echo '</select></div>';
			}

			/* Text Decoration */
			if ( $this->field['text-decoration'] === true ) {
				echo '<div class="select_wrapper typography-decoration tooltip" original-title="' . __( 'Text Decoration', 'Avada' ) . '">';
				echo '<label>' . __( 'Text Decoration', 'Avada' ) . '</label>';
				echo '<select data-placeholder="' . __( 'Text Decoration', 'Avada' ) . '" class="fusionredux-typography fusionredux-typography-decoration ' . $this->field['class'] . '" original-title="' . __( 'Text Decoration', 'Avada' ) . '"  id="' . $this->field['id'] . '-decoration" name="' . $this->field['name'] . $this->field['name_suffix'] . '[text-decoration]' . '" data-value="' . $this->value['text-decoration'] . '" data-id="' . $this->field['id'] . '" >';
				echo '<option value=""></option>';

				$values = array(
					'none',
					'inherit',
					'underline',
					'overline',
					'line-through',
					'blink'
				);

				foreach ( $values as $v ) {
					echo '<option value="' . $v . '" ' . selected( $this->value['text-decoration'], $v, false ) . '>' . ucfirst( $v ) . '</option>';
				}

				echo '</select></div>';
			}

			/* Font Size */
			if ( $this->field['font-size'] === true ) {
				echo '<div class="input_wrapper font-size fusionredux-container-typography">';
				echo '<label>' . __( 'Font Size', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-size mini typography-input ' . $this->field['class'] . '" title="' . __( 'Font Size', 'Avada' ) . '" placeholder="' . __( 'Size', 'Avada' ) . '" id="' . $this->field['id'] . '-size" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-size]' . '" value="' . $this->value['font-size'] . '" data-value="' . $this->value['font-size'] . '"></div>';
				echo '<input type="hidden" class="typography-font-size" name="' . $this->field['name'] . $this->field['name_suffix'] . '[font-size]' . '" value="' . $this->value['font-size'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			/* Line Height */
			if ( $this->field['line-height'] === true ) {
				echo '<div class="input_wrapper line-height fusionredux-container-typography">';
				echo '<label>' . __( 'Line Height', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-height mini typography-input ' . $this->field['class'] . '" title="' . __( 'Line Height', 'Avada' ) . '" placeholder="' . __( 'Height', 'Avada' ) . '" id="' . $this->field['id'] . '-height" value="' . $this->value['line-height'] . '" data-value="' . $this->value['line-height'] . '"></div>';
				echo '<input type="hidden" class="typography-line-height" name="' . $this->field['name'] . $this->field['name_suffix'] . '[line-height]' . '" value="' . $this->value['line-height'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			/* Word Spacing */
			if ( $this->field['word-spacing'] === true ) {
				echo '<div class="input_wrapper word-spacing fusionredux-container-typography">';
				echo '<label>' . __( 'Word Spacing', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-word mini typography-input ' . $this->field['class'] . '" title="' . __( 'Word Spacing', 'Avada' ) . '" placeholder="' . __( 'Word Spacing', 'Avada' ) . '" id="' . $this->field['id'] . '-word" value="' . $this->value['word-spacing'] . '" data-value="' . $this->value['word-spacing'] . '"></div>';
				echo '<input type="hidden" class="typography-word-spacing" name="' . $this->field['name'] . $this->field['name_suffix'] . '[word-spacing]' . '" value="' . $this->value['word-spacing'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			/* Letter Spacing */
			if ( $this->field['letter-spacing'] === true ) {
				echo '<div class="input_wrapper letter-spacing fusionredux-container-typography">';
				echo '<label>' . __( 'Letter Spacing', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-letter mini typography-input ' . $this->field['class'] . '" title="' . __( 'Letter Spacing', 'Avada' ) . '" placeholder="' . __( 'Letter Spacing', 'Avada' ) . '" id="' . $this->field['id'] . '-letter" value="' . $this->value['letter-spacing'] . '" data-value="' . $this->value['letter-spacing'] . '"></div>';
				echo '<input type="hidden" class="typography-letter-spacing" name="' . $this->field['name'] . $this->field['name_suffix'] . '[letter-spacing]' . '" value="' . $this->value['letter-spacing'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			echo '<div class="clearfix"></div>';

			/* Top Margin */
			if ( $this->field['margin-top'] === true ) {
				echo '<div class="input_wrapper margin-top fusionredux-container-typography">';
				echo '<label>' . __( 'Margin Top', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-margin-top mini typography-input ' . $this->field['class'] . '" title="' . __( 'Margin Top', 'Avada' ) . '" placeholder="' . __( 'Top', 'Avada' ) . '" id="' . $this->field['id'] . '-margin-top" name="' . $this->field['name'] . $this->field['name_suffix'] . '[margin-top]' . '" value="' . $this->value['margin-top'] . '" data-value="' . $this->value['margin-top'] . '"></div>';
				echo '<input type="hidden" class="typography-margin-top" name="' . $this->field['name'] . $this->field['name_suffix'] . '[margin-top]' . '" value="' . $this->value['margin-top'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			/* Bottom Margin */
			if ( $this->field['margin-bottom'] === true ) {
				echo '<div class="input_wrapper margin-bottom fusionredux-container-typography">';
				echo '<label>' . __( 'Margin Bottom', 'Avada' ) . '</label>';
				echo '<div class="input"><input type="text" class="span2 fusionredux-typography fusionredux-typography-margin-bottom mini typography-input ' . $this->field['class'] . '" title="' . __( 'Margin Bottom', 'Avada' ) . '" placeholder="' . __( 'Bottom', 'Avada' ) . '" id="' . $this->field['id'] . '-margin-bottom" name="' . $this->field['name'] . $this->field['name_suffix'] . '[margin-bottom]' . '" value="' . $this->value['margin-bottom'] . '" data-value="' . $this->value['margin-bottom'] . '"></div>';
				echo '<input type="hidden" class="typography-margin-bottom" name="' . $this->field['name'] . $this->field['name_suffix'] . '[margin-bottom]' . '" value="' . $this->value['margin-bottom'] . '" data-id="' . $this->field['id'] . '"  />';
				echo '</div>';
			}

			echo '<div class="clearfix"></div>';

			/* Font Color */
			if ( $this->field['color'] === true ) {
				$default = "";

				if ( empty( $this->field['default']['color'] ) && ! empty( $this->field['color'] ) ) {
					$default = $this->value['color'];
				} else if ( ! empty( $this->field['default']['color'] ) ) {
					$default = $this->field['default']['color'];
				}

				echo '<div class="picker-wrapper">';
				echo '<label>' . __( 'Font Color', 'Avada' ) . '</label>';
				echo '<div id="' . $this->field['id'] . '_color_picker" class="colorSelector typography-color"><div style="background-color: ' . $this->value['color'] . '"></div></div>';
				echo '<input data-default-color="' . $default . '" class="fusionredux-color fusionredux-typography-color ' . $this->field['class'] . '" original-title="' . __( 'Font color', 'Avada' ) . '" id="' . $this->field['id'] . '-color" name="' . $this->field['name'] . $this->field['name_suffix'] . '[color]' . '" type="text" data-alpha="true" value="' . $this->value['color'] . '" data-id="' . $this->field['id'] . '" />';
				echo '</div>';
			}

			echo '<div class="clearfix"></div>';

			/* Font Preview */
			if ( ! isset( $this->field['preview'] ) || $this->field['preview'] !== false ) {
				if ( isset( $this->field['preview']['text'] ) ) {
					$g_text = $this->field['preview']['text'];
				} else {
					$g_text = '1 2 3 4 5 6 7 8 9 0 A B C D E F G H I J K L M N O P Q R S T U V W X Y Z a b c d e f g h i j k l m n o p q r s t u v w x y z';
				}

				$style = '';
				if ( isset( $this->field['preview']['always_display'] ) ) {
					if ( true === filter_var( $this->field['preview']['always_display'], FILTER_VALIDATE_BOOLEAN ) ) {
						if ( $isGoogleFont == true && isset( $fontFamily ) && is_array( $fontFamily ) && isset( $fontFamily[0] ) ) {
							$this->parent->typography_preview[ $fontFamily[0] ] = array(
								'font-style' => array( $this->value['font-weight'] . $this->value['font-style'] ),
								'subset'     => array( $this->value['subsets'] )
							);

							$protocol = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ) ? "https:" : "http:";

							wp_deregister_style( 'fusionredux-typography-preview' );
							wp_dequeue_style( 'fusionredux-typography-preview' );

							wp_register_style( 'fusionredux-typography-preview', $protocol . $this->makeGoogleWebfontLink( $this->parent->typography_preview ), '', time() );
							wp_enqueue_style( 'fusionredux-typography-preview' );
						}

						$style = 'display: block; font-family: ' . $this->value['font-family'] . '; font-weight: ' . $this->value['font-weight'] . ';';
					}
				}

				if ( isset( $this->field['preview']['font-size'] ) ) {
					$style .= 'font-size: ' . $this->field['preview']['font-size'] . ';';
					$inUse = '1';
				} else {
					//$g_size = '';
					$inUse = '0';
				}

				echo '<p data-preview-size="' . $inUse . '" class="clear ' . $this->field['id'] . '_previewer typography-preview" ' . 'style="' . $style . '">' . $g_text . '</p>';
				echo '</div>'; // end typography container
			}
		}  //function

		/**
		 * Enqueue Function.
		 * If this field requires any scripts, or css define this function and register/enqueue the scripts/css
		 *
		 * @since FusionReduxFramework 1.0.0
		 */
		function enqueue() {
			if (!wp_style_is('select3-css')) {
				wp_enqueue_style( 'select3-css' );
			}

			if (!wp_style_is('wp-color-picker')) {
				wp_enqueue_style( 'wp-color-picker' );
			}

			if (!wp_script_is( 'fusion-redux-field-typography-js' )) {
				wp_enqueue_script(
					'fusion-redux-field-typography-js',
					trailingslashit( FUSION_LIBRARY_URL ) . 'inc/redux/custom-fields/typography/field_typography.js',
					array( 'jquery', 'wp-color-picker', 'select3-js', 'fusionredux-js' ),
					time(),
					true
				);
			}

			wp_localize_script(
				'fusion-redux-field-typography-js',
				'fusionredux_ajax_script',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			);

			if ($this->parent->args['dev_mode']) {
				if (!wp_style_is('fusionredux-color-picker-css')) {
					wp_enqueue_style( 'fusionredux-color-picker-css' );
				}

				if (!wp_style_is('fusion-redux-field-typography-css')) {
					wp_enqueue_style(
						'fusion-redux-field-typography-css',
						trailingslashit( FUSION_LIBRARY_URL ) . 'inc/redux/custom-fields/typography/field_typography.css',
						array(),
						time(),
						'all'
					);
				}
			}
		}  //function

		/**
		 * makeGoogleWebfontLink Function.
		 * Creates the google fonts link.
		 *
		 * @since FusionReduxFramework 3.0.0
		 */
		function makeGoogleWebfontLink( $fonts ) {
			$link    = "";
			$subsets = array();

			foreach ( $fonts as $family => $font ) {
				if ( ! empty( $link ) ) {
					$link .= "%7C"; // Append a new font to the string
				}
				$link .= $family;

				if ( ! empty( $font['font-style'] ) || ! empty( $font['all-styles'] ) ) {
					$link .= ':';
					if ( ! empty( $font['all-styles'] ) ) {
						$link .= implode( ',', $font['all-styles'] );
					} else if ( ! empty( $font['font-style'] ) ) {
						$link .= implode( ',', $font['font-style'] );
					}
				}

				if ( ! empty( $font['subset'] ) ) {
					foreach ( $font['subset'] as $subset ) {
						if ( ! in_array( $subset, $subsets ) ) {
							array_push( $subsets, $subset );
						}
					}
				}
			}

			if ( ! empty( $subsets ) ) {
				$link .= "&subset=" . implode( ',', $subsets );
			}


			return 'https://fonts.googleapis.com/css?family=' . str_replace( '|', '%7C', $link );
		}

		/**
		 * makeGoogleWebfontString Function.
		 * Creates the google fonts link.
		 *
		 * @since FusionReduxFramework 3.1.8
		 */
		function makeGoogleWebfontString( $fonts ) {

			$link    = "";
			$subsets = array();

			foreach ( $fonts as $family => $font ) {
				if ( ! empty( $link ) ) {
					$link .= "', '"; // Append a new font to the string
				}
				$link .= $family;

				if ( ! empty( $font['font-style'] ) || ! empty( $font['all-styles'] ) ) {
					$link .= ':';
					if ( ! empty( $font['all-styles'] ) ) {
						$link .= implode( ',', $font['all-styles'] );
					} else if ( ! empty( $font['font-style'] ) ) {
						$link .= implode( ',', $font['font-style'] );
					}
				}

				if ( ! empty( $font['subset'] ) ) {
					foreach ( $font['subset'] as $subset ) {
						if ( ! in_array( $subset, $subsets ) ) {
							array_push( $subsets, $subset );
						}
					}
				}
			}

			if ( ! empty( $subsets ) ) {
				$link .= "&subset=" . implode( ',', $subsets );
			}

			return "'" . $link . "'";
		}

		function output() {
			$font = $this->value;

			// Shim out old arg to new
			if ( isset( $this->field['all_styles'] ) && ! empty( $this->field['all_styles'] ) ) {
				$this->field['all-styles'] = $this->field['all_styles'];
				unset ( $this->field['all_styles'] );
			}

			// Check for font-backup.  If it's set, stick it on a variabhle for
			// later use.
			if ( ! empty( $font['font-family'] ) && ! empty( $font['font-backup'] ) ) {
				$font['font-family'] = str_replace( ', ' . $font['font-backup'], '', $font['font-family'] );
				$fontBackup          = ',' . $font['font-backup'];
			}

//                if (strpos($font['font-family'], ' ')) {
//                    $font['font-family'] = '"' . $font['font-family'] . '"';
//                }

			$style = '';

			$fontValueSet = false;

			if ( ! empty( $font ) && is_array( $font ) ) {
				foreach ( $font as $key => $value ) {
					if ( ! empty( $value ) && in_array( $key, array( 'font-family', 'font-weight' ) ) ) {
						$fontValueSet = true;
					}
				}
			}

			if ( ! empty( $font ) && is_array( $font ) ) {
				foreach ( $font as $key => $value ) {
					if ( $key == 'font-options' ) {
						continue;
					}
					// Check for font-family key
					if ( 'font-family' == $key ) {

						// Enclose font family in quotes if spaces are in the
						// name.  This is necessary because if there are numerics
						// in the font name, they will not render properly.
						// Google should know better.
						if (strpos($value, ' ') && !strpos($value, ',')){
							$value = '"' . $value . '"';
						}

						// Ensure fontBackup isn't empty (we already option
						// checked this earlier.  No need to do it again.
						if ( ! empty( $fontBackup ) ) {

							// Apply the backup font to the font-family element
							// via the saved variable.  We do this here so it
							// doesn't get appended to the Google stuff below.
							$value .= $fontBackup;
						}
					}

					if ( empty( $value ) && in_array( $key, array(
							'font-weight',
							'font-style'
						) ) && $fontValueSet == true
					) {
						$value = "normal";
					}

					if ($key == 'font-weight' && $this->field['font-weight'] == false) {
						continue;
					}

					if ($key == 'font-style' && $this->field['font-style'] == false) {
						continue;
					}


					if ( $key == "google" || $key == "subsets" || $key == "font-backup" || empty( $value ) ) {
						continue;
					}
					$style .= $key . ':' . $value . ';';
				}
				if ( isset( $this->parent->args['async_typography'] ) && $this->parent->args['async_typography'] ) {
					$style .= 'opacity: 1;visibility: visible;-webkit-transition: opacity 0.24s ease-in-out;-moz-transition: opacity 0.24s ease-in-out;transition: opacity 0.24s ease-in-out;';
				}
			}

			if ( ! empty( $style ) ) {
				if ( ! empty( $this->field['output'] ) && is_array( $this->field['output'] ) ) {
					$keys = implode( ",", $this->field['output'] );
					$this->parent->outputCSS .= $keys . "{" . $style . '}';
					if ( isset( $this->parent->args['async_typography'] ) && $this->parent->args['async_typography'] ) {
						$key_string    = "";
						$key_string_ie = "";
						foreach ( $this->field['output'] as $value ) {
							$key_string .= ".wf-loading " . $value . ',';
							$key_string_ie .= ".ie.wf-loading " . $value . ',';
						}
						$this->parent->outputCSS .= $key_string . "{opacity: 0;}";
						$this->parent->outputCSS .= $key_string_ie . "{visibility: hidden;}";
					}
				}

				if ( ! empty( $this->field['compiler'] ) && is_array( $this->field['compiler'] ) ) {
					$keys = implode( ",", $this->field['compiler'] );
					$this->parent->compilerCSS .= $keys . "{" . $style . '}';
					if ( isset( $this->parent->args['async_typography'] ) && $this->parent->args['async_typography'] ) {
						$key_string    = "";
						$key_string_ie = "";
						foreach ( $this->field['compiler'] as $value ) {
							$key_string .= ".wf-loading " . $value . ',';
							$key_string_ie .= ".ie.wf-loading " . $value . ',';
						}
						$this->parent->compilerCSS .= $key_string . "{opacity: 0;}";
						$this->parent->compilerCSS .= $key_string_ie . "{visibility: hidden;}";
					}
				}
			}

			// Google only stuff!
			if ( ! empty( $font['font-family'] ) && ! empty( $this->field['google'] ) && filter_var( $this->field['google'], FILTER_VALIDATE_BOOLEAN ) ) {

				// Added standard font matching check to avoid output to Google fonts call - kp
				// If no custom font array was supplied, the load it with default
				// standard fonts.
				if ( empty( $this->field['fonts'] ) ) {
					$this->field['fonts'] = $this->std_fonts;
				}

				// Ensure the fonts array is NOT empty
				if ( ! empty( $this->field['fonts'] ) ) {

					//Make the font keys in the array lowercase, for case-insensitive matching
					$lcFonts = array_change_key_case( $this->field['fonts'] );

					// Rebuild font array with all keys stripped of spaces
					$arr = array();
					foreach ( $lcFonts as $key => $value ) {
						$key         = str_replace( ', ', ',', $key );
						$arr[ $key ] = $value;
					}

					if ( is_array( $this->field['custom_fonts'] ) ) {
						$lcFonts = array_change_key_case( $this->field['custom_fonts'] );
						foreach ( $lcFonts as $group => $fontArr ) {
							foreach ( $fontArr as $key => $value ) {
								$arr[ strtolower( $key ) ] = $key;
							}
						}
					}

					$lcFonts = $arr;

					unset( $arr );

					// lowercase chosen font for matching purposes
					$lcFont = strtolower( $font['font-family'] );

					// Remove spaces after commas in chosen font for mathcing purposes.
					$lcFont = str_replace( ', ', ',', $lcFont );

					// If the lower cased passed font-family is NOT found in the standard font array
					// Then it's a Google font, so process it for output.
					if ( ! array_key_exists( $lcFont, $lcFonts ) ) {
						$family = $font['font-family'];

						// Don't add the font if it's a custom font.
						$custom_fonts = fusion_library()->get_option( 'custom_fonts' );
						if ( ! empty( $custom_fonts ) && isset( $custom_fonts['name'] ) ) {
							foreach ( $custom_fonts['name'] as $key => $name ) {
								if ( $name == $font['font-family'] ) {
									return;
								}
							}
						}

						// Strip out spaces in font names and replace with with plus signs
						// TODO?: This method doesn't respect spaces after commas, hence the reason
						// for the std_font array keys having no spaces after commas.  This could be
						// fixed with RegEx in the future.
						$font['font-family'] = str_replace( ' ', '+', $font['font-family'] );

						// Push data to parent typography variable.
						if ( empty( $this->parent->typography[ $font['font-family'] ] ) ) {
							$this->parent->typography[ $font['font-family'] ] = array();
						}

						if ( isset( $this->field['all-styles'] ) ) {
							if ( ! isset( $font['font-options'] ) || empty( $font['font-options'] ) ) {
								$this->getGoogleArray();

								if ( isset( $this->parent->googleArray ) && ! empty( $this->parent->googleArray ) && isset( $this->parent->googleArray[ $family ] ) ) {
									$font['font-options'] = $this->parent->googleArray[ $family ];
								}
							} else {
								$font['font-options'] = json_decode( $font['font-options'], true );
							}
							//print_r($font['font-options']);
							//exit();
						}

						if ( isset( $font['font-options'] ) && ! empty( $font['font-options'] ) && isset( $this->field['all-styles'] ) && filter_var( $this->field['all-styles'], FILTER_VALIDATE_BOOLEAN ) ) {
							if ( isset( $font['font-options'] ) && ! empty( $font['font-options']['variants'] ) ) {
								if ( ! isset( $this->parent->typography[ $font['font-family'] ]['all-styles'] ) || empty( $this->parent->typography[ $font['font-family'] ]['all-styles'] ) ) {
									$this->parent->typography[ $font['font-family'] ]['all-styles'] = array();
									if ( is_array( $font['font-options']['variants'] ) ) {
										foreach ( $font['font-options']['variants'] as $variant ) {
											$this->parent->typography[ $font['font-family'] ]['all-styles'][] = $variant['id'];
										}
									}
								}
							}
						}

						if ( ! empty( $font['font-weight'] ) ) {
							if ( empty( $this->parent->typography[ $font['font-family'] ]['font-weight'] ) || ! in_array( $font['font-weight'], $this->parent->typography[ $font['font-family'] ]['font-weight'] ) ) {
								$style = $font['font-weight'];
							}

							if ( ! empty( $font['font-style'] ) ) {
								$style .= $font['font-style'];
							}

							if ( empty( $this->parent->typography[ $font['font-family'] ]['font-style'] ) || ! in_array( $style, $this->parent->typography[ $font['font-family'] ]['font-style'] ) ) {
								$this->parent->typography[ $font['font-family'] ]['font-style'][] = $style;
							}
						}

						if ( ! empty( $font['subsets'] ) ) {
							if ( empty( $this->parent->typography[ $font['font-family'] ]['subset'] ) || ! in_array( $font['subsets'], $this->parent->typography[ $font['font-family'] ]['subset'] ) ) {
								$this->parent->typography[ $font['font-family'] ]['subset'][] = $font['subsets'];
							}
						}
					} // !array_key_exists
				} //!empty fonts array
			} // Typography not set
		}

		private function localizeStdFonts() {
			if ( false == $this->user_fonts ) {
				if ( isset( $this->parent->fonts['std'] ) && ! empty( $this->parent->fonts['std'] ) ) {
					return;
				}

				$this->parent->font_groups['std'] = array(
					'text'     => __( 'Standard Fonts', 'Avada' ),
					'children' => array(),
				);

				foreach ( $this->field['fonts'] as $font => $extra ) {
					$this->parent->font_groups['std']['children'][] = array(
						'id'          => $font,
						'text'        => $font,
						'data-google' => 'false',
					);
				}
			}

			$this->parent->font_groups = apply_filters( 'fusion_redux_typography_font_groups', $this->parent->font_groups );

		}

		/**
		 *   Construct the google array from the stored JSON/HTML

		 */
		function getGoogleArray() {

			if ( ( isset( $this->parent->fonts['google'] ) && ! empty( $this->parent->fonts['google'] ) ) || isset( $this->parent->fonts['google'] ) && $this->parent->fonts['google'] == false ) {
				return;
			}

			$gFile = FUSION_LIBRARY_PATH . '/inc/googlefonts-array.php';

			if ( ! file_exists( $gFile ) ) {

				$result = wp_remote_get( apply_filters( 'fusionredux-google-fonts-api-url', 'https://www.googleapis.com/webfonts/v1/webfonts?key=' ) . $this->parent->args['google_api_key'], array( 'sslverify' => false ) );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$result = json_decode( $result['body'] );
					foreach ( $result->items as $font ) {
						$this->parent->googleArray[ $font->family ] = array(
							'variants' => $this->getVariants( $font->variants ),
							'subsets'  => $this->getSubsets( $font->subsets )
						);
					}

					if ( ! empty( $this->parent->googleArray ) ) {
						$this->parent->filesystem->execute( 'put_contents', $gFile, array( 'content' => "<?php return json_decode( '" . json_encode( $this->parent->googleArray ) . "', true );" ) );
					}
				}
			}

			if ( ! file_exists( $gFile ) ) {
				$this->parent->fonts['google'] = false;

				return;
			}

			if ( ! isset( $this->parent->fonts['google'] ) || empty( $this->parent->fonts['google'] ) ) {

				$fonts = include $gFile;

				if ( $fonts === true ) {
					$this->parent->fonts['google'] = false;

					return;
				}

				if ( isset( $fonts ) && ! empty( $fonts ) && is_array( $fonts ) && $fonts != false ) {
					$this->parent->fonts['google'] = $fonts;
					$this->parent->googleArray     = $fonts;

					// optgroup
					$this->parent->font_groups['google'] = array(
						'text'     => __( 'Google Webfonts', 'Avada' ),
						'children' => array(),
					);

					// options
					foreach ( $this->parent->fonts['google'] as $extra ) {
						if ( is_array( $extra ) ) {
							foreach ( $extra as $extra_item ) {
								if ( is_array( $extra_item ) && isset( $extra_item['family'] ) ) {
									$this->parent->font_groups['google']['children'][] = array(
										'id'          => $extra_item['family'],
										'text'        => $extra_item['family'],
										'data-google' => 'true'
									);			
								}
							}
						}
					}
				}
			}
		}

		/**
		 * getSubsets Function.
		 * Clean up the Google Webfonts subsets to be human readable
		 *
		 * @since FusionReduxFramework 0.2.0
		 */
		private function getSubsets( $var ) {
			$result = array();

			foreach ( $var as $v ) {
				if ( strpos( $v, "-ext" ) ) {
					$name = sprintf(
						/* Translators: language subset. */
						esc_html__( '%s Extended', 'Avada' ),
						ucfirst( str_replace( '-ext', '', $v ) )
					);
					$name = ucfirst( str_replace( "-ext", " Extended", $v ) );
				} else {
					$name = ucfirst( $v );
				}

				array_push( $result, array(
					'id'   => $v,
					'name' => $name
				) );
			}

			return array_filter( $result );
		}

		/**
		 * getVariants Function.
		 * Clean up the Google Webfonts variants to be human readable
		 *
		 * @since FusionReduxFramework 0.2.0
		 */
		private function getVariants( $var ) {
			$result = array();
			$italic = array();

			foreach ( $var as $v ) {
				$name = "";
				if ( $v[0] == 1 ) {
					$name = esc_html( 'Ultra-Light 100', 'Avada' );
				} else if ( $v[0] == 2 ) {
					$name = esc_html( 'Light 200', 'Avada' );
				} else if ( $v[0] == 3 ) {
					$name = esc_html( 'Book 300', 'Avada' );
				} else if ( $v[0] == 4 || $v[0] == "r" || $v[0] == "i" ) {
					$name = esc_html( 'Normal 400', 'Avada' );
				} else if ( $v[0] == 5 ) {
					$name = esc_html( 'Medium 500', 'Avada' );
				} else if ( $v[0] == 6 ) {
					$name = esc_html( 'Semi-Bold 600', 'Avada' );
				} else if ( $v[0] == 7 ) {
					$name = esc_html( 'Bold 700', 'Avada' );
				} else if ( $v[0] == 8 ) {
					$name = esc_html( 'Extra-Bold 800', 'Avada' );
				} else if ( $v[0] == 9 ) {
					$name = esc_html( 'Ultra-Bold 900', 'Avada' );
				}

				if ( $v == 'regular' ) {
					$v = '400';
				}

				if ( strpos( $v, "italic" ) || $v == "italic" ) {
					$name = sprintf(
						/* Translators: font-weight. */
						esc_html__( '%s Italic', 'Avada' ),
						$name
					);
					$name = trim( $name );
					if ( $v == "italic" ) {
						$v = "400italic";
					}
					$italic[] = array(
						'id'   => $v,
						'name' => $name
					);
				} else {
					$result[] = array(
						'id'   => $v,
						'name' => $name
					);
				}
			}

			foreach ( $italic as $item ) {
				$result[] = $item;
			}

			return array_filter( $result );
		}
	}
}
