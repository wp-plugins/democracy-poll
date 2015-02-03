<?php

/**
 * Класс инициализирующий плагин и включающий все необходимые функции для админ-панели и фронт энда
 * влкючает в себя основную работу плагина WordPress
 */

class Dem {
	public $dir_path;
	public $dir_url;
	public $ajax_url;
	public $css_dirname = 'styles/';
	
	public $message = array();
	
	public $opt;
	
	const OPT_NAME = 'democracy_options';
	
	static $inst;
	
	static function init(){
		is_null( self::$inst ) && self::$inst = new self;
		return self::$inst;
	}
	
	function __construct(){
		// регистрируем таблицы
		global $wpdb;
		$wpdb->democracy_q   = $wpdb->prefix . 'democracy_q';
		$wpdb->democracy_a   = $wpdb->prefix . 'democracy_a';
		$wpdb->democracy_log = $wpdb->prefix . 'democracy_log';
		
		$this->opt      = $this->get_options();
		$this->dir_path = plugin_dir_path(__FILE__);
		$this->dir_url  = plugin_dir_url(__FILE__);
		$this->ajax_url = admin_url('admin-ajax.php'); //$this->dir_url . 'ajax_request.php';
		
		# admin part
		if( is_admin() && ! defined('DOING_AJAX') ){ add_action('init', array( $this, 'admin_init')); }
		# front-end
		else                                       { add_action('init', array( $this, 'frontend_init')); }
		
		// меню в панели инструментов
		if( $this->opt['toolbar_menu'] )
			add_action('admin_bar_menu', array( $this, 'toolbar'), 99);

		// файл перевода
		if( $this->opt['load_textdomain'] )
			add_action('plugins_loaded', array($this, 'load_textdomain') );
		
	}
			
	## подключаем файл перевода
	function load_textdomain(){
		load_textdomain('dem', Dem::$inst->dir_path . 'languages/' . get_locale() . '.mo' );
	}
	
	## Добавляет пункты меню в панель инструментов
	function toolbar( $toolbar ) {
		$toolbar->add_node( array(
			'id'    => 'dem_settings',
			'title' => __('Democracy','dem'), 
			'href'  => $this->admin_page_url() . '&subpage=general_settings',
		) );
		$toolbar->add_node( array(
			'parent' => 'dem_settings', 
			'id'     => 'dem_add_poll',
			'title'  => __('Добавить опрос','dem'),
			'href'   => $this->admin_page_url() . '&subpage=add_new', 
		) );
		$toolbar->add_node( array(
			'parent' => 'dem_settings', 
			'id'     => 'dem_main_page',
			'title'  => __('Список опросов','dem'),
			'href'   => $this->admin_page_url(), 
		) );
		$toolbar->add_node( array(
			'parent' => 'dem_settings', 
			'id'     => 'dem_settings2',
			'title'  => __('Настройки','dem'),
			'href'   => $this->admin_page_url() . '&subpage=general_settings',
		) );
	}
	
	/**
	 * Получает все настройки и устанавливает глобальную переменную настроек $dem_options
	 * @return Массив
	 */
	function get_options(){
		if( empty( $this->opt ) ) $this->opt = get_option( self::OPT_NAME );
		if( empty( $this->opt ) ) $this->update_options( true );

		return $this->opt;
	}
	
	/**
	 * Обнолвяет опции. Если опция не передана, то на её место будет записано 0
	 * @param bool $default устанавливать опции по умолчанию?
	 * @return none
	 */
	function update_options( $default = false ){
		$def_options = $this->default_options();

		if( $default ){
			$this->opt = $def_options;
		} 
		else {			
			foreach( $def_options as $k => $v ){				
				$value = isset( $_POST['dem'][ $k ] ) ? stripslashes( $_POST['dem'][ $k ] ) : 0; // именно 0/null, а не $v
				$this->opt[ $k ] = $value;
			}
		}
		
		if( update_option( self::OPT_NAME, $this->opt ) ) $this->message[] = __('Обновленно','dem');
	}
	
