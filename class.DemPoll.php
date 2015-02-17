<?php
/* 
 Класс отвечает за вывод и голосование отдельного опроса.
 Нуждается в классе плагина Dem 
 */

class DemPoll {
	var $id;
	var $poll;
		
	var $hasVoted     = false;
	var $votedFor     = '';
	var $blockVoting  = false; // блокировать голосование
	var $blockForVisitor = false; // только для зарегистрированных
	
    var $inArchive    = false; // для вывода опросов в архиве
    
	var $cachegear_on = false; // проверка включен ли механихм кэширвоания
	var $for_cache    = false; 
	
	var $cookey;           // Название ключа cookie
    
	function __construct( $id = 0 ){
		global $wpdb;
		
		$this->id = (int) $id;
							
		if( ! $this->id )
			$poll = $wpdb->get_row("SELECT * FROM $wpdb->democracy_q WHERE active = 1 ORDER BY RAND() LIMIT 1");			
		else
			$poll = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->democracy_q WHERE id = %d LIMIT 1", $this->id ) );			

        if( ! $poll ) return print "<!-- democracy: there is no active polls -->";
		
		// устанавливаем необходимые переменные
		$this->id = (int) $poll->id;
		
		if( ! $this->id ) return; // влияет на весь класс, важно!
		
		$this->cookey    = 'demPoll_' . $this->id;
		$this->poll      = $poll;
        
		$this->cachegear_on = Dem::$inst->is_cachegear_on();		
		
		$this->setVotedData();
        $this->set_answers(); // установим свойство $this->poll->answers

		// закрываем опрос т.к. срок закончился
		if( $this->poll->end && $this->poll->open && ( current_time('timestamp') > $this->poll->end ) )
			$wpdb->update( $wpdb->democracy_q, array( 'open'=>0 ), array( 'id'=>$this->id ) );

		// только для зарегистрированных
		if( ( Dem::$inst->opt['only_for_users'] || $this->poll->forusers ) && ! is_user_logged_in() ) $this->blockForVisitor = true;

		// блокировка возможности голосовать
		if( $this->blockForVisitor || ! $this->poll->open || $this->hasVoted )   $this->blockVoting = true;
        
	}
		
	
    /**
     * Получает HTML опроса
     * @param bool $show_screen Какой экран показывать: vote, voted, force_vote
     * @return HTML
     */
	function get_screen( $show_screen = 'vote', $before_title = '', $after_title = '' ){
	    if ( ! $this->id ) return false;
        		
		$this->inArchive = ( @$GLOBALS['post']->ID == Dem::$inst->opt['archive_page_id'] ) && is_singular();

		if( $this->blockVoting && $show_screen != 'force_vote' ) $show_screen = 'voted';
			
		if( ! Dem::$inst->opt['disable_js'] ) Dem::$inst->add_js(); // подключаем скрипты (срабатывает один раз)

		$___ = '';
		$___ .= Dem::$inst->add_css(); //Dem::$inst->opt['inline_js_css'] ? Dem::$inst->add_css() : ''; // подключаем стили
			
		$___ .= '<div class="democracy" data-ajax-url="'. Dem::$inst->ajax_url .'" data-pid="'. $this->id .'">';
		    $___ .=  ( $before_title ?: Dem::$inst->opt['before_title'] ) . $this->poll->question . ( $after_title  ?: Dem::$inst->opt['after_title'] );
		
			// изменяемая часть
			$___ .=  $this->get_screen_basis( $show_screen );
			// изменяемая часть
		
			$___ .= $this->poll->note ? '<div class="dem-poll-note">'. wpautop( $this->poll->note ) .'</div>' : '';
			if( current_user_can('manage_options') )
				$___ .= '<a class="dem-edit-link" href="'. ( Dem::$inst->admin_page_url() .'&edit_poll='. $this->id ) .'" title="'. __('Редактировать опрос','dem') .'"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1.5em" height="100%" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><path d="M617.8,203.4l175.8,175.8l-445,445L172.9,648.4L617.8,203.4z M927,161l-78.4-78.4c-30.3-30.3-79.5-30.3-109.9,0l-75.1,75.1 l175.8,175.8l87.6-87.6C950.5,222.4,950.5,184.5,927,161z M80.9,895.5c-3.2,14.4,9.8,27.3,24.2,23.8L301,871.8L125.3,696L80.9,895.5z"/></svg>
</a>';
        // copyright
		if( Dem::$inst->opt['show_copyright'] && ( is_home() || is_front_page() ) )
			$___ .=  '<a class="dem-copyright" href="http://wp-kama.ru/?p=67" title="'. __('Скачать Опрос Democracy','dem') .'"> © </a>';
		
        // loader 
        if( Dem::$inst->opt['loader_fname'] ){
            static $loader; // оптимизация, чтобы один раз выводился код на странице
            if( ! $loader ){
                $loader = '<div class="dem-loader"><div>'. file_get_contents( Dem::$inst->dir_path .'styles/loaders/'. Dem::$inst->opt['loader_fname'] ) .'</div></div>';
                $___ .=  $loader;
            }
        }

		$___ .=  "</div><!--democracy-->";
		
		
		// Скрытый код если используется плагин страничного кэширования
		if( $this->cachegear_on ){
			$___ .= '<!--noindex--><div class="dem-cache-screens" style="display:none;" data-opt_logs="'. Dem::$inst->opt['keep_logs'] .'">';
			
			// запоминаем
			$votedFor = $this->votedFor;
			$this->votedFor = false;
            $this->for_cache = 1;
            
			$compress = function( $str ){ return preg_replace("~[\n\r\t]~u", '', preg_replace('~\s+~u',' ',$str) ); };
			$___ .= $compress( $this->get_screen_basis('voted') );  // voted_screen
            if( $this->poll->open )
                $___ .= $compress( $this->get_screen_basis('force_vote') ); // vote_screen
            
            $this->for_cache = 0;
			$this->votedFor = $votedFor; // возвращаем

			$___ .=	'</div><!--/noindex-->';
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
        $screen = ( $show_screen == 'vote' || $show_screen == 'force_vote' ) ? 'vote' : 'voted';
		$___ = '<div class="dem-screen'. $class_suffix .' '. $screen  .'">';
		$___ .= ( $screen == 'vote' ) ? $this->getVoteScreen() : $this->getResultScreen();
		$___ .=  '</div>';
		
		return $___;
	}
	
    /**
     * Получает код для голосования
     * @return HTML
     */
	function getVoteScreen(){
	    if( ! $this->id ) return false;

		$___ = $dem_act = ''; // vars
					
        $___ .= '<form method="post" action="">';	
            $___ .= '<ul class="dem-vote">';

                $type = $this->poll->multiple ? 'checkbox' : 'radio';

                foreach( $this->poll->answers as $answer ){
                    $___ .= "
                    <li>
                        <label>
                            <input type='$type' value='{$answer->aid}' name='answer_ids[]' /> ". stripslashes( $answer->answer ) ."
                        </label>
                    </li>";
                }

                if( $this->poll->democratic ){
                    // Событие добавления ответа пользователя без AJAX
                    if( isset( $_GET['show_addanswerfield'] ) && @$_GET['dem_pid'] == $this->id ){
                        $___ .= '
                        <li>
                            <input type="text" name="answer_ids[]" value="" class="dem-add-answer-txt" />
                        </li>';
                    } 
                    else {
                        $url = add_query_arg( array('show_addanswerfield'=>1, 'dem_pid' => $this->id, 'dem_act'=>null ) );
                        $___ .= '<li class="dem-add-answer"><a href="'. $url .'" rel="nofollow" data-dem-act="newAnswer" class="dem-link">'. __('Добавить свой ответ','dem') .'</a></li>';
                    }
                }		
            $___ .= "</ul>";

            $___ .= '<div class="dem-bottom">';
                $___ .= '<input type="hidden" name="dem_act" value="vote" />';
                $___ .= '<input type="hidden" name="dem_pid" value="'. $this->id .'" />';
                $___ .= '<div class="dem-vote-button"><input class="dem-button" type="submit" value="'. __('Голосовать','dem') .'" data-dem-act="vote" /></div>';

                $url   = add_query_arg( array('dem_act' => 'view', 'dem_pid' => $this->id) );
                $___ .= '<a href="'. $url .'" class="dem-link dem-results-link" data-dem-act="view" rel="nofollow">'. __('Результаты','dem') .'</a>';
            $___ .= '</div>';

        $___ .= '</form>';	
		
		return $___;

	}
    
    /**
     * Получает код результатов голосования
     * @return HTML
     */
	function getResultScreen(){
	    if( ! $this->id ) return false;

		$___ = '';
		
		$max = $total = 0;

        foreach ( $this->poll->answers as $answer ){
			$total += $answer->votes;
			if ( $max < $answer->votes ) $max = $answer->votes;
		}
		
		$voted_class = 'dem-voted-this';
		$voted_txt   = __('Ваш голос. ','dem');
		$___ .= '<ul class="dem-answers" data-voted-class="'. $voted_class .'" data-voted-txt="'. $voted_txt .'">';
			foreach ( $this->poll->answers as $answer ){
				$word          = stripslashes( $answer->answer );
				$votes         = (int) $answer->votes;
				$is_voted_this = ( $this->hasVoted && in_array( $answer->aid, explode(',', $this->votedFor) ) );
				$is_winner     = ( $max == $votes );
				
				$li_class = ' class="'. ( $is_winner ? 'dem-winner':'' ) . ( $is_voted_this ? " $voted_class":'' ) .'"';
				$sup = $answer->added_by ? ' <sup class="dem-star" title="'. __('Ответ добавлен посетителем','dem') .'">*</sup>' : '';
				$percent = ( $votes > 0 ) ? round($votes / $total * 100) : 0;
				
				$percent_txt = sprintf( __("%s%% от всех голосов",'dem'), $percent );
				$title       = ( $is_voted_this ? $voted_txt : '' ) . ' '. $percent_txt;
				$title       = " title='$title'";
				
				// склонение голосов
				$sclonenie = function( $number, $titles ){ $cases = array (2, 0, 1, 1, 1, 2);
					return $number .' '. $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
				};
				$votes_txt = $sclonenie( $votes, array(__('голос','dem'),__('голоса','dem'),__('голосов','dem')) );

				$___ .= '<li'. $li_class . $title .' data-aid="'. $answer->aid .'">';
                    $novoted_class = ( $votes == 0 ) ? ' dem-novoted' : '';
					$label_perc_txt = ' <span class="dem-label-percent-txt'. $novoted_class .'">'. $percent .'%, '. $votes_txt .'</span>';
					$percent_txt = '<div class="dem-percent-txt'. $novoted_class .'">'. $percent_txt .'</div>';
                    $percent_txt_inline = ( $percent > 0 ) ? ' <span class="dem-votes-txt-percent">'. $percent .'%</span>' : '';
					$votes_txt   = '<div class="dem-votes-txt'. $novoted_class .'">'. $votes_txt . $percent_txt_inline . '</div>';
					                
                    $___ .= '<div class="label">'. $word . $sup . $label_perc_txt .'</div>';
				
                    $graph_percent = ( ! Dem::$inst->opt['graph_from_total'] && $percent != 0 ) ? round( $votes / $max * 100 ) : $percent;
					
					$___ .= '<div class="dem-graph">';
						$___ .= "<div class='dem-fill' style='width:{$graph_percent}%;'></div>";
						$___ .= $votes_txt;
						$___ .= $percent_txt;
					$___ .= "</div>";
				$___ .= "</li>";
			}
		$___ .= '</ul>';
		
		$___ .= '<div class="dem-bottom">';
			$___ .= '<div class="dem-poll-info">';
				$___ .= '<div class="dem-total-votes">'. sprintf( __('Всего голосов: %s','dem'), $total ) .'</div>';
				$___ .= '<div class="dem-begin-date" title="'. __('Начало','dem') .'">'. date_i18n( get_option('date_format'), $this->poll->added ) .'</div>';
				$___ .= $this->poll->end    ? '<div class="dem-begin-date" title="'. __('Конец','dem') .'">'. date_i18n( get_option('date_format'), $this->poll->end ) .'</div>' : '';
				$___ .= $answer->added_by   ? '<div class="dem-added-by-user"><span class="dem-star">*</span>'. __(' - добавлен посетителем','dem') .'</div>' : '';
				$___ .= ! $this->poll->open ? '<div>'. __('Опрос закрыт','dem') .'</div>' : '';
				if( ! $this->inArchive && Dem::$inst->opt['archive_page_id'] )
					$___ .= '<a class="dem-archive-link dem-link" href="'. get_permalink( Dem::$inst->opt['archive_page_id'] ) .'" rel="nofollow">'. __('Архив опросов','dem') .'</a>';
			$___ .= '</div>';

        if( $this->poll->open ){
            // заметка для незарегистрированных пользователей
            $url = esc_url( wp_login_url( $_SERVER['REQUEST_URI'] ) );
            $html_only_users = '<div class="dem-only-users">'. sprintf( __('Голосовать могут только зарегистрированные пользователи. <a href="%s" rel="nofollow">Войдите</a> для голосования.','dem'), $url ) .'</div>';
            // переголосовать
            $url = add_query_arg( array('dem_act' => 'delVoted', 'dem_pid' => $this->id ) );
            $html_revote = '<a class="dem-revote-link dem-link" href="'. $url .'" data-dem-act="delVoted" data-confirm-text="'. __('Точно отменить голоса?','dem') .'" rel="nofollow">
                '. __('Переголосовать', 'dem') .'
            </a>';
            // вернуться к голосованию
            $url = add_query_arg( array('dem_act' => 'vote_screen', 'dem_pid' => $this->id ) );
            $html_backvote = '<a href="'. $url .'" class="dem-button dem-vote-link" data-dem-act="vote_screen" rel="nofollow">'. __('Голосовать','dem') .'</a>';
        
			if( ! $this->for_cache && $this->blockForVisitor ){
                $___ .= $html_only_users;
            }else{
                if( $this->for_cache || ($this->hasVoted && $this->poll->revote) )
                    $___ .= $html_revote;
                else
                    $___ .= $html_backvote;
            }
        
            if( $this->for_cache ){
                $___ .= '<div class="dem-cache-notice dem-youarevote" style="display:none;">'. __('Вы уже голосовали','dem') .'</div>';
                $___ .= str_replace( array('<div', 'class="'), array('<div style="display:none;"', 'class="dem-cache-notice '), $html_only_users );
            }
        }
        
		$___ .= '</div>';
        
		
		return $___;
	}
    
	/**
	 * Обновляет голоса.
	 * @param str $aids ID ответов через запятую. Также там может быть строка, тогда она будет добавлена, как ответ пользователя ответ.
	 * @return false or none
	 */
	function addVote( $aids ){
	    if( ! $this->id || $this->hasVoted || $this->blockVoting ) return false;
		
		global $wpdb;
		
		if( ! is_array( $aids ) ){
			$aids = trim( $aids );
			$aids = explode('~', $aids );
		}
		$aids = array_map('trim', $aids);
		// Добавка ответа пользователя. 
		// Добавление произвольного ответа. 
		// Првоеряет значение массива, ищет строку, есил есть то это и есть произвольный ответ.
		if( $this->poll->democratic ){
			$new_user_answer = false;
			foreach( $aids as $k => $id ){
				if( ! preg_match('~^[0-9]+$~', $id) ){
					$new_user_answer = $id;
					unset( $aids[ $k ] ); // удалим из общего массива, чтобы дельше его не было
					
					if( ! $this->poll->multiple ) $aids = array(); // опусташим массив так как множественное голосование запрещено
					//break; !NO
				}
			}
			if( $new_user_answer ){
				// есть ответ пользователя, добавляем и голосуем
				if( $aid = (int) $this->_addVote_InlineAnswer( $new_user_answer ) );
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
		$this->hasVoted    = true;
		$this->votedFor    = $aids;
        
        $this->set_answers(); // переустановим ответы

        $this->set_cookie(); // установим куки
        
		if( Dem::$inst->opt['keep_logs'] ) $this->add_logs();
		
	}
    
	private function _addVote_InlineAnswer( $answer ){
	    if( ! $this->id || $this->hasVoted || $this->blockVoting ) return false;

		global $wpdb;
		
		$new_answer = strip_tags( $answer );
		
		$insert_id = 0;
		// проверим нет ли уже такого ответа
		$exists = $wpdb->query( $wpdb->prepare("SELECT aid FROM $wpdb->democracy_a WHERE answer = '%s' AND qid = $this->id", $new_answer ) );
		if( ! $exists ) 
			if( $wpdb->insert( $wpdb->democracy_a, array( 'qid'=>$this->id, 'answer'=>$new_answer, 'votes'=>0, 'added_by'=>1 ) ) )
				$insert_id = $wpdb->insert_id;
	    
	    return $insert_id;
	}	
	
	/**
	 * Удаляет данные пользователя о голосовании
	 * Отменяет установленные $this->hasVoted и $this->votedFor
     * Должна вызываться как зоголовки, до вывода данных
	 */
	public function unsetVotedData(){
	    if ( ! $this->id ) return false;
	    if ( ! $this->poll->revote ) return false;
        
        // если опция логов не включена, то отнимаем по кукам, 
        // тут голоса можно откручивать назад, потому что разные браузеры проверить не получится
        if( ! Dem::$inst->opt['keep_logs'] )
            $this->_minusVotedData_inDB();
            
        // Прежде чем удалять, проверим включена ли опция ведения логов и есть ли записи о голосовании в БД, 
        // так как их могут удалить в другом браузере и тогда, если не проверить, данные о голосовании пойдут в минус
        if( Dem::$inst->opt['keep_logs'] && $this->get_logs_voted_data() ){
            $this->_minusVotedData_inDB();
            $this->delete_logs_voted_data(); // чистим логи
        }
        
        $this->unset_cookie();
        
        $this->hasVoted    = false;
        $this->votedFor    = false;
        $this->blockVoting = $this->poll->open ? false : true; // тут еще нужно учесть открыт опрос или нет...

        $this->set_answers(); // переустановим ответы, если вдруг добавленный ответ был удален
	}
    
    ## отнимает голоса в БД
    protected function _minusVotedData_inDB(){
        global $wpdb;
        
        $INaids = implode(',', $this->_sql_get_aids_from_str( $this->votedFor ) );
        
        if( ! $INaids ) return false;
        
        // сначала удалим добавленные пользователем ответы, если они есть и у них 0 или 1 голос.
        $r1 = $wpdb->query("DELETE FROM $wpdb->democracy_a WHERE added_by = 1 AND votes IN (0,1) AND aid IN ($INaids) ORDER BY aid DESC LIMIT 1");
        // отнимаем голоса
        $r2 = $wpdb->query("UPDATE $wpdb->democracy_a SET votes = (votes-1) WHERE aid IN ($INaids)");
        
        return ($r1 || $r2);
    }
    
	/**
	 * Получает массив ID из переданной строки, где id разделены запятой.
	 * Преобразует ID в числа, готовые для SQL запроса.
	 * @param string $str строка, где id разделены запятой
	 * @return Массив.
	 */
	protected function _sql_get_aids_from_str( $str ){
		$arr = explode(',', $str);
		$arr = array_map('trim', $arr );
		$arr = array_map('intval', $arr );
		$arr = array_filter( $arr ); // удалим пустые
		return $arr;
	}
	
	/**
	 * Устанавливает глобальные переменные $this->hasVoted и $this->votedFor
	 */
	protected function setVotedData(){
		if( ! $this->id ) return false;
        
        // база приоритетнее куков, потому в одном раузере можно отменить голосование, а куки в другому будут показывать что голосовал...
        // ЗАМЕТКА: обновим куки, если не совпадают. Потому что в разных браузерах могут быть разыне. 
        // Не работает потому что куки нужно устанавливать перед выводом данных и вообще так делать нельзя, 
        // потмоу что проверка по кукам становится не нужной в целом...
        if( Dem::$inst->opt['keep_logs'] && $res = $this->get_logs_voted_data() ){			
            $this->hasVoted = true;
            $this->votedFor = $res->aids;

            return;
        }
        
        // если дошли до сюда, проверяем куки
        if( isset($_COOKIE[ $this->cookey ]) && $_COOKIE[ $this->cookey ] != 'notVote' ){
			$this->hasVoted = true;
			$this->votedFor = $_COOKIE[ $this->cookey ];
		}
		
	}
	
    // время до которого логи будут жить
    function expire_time(){
        return current_time('timestamp') + ( intval( Dem::$inst->opt['cookie_days'] ) * DAY_IN_SECONDS );
    }
    
    /**
     * Устанавливает куки для текущего опроса
     * @param str $value Значение куки, по умолчанию текущие голоса.
     * @param int $expire Время окончания кики.
     * @return none.
     */
    public function set_cookie( $value = '', $expire = false ){
        $expire = $expire ?: $this->expire_time();
        $value  = $value  ?: $this->votedFor;
	    setcookie( $this->cookey, $value, $expire, COOKIEPATH );
        $_COOKIE[ $this->cookey ] = $value;
    }
    
    public function unset_cookie(){
        setcookie( $this->cookey, null, strtotime('-1 day'), COOKIEPATH );
		$_COOKIE[ $this->cookey ] = '';
    }
    
	/**
     * получает ответы в нужном порядке
     * @return $wpdb->get_results object
     */
	protected function set_answers(){
		global $wpdb;
		
		$ORDER = ( Dem::$inst->opt['order_answers'] ) ? ' ORDER BY votes DESC' : '';
		
		$this->poll->answers = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->democracy_a WHERE qid = %d $ORDER", $this->id ) ) ;
		
		return $this->poll->answers;
	}
    
    /**
     * Ищет пользвоателя или IP в базе в логах
     * @return $wpdb->get_results object или false
     */
    function get_logs_voted_data(){
        global $wpdb;
        
        $user_ip = ip2long( $_SERVER['REMOTE_ADDR'] );
        $user_id = get_current_user_id();

        // Ищем пользвоателя или IP в базе 
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->democracy_log WHERE qid = %d AND (ip = %d OR userid = %d)", $this->id, $user_ip, $user_id );
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

        $user_ip = ip2long( $_SERVER['REMOTE_ADDR'] );
        $user_id = get_current_user_id();

        // Ищем пользвоателя или IP в базе 
        $sql = $wpdb->prepare("DELETE FROM $wpdb->democracy_log WHERE qid = %d AND (ip = %d OR userid = %d)", $this->id, $user_ip, $user_id );
        return $wpdb->query( $sql );
    }
    			    
	protected function add_logs(){
	    if( ! $this->id ) return false;

		global $wpdb;
		
		$user_ip = ip2long( $_SERVER['REMOTE_ADDR'] );
        
        $data = array( 'ip' => $user_ip, 'qid' => $this->id, 'aids' => $this->votedFor, 'userid' => (int) get_current_user_id(), 'expire'=>$this->expire_time() );
		
		$foo = $wpdb->insert( $wpdb->democracy_log, $data );
	}
	
	
}