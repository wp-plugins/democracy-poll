<?php
/*
Plugin Name: Democracy Poll
Description: Позволяет удобно создавать демократические опросы. Пользователи могут голосовать за несколько вариантов ответа или добавлять свои собственные ответы.
Author: Kama
Author URI: http://wp-kama.ru/
Plugin URI: http://wp-kama.ru/id_67/plagin-oprosa-dlya-wordpress-democracy-poll.html
Text Domain: dem
Domain Path: languages
Version: 4.5.4
*/

define('DEM_VER', '4.5.4');

// Перевод заголовка
__('Позволяет удобно создавать демократические опросы. Пользователи могут голосовать за несколько вариантов ответа или добавлять свои собственные ответы.');


require dirname(__FILE__) . '/class.DemInit.php';
require dirname(__FILE__) . '/class.DemPoll.php';
Dem::init();




### активируем виджет, если включен
if( Dem::$inst->opt['use_widget'] ) require 'widget_democracy.php';






###### функции обертки ######
/**
 * Для вывода отдельного опроса
 * @param int $id ID опроса
 * @return HTML
 */
function democracy_poll( $id = 0, $before_title = '', $after_title = ''){
	echo get_democracy_poll( $id, $before_title, $after_title );
}
function get_democracy_poll( $id = 0, $before_title = '', $after_title = '' ){
//	die($before_title . $after_title);
	$poll = new DemPoll( $id );
	
	$show_results = __query_poll_screen_choose( $poll );
		
	return $poll->display( $show_results, $before_title, $after_title );
}

/**
 * Для вывода архивов
 * @param bool $hide_active Не показывать активные опросы?
 * @return HTML
 */
function democracy_archives( $hide_active = false, $before_title = '', $after_title = '' ){
	echo get_democracy_archives( $hide_active, $before_title, $after_title );
}
function get_democracy_archives( $hide_active = false, $before_title = '', $after_title = '' ){
	global $wpdb;
		
	$WHERE = $hide_active ? 'WHERE active = 0' : '';
	$ids = $wpdb->get_col("SELECT id FROM $wpdb->democracy_q $WHERE ORDER BY active DESC, id DESC");

	$output = '<div class="dem-archives">';
	foreach( $ids as $poll_id ){
		$poll = new DemPoll( $poll_id );
		$poll->opt['archive_page_url'] = ''; // убираем ссылку на архив
		
		$show_results = isset( $_REQUEST['dem_act'] ) ? __query_poll_screen_choose( $poll ) : true;
		
		$output .= $poll->display( $show_results, $before_title, $after_title );
	}
	$output .= "</div>";
	
	return $output;
}

// Установка какой экран показать, на основе переданных запросов.
function __query_poll_screen_choose( $poll ){
	return ( @$_REQUEST['dem_act'] == 'view' && @$_REQUEST['dem_pid'] == $poll->id ) ? true : false;
}







		
#### Активаниция плагина
register_activation_hook( __FILE__, 'democracy_activate' );

// Добалвяет таблицы в БД и инициализирует себя
function democracy_activate(){
	global $wpdb;
			
	Dem::init()->load_textdomain();
	
	// Устанавливаем передт тем, как создать таблицы
	$first_time = ! $wpdb->get_var("SHOW TABLES LIKE '$wpdb->democracy_q'");

	$charset_collate = (!empty($wpdb->charset) && !empty($wpdb->collate)) ? "DEFAULT CHARSET=$wpdb->charset COLLATE $wpdb->collate" : "DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

	$query = "
	CREATE TABLE $wpdb->democracy_q (
		id         int(10)    UNSIGNED NOT NULL auto_increment,
		question   text                NOT NULL,
		added      int(10)    UNSIGNED NOT NULL default 0,
		democratic tinyint(1) UNSIGNED NOT NULL default 0,
		active     tinyint(1) UNSIGNED NOT NULL default 0,
		open       tinyint(1) UNSIGNED NOT NULL default 0,
		multiple   tinyint(1) UNSIGNED NOT NULL default 0,
		forusers   tinyint(1) UNSIGNED NOT NULL default 0,
		revote     tinyint(1) UNSIGNED NOT NULL default 0,
		note       text                NOT NULL,
		PRIMARY KEY (id),
		KEY active (active)
	) $charset_collate;

	CREATE TABLE $wpdb->democracy_a (
		aid      int(10)    UNSIGNED NOT NULL auto_increment,
		qid      int(10)    UNSIGNED NOT NULL default 0,
		answer   text                  NOT NULL,
		votes    int(10)    UNSIGNED NOT NULL default 0,
		added_by tinyint(1) UNSIGNED NOT NULL default 0,
		PRIMARY KEY (aid),
		KEY qid (qid)
	) $charset_collate;

	CREATE TABLE $wpdb->democracy_log (
		ip     int(11)    UNSIGNED NOT NULL default 0,
		qid    int(10)    UNSIGNED NOT NULL default 0,
		aids   text                NOT NULL,
		userid bigint(20) UNSIGNED NOT NULL default 0,
		KEY ip  (ip,qid),
		KEY qid (qid),
		KEY userid (userid)
	) $charset_collate;
	";

		
	// если это первая установка
	if( $first_time ){
		// создаем таблицы
		require_once ABSPATH . 'wp-admin/upgrade-functions.php';
		dbDelta( $query );
		
		// Создадим пример опроса
		$wpdb->insert( $wpdb->democracy_q, array( 
			'question'   => __('Что для вас деньги?','dem'), 
			'added'      => current_time('timestamp'),
			'democratic' => 1, 'active' => 1, 'open' => 1, 'revote' => 1, 
		) );
		
		$qid = $wpdb->insert_id;
		
		$answers = array( 
			__('Деньги - это универсальный продукт для обмена.','dem'),
			__('Деньги - это бумага... Не в деньгах счастье...','dem'),
			__('Средство достижения цели.','dem'),
			__('Кусочки дьявола :)','dem'),
			__('Это власть, - это "Сила", - это счастье...','dem'),
		);
		foreach( $answers as $answr ){
			$wpdb->insert( $wpdb->democracy_a, array( 'qid' => $qid, 'answer' => $answr ) );
		}
		
		Dem::init()->update_options( true );
	}

}






