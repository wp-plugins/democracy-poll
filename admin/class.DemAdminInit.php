<?php

### ADMIN PART
class DemAdminInit extends Dem{
        
	function __construct(){
        parent::__construct();
        
		// add the management page to the admin nav bar
		if( $this->user_access )
			add_action('admin_menu', array( $this, 'register_option_page') );
		
		// ссылка на настойки
		add_filter('plugin_action_links', array( $this, 'setting_page_link'), 10, 2 );
		
		// TinyMCE кнопка WP2.5+
		if( $this->opt['tinymce_button'] ) $this->tinymce_button();
		
	}
	
	// Страница плагина
	function register_option_page(){
		$hook_name = add_options_page(__('Опрос Democracy','dem'), __('Опрос Democracy','dem'), 'manage_options', basename( $this->dir_path ), array( $this, 'admin_page_output') );
		add_action("load-$hook_name", array( $this, 'admin_page_load') );
	}
    
	// admin page html
	function admin_page_output(){	
		if( @$_GET['message'] == 'created' ) $this->message[] = __('Новый опрос создан','dem');
		
		// сообщения
		if( $this->message ){
			foreach( $this->message as $message ){
				echo "<div class='updated'><p>$message</p></div>";
			}
		}
		
		include $this->dir_path .'admin/admin_page.php';
	}
	
	// Ссылка на настройки со страницы плагинов
	function setting_page_link( $actions, $plugin_file ){
		if( false === strpos( $plugin_file, basename( $this->dir_path ) ) ) return $actions;

		$settings_link = '<a href="'. $this->admin_page_url() .'">'. __('Настройки','dem') .'</a>'; 
		array_unshift( $actions, $settings_link );
		
		return $actions; 
	}
    
	
	// предватирельная загрузка страницы настроек плагина, подключение стилей, скриптов, запросов и т.д.
	function admin_page_load(){
		// обновляем опции и БД плагина если нужно
		dem_last_version_up();

		// добавляем стили и скрипты
		// datepicker
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('jquery-ui');
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css');
		
        // Iris Color Picker 
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

		// другие
		wp_enqueue_script('ace', $this->dir_url . 'admin/ace/src-min-noconflict/ace.js', array(), DEM_VER, true );
		wp_enqueue_script('democracy-scripts', $this->dir_url . 'admin/admin.js', array('jquery','ace'), DEM_VER, true );
		wp_enqueue_style('democracy-styles', $this->dir_url . 'admin/style.css', array(), DEM_VER );

		## Обработка запросов
        $up = false;
		// обновляем произвольную локализацию
		if( isset( $_POST['dem_save_l10n'] ) )            $up = $this->update_l10n();
		// сбрасываем произвольную локализацию
		if( isset( $_POST['dem_reset_l10n'] ) )           $up = update_option('democracy_l10n', array() );
		// обновляем основные опции
		if( isset( $_POST['dem_save_main_options'] ) )    $up = $this->update_options('main');
        // сбрасываем основные опции
		if( isset( $_POST['dem_reset_main_options'] ) )   $up = $this->update_options('main_default');
		// обновляем опции дизайна
		if( isset( $_POST['dem_save_design_options'] ) )  $up = $this->update_options('design');
        // сбрасываем опции дизайна
		if( isset( $_POST['dem_reset_design_options'] ) ) $up = $this->update_options('design_default');
        
        if( $up ){
            // костыль, чтобы сразу применялся результат при отключении/включении перевода
            if( ! $this->opt['load_textdomain'] ) unload_textdomain('dem'); else Dem::$inst->load_textdomain();
            // костыль, чтобы сразу применялся результат при отключении/включении тулбара
            $this->opt['toolbar_menu'] ? add_action('admin_bar_menu', array( $this, 'toolbar'), 99) : remove_action('admin_bar_menu', array( $this, 'toolbar'), 99);
        }
        
		// запрос на создание страницы архива
		if( isset( $_GET['dem_create_archive_page'] ) ) $this->dem_create_archive_page();
		// запрос на создание страницы архива
		if( isset( $_GET['dem_clear_log'] ) )       $this->clear_log();
		// Add a poll
		if( isset( $_POST['dmc_create_poll'] ) )    $this->create_poll();
		// Edit a poll
		if( isset( $_POST['dmc_update_poll'] ) )    $this->update_poll( $_POST['dmc_update_poll'] );
		// delete a poll
		if( isset( $_GET['delete_poll'] ) )         $this->delete_poll( $_GET['delete_poll'] );
		// activates a poll
		if( isset( $_GET['dmc_activate_poll'] ) )   $this->poll_activation( $_GET['dmc_activate_poll'], true );
		// deactivates a poll
		if( isset( $_GET['dmc_deactivate_poll'] ) ) $this->poll_activation( $_GET['dmc_deactivate_poll'], false );	
		// close voting a poll
		if( isset( $_GET['dmc_close_poll'] ) )      $this->poll_opening( $_GET['dmc_close_poll'], 0 );
		// open voting a poll
		if( isset( $_GET['dmc_open_poll'] ) )       $this->poll_opening( $_GET['dmc_open_poll'], 1 );		
	}
    
    
    
    
    ### опции плагина
	/**
	 * Обновляет произвольный текст перевода.
	 */
	function update_l10n(){
		$new_l10n = stripslashes_deep( $_POST['l10n'] );
		
		// удалим если нет отличия оторигинального перевода
		foreach( $new_l10n as $k => $v ) if( __( $k ,'dem') == $v ) unset( $new_l10n[ $k ] );
		
		update_option('democracy_l10n', $new_l10n );
	}
	
