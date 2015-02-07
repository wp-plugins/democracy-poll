<?php

### ADMIN PART
class DemAdminInit extends Dem{
        
	function __construct(){
        parent::__construct();
        
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
