<?php

__('Позволяет удобно создавать демократические опросы. Пользователи могут голосовать за несколько вариантов ответа или добавлять свои собственные ответы.', 'dem');

/*
Plugin Name: Democracy Poll
Description: Позволяет удобно создавать демократические опросы. Пользователи могут голосовать за несколько вариантов ответа или добавлять свои собственные ответы.
Author: Kama
Author URI: http://wp-kama.ru/
Plugin URI: http://wp-kama.ru/id_67/plagin-oprosa-dlya-wordpress-democracy-poll.html
Text Domain: dem
Domain Path: lang
Version: 4.9.4
*/

if( defined('WP_INSTALLING') && WP_INSTALLING ) return;

$data = get_file_data( __FILE__, array('ver'=>'Version', 'lang_dir'=>'Domain Path') );

define('DEM_VER', $data['ver'] );
define('DEM_LANG_DIRNAME', $data['lang_dir'] );
define('DEMOC_URL',  plugin_dir_url(__FILE__) );
define('DEMOC_PATH', plugin_dir_path(__FILE__) );

if( is_admin() && ! require DEMOC_PATH . 'admin/is_php53.php' ) return;

// таблицы
global $wpdb;
$wpdb->democracy_q   = $wpdb->prefix . 'democracy_q';
$wpdb->democracy_a   = $wpdb->prefix . 'democracy_a';
$wpdb->democracy_log = $wpdb->prefix . 'democracy_log';


require_once DEMOC_PATH . 'class.DemInit.php';
require_once DEMOC_PATH . 'admin/class.DemAdminInit.php';
require_once DEMOC_PATH . 'class.DemPoll.php';

register_activation_hook( __FILE__, 'democracy_activate' );


## Активируем плагин. активируем виджет, если включен
add_action('plugins_loaded', function(){
	Dem::init();
	if( Dem::$opt['use_widget'] )
		require_once DEMOC_PATH . 'widget_democracy.php';
} );


## ФУНКЦИИ -------------------------------------------------------------------------------------------- 

## Функция локализации внешней части
function __dem( $str ){	
	static $cache;
	if( $cache === null )
		$cache = get_option('democracy_l10n');

	return isset( $cache[ $str ] ) ? $cache[ $str ] : __( $str, 'dem');
}


#### ФУНКЦИИ ОБЕРТКИ ------------------------------------------------------------
/**
 * Для вывода отдельного опроса
 * @param int $id ID опроса
 * @return HTML
 */
function democracy_poll( $id = 0, $before_title = '', $after_title = ''){
	echo get_democracy_poll( $id, $before_title, $after_title );
}
function get_democracy_poll( $id = 0, $before_title = '', $after_title = '' ){
	$poll = new DemPoll( $id );
	
	$show_results = __query_poll_screen_choose( $poll );
		
	return $poll->get_screen( $show_results, $before_title, $after_title );
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
	$ids = $wpdb->get_col("SELECT id FROM $wpdb->democracy_q $WHERE ORDER BY active DESC, open DESC, id DESC");

	$output = '<div class="dem-archives">';
	foreach( $ids as $poll_id ){
		$poll = new DemPoll( $poll_id );
		
		$show_results = isset( $_REQUEST['dem_act'] ) ? __query_poll_screen_choose( $poll ) : 'voted';
		
		$output .= $poll->get_screen( $show_results, $before_title, $after_title );
	}
	$output .= "</div>";
	
	return $output;
}

// Установка какой экран показать, на основе переданных запросов.
function __query_poll_screen_choose( $poll ){
	return ( @ $_REQUEST['dem_act'] == 'view' && @ $_REQUEST['dem_pid'] == $poll->id ) ? 'voted' : 'vote'; 
}
#### / ФУНКЦИИ ОБЕРТКИ  ------------------------------------------------------------


