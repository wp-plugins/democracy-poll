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
	
});