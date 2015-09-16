<?php


class DemPolls_List_Table extends WP_List_Table{
	static $cache;
	
	public function __construct(){
		parent::__construct();
		
		$this->prepare_items();		
	}
	
    public function prepare_items(){ 
		global $wpdb;
		
		$per_page = 10;
		
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		// Строим запрос
		// where ----		
		$where   = 'WHERE 1';
		//if( @ $_GET['userid'] ) $where .= ' AND userid = ' . intval($_GET['userid']);		
		
		// пагинация
        $this->set_pagination_args( array(
            'total_items' => $wpdb->get_var("SELECT count(*) FROM $wpdb->democracy_q $where"),
            'per_page'    => $per_page,
        ) );
        $cur_page = (int) $this->get_pagenum(); // после set_pagination_args()
		
		// orderby offset
		$OFFSET  = 'LIMIT '. (($cur_page-1) * $per_page .','. $per_page );
		$order   = @ $_GET['order']=='asc' ? 'ASC' : 'DESC';
		$orderby = @ $_GET['orderby'] ?: 'id';
		$ORDER_BY = sprintf("ORDER BY %s %s", esc_sql($orderby), $order );
		
		// выполняем запрос
		$sql = "SELECT * FROM $wpdb->democracy_q $where $ORDER_BY $OFFSET";

		$this->items = $wpdb->get_results( $sql );
    }
 
    public function get_columns(){
        $columns = array(
			//'cb'        => '<input type="checkbox" />',
            'id'        => __('ID','dem'),
            'question'  => __('Вопрос','dem'),
            'open'      => __('Открыт','dem'),
            'active'    => __('Актив.','dem'),
            'votes'     => __('Голосов','dem'),
            'winner'    => __('Лидер','dem'),
            'added'     => __('Добавлен','dem'),
        );
		
        return $columns;
    }
	
    public function get_hidden_columns(){
        return array();
    }

    public function get_sortable_columns(){
        return array(
			'id'       => array('id','asc'),
			'question' => array('question','asc'),
			'open'     => array('open','asc'),
			'active'   => array('active','asc'),
			'added'    => array('added','asc'),
		);
    }
  
	## Extra controls to be displayed between bulk actions and pagination
	/*
	function extra_tablenav( $which ){
		if( $which == 'bottom' ){
			wp_nonce_field('delete_logs_nonce', '_sdosmsnonce');
			echo '<input type="submit" class="button" value="Удалить выбранные">';
		}
	}
	*/
	
	## Заполнения для колонок
    public function column_default( $poll, $col ){
		global $wpdb;
		
		$cache = & self::$cache;
		if( ! isset( $cache[ $poll->id ] ) )
			$cache[ $poll->id ] = $wpdb->get_results("SELECT * FROM $wpdb->democracy_a WHERE qid = ". (int) $poll->id );
		
		$answ = & $cache[ $poll->id ];
		
		$url = Dem::$i->admin_page_url();
		$date_format = get_option('date_format');
		
		// вывод
		if(0){}
		elseif( $col == 'question' ){
			$statuses = 
			'<span class="statuses">'.
				($poll->democratic ? '<span class="dashicons dashicons-megaphone" title="'. __('Пользователи могут добавить свои ответы (democracy).','dem') .'"></span>' : '').
				($poll->revote ? '<span class="dashicons dashicons-update" title="'. __('Пользователи могут изменить мнение (переголосование).','dem') .'"></span>' : '').
				($poll->forusers ? '<span class="dashicons dashicons-admin-users" title="'. __('Голосовать могут только зарегистрированные пользователи.','dem') .'"></span>' : '').
/*
				($poll->active ? '<span class="dashicons dashicons-controls-play" title="'. __('Активный (участвует при выводе случайного опроса).','dem') .'"></span>' : '').
				(!$poll->open ? '<span class="dashicons dashicons-no-alt" title="'. __('Голосование закрыто.','dem') .'"></span>' : '').
*/
			'</span>';

			return $statuses . $poll->question . '
			<div class="row-actions">
				<span class="edit"><a href="'. add_query_arg( array('edit_poll'=> $poll->id), $url ) .'">'. __('Редактировать','dem') .'</a> | </span>'.
				( Dem::$opt['keep_logs'] ? '<span class="edit"><a href="'. add_query_arg( array('subpage'=>'logs', 'poll'=> $poll->id), $url ) .'">'. __('Логи','dem') .'</a> | </span>' : '') .
				'<span class="delete"><a href="'. add_query_arg( array('delete_poll'=> $poll->id), $url ) .'" onclick="return confirm(\''. __('Точно удалить?','dem') .'\');">'. __('Удалить','dem') .'</a> | </span>
				<span style="color:#999">[democracy id="'. $poll->id .'"]</span>
			</div>
			';
		}
		elseif( $col == 'votes' ){
			return array_sum( wp_list_pluck( (array) $answ, 'votes' ) );
		}
		elseif( $col == 'winner' ){
			if( ! $answ )  return 'Нет';
			
			$winner = $answ[ key($answ) ];
			foreach( (array) $answ as $ans )
				if( $ans->votes > $winner->votes )
					$winner = $ans;
			return $winner->answer;
		}
		elseif( $col == 'active' ){
			return dem_activatation_buttons( $poll );
		}
		elseif( $col == 'open' ){
			return dem_opening_buttons( $poll );
		}
		elseif( $col == 'added' ){
			$date = date( $date_format, $poll->added );
    	    $end  = $poll->end ? date( $date_format, $poll->end ) : '';

			return "$date<br>$end";
		}
		else
			return isset( $poll->$col ) ? $poll->$col : print_r( $poll, true );
    }
	
	public function column_cb( $item ){
		echo '<label><input id="cb-select-'. @ $item->id .'" type="checkbox" name="delete[]" value="'. @ $item->id .'" /></label>';
	}
 
}