	/**
	 * Обнолвяет опции. Если опция не передана, то на её место будет записано 0
	 * @param bool $type Какие опции обновлять: default, main_default, design_default, main, design
	 * @return none
	 */
	function update_options( $type ){
		$def_opt = $this->default_options();

        // полный сброс
        if( $type == 'default' ){
            $this->update_options('main_default');
            $this->update_options('design_default');
        }
            
        // сброс основных опций и опций дизайна
        if( $type == 'main_default' || $type == 'design_default' ){
            $_type = str_replace('_default', '', $type );
            foreach( $def_opt[ $_type ] as $k => $value )
                $this->opt[ $k ] = $value;
        }
        
        // обновление опций
		if( $type == 'main' || $type == 'design' ){			
			foreach( $def_opt[ $type ] as $k => $v ){                    
				$value = isset( $_POST['dem'][ $k ] ) ? stripslashes( $_POST['dem'][ $k ] ) : 0; // именно 0/null, а не $v для checkbox
				$this->opt[ $k ] = $value;
			}
		}
        
        // обновление опцию css стилей
        if( $type == 'design' || $type == 'design_default' ){
            $this->update_democracy_css();
        }
		
        $up = update_option( self::OPT_NAME, $this->opt ); 
        
		if( $up ) $this->message[] = __('Обновленно','dem');
        
        return $up;
	}
    
	/**
	 * Получает опции по умолчанию
	 * @return Массив
	 */
	function default_options(){
        $arr = array();
        
		$arr['main'] = array(
			'inline_js_css'    => 0,   // встараивать стили и скрипты в HTML
			'keep_logs'        => 1, // вести лог в БД
			'before_title'     => '<strong class="dem-poll-title">',
			'after_title'      => '</strong>',
			'force_cachegear'  => 0,
			'archive_page_id'  => 0,
			'use_widget'       => 1,
			'toolbar_menu'     => 1,
			'tinymce_button'   => 1,
			'load_textdomain'  => 1,
			'show_copyright'   => 1,
			'only_for_users'   => 0,			
			'democracy_off'    => 0,  // глобальная опция democracy
			'revote_off'       => 0,  // глобальная опция переголосование
			'disable_js'       => 0,   // Дебаг: отключает JS
			'cookie_days'      => 365, // Дебаг
		);
        
        $arr['design'] = array(
			'loader_fname'  => 'css-roller.css3',			
			'css_file_name' => 'alternate.css', // название файла стилей который будет использоваться для опроса.
			'css_button'    => 'flat.css',
			'loader_fill'   => '', // как заполнять шкалу прогресса
			'graph_from_total' => 1,
			'order_answers'    => 1,
            // progress
            'line_bg'         => '',
            'line_fill'       => '',
            'line_height'   => '',
            'line_fill_voted' => '',
            // button
			'btn_bg_color'         => '',
			'btn_color'            => '',
			'btn_border_color'     => '',
			'btn_hov_bg'           => '',
			'btn_hov_color'        => '',
			'btn_hov_border_color' => '',
        );
        
        return $arr;
	}	

    
    
    
    /**
     * Получает существующие полные css файлы из каталога плагина
     * @return Возвращает массив имен (путей) к файлам
     */
    function _get_styles_files(){
        $arr = array();
        
        foreach( glob( Dem::$inst->dir_path . 'styles/*.css' ) as $file ){
//            if( preg_match('~\.min|^_~', basename( $file ) ) ) continue;
            if( preg_match('~\.min~', basename( $file ) ) ) continue;
            
            $arr[] = $file;
        }
        
        return $arr;
    }
    
    
    
    
	### обработка запросов с вязанных с управлением опросами
	/**
	 * Конвертирует дату в UNIX time
	 * @param (str) $date Дата в формате dd-mm-yyyy
	 * @return Исправленную дату
	 */
	function dem_strtotime( $date ){
		if( ! preg_match("~[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}~", $date ) ) return 0;
		
		return (int) strtotime( $date );
	}
	
	
	function delete_poll( $poll_id ){
		global $wpdb;

		if( ! $id = (int) $poll_id ) return;
		
		$wpdb->delete( $wpdb->democracy_q,   array('id'  => $id ) );
		$wpdb->delete( $wpdb->democracy_a,   array('qid' => $id ) );
		$wpdb->delete( $wpdb->democracy_log, array('qid' => $id ) );
		
		$this->message[] = __('Опрос удален','dem');
	}
	
