<?php

## Вывод и голосование отдельного опроса.
## Нуждается в классе плагина Dem
class DemPoll {
	var $id;
	var $poll;
		
	var $has_voted     = false;
	var $votedFor     = '';
	var $blockVoting  = false; // блокировать голосование
	var $blockForVisitor = false; // только для зарегистрированных
	var $not_show_results = false; // не показывать результаты
	
    var $inArchive    = false; // в архивной странице
    
	var $cachegear_on = false; // проверка включен ли механихм кэширвоания
	var $for_cache    = false; 
	
	var $cookey;           // Название ключа cookie
    
	function __construct( $id = 0 ){
		global $wpdb;
		
		$this->id = (int) $id;
							
		if( ! $this->id )
			$poll = $wpdb->get_row("SELECT * FROM $wpdb->democracy_q WHERE active = 1 ORDER BY RAND() LIMIT 1");			
		else
			$poll = self::get_poll( $this->id );			

        if( ! $poll ) return print "<!-- democracy: there is no active polls -->";
		
		// устанавливаем необходимые переменные
		$this->id = (int) $poll->id;
		
		if( ! $this->id ) return; // влияет на весь класс, важно!
		
		$this->cookey = 'demPoll_' . $this->id;
		$this->poll   = $poll;
		
		// отключим демокраси опцию
		if( Dem::$opt['democracy_off'] )
			$this->poll->democratic = false;
		// отключим опцию переголосования
		if( Dem::$opt['revote_off'] )
			$this->poll->revote = false;
        
		$this->cachegear_on = Dem::$i->is_cachegear_on();		
		
		$this->setVotedData();
        $this->set_answers(); // установим свойство $this->poll->answers

		// закрываем опрос т.к. срок закончился
		if( $this->poll->end && $this->poll->open && ( current_time('timestamp') > $this->poll->end ) )
			$wpdb->update( $wpdb->democracy_q, array( 'open'=>0 ), array( 'id'=>$this->id ) );

		// только для зарегистрированных
		if( ( Dem::$opt['only_for_users'] || $this->poll->forusers ) && ! is_user_logged_in() )
			$this->blockForVisitor = true;

		// блокировка возможности голосовать
		if( $this->blockForVisitor || ! $this->poll->open || $this->has_voted )
			$this->blockVoting = true;
		
		if( ! $poll->show_results && $poll->open && ( ! is_admin() || defined('DOING_AJAX') ) )
			$this->not_show_results = true;
        
	}
	
	static function get_poll( $poll_id ){
		global $wpdb;
		
		$poll = $wpdb->get_row("SELECT * FROM $wpdb->democracy_q WHERE id = ". intval( $poll_id ) ." LIMIT 1");
		if( ! $poll ) return false;
		return $poll;
	}
	