	/**
	 * Получает опции по умолчанию
	 * @return Массив
	 */
	function default_options(){
		return array(
			'disable_js'       => 0, // Дебаг: отключает JS
			'inline_js_css'    => 0, // встараивать стили и скрипты в HTML
			'css_file_name'    => 'default.css', // название файла стилей который будет использоваться для опроса.
			'logIPs'           => 1, // вести лог в БД
			'graph_from_total' => 0,
			'cookie_days'      => 365,
			'order_answers'    => 1,
			'before_title'     => '<strong class="dem-poll-title">',
			'after_title'      => '</strong>',
			'archive_page_id'  => 0,
			'use_widget'       => 1,
			'toolbar_menu'     => 1,
			'tinymce_button'   => 1,
			'load_textdomain'  => 1,
			'show_copyright'   => 1,
			'only_for_users'   => 0,			
		);
	}
	
	
	
	
	
	
	
	
	### FRONT END
	function frontend_init(){		
		# шоткод [democracy]
		add_shortcode('democracy',          array($this, 'poll_shortcode'));
		add_shortcode('democracy_archives', array($this, 'archives_shortcode'));
		
		# метатег noindex для AJAX ссылок
		if( isset( $_GET['dem_pid'] ) || isset( $_GET['show_addanswerfield'] ) || isset( $_GET['dem_act'] ) )
			add_filter('wp_head', function(){ echo '<meta name="robots" content="noindex,nofollow" />'; } );
		
		if( ! $this->opt['inline_js_css'] ) $this->add_css(); // подключаем стили если инлайн то подключаем их непосредственно перед опросом

		# для работы функции без AJAX
		if( @$_POST['action'] != 'dem_ajax' ) $this->not_ajax_request_handler();
		
		# ajax request во frontend_init нельзя, потому что срабатывает только как is_admin()
		add_action('wp_ajax_dem_ajax',        array( $this, 'ajax_request_handler') );
		add_action('wp_ajax_nopriv_dem_ajax', array( $this, 'ajax_request_handler') );
	}
	
	## Делает предваритеьную проверку передавемых переменных запроса
	function __sanitize_request_vars(){		
		// $_POST сильнее
		$act  = @$_POST['dem_act'] ?: @$_GET['dem_act'];
		$pid  = @$_POST['dem_pid'] ?: @$_GET['dem_pid'];
		
		return array(
			'act'  => $act ? $act : false,
			'pid'  => $pid ? absint( $pid ) : false,
			'aids' => isset($_POST['answer_ids']) ? wp_unslash($_POST['answer_ids']) : false,
		);
	}
	
	## обрабатывает запрос AJAX
	function ajax_request_handler(){
		extract( $this->__sanitize_request_vars() );
		
		if( ! $act )  wp_die('error: no parameters have been sent or it is unavailable');
		if( ! $pid )  wp_die('error: id unknown');

		// Вывод
		header('Content-Type: text/html; charset=UTF-8');
		
		$poll = new DemPoll( $pid );
		
		// switch
		// голосуем и выводим результаты
		if( $act == 'vote' && $aids ){
			$poll->addVote( $aids );
			echo $poll->getResultScreen();
		}
		// удаляем результаты
		elseif( $act == 'delVoted' ){
			$poll->unsetVotedData();
			echo $poll->getVoteScreen();
		}
		elseif( $act == 'view' ){ // смотрим результаты
			echo $poll->getResultScreen();
		}
		elseif( $act == 'vote_screen' ){ // вернуться к голосованию
			echo $poll->getVoteScreen();
		}

		wp_die();
	}
			
