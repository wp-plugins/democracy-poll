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
		
		$this->setVotedData();

		// закрываем опрос т.к. срок закончился
		if( $this->poll->end && $this->poll->open && ( current_time('timestamp') > $this->poll->end ) )
			$wpdb->update( $wpdb->democracy_q, array( 'open'=>0 ), array( 'id'=>$this->id ) );

		// только для зарегистрированных
		if( ( Dem::$inst->opt['only_for_users'] || $this->poll->forusers ) && ! is_user_logged_in() ) $this->blockForVisitor = true;

		// блокировка возможности голосовать
		if( $this->blockForVisitor || ! $this->poll->open || $this->hasVoted )   $this->blockVoting = true;
	}
	
	
	
	// displays the vote interface of a poll
	function display( $show_results = false, $before_title = '', $after_title = '' ){
	    if ( ! $this->id ) return false;
		
		$this->inArchive = ( $GLOBALS['post']->ID == Dem::$inst->opt['archive_page_id'] ) && is_singular();

		if( $this->blockVoting ) $show_results = true;
		
		$class = 'democracy '. ( $this->hasVoted ? 'dem-vote-screen' : 'dem-result-screen' );
			
		if( ! Dem::$inst->opt['disable_js'] ) Dem::$inst->add_js(); // подключаем скрипты (срабатывает один раз)

		$output = '';
		$output .= Dem::$inst->opt['inline_js_css'] ? Dem::$inst->add_css() : ''; // подключаем стили
			
		$output .= '<div class="'. $class .'" data-ajax-url="'. Dem::$inst->ajax_url .'" data-pid="'. $this->id .'">';
		    $output .=  ( $before_title ?: Dem::$inst->opt['before_title'] ) . $this->poll->question . ( $after_title  ?: Dem::$inst->opt['after_title'] );

			$output .=  '<div class="dem-results">';
				$output .= $show_results ? $this->getResultScreen() : $this->getVoteScreen();
			$output .=  '</div>';
		
			$output .= $this->poll->note ? '<div class="dem-poll-note">'. wpautop( $this->poll->note ) .'</div>' : '';
			if( current_user_can('manage_options') )
				$output .= '<a class="dem-edit-link" href="'. ( Dem::$inst->admin_page_url() .'&edit_poll='. $this->id ) .'" title="'. __('Редактировать опрос','dem') .'"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="1.5em" height="100%" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><path d="M617.8,203.4l175.8,175.8l-445,445L172.9,648.4L617.8,203.4z M927,161l-78.4-78.4c-30.3-30.3-79.5-30.3-109.9,0l-75.1,75.1 l175.8,175.8l87.6-87.6C950.5,222.4,950.5,184.5,927,161z M80.9,895.5c-3.2,14.4,9.8,27.3,24.2,23.8L301,871.8L125.3,696L80.9,895.5z"/></svg>
</a>';
		$output .=  "</div>";
		
			
		return $output;
	}
	
	function getVoteScreen(){
	    if( ! $this->id ) return false;
		if( $this->blockVoting ) return false;

		$output = $dem_act = '';

		$answers = $this->getAnswers();
		
		// copyright
		if( Dem::$inst->opt['show_copyright'] && ( is_home() || is_front_page() ) )
			$output .=  '<a class="dem-copyright" href="http://wp-kama.ru/?p=67" title="'. __('Скачать Опрос Democracy','dem') .'"> © </a>';
		
			
		if( $answers ){
			$output .= '<form method="post" action="">';	
				$output .= '<ul class="dem-vote">';
			
					$type = $this->poll->multiple ? 'checkbox' : 'radio';
			
					foreach( $answers as $answer ){
						$output .= "
						<li>
							<label>
								<input type='$type' value='{$answer->aid}' name='answer_ids[]' />
								". stripslashes( $answer->answer ) ."
							</label>
						</li>";
					}

					if( $this->poll->democratic ){
						// Событие добавления ответа пользователя без AJAX
						if( isset( $_GET['show_addanswerfield'] ) && @$_GET['dem_pid'] == $this->id ){
							$output .= '
							<li>
								<input type="text" name="answer_ids[]" value="" class="dem-add-answer-txt" />
							</li>';
						} 
						else {
							$url = add_query_arg( array('show_addanswerfield'=>1, 'dem_pid' => $this->id, 'dem_act'=>null ) );
							$output .= '<li class="dem-add-answer"><a href="'. $url .'" rel="nofollow" data-dem-act="newAnswer" class="dem-link">'. __('Добавить свой ответ','dem') .'</a></li>';
						}
					}		
				$output .= "</ul>";
				
				$output .= '<div class="dem-bottom">';
					$output .= '<input type="hidden" name="dem_act" value="vote" />';
					$output .= '<input type="hidden" name="dem_pid" value="'. $this->id .'" />';
					$output .= '<div class="dem-vote-button"><input type="submit" value="'. __('Голосовать','dem') .'" data-dem-act="vote" /></div>';

					$url   = add_query_arg( array('dem_act' => 'view', 'dem_pid' => $this->id) );
					$output .= '<a href="'. $url .'" class="dem-link dem-get-votes" data-dem-act="view" rel="nofollow">'. __('результаты','dem') .'</a>';
				$output .= '</div>';
			
			$output .= '</form>';	
		}
		
		return $output;

	}
	
	function getResultScreen(){
	    if( ! $this->id ) return false;

		$output = '';

		$answers = $this->getAnswers();
		
		// vars
		$max = $total = 0;
		foreach ( $answers as $answer ){
			$total += $answer->votes;
			if ( $max < $answer->votes ) $max = $answer->votes;
		}

		$output .= '<ul class="dem-answers">';
			foreach ( $answers as $answer ){
				$word          = stripslashes( $answer->answer );
				$votes         = (int) $answer->votes;
				$is_voted_this = ( $this->hasVoted && in_array( $answer->aid, explode(',', $this->votedFor) ) );
				$is_winner     = ( $max == $votes );
				
				$li_class = ' class="'. ( $is_winner ? 'dem-winner' : '' ) . ( $is_voted_this ? ' dem-voted-this' : '' ) .'"';
				$sup = $answer->added_by ? ' <sup class="dem-footnote" title="'. __('Ответ добавлен посетителем','dem') .'">*</sup>' : '';
				$percent = ( $votes > 0) ? round($votes / $total * 100) : 0;
				
				$percent_txt = sprintf( __("%s%% от всех голосов",'dem'), $percent );
				$title       = ( $is_voted_this ? __('Ваш голос. ','dem') : '' ) . ' '. $percent_txt;
				$title       = " title='$title'";
				
				// склонение голосов
				$sclonenie = function( $number, $titles ){ $cases = array (2, 0, 1, 1, 1, 2);
					return $number .' '. $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
				};
				$votes_txt = $sclonenie( $votes, array(__('голос','dem'),__('голоса','dem'),__('голосов','dem')) );

				$output .= '<li'. $li_class . $title .'>';
					$label_perc_txt = ' <span class="dem-label-percent-txt">'. $percent .'%, '. $votes_txt .'</span>';
					$output .= '<div class="label">'. $word . $sup . $label_perc_txt .'</div>';


					$graph_percent = ( ! Dem::$inst->opt['graph_from_total'] && $percent != 0 ) ? round( $votes / $max * 100 ) : $percent;

					
					$percent_txt = "<div class='dem-percent-text'>". $percent_txt ."</div>";
					$votes_txt   = "<div class='dem-text-votes'>$votes_txt <span class='dem-only-percent-txt'>". $percent ."%</span></div>";
					if( $votes == 0 ){ $votes_txt = $percent_txt = ''; }
					
					$output .= '<div class="dem-graph">';
						$output .= "<div class='dem-fill' style='width:{$graph_percent}%;'></div>";
						$output .= $votes_txt;
						$output .= $percent_txt;
					$output .= "</div>";
				$output .= "</li>";
			}
		$output .= '</ul>';
		
		$output .= '<div class="dem-bottom">';
			$output .= '<div class="dem-vote-info">';
				$output .= '<div class="dem-total-votes">'. sprintf( __('Всего голосов: %s','dem'), $total ) .'</div>';
				$output .= '<div class="dem-begin-date" title="'. __('Начало','dem') .'">'. date_i18n( get_option('date_format'), $this->poll->added ) .'</div>';
				$output .= $this->poll->end    ? '<div class="dem-begin-date" title="'. __('Конец','dem') .'">'. date_i18n( get_option('date_format'), $this->poll->end ) .'</div>' : '';
				$output .= $answer->added_by   ? '<div class="dem-added-by-user"><span class="dem-footnote">*</span>'. __(' - добавлен посетителем','dem') .'</div>' : '';
				$output .= ! $this->poll->open ? '<div>'. __('Опрос закрыт','dem') .'</div>' : '';
				if( ! $this->inArchive )
					$output .= '<a class="dem-archive-link dem-link" rel="nofollow" href="'. get_permalink( Dem::$inst->opt['archive_page_id'] ) .'">'. __('Архив опросов','dem') .'</a>';
			$output .= '</div>';

			if( ! $this->blockVoting ){
				$url    = add_query_arg( array('dem_act' => 'vote_screen', 'dem_pid' => $this->id ) );
				$output .= '<a href="'. $url .'" class="dem-link dem-vote-link" rel="nofollow" data-dem-act="vote_screen">'. __('Голосовать','dem') .'</a>';

			}

			// заметка для незарегистрированных пользователей
			if( $this->blockForVisitor ){
				$url = esc_url( wp_login_url( $_SERVER['REQUEST_URI'] ) );
				$output .= '<div class="dem-poll-note">'. sprintf( __('Голосовать могут только зарегистрированные пользователи. <a href="%s">Войдите</a> для голосования','dem'), $url ) .'</div>';
			}

			if( $this->hasVoted && $this->poll->revote ){
				$url    = add_query_arg( array('dem_act' => 'delVoted', 'dem_pid' => $this->id ) );
				$jsdata = "{ dem_act: 'delVoted', dem_pid: $this->id }";
				$output .= '<a class="dem-revote-link dem-link" href="'. $url .'" data-dem-act="delVoted" data-confirm-text="'. __('Точно отменить голоса?','dem') .'">
					'. __('Переголосовать', 'dem') .'
				</a>';
			}
		$output .= '</div>';
		
		return $output;
	}
	
	/**
	 * Получает массив ID из переданной строки, где id разделены запятой.
	 * Преобразует ID в числа, готовые для SQL запроса.
	 * @param string $str строка, где id разделены запятой
	 * @return Массив.
	 */
	protected function _get_aids_from_str( $str ){
		$arr = explode(',', $str);
		$arr = array_map('trim', $arr );
		$arr = array_map('intval', $arr );
		$arr = array_filter( $arr ); // удалим пустые
		return $arr;
	}
	
	/**
	 * Удаляет данные пользователя о голосовании
	 * Отменяет установленные $this->hasVoted и $this->votedFor
	 */
	function unsetVotedData(){
	    if ( ! $this->id ) return false;
	    if ( ! $this->poll->revote ) return false;
		
        global $wpdb;
		
        setcookie( $this->cookey, '', time()-99999, COOKIEPATH );
		$_COOKIE[ $this->cookey ] = '';
		
		// отнимаем голоса, но сначала удалим добавленные пользователем ответы, если они есть.
		$INaids = implode(',', $this->_get_aids_from_str( $this->votedFor ) );
		$wpdb->query("DELETE FROM $wpdb->democracy_a WHERE added_by = 1 AND votes IN (0,1) AND aid IN ($INaids) ORDER BY aid DESC LIMIT 1");
		$wpdb->query("UPDATE $wpdb->democracy_a SET votes = (votes-1) WHERE aid IN ($INaids)");
		
		// удаляем записи о голосовании
		$field_name = 'ip';
		$field_val  = ip2long( $_SERVER['REMOTE_ADDR'] );
		if( $user_id = (int) get_current_user_id() ){
			$field_name = 'userid';
			$field_val  = $user_id;			
		}
		$wpdb->delete( $wpdb->democracy_log, array( 'qid'=>$this->id, $field_name=>$field_val ) );
		
		$this->hasVoted    = false;
		$this->votedFor    = false;
		$this->blockVoting = false;
	}
		
	/**
	 * Устанавливает глобальные переменные $this->hasVoted и $this->votedFor
	 */
	protected function setVotedData(){
		if( ! $this->id ) return false;
		
        global $wpdb;
        		
        if( isset( $_COOKIE[ $this->cookey ] ) ){
			$this->hasVoted = true;
			$this->votedFor = $_COOKIE[ $this->cookey ];
			return;
		}
		
        if( Dem::$inst->opt['logIPs'] ){			
			$user_ip = ip2long( $_SERVER['REMOTE_ADDR'] );
			$user_id = get_current_user_id();
			
			// Если пользователь зарегистрирован, проверяем только голосовал ли пользователь. 
			// Потому что с одного IP могут голосовать несколько пользователей.
			// Если не авторизован, то проверяем IP.
			$field_name = 'ip';
			$field_val  = $user_ip;
			if( $user_id ){
				$field_name = 'userid';
				$field_val  = $user_id;
			}
			$sql = $wpdb->prepare("SELECT * FROM $wpdb->democracy_log WHERE qid = %d AND $field_name = %d LIMIT 1", $this->id, $field_val );
			$res = $wpdb->get_results( $sql );

			if( $res = array_shift( $res ) ){
				$this->hasVoted = true;
				$this->votedFor = $res->aids;
				return;
			}
			
        }
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
			$aids = explode(',', $aids );
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
				if( $aid = (int) $this->addInlineAnswer( $new_user_answer ) );
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
		
		// setcookie
		$cookie_last = current_time('timestamp') + ( intval( Dem::$inst->opt['cookie_days'] ) * DAY_IN_SECONDS );
	    setcookie( $this->cookey, $aids, $cookie_last, COOKIEPATH );

		$this->blockVoting = true;
		$this->hasVoted    = true;
		$this->votedFor    = $aids;

		if( Dem::$inst->opt['logIPs'] ) $this->logIP();
	}
	
	protected function getAnswers(){
		global $wpdb;

		$ORDER = ( Dem::$inst->opt['order_answers'] ) ? ' ORDER BY votes DESC' : '';
		
		return $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->democracy_a WHERE qid = %d $ORDER", $this->id ) ) ;	
	}
		
	protected function logIP(){
	    if( ! $this->id ) return false;

		global $wpdb;
		
		$user_ip = ip2long( $_SERVER['REMOTE_ADDR'] );
		
		$foo = $wpdb->insert( $wpdb->democracy_log, array( 'ip' => $user_ip, 'qid' => $this->id, 'aids' => $this->votedFor, 'userid' => (int) get_current_user_id() ) );
	}
	
	protected function addInlineAnswer( $answer ){
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
	
	
	
}