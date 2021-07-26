<?php
if (isset($_GET['update']) && $_GET['update'] == 1 ) {
  echo "<script>alert('Expiry Date Updated Successfully')</script>";
}
if (isset($_GET['update']) && $_GET['update'] == 0 ) {
  echo "<script>alert('Operation Failed')</script>";
}
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

 /**
  * 
  */

 class ExpiryListTableClass extends WP_List_Table
 {
 	/**
     * Prepare the items for the table to process
     *
     * @return Void
     */
 	public function prepare_items()
    {
        
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
    	//$this->items = $this->$data;
         $data = $this->table_data();
         usort( $data, array( &$this, 'sort_data' ) );
         $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }


      /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
        	'event_id'=>'Event ID',
            'event_name' => 'Event Name',
            'start_date' => 'Start date',
            'end_date'=>'End Date',
            'expiry_date' => 'Expiry Date',
            'actions' => 'Action'
            );
        return $columns;
    }

    public function column_default($item,$column_name)
    {
    	switch( $column_name ) {
            case 'event_id':
            case 'event_name':
            case 'start_date':
            case 'end_date':
            case 'expiry_date':
                return $item[ $column_name ];
            case 'actions':
            	return '<a href="?page='.$_GET['page'].'&action=changeExpiry&event_id='.$item['event_id'].'&event_name='.$item['event_name'].'&expiry='.$item['expiry_date'].'">Change Expiry Date</a>'; 
            default:
                return print_r( $item, true ) ;
        }
    }
    
	      /**
	     * Define which columns are hidden
	     *
	     * @return Array
	     */
	    public function get_hidden_columns()
	    {
	        return array('');
	    }
	    /**
	     * Define the sortable columns
	     *
	     * @return Array
	     */
	    public function get_sortable_columns()
	    {
	        return array('expiry_date' => array('expiry_date', false));
	    }
        /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'expiry_date';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }

     private function table_data()
    {
    	global $wpdb;
        $expiry_table = $wpdb->prefix."events_expiry_records";
		$event_table = $wpdb->prefix."events_detail";
        $posts = $wpdb->get_results("SELECT $event_table.id,$event_table.event_name,$event_table.start_date,$event_table.end_date,$expiry_table.event_expiry_date FROM $event_table INNER JOIN $expiry_table ON $event_table.id = $expiry_table.event_id");
		

		$posts_array = array();

		 foreach($posts as $post){ 

		$posts_array[] = array(
			"event_id" => $post->id,
			"event_name" => $post->event_name,
			"start_date" => $post->start_date,
			"end_date"=>$post->end_date,
			"expiry_date"=>$post->event_expiry_date
		);

		} 
        return $posts_array;
    }


 }

    	function list_table_layout()
		 
		 {
		 	$expiry_list_table = new ExpiryListTableClass();

		 	echo "<h1 align = center> Events Expiry List </h1>";
		 	$expiry_list_table->prepare_items();
		 	$expiry_list_table->display();

		 }
		 ?>
		 <div style="padding-right: 10px;">
		 <?php
		 list_table_layout();
		 ?>	
		 </div>
		 
		 <?php
		