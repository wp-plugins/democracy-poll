<?php

/**
 * Класс инициализирующий плагин и включающий все необходимые функции для админ-панели и фронт энда
 * влкючает в себя основную работу плагина WordPress
 */

class Dem{
	public $dir_path;
	public $dir_url;
	public $ajax_url;
	
	public $user_access; // доступ пользователя к админ-функциям Democracy
	
	public $message = array();
	
	public $opt;
	
	const OPT_NAME = 'democracy_options';
	
	static $inst;
    
    static function init(){
        if( ! is_null( self::$inst ) ) return self::$inst;
        
        # admin part
		if( is_admin() && ! defined('DOING_AJAX') )
            self::$inst = new DemAdminInit();
		# front-end
		else {
            self::$inst = new self;
            self::$inst->DemFrontInit();
        }
        
        return self::$inst;
    }
	
	function __construct(){
		if( ! is_null( self::$inst ) ) return self::$inst;
                
		$this->dir_path = plugin_dir_path(__FILE__);
		$this->dir_url  = plugin_dir_url(__FILE__);
		$this->ajax_url = admin_url('admin-ajax.php'); //$this->dir_url . 'ajax_request.php';
		
        $this->opt      = $this->get_options(); // !!! должна идти после установки путей
        
        $this->dem_init();
	}
    
    ## Инициализирует основные хуки Democracy вешается на хук plugins_loaded.
    function dem_init(){
		$this->user_access = current_user_can('manage_options');
		
		$this->load_textdomain();
        
		// меню в панели инструментов
		if( @ $this->opt['toolbar_menu'] && $this->user_access )
			add_action('admin_bar_menu', array( $this, 'toolbar'), 99);
    }
				
	## подключаем файл перевода
	function load_textdomain(){
		$locale = get_locale();
		
		if( $locale == 'ru_RU' ) return;
		
		$patt   = $this->dir_path . DEM_LANG_DIRNAME .'/%s.mo';
		$mofile = sprintf( $patt, $locale );
		if( ! file_exists( $mofile ) )
			$mofile = sprintf( $patt, 'en_US' );

		load_textdomain('dem', $mofile );
	}
	
	## Добавляет пункты меню в панель инструментов
	function toolbar( $toolbar ) {
		$toolbar->add_node( array(
			'id'    => 'dem_settings',
			'title' => 'Democracy',
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
			'title'  => __('Настройки Democracy','dem'),
			'href'   => $this->admin_page_url() . '&subpage=general_settings',
		) );
		$toolbar->add_node( array(
			'parent' => 'dem_settings', 
			'id'     => 'dem_degign',
			'title'  => __('Настройки дизайна','dem'),
			'href'   => $this->admin_page_url() . '&subpage=design',
		) );
		$toolbar->add_node( array(
			'parent' => 'dem_settings', 
			'id'     => 'dem_txts',
			'title'  => __('Изменение текстов','dem'),
			'href'   => $this->admin_page_url() . '&subpage=l10n',
		) );
	}
	
	## Получает настройки. Устанавливает если их нет
	function get_options(){
		if( empty( $this->opt ) ) $this->opt = get_option( self::OPT_NAME );
		if( empty( $this->opt ) ) $this->update_options('default');

		return $this->opt;
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
     * проверяет используется ли страничный плагин кэширования на сайте
     * @return bool
     */
	function is_cachegear_on(){
        if( $this->opt['force_cachegear'] ) return true;
        
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

	
	
	
    
    ### FRONT END ------------------------------------------------------
	function DemFrontInit(){
		# шоткод [democracy]
		add_shortcode('democracy',          array($this, 'poll_shortcode'));
		add_shortcode('democracy_archives', array($this, 'archives_shortcode'));
		
		//if( ! $this->opt['inline_js_css'] ) $this->add_css(); // подключаем стили как файл, если не инлайн

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
			'aids' => $aids ? wp_unslash( $_POST['answer_ids'] ) : false,
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
//		$cssurl   = $this->dir_url  . "$css.min.css";
//		$csspath  = $this->dir_path . "$css.min.css";
//		
//		if( ! file_exists( $csspath ) ){
//			$cssurl   = $this->dir_url  . "$css.css";
//			$csspath  = $this->dir_path . "$css.css";
//		}

		// inline HTML
//		if( $this->opt['inline_js_css'] )
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
}