## Добалвяет таблицы в БД и инициализирует себя
function democracy_activate(){
	global $wpdb;
	
	Dem::init()->load_textdomain();
	
	// create tables
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	
	$collate = (!empty($wpdb->charset) && !empty($wpdb->collate)) ? 
		"DEFAULT CHARSET=$wpdb->charset COLLATE $wpdb->collate" : 
		"DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci";

	if( ! $wpdb->get_var("SHOW TABLES LIKE '$wpdb->democracy_q'") ){
		dbDelta("
		CREATE TABLE $wpdb->democracy_q (
			id         int(10)    UNSIGNED NOT NULL auto_increment,
			question   text                NOT NULL default '',
			added      int(10)    UNSIGNED NOT NULL default 0,
			added_user bigint(20) UNSIGNED NOT NULL default 0,
			end        int(10)    UNSIGNED NOT NULL default 0,
			democratic tinyint(1) UNSIGNED NOT NULL default 0,
			active     tinyint(1) UNSIGNED NOT NULL default 0,
			open       tinyint(1) UNSIGNED NOT NULL default 0,
			multiple   tinyint(5) UNSIGNED NOT NULL default 0,
			forusers   tinyint(1) UNSIGNED NOT NULL default 0,
			revote     tinyint(1) UNSIGNED NOT NULL default 0,
			show_results tinyint(1) UNSIGNED NOT NULL default 0,
			note       text                NOT NULL default '',

			PRIMARY KEY (id),
			KEY active (active)
		) $collate; 
		");
	}
	
	if( ! $wpdb->get_var("SHOW TABLES LIKE '$wpdb->democracy_a'") ){
		dbDelta("
		CREATE TABLE $wpdb->democracy_a (
			aid      int(10)    UNSIGNED NOT NULL auto_increment,
			qid      int(10)    UNSIGNED NOT NULL default 0,
			answer   text                NOT NULL default '',
			votes    int(10)    UNSIGNED NOT NULL default 0,
			added_by varchar(100)        NOT NULL default '',

			PRIMARY KEY (aid),
			KEY qid (qid)
		) $collate;
		");
	}
	
	if( ! $wpdb->get_var("SHOW TABLES LIKE '$wpdb->democracy_log'") ){
		dbDelta("
		CREATE TABLE $wpdb->democracy_log (
			ip       int(11)    UNSIGNED NOT NULL default 0,
			qid      int(10)    UNSIGNED NOT NULL default 0,
			aids     text                NOT NULL default '',
			userid   bigint(20) UNSIGNED NOT NULL default 0,
			date     DATETIME            NOT NULL default '0000-00-00 00:00:00',
			expire   bigint(20) UNSIGNED NOT NULL default 0,

			KEY ip  (ip,qid),
			KEY qid (qid),
			KEY userid (userid)
		) $collate;
		");
	}

    // Poll example
	if( ! $wpdb->get_row("SELECT * FROM $wpdb->democracy_q LIMIT 1") ){
		$wpdb->insert( $wpdb->democracy_q, array( 
			'question'   => __('Что для вас деньги?','dem'), 
			'added'      => current_time('timestamp'),
			'added_user' => get_current_user_id(),
			'democratic' => 1,
			'active'     => 1,
			'open'       => 1,
			'revote'     => 1,
		) );

		$qid = $wpdb->insert_id;

		$answers = array( 
			__('Деньги - это универсальный продукт для обмена.','dem'),
			__('Деньги - это бумага... Не в деньгах счастье...','dem'),
			__('Средство достижения цели.','dem'),
			__('Кусочки дьявола :)','dem'),
			__('Это власть, - это "Сила", - это счастье...','dem'),
		);

		// create votes
		foreach( $answers as $answr )
			$wpdb->insert( $wpdb->democracy_a, array( 'votes'=> rand(0,100), 'qid' => $qid, 'answer' => $answr ) );
	}
	
	// add options, if needed
	if( ! get_option( Dem::OPT_NAME ) )
    	Dem::init()->update_options('default');
	
	// upgrade
	dem_last_version_up();
}



## Plugin Update
## Нужно вызывать на странице настроек плагина, чтобы не грузить лишний раз сервер.
function dem_last_version_up(){	
	$dem_ver = get_option('democracy_version');
	
	if( $dem_ver == DEM_VER ) return;

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
	$fields   = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->democracy_q");
	$fields_q = wp_list_pluck( $fields, 'Field' );
	
	$fields   = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->democracy_a");
	$fields_a = wp_list_pluck( $fields, 'Field' );
	
	$fields     = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->democracy_log");
	$fields_log = wp_list_pluck( $fields, 'Field' );
	
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
    
    // 4.5.6
	if( ! in_array('expire', $fields_log ) )
		$wpdb->query("ALTER TABLE $wpdb->democracy_log ADD `expire` bigint(20) UNSIGNED NOT NULL default 0 AFTER `userid`;");
	
	// 4.7.5
	// конвертируем в кодировку utf8mb4
	if( $wpdb->charset === 'utf8mb4' ){
		foreach( array( $wpdb->democracy_q, $wpdb->democracy_a, $wpdb->democracy_log ) as $table ){
			$alter = false;
			if( ! $results = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table`" ) )
				continue;

			foreach( $results as $column ){
				if ( ! $column->Collation ) continue;
				
				list( $charset ) = explode( '_', $column->Collation );

				if( strtolower( $charset ) != 'utf8mb4' ){
					$alter = true;
					break;
				}
			}
			
			if( $alter )
				$wpdb->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		}
		
	}
	
	// 4.9
	if( ! in_array('date', $fields_log ) )
		$wpdb->query("ALTER TABLE `$wpdb->democracy_log` ADD `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `userid`;");
	
	// 4.9.3
	if( version_compare( $dem_ver, '4.9.3', '<') ){
		$wpdb->query("ALTER TABLE `$wpdb->democracy_log` CHANGE `date` `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';");
		
		$wpdb->query("ALTER TABLE `$wpdb->democracy_q` CHANGE `multiple` `multiple` tinyint(5) UNSIGNED NOT NULL DEFAULT 0;");
		
		$wpdb->query("ALTER TABLE `$wpdb->democracy_a` CHANGE `added_by` `added_by` varchar(100) NOT NULL default '';");
		$wpdb->query("UPDATE `$wpdb->democracy_a` SET added_by = '' WHERE added_by = '0'");
	}
	if( ! in_array('added_user', $fields_q ) )
		$wpdb->query("ALTER TABLE `$wpdb->democracy_q` ADD `added_user` bigint(20) UNSIGNED NOT NULL DEFAULT 0 AFTER `added`;");
	if( ! in_array('show_results', $fields_q ) )
		$wpdb->query("ALTER TABLE `$wpdb->democracy_q` ADD `show_results` tinyint(1) UNSIGNED NOT NULL default 1 AFTER `revote`;");
	
	
	// обновим css
	Dem::init()->regenerate_democracy_css();
	
	update_option('democracy_version', DEM_VER );
	
}
