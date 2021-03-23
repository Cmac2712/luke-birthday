/* global tinymce, FusionPageBuilderApp, openShortcodeGenerator, builderConfig */
( function( $ ) {
	var pluginDir;

	if ( 'undefined' !== typeof tinymce && 'undefined' !== typeof FusionPageBuilderApp ) {

		pluginDir = ( 'undefined' !== typeof FusionPageBuilderApp.fusion_builder_plugin_dir ) ? FusionPageBuilderApp.fusion_builder_plugin_dir : builderConfig.fusion_builder_plugin_dir;

		tinymce.PluginManager.add( 'fusion_button', function( editor ) {

			if ( ( ( true === FusionPageBuilderApp.allowShortcodeGenerator && true !== FusionPageBuilderApp.shortcodeGenerator ) || 'content' === editor.id || 'excerpt' === editor.id ) || ( ( jQuery( 'body' ).hasClass( 'gutenberg-editor-page' ) || jQuery( 'body' ).hasClass( 'block-editor-page' ) ) && 0 === editor.id.indexOf( 'editor-' ) ) ) {

				editor.addButton( 'fusion_button', {
					title: 'Fusion Builder Element Generator',
					icon: true,
					image: pluginDir + 'images/icons/fb_logo.svg',
					onclick: function() {

						// Set editor that triggered shortcode generator.
						FusionPageBuilderApp.shortcodeGeneratorActiveEditor = editor;

						// Open shortcode generator.
						openShortcodeGenerator( $( this ) );
					}
				} );
			}
		} );
	}
}( jQuery ) );
