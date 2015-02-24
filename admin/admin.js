jQuery(document).ready(function($){
	'use strict';
	
	// добавить новый ответ
	$('.demAddAnswer').click(function(){
		var $_li = $(this).prev('li').clone();
		$_li.find('input').remove();
		$_li.prepend('<input type="text" name="dmc_new_answers[]">');
		$(this).before( $_li );
	});
	
	// добавим кнопки удаления
	$('.new-poll-answers li').each(function(){
		var delButton = $('<span class="dem-del-button" onclick="return demRemoveAnswer(this);">×</span>');
		$(this).append( delButton );
	});
	
	window.demRemoveAnswer = function( that ){		
		$(that).parent('li').remove();
	}
	
	// дата
	$('[name="dmc_end"]').datepicker({ dateFormat : 'dd-mm-yy' });
    
    
    // DESIGN
    $('.dem-screen').height(function(){ return $(this).outerHeight(); } );
    
    $('[data-dem-act], .democracy a').click(function(e){ e.preventDefault(); }); // отменяем клики
    
    // предпросмотр
    var $demLoader = $(document).find('.dem-loader').first(); // loader
    $('.poll.show-loader .dem-screen').append( $demLoader.css('display','table') );
    
    // wpColorPicker
    $('.iris_color').wpColorPicker();
    
    
    var myOptions ={};
    var $preview = $('.polls-preview');
    myOptions.change = function(event, ui){
        var hexcolor = $(this).wpColorPicker('color');
        $preview.css('background-color', hexcolor );
        console.log( hexcolor );
    };
	$('.preview-bg').wpColorPicker( myOptions );
    
    
    // ACE
    var $textarea = $('[name="additional_css"]').hide();
    var aceEl = $('<pre style="font-size:100%;"></pre>');
    $textarea.before( aceEl );
    
    var editor = ace.edit( aceEl[0] );
	editor.setOptions({
		maxLines: 'Infinity',
		minLines: 10,
		printMargin: false
	} );
	//    editor.getSession().setUseWrapMode( true );
    editor.setTheme("ace/theme/monokai");
    editor.getSession().setMode("ace/mode/css");
    editor.getSession().setValue( $textarea.val() );

    // set textarea value on change
    var settxval = function(){ $textarea.val( editor.getSession().getValue() ); };
    $textarea.closest('form').submit( settxval );
    editor.getSession().on('change', settxval);  
    
});