    /**
     * Получает HTML опроса
     * @param bool $show_screen Какой экран показывать: vote, voted, force_vote
     * @return HTML
     */
	function get_screen( $show_screen = 'vote', $before_title = '', $after_title = '' ){
	    if( ! $this->id )
			return false;
		
		$this->inArchive = ( @ $GLOBALS['post']->ID == Dem::$opt['archive_page_id'] ) && is_singular();

		if( $this->blockVoting && $show_screen != 'force_vote' )
			$show_screen = 'voted';
			
		if( ! Dem::$opt['disable_js'] )
			Dem::$i->add_js(); // подключаем скрипты (один раз)
		
		$___ = '';
		$___ .= Dem::$i->add_css();
			
		$___ .= '<div id="democracy-'. $this->id .'" class="democracy" data-ajax-url="'. Dem::$i->ajax_url .'" data-pid="'. $this->id .'" 
					'. ($this->poll->multiple > 1 ? 'data-max__answs="'. $this->poll->multiple .'"' : '' ) .'>';
		    $___ .=  ( $before_title ?: Dem::$opt['before_title'] ) . $this->poll->question . ( $after_title  ?: Dem::$opt['after_title'] );
		
			// изменяемая часть
			$___ .=  $this->get_screen_basis( $show_screen );
			// изменяемая часть
		
			$___ .= $this->poll->note ? '<div class="dem-poll-note">'. wpautop( $this->poll->note ) .'</div>' : '';
			if( current_user_can('manage_options') )
				$___ .= '<a class="dem-edit-link" href="'. ( Dem::$i->admin_page_url() .'&edit_poll='. $this->id ) .'" title="'. __('Редактировать опрос','dem') .'"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1.5em" height="100%" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><path d="M617.8,203.4l175.8,175.8l-445,445L172.9,648.4L617.8,203.4z M927,161l-78.4-78.4c-30.3-30.3-79.5-30.3-109.9,0l-75.1,75.1 l175.8,175.8l87.6-87.6C950.5,222.4,950.5,184.5,927,161z M80.9,895.5c-3.2,14.4,9.8,27.3,24.2,23.8L301,871.8L125.3,696L80.9,895.5z"/></svg>
</a>';
        // copyright
		if( Dem::$opt['show_copyright'] && ( is_home() || is_front_page() ) )
			$___ .=  '<a class="dem-copyright" href="http://wp-kama.ru/?p=67" title="'. __('Скачать Опрос Democracy','dem') .'"> © </a>';
		
        // loader 
        if( Dem::$opt['loader_fname'] ){
            static $loader; // оптимизация, чтобы один раз выводился код на странице
            if( ! $loader ){
                $loader = '<div class="dem-loader"><div>'. file_get_contents( DEMOC_PATH .'styles/loaders/'. Dem::$opt['loader_fname'] ) .'</div></div>';
                $___ .=  $loader;
            }
        }

		$___ .=  "</div><!--democracy-->";
		
		
		// для КЭША
		if( $this->cachegear_on && ! $this->inArchive ){
			$___ .= '
			<!--noindex-->
			<div class="dem-cache-screens" style="display:none;" data-opt_logs="'. Dem::$opt['keep_logs'] .'">';
			
			// запоминаем
			$votedFor = $this->votedFor;
			$this->votedFor = false;
            $this->for_cache = 1;
            
			$compress = function( $str ){		
				return preg_replace("~[\n\r\t]~u", '', preg_replace('~\s+~u',' ',$str) );	
			};
			
			if( ! $this->not_show_results )
				$___ .= $compress( $this->get_screen_basis('voted') );  // voted_screen
			
            if( $this->poll->open )
                $___ .= $compress( $this->get_screen_basis('force_vote') ); // vote_screen
            
            $this->for_cache = 0;
			$this->votedFor = $votedFor; // возвращаем

			$___ .=	'
			</div>
			<!--/noindex-->';
		}
		
			
		return $___;
	}
	
	/**
     * Получает сердце HTML опроса (изменяемую часть)
     * @param bool $show_screen 
     * @return HTML
     */
	function get_screen_basis( $show_screen = 'vote' ){
        $class_suffix = $this->for_cache ? '-cache' : '';
		
		if( $this->not_show_results )
			$show_screen = 'force_vote';		
		
        $screen = ( $show_screen == 'vote' || $show_screen == 'force_vote' ) ? 'vote' : 'voted';
		
		$___ = '<div class="dem-screen'. $class_suffix .' '. $screen  .'">';
		$___ .= ( $screen == 'vote' ) ? $this->get_vote_screen() : $this->get_result_screen();
		$___ .=  '</div>';
		
		if( ! $this->for_cache )
			$___ .=  '<noscript>Poll Options are limited because JavaScript is disabled in your browser.</noscript>';
		
		return $___;
	}
	
