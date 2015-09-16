<?php

if( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

global $wpdb, $table_prefix;
$wpdb->query("DROP TABLE {$table_prefix}democracy_q, {$table_prefix}democracy_a, {$table_prefix}democracy_log");

delete_option('widget_democracy'); // встроенная
delete_option('democracy_options');
delete_option('democracy_version');
delete_option('democracy_css');
delete_option('democracy_l10n');

delete_transient('democracy_referer', '', 2 );