	/**
	 * Закрывает/открывает голосование
	 * @param int $poll_id ID опроса
	 * @param bool $open Что сделать, открыть или закрыть голосование?
	 */
	function poll_opening( $poll_id, $open ){
		global $wpdb;
		if( ! $id = (int) $poll_id ) return;
		
		$open = $open ? 1 : 0;
		
		$new_data = array( 'open' => $open );
		
        // удаляем дату окончания при открытии голосования
		if( $open )
            $new_data['end'] = 0;
        // ставим дату при закрытии опроса и деактивируем опрос
        else{
            $new_data['end'] = current_time('timestamp') - 10;
            $this->poll_activation( $poll_id, false );
        }
		
		if( $wpdb->update( $wpdb->democracy_q, $new_data, array( 'id'=>$id ) ) )
			$this->message[] = $open ? __('Опрос открыт','dem') : __('Опрос закрыт','dem');
	}
	
	/**
	 * Активирует/деактивирует опрос
	 * @param int $poll_id ID опроса
	 * @param bool $activation Что сделать, активировать (true) или деактивировать?
	 */
	function poll_activation( $poll_id, $activation = true ){
		global $wpdb;

		if( ! $id = (int) $poll_id ) return;
		
		$active = (int) $activation;

//		$wpdb->update( $wpdb->democracy_q, array( 'active'=>0 ) ); // удалим все активные, перед тем как поставить
		$done = $wpdb->update( $wpdb->democracy_q, array( 'active'=>$active ), array( 'id'=>$id ) );
		
		if( $done ) $this->message[] = $active ? __('Опрос активирован','dem') : __('Опрос деактивирован','dem');
	}
	
	// поля для таблицы опросов
	function _comon_polls_table_fields(){
		return array(
			'question'   => trim( stripslashes( $_POST['dmc_question'] ) ),
			'end'        => isset( $_POST['dmc_end'] ) ? (int) $this->dem_strtotime( $_POST['dmc_end'] ) : '', // дата окончания
			'democratic' => isset($_POST['dmc_is_democratic']),
			'active'     => isset($_POST['dmc_is_active']),
			'multiple'   => isset($_POST['dmc_multiple']),
			'forusers'   => isset($_POST['dmc_forusers']),
			'revote'     => isset($_POST['dmc_revote']),
			'note'       => trim( stripslashes( $_POST['dmc_note'] ) ),
		);
	}

	function create_poll(){
		global $wpdb;

		// fields
		extract( $this->_comon_polls_table_fields() );
		$added       = current_time('timestamp');
		$open        = 1; // poll is open to be answered by default
		$new_answers = stripslashes_deep( $_POST['dmc_new_answers'] );
		
		if( empty( $question ) || empty( $new_answers ) ) return;
			
		// if( $active ) $wpdb->update( $wpdb->democracy_q, array( 'active'=>0 ) ); // сброс всех других активных опросов
		
		// вставляем
		$fields_array = array('added','open');
		foreach( $this->_comon_polls_table_fields() as $key => $foo ) $fields_array[] = $key;
		$wpdb->insert( $wpdb->democracy_q, compact( $fields_array )	);

		$poll_id = $wpdb->insert_id;
		
		if( ! $poll_id ) return false;
		
		foreach( $new_answers as $answer ){
			$answer = trim( $answer );
			
			if( ! empty( $answer ) )
				$wpdb->insert( $wpdb->democracy_a, array( 'answer' => $answer, 'qid' => $poll_id ) );
		}
		
		wp_redirect( add_query_arg( array('edit_poll'=>$poll_id, 'subpage'=>false, 'message'=>'created') ) );
	}