    /**
     * Получает код для голосования
     * @return HTML
     */
	function get_vote_screen(){
	    if( ! $this->id ) return false;
		
		$auto_vote_on_select = ( ! $this->poll->multiple && $this->poll->revote && @ Dem::$opt['hide_vote_button'] );
			
		$___ = $dem_act = ''; // vars
					
        $___ .= '<form method="post" action="#democracy-'. $this->id .'">';	
            $___ .= '<ul class="dem-vote">';

                $type = $this->poll->multiple ? 'checkbox' : 'radio';
				
                foreach( $this->poll->answers as $answer ){
					$auto_vote = $auto_vote_on_select ? 'data-dem-act="vote"' : '';
					
					$checked = $disabled = '';
					if( $this->votedFor ){
						if( in_array( $answer->aid, explode(',', $this->votedFor ) ) )
							$checked = ' checked="checked"';
						
						$disabled = ' disabled="disabled"';
					}
					
                    $___ .= '
                    <li data-aid="'. $answer->aid .'">
                        <label>
                            <input '. $auto_vote .' type="'. $type .'" value="'. $answer->aid .'" name="answer_ids[]"'. $checked . $disabled .'> '. stripslashes( $answer->answer ) .'
                        </label>
                    </li>';
                }

                if( $this->poll->democratic && ! $this->has_voted ){
					$___ .= '<li class="dem-add-answer"><a href="javascript:void(0);" rel="nofollow" data-dem-act="newAnswer" class="dem-link">'. __dem('Добавить свой ответ') .'</a></li>';          
                }		
            $___ .= "</ul>";

            $___ .= '<div class="dem-bottom">';
                $___ .= '<input type="hidden" name="dem_act" value="vote">';
                $___ .= '<input type="hidden" name="dem_pid" value="'. $this->id .'">';
				
				$btnVoted  = '<div class="dem-voted-button"><input class="dem-button" type="submit" value="'. __dem('Уже голосовали...') .'" disabled="disabled"></div>';
				$btnVote   = '<div class="dem-vote-button"><input class="dem-button" type="submit" value="'. __dem('Голосовать') .'" data-dem-act="vote"></div>';
		
				if( $auto_vote_on_select )
					$btnVote = '';
		
				// для экша
				if( $this->for_cache ){
					$___ .= $this->__voted_notice();
					
					if( $this->poll->revote )
						$___ .= preg_replace('~(<[^>]+)~s', '$1 style="display:none;"', $this->__revote_btn(), 1 );
					else
						$___ .= substr_replace( $btnVoted, '<div style="display:none;"', 0, 4 );
					$___ .= $btnVote;
				}
				// не для кэша
				else {
					if( $this->has_voted )
						$___ .= $this->poll->revote ? $this->__revote_btn() : $btnVoted;
					else
						$___ .= $btnVote;
				}		
				
				if( ! $this->not_show_results )
                	$___ .= '<a href="javascript:void(0);" class="dem-link dem-results-link" data-dem-act="view" rel="nofollow">'. __dem('Результаты') .'</a>';
		
		
            $___ .= '</div>';

        $___ .= '</form>';	
		
		return $___;

	}
    