/**
 * Проверяет необходимость обновления.
 * Нужно вызывать на странице настроек плагина, чтобы не грузить лишний раз сервер.
 */
function dem_last_version_up(){
	$ver_opt_name = 'democracy_version';

	if( get_option( $ver_opt_name ) == DEM_VER ) return;
	
	global $wpdb, $table_prefix;
	### 
	### переименование таблиц
	// version 2+
	if( $wpdb->get_results("SHOW TABLES LIKE '{$table_prefix}democracyQ'") ){
		$wpdb->query("ALTER TABLE {$table_prefix}democracyQ RENAME $wpdb->democracy_q");
		$wpdb->query("ALTER TABLE {$table_prefix}democracyA RENAME $wpdb->democracy_a");
		$wpdb->query("ALTER TABLE {$table_prefix}democracyIP RENAME $wpdb->democracy_log");
	}
	
	// 4.0 (раньше была таблица democracy_ip )
	if( $wpdb->get_results("SHOW TABLES LIKE '{$table_prefix}democracy_ip'") )
		$wpdb->query("ALTER TABLE {$table_prefix}democracy_ip RENAME $wpdb->democracy_log");
	
	### 
	### изменение данных таблиц
	$fields_q = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->democracy_q");
	$fields_q = wp_list_pluck( $fields_q, 'Field' );
	$fields_log = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->democracy_log");
	$fields_log = wp_list_pluck( $fields_log, 'Field' );
	
	// 3.1.3
	if( ! in_array('end', $fields_q ) )
		$wpdb->query("ALTER TABLE $wpdb->democracy_q ADD `end` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `added`;");
	
	if( ! in_array('note', $fields_q ) )
		$wpdb->query("ALTER TABLE $wpdb->democracy_q ADD `note` text NOT NULL;");
	
	if( in_array('current', $fields_q ) ){
		$wpdb->query("ALTER TABLE $wpdb->democracy_q CHANGE `current` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;");
		$wpdb->query("ALTER TABLE $wpdb->democracy_q CHANGE `active` `open`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;");
	}
	
	// 4.1
	if( ! in_array('aids', $fields_log ) ){
		// если нет поля aids, создаем 2 поля и индексы
		$wpdb->query("ALTER TABLE $wpdb->democracy_log ADD `aids`   text NOT NULL;");
		$wpdb->query("ALTER TABLE $wpdb->democracy_log ADD `userid` bigint(20) UNSIGNED NOT NULL DEFAULT 0;");
		$wpdb->query("ALTER TABLE $wpdb->democracy_log ADD KEY userid (userid)");
		$wpdb->query("ALTER TABLE $wpdb->democracy_log ADD KEY qid (qid)");
	}
	
	// 4.2
	if( in_array('allowusers', $fields_q ) )
		$wpdb->query("ALTER TABLE $wpdb->democracy_q CHANGE `allowusers` `democratic` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';");
	
	if( ! in_array('forusers', $fields_q ) ){
		$wpdb->query("ALTER TABLE $wpdb->democracy_q ADD `forusers` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `multiple`;");
		$wpdb->query("ALTER TABLE $wpdb->democracy_q ADD `revote`   TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `multiple`;");
	}
	
	update_option( $ver_opt_name, DEM_VER );
	
}
