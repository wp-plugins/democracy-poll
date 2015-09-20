<?php


class DemLogs_List_Table extends WP_List_Table{
	static $cache;
	
	public $poll_id;
	
	public function __construct(){
		parent::__construct();
		
		$this->poll_id = (int) @ $_GET['poll'];
		
		$this->prepare_items();		
	}
	
    public function prepare_items(){ 
		global $wpdb;
		
		$per_page = 20;
		
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		// Строим запрос
		// where ----		
		$where   = 'WHERE 1';
		if( $this->poll_id ) $where .= ' AND qid = ' . $this->poll_id;		
		if( $userid = (int) @ $_GET['userid'] ) $where .= ' AND userid = ' . $userid;		
		if( $ip = (int) @ $_GET['ip'] ) $where .= ' AND ip = ' . (int) $ip;
		
		// пагинация
        $this->set_pagination_args( array(
            'total_items' => $wpdb->get_var("SELECT count(*) FROM $wpdb->democracy_log $where"),
            'per_page'    => $per_page,
        ) );
        $cur_page = (int) $this->get_pagenum(); // после set_pagination_args()
		
		// orderby offset
		$OFFSET  = 'LIMIT '. (($cur_page-1) * $per_page .','. $per_page );
		$order   = @ strtolower($_GET['order'])=='asc' ? 'ASC' : 'DESC';
		$orderby = @ $_GET['orderby'] ?: 'date';
		$ORDER_BY = '';
		if( $orderby )
			$ORDER_BY = sprintf("ORDER BY %s %s", esc_sql($orderby), $order );
		
		// выполняем запрос
		$sql = "SELECT * FROM $wpdb->democracy_log $where $ORDER_BY $OFFSET";

		$this->items = $wpdb->get_results( $sql );
    }
 
    public function get_columns(){
        $columns = array(
			//'cb'     => '<input type="checkbox" />',
            'ip'     => __('IP','dem'),
            'qid'    => __('Опрос','dem'),
            'aids'   => __('Ответ','dem'),
            'userid' => __('Юзер','dem'),
            'date'   => __('Дата','dem'),
        );
		
		if( $this->poll_id )
			unset( $columns['qid'] );
		
        return $columns;
    }
	
    public function get_hidden_columns(){
        return array();
    }

    public function get_sortable_columns(){
        return array(
			'ip'     => array('ip','asc'),
			'qid'    => array('qid','desc'),
			'userid' => array('userid','asc'),
			'date'   => array('date','desc'),
		);
    }
  
	## Extra controls to be displayed between bulk actions and pagination
	
	function extra_tablenav( $which ){
		if( $which == 'top' ){
			if( $this->poll_id ){
				if( ! $poll = $this->cache('polls', $this->poll_id ) )
					$poll = $this->cache('polls', $this->poll_id, DemPoll::get_poll( $this->poll_id ) );
				
				$delete_link = '<a class="button" href="'. esc_url( add_query_arg(array('del_poll_logs'=>wp_create_nonce('del_poll_logs'), 'poll'=>$this->poll_id )) ) .'" onclick="return confirm(\''. __('Точно удалить?','dem') .'\');">'. __('Удалить логи опроса','dem') .'</a>';
					
				echo '<h2>'. __('Логи опроса: ','dem') . esc_html( $poll->question ) .'</h2>'.
					'<div style="display:inline-block;margin-top:15px;">'. $delete_link .'</div>';
			}
		}
//		if( $which == 'bottom' ){
//			wp_nonce_field('dem_del_logs', 'dem_del_logs');
//			echo '<input type="submit" class="button" value="'. __('Удалить выбранные', 'dem') .'">';
//		}
	}
	
	## если указать $val кэш будет устанавливаться
	function cache( $type, $key, $val = null ){
		$cache = & self::$cache[ $type ][ $key ];
		
		if( ! isset( $cache ) && $val !== null )
			$cache = $val;
		
		return $cache;
	}
	
	## Заполнения для колонок
    public function column_default( $log, $col ){
		global $wpdb;
		
		// вывод
		if(0){}
		elseif( $col == 'ip' ){
			return '<a title="'. __('Поиск по IP', 'dem') .'" href="'. esc_url( add_query_arg( array('ip'=>$log->ip, 'poll'=>null) ) ) .'">'. long2ip( $log->ip ) .'</a>';
		}
		elseif( $col == 'qid' ){
			if( ! $poll = $this->cache('polls', $log->qid ) )
				$poll = $this->cache('polls', $log->qid, DemPoll::get_poll( $log->qid ) );
			
			$url = Dem::$i->admin_page_url();
			
			return esc_html( $poll->question ) .'
			<div class="row-actions">
				<span class="edit"><a href="'. add_query_arg( array('edit_poll'=> $poll->id), $url ) .'">'. __('Редактировать','dem') .'</a> | </span>
				<span class="edit"><a href="'. esc_url( add_query_arg( array('ip'=>null, 'poll'=>$log->qid) ) ) .'">'. __('Логи опроса','dem') .'</a></span>
			</div>
			';
		}
		elseif( $col == 'userid' ){
			if( ! $user = $this->cache('users', $log->userid ) )
				$user = $this->cache('users', $log->userid, $wpdb->get_row("SELECT * FROM $wpdb->users WHERE ID = ". (int) $log->userid ) );
			
			return esc_html( @ $user->user_nicename );
		}
		elseif( $col == 'aids' ){
			$out = array();
			foreach( explode(',', $log->aids ) as $aid ){
				if( ! $answ = $this->cache('answs', $aid ) )
					$answ = $this->cache('answs', $aid, $wpdb->get_row("SELECT * FROM $wpdb->democracy_a WHERE aid = ". (int) $aid ) );
				
				$out[] = '- '. esc_html( $answ->answer );
			}
			
			return implode('<br>', $out );
		}
		else
			return isset( $log->$col ) ? $log->$col : print_r( $log, true );
    }
	
	public function column_cb( $item ){
		echo '<label><input id="cb-select-'. @ $item->ip .'" type="checkbox" name="delete[]" value="'. @ $item->ip .'" /></label>';
	}
 
}