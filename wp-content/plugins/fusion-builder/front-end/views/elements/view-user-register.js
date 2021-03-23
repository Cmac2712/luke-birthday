var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// User Register Element View.
		FusionPageBuilder.fusion_register = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				this.extras        = atts.extras;
				atts.values.action = 'register';

				// Create attribute objects.
				atts.loginShortCodeAttr     = this.buildLoginShortCodeAttr( atts.values );
				atts.loginShortcodeFormAttr = this.buildLoginShortcodeFormAttr( atts.values );
				atts.loginShortcodeButton   = this.buildLoginShortcodeButtonAttr( atts.values );
				atts.loggedIn               = true;
				atts.styles                 = this.buildRegisterStyles( atts.values );

				// Any extras that need passed on.
				atts.cid    = this.model.get( 'cid' );

				return atts;
			},

			/**
			 * Builds login attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildLoginShortCodeAttr: function( values ) {

				// LoginShortcode Attributes.
				var loginShortcode = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'fusion-login-box fusion-login-box-cid' + this.model.get( 'cid' ) + ' fusion-login-box-' + values.action + ' fusion-login-align-' + values.text_align + ' fusion-login-field-layout-' + values.form_field_layout
				} );

				if ( '' !== values[ 'class' ] ) {
					loginShortcode[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					loginShortcode.id = values.id;
				}

				return loginShortcode;
			},

			/**
			 * Builds form attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildLoginShortcodeFormAttr: function( values ) {

				// LoginShortcodeForm Attributes.
				var loginShortcodeForm = {
					class: 'fusion-login-form'
				};

				if ( '' !== values.form_background_color ) {
					loginShortcodeForm.style = 'background-color:' + values.form_background_color + ';';
				}

				loginShortcodeForm.name   = values.action + 'form';
				loginShortcodeForm.id     = values.action + 'form';
				loginShortcodeForm.method = 'post';
				loginShortcodeForm.action = '';

				return loginShortcodeForm;
			},

			/**
			 * Builds button attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildLoginShortcodeButtonAttr: function( values ) {

				// LoginShortcodeButton Attributes.
				var loginShortcodeButton = {
					class: 'fusion-login-button fusion-button button-default button-' + this.extras.button_size
				};

				if ( 'yes' !== values.button_fullwidth ) {
					loginShortcodeButton[ 'class' ] += ' fusion-login-button-no-fullwidth';
				}

				loginShortcodeButton.type = 'submit';
				loginShortcodeButton.name = 'wp-submit';

				return loginShortcodeButton;
			},

			/**
			 * Builds register Styles.
			 *
			 * @since 2.0
			 * @param {Object} values - The attributes.
			 * @return {Sting}
			 */
			buildRegisterStyles: function( values ) {
				var styles  = '',
					cid       = this.model.get( 'cid' );

				if ( '' !== values.heading_color ) {
					styles += '.fusion-login-box-cid' + cid + ' .fusion-login-heading{color:' + values.heading_color + ';}';
				}

				if ( '' !== values.caption_color ) {
					styles += '.fusion-login-box-cid' + cid + ' .fusion-login-caption{color:' + values.caption_color + ';}';
				}

				if ( '' !== values.link_color ) {
					styles += '.fusion-login-box-cid' + cid + ' a{color:' + values.link_color + ';}';
				}

				if ( '' !== styles ) {
					styles = '<style type="text/css">' + styles + '</style>';
				}

				values.label_class = 'yes' === values.show_labels  ? 'fusion-login-label' : 'fusion-hidden-content';

				return styles;
			}
		} );
	} );
}( jQuery ) );
