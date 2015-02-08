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
        if( ! is_null( self::$inst ) ) return self::$inst;
        
        # admin part
		if( is_admin() && ! defined('DOING_AJAX') )
            self::$inst = new DemAdminInit();
		# front-end
		else
            self::$inst = new DemFrontInit();
        
        return self::$inst;
    }
	
	function __construct(){
		if( ! is_null( self::$inst ) ) return self::$inst;
                
        $this->opt      = $this->get_options();
		$this->dir_path = plugin_dir_path(__FILE__);
		$this->dir_url  = plugin_dir_url(__FILE__);
		$this->ajax_url = admin_url('admin-ajax.php'); //$this->dir_url . 'ajax_request.php';
        
        add_action('plugins_loaded', array($this, 'dem_init') );
	}
    
    /**
     * Инициализирует основные хуки Democracy вешается на хук plugins_loaded.
     */
    function dem_init(){
		// файл перевода
		if( $this->opt['load_textdomain'] ) $this->load_textdomain();
        
		// меню в панели инструментов
		if( $this->opt['toolbar_menu'] )
			add_action('admin_bar_menu', array( $this, 'toolbar'), 99);
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
			'inline_js_css'    => 0,   // встараивать стили и скрипты в HTML
			'css_file_name'    => 'default.css', // название файла стилей который будет использоваться для опроса.
			'keep_logs'        => 1, // вести лог в БД
			'graph_from_total' => 0,
			'order_answers'    => 1,
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
			'loader_fname'     => 'cube.svg',			
			'disable_js'       => 0,   // Дебаг: отключает JS
			'cookie_days'      => 365, // Дебаг
		);
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
		
}






### FRONT END
class DemFrontInit extends Dem{
	function __construct(){
        parent::__construct();
        
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
        elseif( $act == 'getVotedIds' ){
            $poll->set_cookie(); // установим куки, т.к. этот запрос делается только если куки не установлены
            if( $poll->votedFor ) echo $poll->votedFor;
            elseif( $poll->blockForVisitor ) echo 'blockForVisitor'; // чтобы вывести ошибку
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
}