    /**
     * Получает код результатов голосования
     * @return HTML
     */
	function get_result_screen(){
	    if( ! $this->id ) return false;

		$___ = '';
		
		$max = $total = 0;

        foreach ( $this->poll->answers as $answer ){
			$total += $answer->votes;
			if ( $max < $answer->votes ) $max = $answer->votes;
		}
		
		$voted_class = 'dem-voted-this';
		$voted_txt   = __dem('Это Ваш голос. ');
		$___ .= '<ul class="dem-answers" data-voted-class="'. $voted_class .'" data-voted-txt="'. $voted_txt .'">';
			foreach ( $this->poll->answers as $answer ){
				$word          = stripslashes( $answer->answer );
				$votes         = (int) $answer->votes;
				$is_voted_this = ( $this->has_voted && in_array( $answer->aid, explode(',', $this->votedFor) ) );
				$is_winner     = ( $max == $votes );
				
                $novoted_class = ( $votes == 0 ) ? ' dem-novoted' : '';
				$li_class = ' class="'. ( $is_winner ? 'dem-winner':'' ) . ( $is_voted_this ? " $voted_class":'' ) . $novoted_class .'"';
				$sup = $answer->added_by ? ' <sup class="dem-star" title="'. __dem('Ответ добавлен посетителем') .'">*</sup>' : '';
				$percent = ( $votes > 0 ) ? round($votes / $total * 100) : 0;
				
				$percent_txt = sprintf( __dem("%s%% от всех голосов"), $percent );
				$title       = ( $is_voted_this ? $voted_txt : '' ) . ' '. $percent_txt;
				$title       = " title='$title'";
				
				// склонение голосов
				$sclonenie = function( $number, $titles ){ $cases = array (2, 0, 1, 1, 1, 2);
					return $number .' '. $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
				};
				$votes_txt = $sclonenie( $votes, array( __dem('голос'),__dem('голоса'),__dem('голосов') ) );

				$___ .= '<li'. $li_class . $title .' data-aid="'. $answer->aid .'">';
					$label_perc_txt = ' <span class="dem-label-percent-txt">'. $percent .'%, '. $votes_txt .'</span>';
					$percent_txt = '<div class="dem-percent-txt">'. $percent_txt .'</div>';
					$votes_txt = '<div class="dem-votes-txt">
						<span class="dem-votes-txt-votes">'. $votes_txt .'</span>
						'. ( ( $percent > 0 ) ? ' <span class="dem-votes-txt-percent">'. $percent .'%</span>' : '' ) . '
						</div>';
					                
                    $___ .= '<div class="dem-label">'. $word . $sup . $label_perc_txt .'</div>';
					
					// css процент
                    $graph_percent = ( ( ! Dem::$opt['graph_from_total'] && $percent != 0 ) ? round( $votes / $max * 100 ) : $percent ) . '%';
                    if( $graph_percent == 0 ) $graph_percent = '1px';
					
					$___ .= '<div class="dem-graph">';
						$___ .= '<div class="dem-fill" style="width:'. $graph_percent .';"></div>';
						$___ .= $votes_txt;
						$___ .= $percent_txt;
					$___ .= "</div>";
				$___ .= "</li>";
			}
		$___ .= '</ul>';
		
		$___ .= '<div class="dem-bottom">';
			$___ .= '<div class="dem-poll-info">';
				$___ .= '<div class="dem-total-votes">'. sprintf( __dem('Всего голосов: %s'), $total ) .'</div>';
				$___ .= '<div class="dem-begin-date" title="'. __dem('Начало') .'">'. date_i18n( get_option('date_format'), $this->poll->added ) .'</div>';
				$___ .= $this->poll->end    ? '<div class="dem-begin-date" title="'. __dem('Конец') .'">'. date_i18n( get_option('date_format'), $this->poll->end ) .'</div>' : '';
				$___ .= $answer->added_by   ? '<div class="dem-added-by-user"><span class="dem-star">*</span>'. __dem(' - добавлен посетителем') .'</div>' : '';
				$___ .= ! $this->poll->open ? '<div>'. __dem('Опрос закрыт') .'</div>' : '';
				if( ! $this->inArchive && Dem::$opt['archive_page_id'] )
					$___ .= '<a class="dem-archive-link dem-link" href="'. get_permalink( Dem::$opt['archive_page_id'] ) .'" rel="nofollow">'. __dem('Архив опросов') .'</a>';
			$___ .= '</div>';
		
		
        if( $this->poll->open ){
            // заметка для незарегистрированных пользователей
            $url = esc_url( wp_login_url( $_SERVER['REQUEST_URI'] ) );
            $for_users_alert = '<div class="dem-only-users">'. sprintf( __dem('Голосовать могут только зарегистрированные пользователи. <a href="%s" rel="nofollow">Войдите</a> для голосования.'), $url ) .'</div>';

            
			// вернуться к голосованию
            $vote_btn = '<a href="javascript:void(0);" class="dem-button dem-vote-link" data-dem-act="vote_screen" rel="nofollow">'. __dem('Голосовать') .'</a>';
        	
			// для экша
            if( $this->for_cache ){
                $___ .= $this->__voted_notice();
				
				if( $this->blockForVisitor )
                	$___ .= str_replace( array('<div', 'class="'), array('<div style="display:none;"', 'class="dem-cache-notice '), $for_users_alert );
				
					if( $this->poll->revote )
						$___ .= $this->__revote_btn();
					else
						$___ .= $vote_btn;
            }
			// не для кэша
			else {
				if( $this->blockForVisitor ){
					$___ .= $for_users_alert;
				}
				else{
					if( $this->has_voted ){
						if( $this->poll->revote )
							$___ .= $this->__revote_btn();
					}
					else
						$___ .= $vote_btn;
				}
				
			}
		// is open
        }
        
		$___ .= '</div>';
        
		
		return $___;
	}
	
