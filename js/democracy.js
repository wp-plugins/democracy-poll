/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function(c){"function"===typeof define&&define.amd?define(["jquery"],c):"object"===typeof exports?c(require("jquery")):c(jQuery)})(function(c){function n(b){b=f.json?JSON.stringify(b):String(b);return f.raw?b:encodeURIComponent(b)}function m(b,e){var a;if(f.raw)a=b;else a:{var d=b;0===d.indexOf('"')&&(d=d.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{d=decodeURIComponent(d.replace(l," "));a=f.json?JSON.parse(d):d;break a}catch(g){}a=void 0}return c.isFunction(e)?e(a):a}var l=/\+/g,f=c.cookie=function(b,e,a){if(void 0!==e&&!c.isFunction(e)){a=c.extend({},f.defaults,a);if("number"===typeof a.expires){var d=a.expires,g=a.expires=new Date;g.setTime(+g+864E5*d)}return document.cookie=[f.raw?b:encodeURIComponent(b),"=",n(e),a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}a=b?void 0:{};for(var d=document.cookie?document.cookie.split("; "):[],g=0,l=d.length;g<l;g++){var h=d[g].split("="),k;k=h.shift();k=f.raw?k:decodeURIComponent(k);h=h.join("=");if(b&&b===k){a=m(h,e);break}b||void 0===(h=m(h))||(a[k]=h)}return a};f.defaults={};c.removeCookie=function(b,e){if(void 0===c.cookie(b))return!1;c.cookie(b,"",c.extend({},e,{expires:-1}));return!c.cookie(b)}});


/* 
 * main democracy vars & functions 
 */
(function($){
	'use strict';
	
	var democracy  = '.democracy';
	var demScreen  = '.dem-screen'; // селектор контейнера с результатами
	var userAnswer = '.dem-add-answer-txt'; // класс поля ответа
    var demAjaxUrl = $(democracy).attr('data-ajax-url'); // URL ajax
    
    var loader;
    var $demLoader = $(document).find('.dem-loader').first(); // loader
	
	// загрузка для AJAX. Точки ...
	$.fn.demLoadingDots = function(){
		var $the = this;
		var isInput = $the.is('input');

		var str = (isInput) ? $the.val() : $the.text();

		if( str.substring(str.length-3) == '...' )
			if( isInput ) 
				$the.val( str.substring(0, str.length-3) );
			else
				$the.text( str.substring(0, str.length-3) );
		else
			if( isInput )
				$the[0].value     += '.';
			else
				$the[0].innerHTML += '.';

		loader = setTimeout( function(){ $the.demLoadingDots(); }, 200 );
	};
    
    // Loader
	$.fn.demSetLoader = function(){ var $the = this;
        if( $demLoader.length ) $the.closest(demScreen).append( $demLoader.clone().css('display','table') );
        else loader = setTimeout( function(){ $the.demLoadingDots(); }, 50 ); // dats
        return this;
    };
	$.fn.demUnsetLoader = function(){
        if( $demLoader.length ) this.closest(demScreen).find('.dem-loader').remove();
        else clearTimeout( loader );
        return this;
    };
    
	// Добавить ответ пользователя (ссылка)
	$.fn.demAddAnswer = function(){
		var $the = this.first();
		var $demScreen = $the.closest( demScreen );
		var isMultiple  = $demScreen.find('[type=checkbox]').length > 0;
		var $input      = $('<input type="text" class="'+ userAnswer.replace(/\./,'') +'" value="">'); // поле добавления ответа
		
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

		answ = answ.join('~');

		return answ ? answ : '';
	};
    
    // Устанавливает высоту жестко.
    $.fn.demSetHeight = function( noanimation ){
		return this.each(function(){
			var $the = $(this);
			var speed = 300;
			var html = $the.html();

			// получим нужную высоту
			var $_the  = $the.clone().html( html ).css({height:'auto'}).appendTo( $the ); // получим высоту
			var newH = ($_the.css('box-sizing') == 'border-box') ? $_the.outerHeight() : $_the.height();
			//newH += 5; // запас 5px
			$_the.remove();

			if( ! noanimation  ) // Анимируем до нужной выстоты
				$the.css({ opacity:0 }).animate({ height: newH }, speed, function(){ $(this).animate({opacity:1}, speed*1.5); } );
			else
				$the.css({ height: newH });
		});
	};
		
	$.fn.demShake=function(){return this.each(function(){var a=$(this).css("position");a&&"static"!==a||$(this).css("position","relative");for(a=1;2>=a;a++)$(this).animate({left:-10},50).animate({left:10},100).animate({left:0},50)})};
		
	// обрабатывает запросы при клике, вешается на событие клика
	$.fn.demDoAction = function( act ){
		var $the = this.first();
				
		var $dem = $the.closest( democracy );
		var data = {
			dem_pid: $dem.attr('data-pid'),
			dem_act: act,
			action: 'dem_ajax'
		};

		if( typeof data.dem_pid == 'undefined' ){ console.log('Poll id is not defined!'); return false; }
		
		// Соберем ответы
		if( act == 'vote' ){ 
			data.answer_ids = $the.demCollectAnsw();
			if( ! data.answer_ids ){ $the.demShake(); return false; }
		}
		
		// кнопка переголосовать, подтверждение
		if( act == 'delVoted' && ! confirm( $the.attr('data-confirm-text') ) ){ return false; }
		
		// кнопка добавления ответа посетителя
		if( act == 'newAnswer' ){ $the.demAddAnswer(); return false; }
		
		// AJAX
        $the.demSetLoader();
		$.post( demAjaxUrl, data, 
			function( respond ){
                $the.demUnsetLoader();
            
				// анимация
				var speed = 300;
				var $div  = $the.closest( demScreen );
					
				// снова устанавливаем событие клика для кнопок
				$div.html( respond ).demSetHeight().demSetClick();
			}
		);

		return false;
	};
	
	/*
	 * Устанавливает события клика для всех помеченных элементов в переданом элементе:
	 * тут и AJAX запрос по клику и другие интерактивные события Democracy
	 */
	$.fn.demSetClick = function(){ return this.each(function(){
		var attr = 'data-dem-act';
		$(this).find('['+ attr +']').each(function(){
			$(this).attr('href','#'); // удалим УРЛ чтобы не было видно УРЛ запроса
            
            $(this).click(function(e){
                e.preventDefault();
                $(this).blur().demDoAction( $(this).attr( attr ) );
            });
		});
	}); };
    
    
	// Механихм обработки кэша. Для плагинов страничного кэширования
	$.fn.demCacheInit = function(){
        
        // показывает заметку
        $.fn.demCacheShowNotice = function( type ){
            var $the = this.first();

            // уведомление что уже голосовал
            var $notice = $the.find('.dem-youarevote').first();
            // уведомление если на опрос могут овтечать только зарегистрированные
            if( type == 'blockForVisitor' ){
                $the.find('.dem-revote-link').remove(); // удаляем переголосовать
                $notice = $the.find('.dem-only-users').first();
            }
            $the.prepend( $notice.show() );
            setTimeout( function(){ $notice.slideUp('slow'); }, 3000 ); // удалим заметку                                   

            return this;
        }
        
        // устанавливает метки ответов пользователя в блоке результатов
        var setAnswrs = function( $res, answrs ){
            var $dema = $res.find('.dem-answers');
            var votedClass = $dema.attr('data-voted-class');
            var votedtxt   = $dema.attr('data-voted-txt');

            // set vote marks
            var aids = answrs.split(/,/);
            $.each( aids, function(key,val){
                $res.find('[data-aid="'+ val +'"]')
                    .addClass( votedClass )
                    .attr('title', function(){ return votedtxt + $(this).attr('title'); } );
            });
        };
        
        return this.each(function(){
			var $the = $(this);
			
            // ищем главный блок
            var $dem   = $the.prev( democracy );
            if( ! $dem.length ) $dem   = $the.closest( democracy );
            if( ! $dem.length ){ console.log('Main dem div not found'); return; }

            var $res   = $dem.find( demScreen ).first(); // получим основной блок результатов
                                    
            var dem_id    = $dem.attr('data-pid');
            var pCookie   = $.cookie('demPoll_' + dem_id);
            var notVoteFlag = ( pCookie == 'notVote' ) ? true : false; // Если уже проверялось, что пользователь не голосовал, не отправляем запрос еще раз
            var isAnswrs = !(typeof pCookie == 'undefined') && ! notVoteFlag;
                                    
            // обрабатываем экраны, какой показать и что делать при этом
            var voteHTML  = $the.find( demScreen + '-cache.vote' ).html();
            var votedHTML = $the.find( demScreen + '-cache.voted' ).html();
                                    
            if( ! voteHTML ) return; // если опрос закрыт должны кэшироваться только результаты голосования. Просто выходим.
            
			// устанавливаем нужный кэш
            var HTML =  voteHTML;
            if( isAnswrs ) HTML =  votedHTML;
            $res.html( HTML + '<!--cache-->' ).demSetHeight(1).demSetClick();

            if( notVoteFlag ) return; // если уже проверялось, что пользователь не голосовал, выходим
            
            var answrs = pCookie;
            if( isAnswrs ) setAnswrs( $res, answrs );
                 
            // Если голосов нет в куках и опция плагина keep_logs включена,
            // отправляем запрос в БД на проверку, по событию (наведение мышки на блок),
            if( ! isAnswrs && $the.attr('data-opt_logs') == 1 ){
                var tmout;
                var notcheck = function(){ clearTimeout( tmout ); };
                var check = function(){
                    tmout = setTimeout( function(){
                        // Выполняем один раз!
                        if( $dem.hasClass('checkAnswDone') ) return;
                        $dem.addClass('checkAnswDone');
                        
                        var $forDotsLoader = $dem.find('.dem-link').first();
                        $forDotsLoader.demSetLoader();
                        $.post( demAjaxUrl, 
                            {
                                dem_pid: $dem.attr('data-pid'),
                                dem_act: 'getVotedIds',
                                action:  'dem_ajax'
                            },
                            function( reply ){
                                $forDotsLoader.demUnsetLoader();
                                if( ! reply ) return; // выходим если нет ответов

                                $res.html( votedHTML ).demSetHeight().demSetClick();
                                setAnswrs( $res, reply );
                                
                                // выводим сообщение о том что голосовал или только для пользователей
                                $res.demCacheShowNotice( reply );
                            } 
                        );
                    }, 700 ); // 700 для оптимизации, чтобы моментально не отправлялся запрос, если мышкой просто провели по опросу...
                };
                $dem.hover( check, notcheck );
                $dem.click( check );
            }

        });
    };
    	
	$(window).load(function() {
		// Основные события Democracy для всех блоков
		$( demScreen ).filter(':visible').demSetClick().demSetHeight(1);
		
		/*
		 * Обработка кэша.
		 * Нужен установленный jQuery Cookie Plugin 
		 * и дополнительные js переменные и методы самого Democracy.
		 */
		var $cache = $('.dem-cache-screens');
		if( $cache.length > 0 ){
            //console.log('Democracy cache gear ON');
			$cache.demCacheInit();
		}
	});


})(jQuery)
