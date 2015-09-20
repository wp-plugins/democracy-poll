/*jslint browser: true, devel: true, eqeq: true, sloppy: true, vars: true, white: true*/

// @koala-prepend "js-cookie.js"


// main democracy vars & functions
+function($){
	'use strict';
	
	// INIT --------------
	$(window).load(function() {
		// Основные события Democracy для всех блоков ---
		$( demScreen ).filter(':visible').demInitActions(1);
		
		// запрет выбора ответов ---
		Dem.maxAnswLimit();
		
		/* ---
		 * Обработка кэша.
		 * Нужен установленный js-cookie
		 * и дополнительные js переменные и методы самого Democracy.
		 */
		var $cache = $('.dem-cache-screens');
		if( $cache.length > 0 ){
			//console.log('Democracy cache gear ON');
			$cache.demCacheInit();
		}
	});
	
	
	// HARD WORK --------------
	var democracy  = '.democracy',
		demScreen  = '.dem-screen', // селектор контейнера с результатами
		userAnswer = '.dem-add-answer-txt', // класс поля free ответа
		demAjaxUrl = $(democracy).data('ajax-url'),
		loader,
		$demLoader = $('.dem-loader').first(),
		Dem = {};
	
	$.fn.demLoadingDots = function(){
		var $the = this,
			isInput = $the.is('input'),
			str = (isInput) ? $the.val() : $the.text();

		if( str.substring(str.length-3) == '...' ){
			if( isInput ) 
				$the.val( str.substring(0, str.length-3) );
			else
				$the.text( str.substring(0, str.length-3) );
		}
		else{
			if( isInput )
				$the[0].value	 += '.';
			else
				$the[0].innerHTML += '.';
		}

		loader = setTimeout( function(){ $the.demLoadingDots(); }, 200 );
	};
	
	// Loader
	$.fn.demSetLoader = function(){
		var $the = this;
		
		if( $demLoader.length )
			$the.closest(demScreen).append( $demLoader.clone().css('display','table') );
		else
			loader = setTimeout( function(){ $the.demLoadingDots(); }, 50 ); // dots
		return this;
	};
	$.fn.demUnsetLoader = function(){
		if( $demLoader.length ) this.closest(demScreen).find('.dem-loader').remove();
		else clearTimeout( loader );
		return this;
	};
	
	// Добавить ответ пользователя (ссылка)
	$.fn.demAddAnswer = function(){
		var $the = this.first(),
			$demScreen  = $the.closest( demScreen ),
			isMultiple  = $demScreen.find('[type=checkbox]').length > 0,
			$input	  = $('<input type="text" class="'+ userAnswer.replace(/\./,'') +'" value="">'); // поле добавления ответа
		
		// покажем кнопку голосования
		$demScreen.find('.dem-vote-button').show();
		
		// обрабатывает input radio деселектим и вешаем событие клика
		$demScreen.find('[type=radio]').each(function(){
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
			var $ua = $demScreen.find( userAnswer );
			$('<span class="dem-add-answer-close">×</span>')
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
	
	// Собирает ответы и возращает их в виде строки
	$.fn.demCollectAnsw = function(){
		var $form	 = this.closest('form'),
			$answers  = $form.find('[type=checkbox],[type=radio],[type=text]'),
			userText  = $form.find( userAnswer ).val(),
			answ	  = [],
			$checkbox = $answers.filter('[type=checkbox]:checked');
		
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

		answ = answ.join('~');

		return answ ? answ : '';
	};
		
	// обрабатывает запросы при клике, вешается на событие клика
	$.fn.demDoAction = function( act ){
		var $the = this.first();
				
		var $dem = $the.closest( democracy );
		var data = {
			dem_pid: $dem.data('pid'),
			dem_act: act,
			action: 'dem_ajax'
		};

		if( typeof data.dem_pid == 'undefined' ){
			console.log('Poll id is not defined!');
			return false;
		}
		
		// Соберем ответы
		if( act == 'vote' ){ 
			data.answer_ids = $the.demCollectAnsw();
			if( ! data.answer_ids ){
				Dem.demShake( $the );
				return false;
			}
		}
		
		// кнопка переголосовать, подтверждение
		if( act == 'delVoted' && ! confirm( $the.data('confirm-text') ) )
			return false;
		
		// кнопка добавления ответа посетителя
		if( act == 'newAnswer' ){		
			$the.demAddAnswer();
			return false;	
		}
		
		// AJAX
		$the.demSetLoader();
		$.post( demAjaxUrl, data, 
			function( respond ){
				$the.demUnsetLoader();
			
				// анимация
				var speed = 300;
				var $div  = $the.closest( demScreen );
					
				// снова устанавливаем событие клика для кнопок
				$div.html( respond ).demInitActions();
			}
		);

		return false;
	};
	
	// Инициализация всех событий связаных с внутренней частью каждого опроса: клики, высота, скрытие кнопки
	$.fn.demInitActions = function( noanimation ){
		return this.each(function(){
			// Устанавливает события клика для всех помеченных элементов в переданом элементе:
	 		// тут и AJAX запрос по клику и другие интерактивные события Democracy ----------
			var attr = 'data-dem-act';
			$(this).find('['+ attr +']').each(function(){
				$(this).attr('href',''); // удалим УРЛ чтобы не было видно УРЛ запроса

				$(this).click(function(e){
					e.preventDefault();
					$(this).blur().demDoAction( $(this).attr( attr ) );
				});
			});
			
			// Прячем кнопку сабмита, где нужно ------------
			var autoVote = !! $(this).find('input[type=radio][data-dem-act=vote]').first().length;
			//console.log( autoVote );
			if( autoVote )
				$(this).find('.dem-vote-button').hide();
			
			// Устанавливает высоту жестко ------------
			Dem.setHeight( $(this), noanimation );
			
		});
	};

			
	// КЭШ ------------
	// показывает заметку
	$.fn.demCacheShowNotice = function( type ){
		var $the = this.first(),
			$notice = $the.find('.dem-youarevote').first(); // "уже голосовал"
		
		// Если могут овтечать только зарегистрированные
		if( type == 'blockForVisitor' ){
			$the.find('.dem-revote-button').remove(); // удаляем переголосовать
			$notice = $the.find('.dem-only-users').first();
		}
		
		$the.prepend( $notice.show() );
		// hide
		setTimeout( function(){ $notice.slideUp('slow'); }, 10000 );

		return this;
	};
	
	// устанавливает ответов пользователя в блоке результатов/голосования
	Dem.cacheSetAnswrs = function( $screen, answrs ){
		var aids = answrs.split(/,/);
		
		// если блок результатов
		if( $screen.hasClass('voted') ){
			var $dema	   = $screen.find('.dem-answers'),
				votedClass = $dema.data('voted-class'),
				votedtxt   = $dema.data('voted-txt');
			
			$.each( aids, function(key,val){
				$screen.find('[data-aid="'+ val +'"]')
					.addClass( votedClass )
					.attr('title', function(){ return votedtxt + $(this).attr('title'); } );
			});
			
			// прячем кнопку "вернуться к голосованию"
			$screen.find('.dem-vote-link').hide();
		}
		
		// если блок голосования
		else{
			var $answs    = $screen.find('[data-aid]'),
				$btnVoted = $screen.find('.dem-voted-button');
			
			// устанавливаем ответы
			$.each( aids, function(key,val){
				$answs.filter('[data-aid="'+ val +'"]').find('input').prop('checked','checked');
			});
			
			// все деактивирем
			$answs.find('input').prop('disabled','disabled');
			
			// прячем голосовать
			$screen.find('[data-dem-act="vote"]').hide();
			
			// если уже логосовали, то переголосование запрещено
			if( $btnVoted.length ){
				$btnVoted.show();
			}
			// показываем кнопку переголосовать
			else{
				$screen.find('input[value="vote"]').remove(); // чтобы можно было переголосовать
				$screen.find('.dem-revote-button-wrap').show();
			}
				
		}

	};
	
	$.fn.demCacheInit = function(){		
		return this.each(function(){
			var $the = $(this),
				$dem   = $the.prev( democracy );
			
			// ищем главный блок
			if( ! $dem.length )
				$dem = $the.closest( democracy );
			if( ! $dem.length ){
				console.log('Main dem div not found');
				return;
			}

			var $screen     = $dem.find( demScreen ).first(), // основной блок результатов
				dem_id      = $dem.data('pid'),
				answrs      = Cookies.get('demPoll_' + dem_id),
				notVoteFlag = ( answrs == 'notVote' ) ? true : false, // Если уже проверялось, что пользователь не голосовал, не отправляем запрос еще раз
				isAnswrs    = !(typeof answrs == 'undefined') && ! notVoteFlag;
			
			// обрабатываем экраны, какой показать и что делать при этом
			var voteHTML  = $the.find( demScreen + '-cache.vote' ).html(),
				votedHTML = $the.find( demScreen + '-cache.voted' ).html();				
			
			// если опрос закрыт должны кэшироваться только результаты голосования. Просто выходим.
			if( ! voteHTML )
				return;
			
			// устанавливаем нужный кэш
			// если закрыт просмотрт ответов
			$screen.html( ((isAnswrs && votedHTML) ? votedHTML : voteHTML) + '<!--cache-->' );

			if( isAnswrs )
				Dem.cacheSetAnswrs( $screen, answrs );
			
			$screen.demInitActions(1);
			
			if( notVoteFlag )
				return; // если уже проверялось, что пользователь не голосовал, выходим
				 
			// Если голосов нет в куках и опция плагина keep_logs включена,
			// отправляем запрос в БД на проверку, по событию (наведение мышки на блок),
			if( ! isAnswrs && $the.data('opt_logs') == 1 ){
				var tmout,
					notcheck = function(){ clearTimeout( tmout ); },
					check	= function(){
						tmout = setTimeout( function(){
							// Выполняем один раз!
							if( $dem.hasClass('checkAnswDone') ) return;
							$dem.addClass('checkAnswDone');

							var $forDotsLoader = $dem.find('.dem-link').first();
							$forDotsLoader.demSetLoader();
							$.post( demAjaxUrl, 
								{
									dem_pid: $dem.data('pid'),
									dem_act: 'getVotedIds',
									action:  'dem_ajax'
								},
								function( reply ){
									$forDotsLoader.demUnsetLoader();
									if( ! reply ) return; // выходим если нет ответов

									$screen.html( votedHTML );
									Dem.cacheSetAnswrs( $screen, reply );
									
									$screen.demInitActions();
								
									// сообщение, что голосовал или только для пользователей
									$screen.demCacheShowNotice( reply );
								} 
							);
						}, 700 ); // 700 для оптимизации, чтобы моментально не отправлялся запрос, если мышкой просто провели по опросу...
					};
				$dem.hover( check, notcheck );
				$dem.click( check );
			}

		});
	};
	
	
	// ФУНКЦИИ --------------
	// Устанавливает высоту жестко
	Dem.setHeight = function( $that, noanimation ){
		var speed = 400,
			html  = $that.html();

		// получим нужную высоту
		var $_the  = $that.clone().html( html ).css({height:'auto'}).appendTo( $that ); // получим высоту
		var newH = ($_the.css('box-sizing') == 'border-box') ? $_the.outerHeight() : $_the.height();
		//newH += 5; // запас 5px
		$_the.remove();

		if( ! noanimation  ) // Анимируем до нужной выстоты
			$that.css({ opacity:0 }).animate({ height: newH }, speed, function(){ $(this).animate({opacity:1}, speed*1.5); } );
		else
			$that.css({ height: newH });
	};
	
	// max answers limit
	Dem.maxAnswLimit = function(){
		var maxSelector = 'data-max__answs';
		$('['+ maxSelector +']').on('change', '[type=checkbox]', function( ev ){
			var maxAnsws   = $(this).closest('['+ maxSelector +']').attr( maxSelector ),
				$checkboxs = $(this).closest( demScreen ).find('[type=checkbox]'),
				$checked   = $checkboxs.filter(':checked').length,
				foo;
			
			if( $checked >= maxAnsws )
				$checkboxs.filter(':not(:checked)').each(function(){
					$(this).prop('disabled','disabled').closest('li').addClass('dem-disabled');
				});
			else
				$checkboxs.each(function(){
					$(this).removeProp('disabled').closest('li').removeClass('dem-disabled');
				});
		});
	};

	Dem.demShake = function( $that ){
		var a = $that.css("position");
		a && "static" !== a || $that.css("position","relative");
		for( a=1; 2>=a; a++ )
			$that.animate({left:-10},50).animate({left:10},100).animate({left:0},50);
	};

}(jQuery)
