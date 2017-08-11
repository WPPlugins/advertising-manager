( function() {
  tinymce.PluginManager.add( 'advman', function( editor, url ) {

    // Add a button that opens a window
    editor.addButton( 'advman_ad_key', {
      title: 'Insert Ad',
      type:  'menubutton',
      icon: 'icon advman-editor-icon',
      menu: advman_build_tinymce_menu(editor)
    } );

  } );

} )();