	function update_poll( $poll_id ){
		global $wpdb;

		if( ! $poll_id = (int) $poll_id ) return;
		
		// fields
		extract( $this->_comon_polls_table_fields() );

		if( empty( $question ) ) return;
		
		// обновляем
		$fields_array = array();
		foreach( $this->_comon_polls_table_fields() as $key => $foo ) $fields_array[] = $key;
		$wpdb->update( $wpdb->democracy_q, compact( $fields_array ), array('id' => $poll_id ) );
		
		// update answers
		$old_answers = stripslashes_deep( @$_POST['dmc_old_answers'] );
		$new_answers = stripslashes_deep( @$_POST['dmc_new_answers'] );
		
		if( empty( $old_answers ) && empty( $new_answers ) ) return;
		
		$ids = array();
		
		// Обновим имеющиеся старые
		foreach( $old_answers as $aid => $data ){			
			$wpdb->update( $wpdb->democracy_a, 
				array('answer' => $data['answer'], 'votes' => $data['votes'] ), 
				array( 'qid' => $poll_id, 'aid' => $aid ) 
			);
			
			// собираем ID для исключения из удаления
			$ids[] = $aid;
		}

		// Удаляем удаленные старые
		if( count( $ids ) > 0 ){
			$ids = array_map('absint', $ids );
			$ids = implode(',', $ids );
			$wpdb->query("DELETE FROM $wpdb->democracy_a WHERE qid=$poll_id AND aid NOT IN ($ids)");
		}
		
		// Добавим новые добавленные
		if( $new_answers ){
			foreach( $new_answers as $answer ){
				$answer = trim( $answer );

				if( ! empty( $answer ) )
					$wpdb->insert( $wpdb->democracy_a, array( 'answer' => $answer, 'qid' => $poll_id ) );
			}
		}
		
		$this->message[] = __('Опрос обновлён','dem');
		
	}
	
    
    
    #### работа с css
    /**
     * Обновляет опцию "democracy_css".
     * @param [in|out] type parameter_name Parameter description.
     * @param [in|out] type parameter_name Parameter description.
     * @return Description of returned value.
     */
    function update_democracy_css(){
        $def_opt = $this->default_options();
        
        $additional = stripslashes( @$_POST['additional_css'] );
        $base  =  $this->collect_base_css(); // если нет то тема отключена
        
        $full_css = $base ? ($this->collect_base_css() . "\n\n\n/*\n---------------------------------------------------------------------\n custom styles\n--------------------------------------------------------------------- \n*/\n" . $additional) : ''; // если полных стилей нет, то не будем записывать в базу опцию full
        
        $opt_value = array(
            'full'           => $full_css,
            'additional_css' => $additional,
            'minify'         => $this->cssmin( $full_css ?: $additional ), // рабочий элемент, в html добавляется только он
        );

        update_option('democracy_css', $opt_value );        
    }
    
    /**
     * Собирает базовые стили.
     * @return css код стилей. Пусто, если шаблон отключен.
     */
    function collect_base_css(){
        $base = $this->opt['css_file_name'];
        
        if( ! $base ) return; // выходим если не указан шаблон, тут будут использвоаться дополнитлеьные стили
        
        $button    = $this->opt['css_button'];
        $loader    = $this->opt['loader_fill'];
        
        $out = '';
        $stylepath  = $this->dir_path . 'styles/';
        
        $out .= $this->parce_cssimport( $stylepath . $base );
        $out .= $button ? "\n".file_get_contents( $stylepath .'buttons/'. $button ) : '';
        if( $loader ){
            $out .= "\n.dem-loader .fill{ fill: $loader !important; }\n";
            $out .= ".dem-loader .css-fill{ background-color: $loader !important; }\n";
            $out .= ".dem-loader .stroke{ stroke: $loader !important; }\n";
        }
        
        // progress line
        $d_bg       = $this->opt['line_bg'];
        $d_fill     = $this->opt['line_fill'];
        $d_height   = $this->opt['line_height'];
        $d_fillThis = $this->opt['line_fill_voted'];
        
        if( $d_bg )       $out .= "\n.dem-graph{ background: $d_bg !important; }\n";
        if( $d_fill )     $out .= "\n.dem-fill{ background-color: $d_fill !important; }\n";
        if( $d_fillThis ) $out .= ".dem-voted-this .dem-fill{ background-color:$d_fillThis !important; }\n";
        if( $d_height )   $out .= ".dem-graph{ height:{$d_height}px; line-height:{$d_height}px; }\n";
        
        if( $button ){
            // button
            $bbackground = $this->opt['btn_bg_color'];
            $bcolor = $this->opt['btn_color'];
            $bbcolor = $this->opt['btn_border_color'];
            // hover
            $bh_bg = $this->opt['btn_hov_bg'];
            $bh_color = $this->opt['btn_hov_color'];
            $bh_bcolor = $this->opt['btn_hov_border_color'];

            if( $bbackground ) $out .= "\n.dem-button{ background-color:$bbackground !important; }\n";
            if( $bcolor )  $out .= ".dem-button{ color:$bcolor !important; }\n";
            if( $bbcolor ) $out .= ".dem-button{ border-color:$bbcolor !important; }\n";

            if( $bh_bg ) $out .= "\n.dem-button:hover{ background-color:$bh_bg !important; }\n";
            if( $bh_color )  $out .= ".dem-button:hover{ color:$bh_color !important; }\n";
            if( $bh_bcolor ) $out .= ".dem-button:hover{ border-color:$bh_bcolor !important; }\n";
        }
                    
        return $out;
    }