	function __revote_btn(){
		return '
		<span class="dem-revote-button-wrap">
		<form action="#democracy-'. $this->id .'" method="post">
			<input type="hidden" name="dem_act" value="delVoted">
			<input type="hidden" name="dem_pid" value="'. $this->id .'">
			<input type="submit" value="'. __dem('Переголосовать') .'" class="dem-revote-link dem-revote-button dem-button" data-dem-act="delVoted" data-confirm-text="'. __dem('Точно отменить голоса?') .'">
		</form>
		</span>';
	}
	
	## заметка: вы уже голосовали
	function __voted_notice(){
		return '
		<div class="dem-cache-notice dem-youarevote" style="display:none;">
			<div class="dem-notice-close" onclick="jQuery(this).parent().fadeOut();">&times;</div>
			'. __dem('Вы или с вашего IP уже голосовали.') .'
		</div>';
	}
    
	/**
	 * Обновляет голоса.
	 * @param str $aids ID ответов через запятую. Там может быть строка, тогда она будет добавлена, как ответ пользователя ответ.
	 * @return false or none
	 */
	function addVote( $aids ){
	    if( ! $this->id || $this->has_voted || $this->blockVoting )
			return false;
		
		global $wpdb;
		
		if( ! is_array( $aids ) ){
			$aids = trim( $aids );
			$aids = explode('~', $aids );
		}
		
		$aids = array_map('trim', $aids );
		
		// Добавка ответа пользователя. 
		// Првоеряет значение массива, ищет строку, если есть то это и есть произвольный ответ.
		if( $this->poll->democratic ){
			$new_user_answer = false;
			
			foreach( $aids as $k => $id ){
				if( ! preg_match('~^[0-9]+$~', $id ) ){
					$new_user_answer = $id;
					unset( $aids[ $k ] ); // удалим из общего массива, чтобы дельше ответа не было
					
					if( ! $this->poll->multiple )
						$aids = array(); // опусташим массив так как множественное голосование запрещено
					
					//break; !!!!NO
				}
			}
			
			// есть ответ пользователя, добавляем и голосуем
			if( $new_user_answer ){
				if( $aid = (int) $this->add_democratic_answer( $new_user_answer ) );
					$aids[] = $aid;
			}
		}
		
		$AND = '';
		
		// соберем $ids в строку для кук. Там только числа
		$aids = array_map('esc_sql', $aids); // защита
		$aids = implode(',', $aids );
		
		if( ! $aids ) return false;
		
		if( false === strpos($aids, ',') ){ // Есил один ответ
			$aid = esc_sql( $aids );
			$AND = " AND aid = $aid LIMIT 1";
		}
		elseif( (int) $this->poll->multiple ){ // Если несколко ответов (multiple)
			$aids = esc_sql( $aids );
			$AND = " AND aid IN ($aids)";
		}

		if( ! $AND ) return false;
		
		// обновляем в БД
		$wpdb->query( $wpdb->prepare("UPDATE $wpdb->democracy_a SET votes = (votes+1) WHERE qid = %d $AND", $this->id ) );

		$this->blockVoting = true;
		$this->has_voted   = true;
		$this->votedFor    = $aids;
        
        $this->set_answers(); // переустановим ответы

        $this->set_cookie(); // установим куки
        
		if( Dem::$opt['keep_logs'] )
			$this->add_logs();
		
	}
    
