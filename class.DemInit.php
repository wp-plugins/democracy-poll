<?php

## Initiate plugin, add main plugin admin & front functions

class Dem{
	public $ajax_url;
	
	public $user_access; // доступ пользователя к админ-функциям Democracy
	public $message = array();
	
	public $allowed_html; // теги допустимые в вопросах и ответах
	
	const OPT_NAME = 'democracy_options';
	
	static $opt;
	static $i;
    
    static function init(){
        if( ! is_null( self::$i ) )
			return self::$i;
			
        # admin part
		if( is_admin() && ! defined('DOING_AJAX') )
            self::$i = new DemAdminInit();
		# front-end
		else {
            self::$i = new self;
            self::$i->dem_front_init();
        }
        
        return self::$i;
    }
	
	function __construct(){
		if( ! is_null( self::$i ) )
			return self::$i;
		
		$this->allowed_html = $GLOBALS['allowedtags'];
		$this->allowed_html['a']['rel'] = true;
		$this->allowed_html['a']['class'] = true;
		
		$this->ajax_url = admin_url('admin-ajax.php');
		
        self::$opt = $this->get_options();
        
        $this->dem_init();
	}
    
    ## Инициализирует основные хуки Democracy вешается на хук plugins_loaded.
    function dem_init(){
		$this->user_access = current_user_can('manage_options');
		
		$this->load_textdomain();
        
		// меню в панели инструментов
		if( @ self::$opt['toolbar_menu'] && $this->user_access )
			add_action('admin_bar_menu', array( $this, 'toolbar'), 99);
    }
				
	## подключаем файл перевода
	function load_textdomain(){
		$locale = get_locale();
		
		if( $locale == 'ru_RU' ) return;

		$patt   = DEMOC_PATH . DEM_LANG_DIRNAME .'/%s.mo';
		$mofile = sprintf( $patt, $locale );
		if( ! file_exists( $mofile ) )
			$mofile = sprintf( $patt, 'en_US' );

		load_textdomain('dem', $mofile );
	}
	
	## Добавляет пункты меню в панель инструментов
	function toolbar( $toolbar ){
		$parent    = 'dem_settings';
		$admin_url = $this->admin_page_url();
		
		$toolbar->add_node( array(
			'id'    => $parent,
			'title' => 'Democracy',
			'href'  => $admin_url . '&subpage=general_settings',
		) );
		
		$list = array(
			''                 => __('Список опросов','dem'), 
			'add_new'          => __('Добавить опрос','dem'),
			'logs'             => __('Логи','dem'),
			'general_settings' => __('Настройки','dem'),
			'design'           => __('Дизайн','dem'),
			'l10n'             => __('Изменение текстов','dem'),
		);
		
		foreach( $list as $id => $title ){
			$toolbar->add_node( array(
				'parent' => $parent, 
				'id'     => $id ?: 'dem_main',
				'title'  => $title,
				'href'   => add_query_arg( array('subpage'=>$id), $admin_url ), 
			) );
		}
	}
	
	## Получает настройки. Устанавливает если их нет
	function get_options(){
		if( empty( self::$opt ) ) self::$opt = get_option( self::OPT_NAME );
		if( empty( self::$opt ) ) $this->update_options('default');

		return self::$opt;
	}
	
	## Возвращает УРЛ на главную страницу настроек плагина
	## @return Строку
	function admin_page_url(){
		static $url;
		if( ! $url )
			$url = admin_url('options-general.php?page='. basename( DEMOC_PATH ) );
		
		return $url;
	}	
	
    ## проверяет используется ли страничный плагин кэширования на сайте
    ## @return bool
	function is_cachegear_on(){
        if( self::$opt['force_cachegear'] ) return true;
        
        // wp total cache
        if( defined('W3TC') && @w3_instance('W3_ModuleStatus')->is_enabled('pgcache') ) return true;
        // wp super cache
        if( defined('WPCACHEHOME') && @$GLOBALS['cache_enabled'] ) return true;
        // WordFence
        if( defined('WORDFENCE_VERSION') && @wfConfig::get('cacheType') == 'falcon' ) return true;
        // WP Rocket
        if( class_exists('HyperCache')  ) return true;
        // Quick Cache
        if( class_exists('quick_cache') && @\quick_cache\plugin()->options['enable'] ) return true;
        // wp-fastest-cache
        // aio-cache
        
        return false;
	}
	
