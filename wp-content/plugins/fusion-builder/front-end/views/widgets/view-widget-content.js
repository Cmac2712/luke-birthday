/* global FusionApp, fusionAppConfig, FusionEvents */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		FusionPageBuilder.fusion_widget_content = window.wp.Backbone.View.extend( {

			template: FusionPageBuilder.template( jQuery( '#tmpl-fusion_widget_content' ).html() ),

			className: 'fusion-widget-content-view',

			events: {
			},

			filterRenderContent: function ( output ) {
				return output;
			},

			beforeRemove: function () { // eslint-disable-line no-empty-function
			},

			removeElement: function() {
				FusionApp.deleteScripts( this.cid );
				this.beforeRemove();
				this.remove();
			},

			initialize: function() {

				// Set markup
				if ( this.model.attributes.markup && this.model.attributes.markup.output ) {
					this.model.attributes.markup = FusionApp.removeScripts( this.filterRenderContent( this.model.attributes.markup.output ), this.cid );
					this.injectScripts();
				}

				this.onInit();
			},

			render: function() {
				if ( !this.isAjax && ( 'undefined' === typeof this.model.attributes.markup || '' === this.model.attributes.markup ) ) {
					FusionApp.deleteScripts( this.cid );
					this.getHTML( this );
				}
				this.$el.html( this.template( this.model.attributes ) );

				this.onRender();

				return this;
			},

			onInit: function() {
				this.isAjax = false;
			},

			onRender: function() { // eslint-disable-line no-empty-function
			},

			getMarkup: function( view ) {
				this.getHTML( view );
			},

			injectScripts: function() {
				var self, dfd;
				self = this;
				dfd	 = jQuery.Deferred();

				setTimeout( function() {
					FusionApp.injectScripts( self.cid );
					dfd.resolve();
				}, 100 );
				return dfd.promise();
			},

			getHTML: function( view ) {
				var self = this,
					params;

				params = view.model.get( 'params' );
				self.isAjax = true;

				this.beforeGetHTML();

				jQuery.ajax( {
					type: 'POST',
					url: fusionAppConfig.ajaxurl,
					dataType: 'JSON',
					data: {
						action: 'fusion_get_widget_markup',
						fusion_load_nonce: fusionAppConfig.fusion_load_nonce,
						type: view.model.attributes.params.type,
						params: params,
						widget_id: view.model.cid
					},
					success: function( response ) {
						self.isAjax = false;
						FusionApp.deleteScripts( self.cid );

						view.model.attributes.markup = FusionApp.removeScripts( self.filterRenderContent( response ), self.cid );
						view.render();
						self.injectScripts()
						.then( function() {
							self.afterGetHTML();
							// Remove parent loading overlay
							FusionEvents.trigger( 'fusion-widget-rendered' );
						} );
					}
				} );
			},

			beforeGetHTML: function() { // eslint-disable-line no-empty-function
			},

			afterGetHTML: function() { // eslint-disable-line no-empty-function
			}

		} );

	} );

}() );
