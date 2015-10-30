<?php

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
	
	// 5.0.4
	if( version_compare( $dem_ver, '5.0.4', '<') ){
		$wpdb->query("ALTER TABLE $wpdb->democracy_log CHANGE `ip` `ip` BIGINT(11) UNSIGNED NOT NULL DEFAULT '0';");
		$wpdb->query("ALTER TABLE $wpdb->democracy_log CHANGE `qid` `qid` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0';");
		
		$wpdb->query("ALTER TABLE `$wpdb->democracy_a` CHANGE `aid` `aid` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT;");
		$wpdb->query("ALTER TABLE `$wpdb->democracy_a` CHANGE `qid` `qid` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0';");
		
		$wpdb->query("ALTER TABLE `$wpdb->democracy_q` CHANGE `id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT;");
	}
	
	// обновим css
	Dem::init()->regenerate_democracy_css();
	
	update_option('democracy_version', DEM_VER );
	
}