    /**
     * Сжимает css YUICompressor
     * @param str $input_css КОД css
     * @return str min css.
     * $minicss = Dem::$inst->cssmin( file_get_contents( Dem::$inst->dir_url . 'styles/' . Dem::$inst->opt['css_file_name'] ) );
     */
    function cssmin( $input_css ){
        require_once $this->dir_path . 'admin/cssmin.php';
        
        $compressor = new CSSmin();

        // Override any PHP configuration options before calling run() (optional)
        // $compressor->set_memory_limit('256M');
        // $compressor->set_max_execution_time(120);

        return $compressor->run( $input_css );
    }
    
    /**
     * Сжимает css YUICompressor
     * @param str $input_css КОД css
     * @return str min css.
     * $minicss = Dem::$inst->cssmin( file_get_contents( Dem::$inst->dir_url . 'styles/' . Dem::$inst->opt['css_file_name'] ) );
     */
    function csstidymin( $input_css ){

        return $compressor->run( $input_css );
    }
    
    /**
     * Импортирует @import в css
     * @param str $css_filepath Путь к вайлу css который нужно оработать.
     * @return Возвращает полный код с импортируемыми данными
     */
    function parce_cssimport( $css_filepath ){
        $filecode = file_get_contents( $css_filepath );
        
        $filecode = preg_replace_callback('~@import [\'"](.*?)[\'"];~', function( $m ) use ( $css_filepath ){
            return file_get_contents( dirname( $css_filepath ) . '/' . $m[1] );
        }, $filecode );

        return $filecode;
    }
    

    
    
    
    ## others
	// tinymce кнопка
	function tinymce_button(){	
		add_filter('mce_external_plugins', array($this, 'tinymce_plugin') ) ;
		add_filter('mce_buttons',          array($this, 'tinymce_register_button') );
		add_filter('wp_mce_translation',   array($this, 'tinymce_l10n') );
	}
	function tinymce_register_button( $buttons ) {
		array_push( $buttons, 'separator', 'demTiny');
		return $buttons;
	}
	function tinymce_plugin( $plugin_array ) {
		$plugin_array['demTiny'] = $this->dir_url .'admin/tinymce.js';
		return $plugin_array;
	}
	function tinymce_l10n( $mce_l10n ) {
		$l10n = array(
			'Вставка Опроса Democracy' => __('Вставка Опроса Democracy', 'dem'),
			'Введите ID опроса' => __('Введите ID опроса', 'dem'),
			'Ошибка: ID - это число. Введите ID еще раз' => __('Ошибка: ID - это число. Введите ID еще раз', 'dem'),
		);
		$l10n = array_map('esc_js', $l10n );

		return $mce_l10n + $l10n;
	}
	
    
    /**
	 * Создает страницу архива. Сохраняет УРЛ созданой страницы в опции плагина. Перед созданием проверят нет ли уже такой страницы.
	 * Возвращает УРЛ созданной страницы или false
	 */
	function dem_create_archive_page(){
		global $wpdb;

		// Пробуем найти страницу с архивом
		if( $page = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_content LIKE '[democracy_archives]' AND post_status = 'publish' LIMIT 1") ){
			$page_id = $page->ID;
		}
		// Создаем новую страницу
		else {
			$page_id = wp_insert_post( array(
				'post_title'   => __('Архив опросов','dem'),
				'post_content' => '[democracy_archives]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_name'    => 'democracy-archives',
			) );

			if( ! $page_id ) return false;
		}

		// обновляем опцию плагина
		Dem::$inst->opt['archive_page_id'] = $page_id;
		update_option( Dem::OPT_NAME, Dem::$inst->opt );

		wp_redirect( remove_query_arg('dem_create_archive_page') );
	}
    
	// Очищает таблицу логов
	function clear_log(){
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE $wpdb->democracy_log");
		wp_redirect( remove_query_arg('dem_clear_log') );
		exit;
	}
	
}
