jQuery(document).ready(function($){
	tinymce.PluginManager.add('demTiny', function(editor) {
		
		editor.addCommand('demTinyInsert', function() {
			var pID = $.trim( prompt(tinymce.translate('Введите ID опроса')) );
			while( isNaN( pID ) ){
				pID = $.trim( prompt( tinymce.translate('Ошибка: ID - это число. Введите ID еще раз') ));
			}
			if( pID >= -1 && pID != null && pID != "" ){
				editor.insertContent('[democracy id="' + pID + '"]');
			}
		});
		
		editor.addButton('demTiny', {
			text: false,
			tooltip: tinymce.translate('Вставка Опроса Democracy'),
			icon: 'dem dashicons-before dashicons-megaphone',
			onclick: function(){
				tinyMCE.activeEditor.execCommand('demTinyInsert')
			}
		});
	});	
});
