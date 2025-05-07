/**
 * TinyMCE Smart TOC Button
 */
(function() {
    tinymce.PluginManager.add('smart_toc_button', function(editor, url) {
        editor.addButton('smart_toc_button', {
            title: 'Insert Table of Contents',
            image: url.replace('/js/tinymce-button.js', '/images/toc-icon.png'),
            onclick: function() {
                editor.insertContent('[smart_toc]');
            }
        });
    });
})();
