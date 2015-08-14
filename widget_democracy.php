<?php
/* Democracy Widget */

add_action( 'widgets_init', function(){ register_widget("widget_democracy"); } );

class widget_democracy extends WP_Widget {

	public function __construct(){
		// Instantiate the parent object
		parent::__construct( 
			'democracy' // Base ID //создаст опции widget_democracy
			, __('Опрос Democracy','dem') // Name
			, array( 'description' => __('Виджет опроса Democracy','dem') ) // Args
		);
	}
	
	// front end
	public function widget( $args, $instance ){
		extract( $args );
		$title = @ $instance['title'];
		$id    = (int) @ $instance['poll_ID'];
		
		if( isset( $instance['questionIsTitle'] ) ){
			echo $before_widget;
			democracy_poll( $id, $before_title, $after_title );
			echo $after_widget;
		} else {
			echo $before_widget . $before_title . $title . $after_title;
			democracy_poll( $id );
			echo $after_widget;
		}
	}

	// options
	public function update( $new_instance, $old_instance ){
		$instance = array();
		foreach( $new_instance as $k => $v ) $instance[ $k ] = strip_tags( $v );
		
		return $instance;
	}
	
	// admin
	public function form( $instance ){	
		add_action('admin_footer', array( $this, 'dem_widget_footer_js'), 11 );
		
		$checked = isset( $instance['questionIsTitle'] ) ? ' checked="checked"' : '';
		$title   = isset( $instance['title'] )           ? esc_attr( $instance['title'] ) : __('Демократический опрос','dem');
		$poll_ID = isset( $instance['poll_ID'] )         ? (int) $instance['poll_ID'] : 0;
		
		$title_style = $checked ? 'style="display:none;"' : '';
		?>
		<p>
			<label>
				<input type="checkbox" name="<?php echo $this->get_field_name('questionIsTitle')?>" <?php echo $checked?> value="1" class="questionIsTitle" onchange="demHideTitle(this);">
				<small><?php _e('вопрос опроса = заголовок виджета?','dem')  ?> </small>
			</label>
		</p>

		<p class="demTitleWrap" <?php echo $title_style ?>>
			<label><?php _e('Заголовок опроса:','dem'); ?> 
				<input style="width:100%;" type="text" id="demTitle" name="<?php echo $this->get_field_name('title')?>" value="<?php echo $title?>">
			</label>
		</p>


		<?php 
		global $wpdb, $table_prefix;

		$q = $wpdb->get_results("SELECT * FROM {$table_prefix}democracy_q ORDER BY added DESC");
		$out = '<option value="0">'. __('- Активный (рандомно все активные)','dem') .'</option><option disabled></option>';
		
		foreach( $q as $quest ){
			$selected = ($poll_ID==$quest->id) ? ' selected="selected" ' : '';
			$out .= '<option value="'. $quest->id .'" '.$selected.'>'. esc_attr($quest->question) .'</option>';
		}

		echo '
		<p>
			<label>'. __('Какой опрос показывать?','dem') .' 
				<select name="'. $this->get_field_name('poll_ID') .'">'. $out .'</select>
			</label>
		</p>';
	}
	
	function dem_widget_footer_js(){
		?>
		<script type="text/javascript"> 
			var getTitleObj = function(that){ return jQuery(that).closest('.widget-content').find('.demTitleWrap'); };

			window.demHideTitle = function(that){
				if( that.checked ) getTitleObj(that).slideUp(300);
				else               getTitleObj(that).slideDown(300);
			}
		</script>
		<?php
	}
}