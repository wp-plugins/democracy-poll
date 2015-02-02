jQuery(document).ready(function($){
	'use strict';
	
	// загрузка для AJAX. Точки ...
	var demLoading;
	var demLoadingDots = function(){		
		var isInput = this.is('input');

		var str = (isInput) ? this.val() : this.text();

		if( str.substring(str.length-3) == '...' )
			if( isInput ) 
				this.val( str.substring(0, str.length-3) );
			else
				this.text( str.substring(0, str.length-3) );
		else
			if( isInput )
				this[0].value     += '.';
			else
				this[0].innerHTML += '.';

		demLoading = setTimeout( demLoadingDots.bind(this), 200 );
	};
	

	var democracy  = '.democracy';
	var demResults = '.dem-results'; // селектор контейнера с результатами
	var userAnswer = '.dem-add-answer-txt'; // класс поля ответа
	
	// Добавить ответ ссылка
	$.fn.demAddAnswer = function(){
		var $the = this;
		var $demResults = $the.closest( demResults );
		var isMultiple  = $demResults.find('[type=checkbox]').length > 0;
		var $input      = $('<input type="text" class="'+ userAnswer.replace(/\./,'') +'" value="">'); // поле добавления ответа
		
		// обрабатывает input radio деселектим и вешаем событие клика
		$demResults.find('[type=radio]').each(function(){
			$(this).click(function(){
				$the.fadeIn(300);
				$( userAnswer ).remove();
			});
			
			if( $(this)[0].type == 'radio' ) this.checked = false; // uncheck
		});
		
		$the.hide().parent('li').append( $input );
		$input.hide().fadeIn(300).focus(); // animation

		// добавим кнопку удаления пользовательского текста
		if( isMultiple ){
			var $ua = $demResults.find( userAnswer );
			$('<span class="add-answer-txt-close">×</span>')
			.insertBefore( $ua )
			.css('line-height', $ua.outerHeight() + 'px' )
			.click( function(){
				var $par = $(this).parent('li');
				$par.find('input').remove();
				$par.find('a').fadeIn(300);
				$(this).remove();
			} );
		}

		return false; // !!!
	};
	
	// возвращает ответы
	$.fn.demCollectAnsw = function(){
		var $form     = this.closest('form');
		var $answers  = $form.find('[type=checkbox],[type=radio],[type=text]');
		var userText  = $form.find( userAnswer ).val();
		var answ      = [];

		var $checkbox = $answers.filter('[type=checkbox]:checked');
		// multiple
		if( $checkbox.length > 0 ){
			$checkbox.each(function(){
				answ.push( $(this).val() );
			});
		}
		// single
		else {
			var str = $answers.filter('[type=radio]:checked');
			if( str.length )
				answ.push( str.val() );
		}
		// user_added
		if ( userText ){
			answ.push( userText );
		}

		answ = answ.join(',');

		return answ ? answ : '';
	};
	
	// обрабатывает запросы при клике, вешается на событие клика
	$.fn.demDoAction = function( act ){
		var $the = this;
				
		var $dem = $the.closest( democracy );
		
		var demAjaxUrl = $dem.attr('data-ajax-url'); // URL ajax
		var data = {
			dem_pid: $dem.attr('data-pid'),
			dem_act: act,
			action: 'dem_ajax'
		};

		if( typeof data.dem_pid == 'undefined' ){ console.log('Poll id is not defined!'); return false; }
		
		// Соберем ответы
		if( act == 'vote' ){ data.answer_ids = $the.demCollectAnsw(); }
		
		// кнопка переголосовать, подтверждение
		if( act == 'delVoted' && ! confirm( $the.attr('data-confirm-text') ) ){ return false; }
		
		// кнопка добавления ответа посетителя
		if( act == 'newAnswer' ){ $the.demAddAnswer(); return false; }
		
		// AJAX
		demLoading = setTimeout( demLoadingDots.bind( $the ), 50 );
		$.post( demAjaxUrl, data, 
			function( respond ){
				// анимация
				var speed = 300;
				var $div  = $the.closest( demResults );
					
				var $_div  = $div.clone().html( respond ).css({height:'auto'}).appendTo( $div ); // получим высоту
				var newH = ($div.css('box-sizing') == 'border-box') ? $_div.outerHeight() : $_div.height();
			
				$div.css({ opacity:0 }).html( respond ).animate( {height: newH }, speed, function(){ $(this).animate({opacity:1}, speed*1.5); } );
				// снова устанавливаем событие клика для кнопок
				$div.demSetClick();
			}
		);

		return false; // !!!!
	};
	
	// установка событий AJAX запроса на все функции клика
	$.fn.demSetClick = function(){
		var attr = 'data-dem-act';
		this.find('['+attr+']')
		.each(function(){
			$(this).attr('href','#'); // удалим УРЛ чтобы не было видно УРЛ запроса
		})
		.click(function(e){
			e.preventDefault();
			$(this).blur().demDoAction( $(this).attr( attr ) );
		});
	};
	
	
	// Включаем нужные события
	$( document ).demSetClick();
	$( demResults ).each(function(){ $(this).height( $(this).height() ); }); // установим высоту жестко
	
	
});








/*  from prototype.js
Function.prototype.bind = function() {
  var __method = this, args = $A(arguments), object = args.shift();
  return function() {
    return __method.apply(object, args.concat($A(arguments)));
  }
}
var $A = Array.from = function(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0; i < iterable.length; i++)
      results.push(iterable[i]);
    return results;
  }
}
 */