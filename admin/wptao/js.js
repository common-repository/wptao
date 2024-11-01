(function() {
	tinymce.PluginManager.add('wptao_button', function( editor, url ) {
		var de = document.documentElement;
		var w = window.innerWidth || self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
		editor.addButton('wptao_button', {
			title: '插入商品',
			image: url + '/icon.png',
			//text: '',
			//icon: 'wptao',
			onclick: function() {
				 editor.windowManager.open(
                    {
                        title: "插入商品",
                        file: ( typeof ajaxurl !== 'undefined' ) ? ajaxurl + '?action=wptao_ajax&admininit=0&type=editor_wptao' : url + '/index.php',
                        width: w > 600 ? 600 : (w < 364 ? 364 : w),
                        height: 490,
                        inline: 1 // 是否使用模态对话框，而不是单独的浏览器窗口。
                    }
                );
			}
		});
	});
})();