	## очищает данные ответа
	function sanitize_answer_data( $data ){
		$allowed_tags = $this->user_access ? $this->allowed_html : 'strip';
			
		if( is_string( $data ) )
			return wp_kses( trim($data), $allowed_tags );
		
		foreach( $data as $key => & $val ){
			if( is_string($val) ) $val = trim($val);
			
			if(0){}
			// допустимые теги
			elseif( $key == 'answer' )
				$val = wp_kses( $val, $allowed_tags );	
			
			// числа
			elseif( in_array( $key, array('qid','aid','votes') ) )
				$val = (int) $val;
			
			// остальное
			else
				$val = wp_kses( $val, 'strip' );
		}
		
		//die( print_r( $data ) );
		
		return $data;
	}
    
    ### FRONT END ------------------------------------------------------
	function dem_front_init(){
		# шоткод [democracy]
		add_shortcode('democracy',          array($this, 'poll_shortcode'));
		add_shortcode('democracy_archives', array($this, 'archives_shortcode'));
		
		//if( ! self::$opt['inline_js_css'] ) $this->add_css(); // подключаем стили как файл, если не инлайн

		# для работы функции без AJAX
		if( @ $_POST['action'] != 'dem_ajax' ) $this->not_ajax_request_handler();

		# ajax request во frontend_init нельзя, потому что срабатывает только как is_admin()
		add_action('wp_ajax_dem_ajax',        array( $this, 'ajax_request_handler') );
		add_action('wp_ajax_nopriv_dem_ajax', array( $this, 'ajax_request_handler') );
	}
    	
	## обрабатывает запрос AJAX
	function ajax_request_handler(){
		extract( $this->__sanitize_request_vars() );
		
		if( ! $act )  wp_die('error: no parameters have been sent or it is unavailable');
		if( ! $pid )  wp_die('error: id unknown');

		// Вывод		
		$poll = new DemPoll( $pid );
		
		// switch
		// голосуем и выводим результаты
		if( $act == 'vote' && $aids ){
            // если пользователь голосует с другого браузера и он уже голосовал, ставим куки
            //if( $poll->cachegear_on && $poll->votedFor ) $poll->set_cookie();
			
            $poll->addVote( $aids );
			
			if( $poll->not_show_results )
				echo $poll->get_vote_screen();
			else
            	echo $poll->get_result_screen();
		}
		// удаляем результаты
		elseif( $act == 'delVoted' ){
			$poll->unsetVotedData();
			echo $poll->get_vote_screen();
		}
		// смотрим результаты
		elseif( $act == 'view' ){
			if( $poll->not_show_results )
				echo $poll->get_vote_screen();
			else
				echo $poll->get_result_screen();
		}
		// вернуться к голосованию
		elseif( $act == 'vote_screen' ){
			echo $poll->get_vote_screen();
		}
        elseif( $act == 'getVotedIds' ){
            if( $poll->votedFor ){
                $poll->set_cookie(); // установим куки, т.к. этот запрос делается только если куки не установлены    
                echo $poll->votedFor;
            }
            elseif( $poll->blockForVisitor ){
                echo 'blockForVisitor'; // чтобы вывести заметку
            }
            else{
                // если не голосовал ставим куки на день, чтобы не делать эту првоерку каждый раз
                $poll->set_cookie('notVote', (current_time('timestamp') + DAY_IN_SECONDS) );
            }
        }

		wp_die();
	}
			
