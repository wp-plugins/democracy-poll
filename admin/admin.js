jQuery(document).ready(function($){
	'use strict';
	
	var focusFunction = function(){
		// проверка нужно ли добавлять поле новое
		var $li = $(this).closest('li');
		var $nextAnsw    = $li.next('li.answ');
		var $nextAnswTxt = $nextAnsw.find('.answ-text');

		if( $nextAnsw.length ) return this;
		
		// добавляем поле
		$(this).addAnswField();
	};
	
	var blurFunction = function(){
		
	};
		
	// добавляет li блок (поле нового ответа) после текущего li
	$.fn.addAnswField = function(){
		var $li = this.closest('li');
		var $_li = $li.clone();
		$_li.find('input').remove();
		
		var $input = $('<input class="answ-text" type="text" name="dmc_new_answers[]">');
		$input.focus( focusFunction );
		$input.blur( function(){ if( $input.val() == '' ) $_li.remove(); } ); // удаляем блок, если в поле не было введено данных
		
		$_li.prepend( $input );
		$li.after( $_li );
		return this;
	};
	
	
	$('.new-poll-answers .answ-text').focus( focusFunction );
	$('.new-poll-answers li.answ').last().addAnswField(); // добавим поле с новым ответом
	
	
	// добавим кнопки удаления
	$('.new-poll-answers li.answ').each( function(){
		var delButton = $('<span class="dem-del-button" onclick="return demRemoveAnswer(this);">×</span>');
		$(this).append( delButton );
	} );
	
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
	
	
	// toggle blocks set arrows
	var $arrow = $('<div class="tog-arrow" style="cursor:pointer; font-size:80%; width:80%;text-align: right;">▼</div>').css({ position:'absolute', right:0, top:0, padding:'1em 1.2em', color:'#ccc' });
	$('.group .title').each(function(){
		var $_arrow = $arrow.clone();
		$(this).append( $_arrow );
		
		$_arrow.click(function(){
			if( $(this).text() == '▼' ){
				$(this).text('▲');
				var h = $(this).height() * 0.6;
				$(this).closest('.group').css('overflow','hidden').height( h );
			}
			else{
				$(this).text('▼');
				$(this).closest('.group').css('overflow','visible').height('auto');
			}
		});
		
	})
    
});