	private function add_democratic_answer( $answer ){
		global $wpdb;
					
		$new_answer = Dem::$i->sanitize_answer_data( $answer ); // чистим
		$new_answer = wp_unslash( $new_answer );
		
		$insert_id = 0;
		
		// проверим нет ли уже такого ответа
		$exists = $wpdb->query( $wpdb->prepare("SELECT aid FROM $wpdb->democracy_a WHERE answer = '%s' AND qid = $this->id", $new_answer ) );
		
		if( ! $exists ){
			$added_by = self::get_ip();
			if( $user_id = get_current_user_id() )
				$added_by = $user_id;
			
			if( $wpdb->insert( $wpdb->democracy_a, array( 'qid'=>$this->id, 'answer'=>$new_answer, 'votes'=>0, 'added_by'=>$added_by ) ) )
				$insert_id = $wpdb->insert_id;
		}
	    
	    return $insert_id;
	}	
	
	/**
	 * Удаляет данные пользователя о голосовании
	 * Отменяет установленные $this->has_voted и $this->votedFor
     * Должна вызываться как зоголовки, до вывода данных
	 */
	function unsetVotedData(){
	    if ( ! $this->id ) return false;
	    if ( ! $this->poll->revote ) return false;
        
        // если опция логов не включена, то отнимаем по кукам, 
        // тут голоса можно откручивать назад, потому что разные браузеры проверить не получится
        if( ! Dem::$opt['keep_logs'] )
            $this->minus_voted_in_answers();
            
        // Прежде чем удалять, проверим включена ли опция ведения логов и есть ли записи о голосовании в БД, 
        // так как их могут удалить в другом браузере и тогда, если не проверить, данные о голосовании пойдут в минус
        if( Dem::$opt['keep_logs'] && $this->get_logs_voted_data() ){
            $this->minus_voted_in_answers();
            $this->delete_logs_voted_data(); // чистим логи
        }
        
        $this->unset_cookie();
        
        $this->has_voted    = false;
        $this->votedFor    = false;
        $this->blockVoting = $this->poll->open ? false : true; // тут еще нужно учесть открыт опрос или нет...

        $this->set_answers(); // переустановим ответы, если вдруг добавленный ответ был удален
	}
    
    ## отнимает голоса в БД
    protected function minus_voted_in_answers(){
        global $wpdb;
		
        $INaids = implode(',', $this->get_answ_aids_from_str( $this->votedFor ) );
        
        if( ! $INaids ) return false;
        
        // сначала удалим добавленные пользователем ответы, если они есть и у них 0 или 1 голос.
        $r1 = $wpdb->query("DELETE FROM $wpdb->democracy_a WHERE added_by != '' AND votes IN (0,1) AND aid IN ($INaids) ORDER BY aid DESC LIMIT 1");
        
		// отнимаем голоса
        $r2 = $wpdb->query("UPDATE $wpdb->democracy_a SET votes = (votes-1) WHERE aid IN ($INaids)");
        
        return ($r1 || $r2);
    }
    
	## Получает массив ID ответов из переданной строки, где id разделены запятой.
	protected function get_answ_aids_from_str( $str ){
		$arr = explode(',', $str);
		$arr = array_map('trim', $arr );
		$arr = array_map('intval', $arr );
		$arr = array_filter( $arr ); // удалим пустые
		return $arr;
	}
	
	/**
	 * Устанавливает глобальные переменные $this->has_voted и $this->votedFor
	 */
	protected function setVotedData(){
		if( ! $this->id ) return false;
        
        // база приоритетнее куков, потому что в одном браузере можно отменить голосование, а куки в другому будут показывать что голосовал...
        // ЗАМЕТКА: обновим куки, если не совпадают. Потому что в разных браузерах могут быть разыне. 
        // Не работает потому что куки нужно устанавливать перед выводом данных и вообще так делать нельзя, 
        // потмоу что проверка по кукам становится не нужной в целом...
        if( Dem::$opt['keep_logs'] && $res = $this->get_logs_voted_data() ){			
            $this->has_voted = true;
            $this->votedFor = $res->aids;

            return;
        }
        
        // если дошли до сюда, проверяем куки
        if( isset($_COOKIE[ $this->cookey ]) && $_COOKIE[ $this->cookey ] != 'notVote' ){
			$this->has_voted = true;
			$this->votedFor = $_COOKIE[ $this->cookey ];
		}
		
	}
	
    // время до которого логи будут жить
    function expire_time(){
        return current_time('timestamp') + ( intval( Dem::$opt['cookie_days'] ) * DAY_IN_SECONDS );
    }
    