	## для работы функции без AJAX
	function not_ajax_request_handler(){
		extract( $this->__sanitize_request_vars() );
		        
		if( ! $act || ! $pid || ! isset($_SERVER['HTTP_REFERER']) ) return;

		$poll = new DemPoll( $pid );

		if( $act == 'vote' && $aids ){
			$poll->addVote( $aids );
            wp_redirect( remove_query_arg( array('dem_act','dem_pid'), $_SERVER['HTTP_REFERER'] ) );
            exit;
		}
		elseif( $act == 'delVoted' ){
			$poll->unsetVotedData();
			wp_redirect( remove_query_arg( array('dem_act','dem_pid'), $_SERVER['HTTP_REFERER'] ) );
			exit;
		}
	}
	
	## Делает предваритеьную проверку передавемых переменных запроса
	function __sanitize_request_vars(){		
		$act  = @ $_POST['dem_act'];
		$pid  = @ $_POST['dem_pid'];
		$aids = @ $_POST['answer_ids'];
		
		return array(
			'act'  => $act  ? $act : false,
			'pid'  => $pid  ? absint( $pid ) : false,
			'aids' => $aids ? wp_unslash( $aids ) : false,
		);
	}
	
	## шоткод архива опросов
	function archives_shortcode(){
		return '<div class="dem-archives-shortcode">'. get_democracy_archives() .'</div>';
	}
	
	## шоткод опроса
	function poll_shortcode( $atts ){		
		return '<div class="dem-poll-shortcode">'. get_democracy_poll( @$atts['id'] ) .'</div>';
	}
	
	## добавляет стили в WP head
	function add_css(){
		static $once; if( $once ) return; $once=1; // выполняем один раз!
		
        $demcss = get_option('democracy_css');
        $minify = @$demcss['minify'];
		
		if( ! $minify ) return;
					
		// пробуем подключить сжатые версии файлов		
//		$css_name = rtrim( $css_name, '.css');
//		$css      = 'styles/' . $css_name;
//		$cssurl   = DEMOC_URL  . "$css.min.css";
//		$csspath  = DEMOC_PATH . "$css.min.css";
//		
//		if( ! file_exists( $csspath ) ){
//			$cssurl   = DEMOC_URL  . "$css.css";
//			$csspath  = DEMOC_PATH . "$css.css";
//		}

		// inline HTML
//		if( self::$opt['inline_js_css'] )
			return "\n<!--democracy-->\n" .'<style type="text/css">'. $minify .'</style>'."\n";
		
//		else{
//			add_action('wp_enqueue_scripts', function() use ($cssurl){ wp_enqueue_style('democracy', $cssurl, array(), DEM_VER ); } );
//		}
	}
    	
	## добавляет скрипты в подвал
	function add_js(){
		static $once; if( $once ) return; $once=1; // выполняем один раз!
				
		// пробуем подключить сжатые версии файлов
		$js       = 'js/democracy';
		$jsurl    = DEMOC_URL  . "$js.min.js";
		$jspath   = DEMOC_PATH . "$js.min.js";
		if( ! file_exists( $jspath ) ){
			$jsurl   = DEMOC_URL  . "$js.js";
			$jspath  = DEMOC_PATH . "$js.js";
		}

		// inline HTML
		if( self::$opt['inline_js_css'] ){
			wp_enqueue_script('jquery');			
			add_action('wp_footer', function() use($jspath){ echo '<!--democracy-->'. "\n" .'<script type="text/javascript">'. file_get_contents( $jspath ) .'</script>'."\n"; }, 999);
		}
		else
			wp_enqueue_script('democracy', $jsurl, array('jquery'), DEM_VER, true );
	}
	
	## Сортировка массива объектов. Передаете в $array массив объектов, указываете в $args параметры сортировки и получаете отсортированный массив объектов.
	static function objects_array_sort( $array, $args = array('votes' => 'desc') ){
		usort( $array, function( $a, $b ) use ( $args ){
			$res = 0;
			
			if( is_array($a) ){
				$a = (object) $a;
				$b = (object) $b;
			}

			foreach( $args as $k => $v ){
				if( $a->$k == $b->$k ) continue;

				$res = ( $a->$k < $b->$k ) ? -1 : 1;
				if( $v=='desc' ) $res= -$res;
				break;
			}

			return $res;
		} );

		return $array;
	}
}

