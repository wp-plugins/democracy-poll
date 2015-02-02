<?php 
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) exit;

global $wpdb, $table_prefix;
$wpdb->query("DROP TABLE {$table_prefix}democracy_q, {$table_prefix}democracy_a, {$table_prefix}democracy_log");

// проверка пройдена успешно. Начиная от сюда удаляем опции и все сотальное
delete_option('widget_democracy');
delete_option('democracy_options');
delete_option('democracy_version');

delete_transient('democracy_referer', '', 2 );