    /**
     * Устанавливает куки для текущего опроса
     * @param str $value Значение куки, по умолчанию текущие голоса.
     * @param int $expire Время окончания кики.
     * @return none.
     */
    function set_cookie( $value = '', $expire = false ){
        $expire = $expire ?: $this->expire_time();
        $value  = $value  ?: $this->votedFor;
	    
		setcookie( $this->cookey, $value, $expire, COOKIEPATH );
        
		$_COOKIE[ $this->cookey ] = $value;
    }
    
    function unset_cookie(){
        setcookie( $this->cookey, null, strtotime('-1 day'), COOKIEPATH );
		$_COOKIE[ $this->cookey ] = '';
    }
    
	/**
     * получает ответы в нужном порядке
     * @return $wpdb->get_results object
     */
	protected function set_answers(){
		global $wpdb;
		
		$ORDER = ( Dem::$opt['order_answers'] ) ? ' ORDER BY votes DESC' : '';
		
		$this->poll->answers = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->democracy_a WHERE qid = %d $ORDER", $this->id ) ) ;
		
		return $this->poll->answers;
	}
    
    /**
     * Ищет текущего пользвоателя или IP в базе в логах
     * @return $wpdb->get_results object или false
     */
    function get_logs_voted_data(){
        global $wpdb;
        
        $user_ip = ip2long( self::get_ip() );
        $AND = $wpdb->prepare('AND ip = %d', $user_ip );
        
        // нужно проверять пользователя и IP отдельно! А то есил пользователь не залогинен у него id 0 и он будет совпадать со всеми другими незалогиненными пользователями
        if( $user_id = get_current_user_id() ){
            $AND = $wpdb->prepare('AND userid = %d', $user_id ); // только для пользователей, IP не учитывается, т.е. если вы голосовали как посетитель, а потом залогинились, то можено будет голосовать еще раз
            //$AND = $wpdb->prepare('AND (userid = %d OR ip = %d)', $user_id, $user_ip );   
        }
        
        
        // Ищем пользвоателя или IP в базе
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->democracy_log WHERE qid = %d $AND", $this->id );
        $res = $wpdb->get_results( $sql );
     
        if( $res ) $res = array_shift( $res );
        
        return $res;
    }
    
    /**
     * Удаляет записи о голосовании в логах.
     * @return bool
     */
    protected function delete_logs_voted_data(){
        global $wpdb;

        $user_ip = ip2long( self::get_ip() );

        // Ищем пользвоателя или IP в логах 
        $sql = $wpdb->prepare("DELETE FROM $wpdb->democracy_log WHERE qid = %d AND (ip = %d OR userid = %d)", $this->id, $user_ip, get_current_user_id() );
        return $wpdb->query( $sql );
    }
    			    
	protected function add_logs(){
	    if( ! $this->id ) return false;

		global $wpdb;
		
		$user_ip = ip2long( self::get_ip() );
        
        $data = array(
			'ip'     => $user_ip,
			'qid'    => $this->id,
			'aids'   => $this->votedFor,
			'userid' => (int) get_current_user_id(),
			'date'   => current_time('mysql'),
			'expire' => $this->expire_time(),
		);
		//wp_die(print_r( $data ));
		$foo = $wpdb->insert( $wpdb->democracy_log, $data );
	}
	
	static function shortcode_html( $poll_id ){
		if( ! $poll_id )
			return '';
		return '<span style="cursor:pointer;padding:0 2px;background:#fff;" onclick="var sel = window.getSelection(), range = document.createRange(); range.selectNodeContents(this); sel.removeAllRanges(); sel.addRange(range);">[democracy id="'. $poll_id .'"]</span>';
	}
	
	## Получает IP пользвателя
	static function get_ip(){
		            $ip = @ $_SERVER['HTTP_CLIENT_IP'];
		if( ! $ip ) $ip = @ $_SERVER['HTTP_X_FORWARDED_FOR'];
		if( ! $ip )	$ip = $_SERVER['REMOTE_ADDR'];
		
		return $ip;
	}
		
}