	## для работы функции без AJAX
	function not_ajax_request_handler(){
		extract( $this->__sanitize_request_vars() );
		
		if( ! $act || ! $pid ) return;

		$poll = new DemPoll( $pid );
		
		// проверяем
		if( $act == 'vote' && $aids ){
			$poll->addVote( $aids );
		}
		elseif( $act == 'delVoted' && isset( $_SERVER['HTTP_REFERER'] ) ){ // если это не AJAX запрос, возвращаем пользователя обратно
			$poll->unsetVotedData();
			wp_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
	}
	
	# шоткод архива опросов
	function archives_shortcode(){
		return get_democracy_archives();
	}
	## шоткод опроса
	function poll_shortcode( $atts ){		
		return get_democracy_poll( @$atts['id'] );
	}
	
	## добавляет стили в WP head
	function add_css(){
		static $once; if( $once ) return; $once=1; // выполняем один раз!
		
		$css_name = $this->opt['css_file_name'];
		
		if( ! $css_name ) return;
					
		// пробуем подключить сжатые версии файлов		
		$css_name = rtrim( $css_name, '.css');
		$css      = $this->css_dirname . $css_name;
		$cssurl   = $this->dir_url  . "$css.min.css";
		$csspath  = $this->dir_path . "$css.min.css";
		
		if( ! file_exists( $csspath ) ){
			$cssurl   = $this->dir_url  . "$css.css";
			$csspath  = $this->dir_path . "$css.css";
		}

		// inline HTML
		if( $this->opt['inline_js_css'] ){
			return '<!--democracy-->'. "\n" .'<style type="text/css" media="screen">'. file_get_contents( $csspath ) .'</style>'."\n";
		}	
		else{
			add_action('wp_enqueue_scripts', function() use ($cssurl){ wp_enqueue_style('democracy', $cssurl, array(), DEM_VER ); } );
		}
	}
	
	## добавляет скрипты в подвал
	function add_js(){
		static $once; if( $once ) return; $once=1; // выполняем один раз!
				
		// пробуем подключить сжатые версии файлов
		$js       = 'js/democracy';
		$jsurl    = $this->dir_url  . "$js.min.js";
		$jspath   = $this->dir_path . "$js.min.js";
		if( ! file_exists( $jspath ) ){
			$jsurl   = $this->dir_url  . "$js.js";
			$jspath  = $this->dir_path . "$js.js";
		}

		// inline HTML
		if( $this->opt['inline_js_css'] ){
			wp_enqueue_script('jquery');			
			add_action('wp_footer', function() use($jspath){ echo '<!--democracy-->'. "\n" .'<script type="text/javascript">'. file_get_contents( $jspath ) .'</script>'."\n"; }, 999);
		}
		else
			wp_enqueue_script('democracy', $jsurl, array('jquery'), DEM_VER, true );
	}
	
	
	
	
	
	
	
	
	
	
	### ADMIN PART
	function admin_init(){		
		// add the management page to the admin nav bar
		add_action('admin_menu', array( $this, 'register_option_page') );
		
		// ссылка на настойки
		add_filter('plugin_action_links', array( $this, 'setting_page_link'), 10, 2 );
		
		// TinyMCE кнопка WP2.5+
		if( $this->opt['tinymce_button'] ) $this->tinymce_button();
		
		
		// обновляем опции
		if( isset( $_POST['dem_save_options'] ) )  $this->update_options();
		if( isset( $_POST['dem_reset_options'] ) ) $this->update_options( true );
	}
	
	// Страница плагина
	function register_option_page(){
		$hook_name = add_options_page(__('Опрос Democracy','dem'), __('Опрос Democracy','dem'), 'manage_options', basename( $this->dir_path ), array( $this, 'admin_page_output') );
		add_action("load-$hook_name", array( $this, 'admin_page_load') );
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
		
		// другие
		wp_enqueue_script('democracy-scripts', $this->dir_url . 'admin/admin.js', array('jquery'), null, true );
		wp_enqueue_style('democracy-styles', $this->dir_url . 'admin/style.css' );

		## Обработка запросов
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
	
	// Очищает таблицу логов
	function clear_log(){
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE $wpdb->democracy_log");
		wp_redirect( remove_query_arg('dem_clear_log') );
		exit;
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
	
	/**
	 * Возвращает УРЛ на главную страницу настроек плагина
	 * @return Строку
	 */
	function admin_page_url(){
		static $url; if( ! $url ) $url = admin_url('options-general.php?page='. basename( $this->dir_path ) );
		return $url;
	}
	
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
		
		$wpdb->delete( $wpdb->democracy_q,   array( 'id'  => $id ) );
		$wpdb->delete( $wpdb->democracy_a,   array( 'qid' => $id ) );
		$wpdb->delete( $wpdb->democracy_log, array( 'qid'  => $id ) );
		
		$this->message[] = __('Опрос удален','dem');
	}
	
	/**
	 * Запрещает/разрешает голосование
	 * @param int $poll_id ID опроса
	 * @param bool $open Что сделать, открыть или закрыть голосование?
	 */
	function poll_opening( $poll_id, $open ){
		global $wpdb;
		if( ! $id = (int) $poll_id ) return;
		
		$open = $open ? 1 : 0;
		
		$new_data = array( 'open' => $open );
		
		if( $open ) $new_data['end'] = 0; // удаляем дату окончания при открытии голосования
		
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
		
}

//Dem::init();
