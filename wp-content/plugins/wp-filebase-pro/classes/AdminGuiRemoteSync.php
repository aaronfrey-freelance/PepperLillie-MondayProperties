<?php
class WPFB_AdminGuiRemoteSync {
	static function Display()
	{
		wpfb_loadclass('Output','RemoteSync');
		
		if(!empty($_REQUEST['action'])) {
			switch($_REQUEST['action']) {
				case "rsync":
					$rsync = WPFB_RemoteSync::GetSync($_REQUEST['rsync_id']);
					if(empty($rsync)) wp_die('Remote Sync not found!');
					wpfb_loadclass('ProgressReporter');
					$progress_reporter = new WPFB_ProgressReporter();
					$rsync->Sync(false, $progress_reporter);
					$progress_reporter->ChangedFilesReport();
					return;
							
				case "new-rsync":
					$service_class = $_REQUEST['service_class'];
					if(!WPFB_RemoteSync::IsServiceClass($service_class))
						wp_die('Not a service class!');
					$rsync = new $service_class($_REQUEST['name']);
					$rsync->DisplayEditForm();
					WPFB_RemoteSync::AddSync($rsync);
					return;
					
				case "edit-rsync":
					$rsync = WPFB_RemoteSync::GetSync($_REQUEST['rsync_id']);
					if(empty($rsync)) wp_die('Remote Sync not found!');
					$rsync->DisplayEditForm();
					return;
					
				case "edited-rsync":
					$rsync = WPFB_RemoteSync::GetSync($_REQUEST['rsync_id']);
					if(empty($rsync)) wp_die('Remote Sync not found!');
					$res = $rsync->Edited(stripslashes_deep($_POST));
					if($res['err']) wp_die($res['err']);
					if(!empty($res['reload_form'])) {
						$rsync->DisplayEditForm();
						return;
					}
					break;
					
				case "delete-rsync":
					WPFB_RemoteSync::DeleteSync($_REQUEST['rsync_id']);
					break;
			}
		}
		
	  //Create an instance of our package class...
	    $list_table = new WPFB_RemoteSync_List_Table();
	    $list_table->prepare_items();
		 
		 WPFB_Core::PrintJS();
    ?>
    <div class="wrap"> 
    <h2><?php _e('Cloud Syncs', WPFB) ?></h2>      
        <form method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $list_table->display() ?>
        </form>
        <?php self::NewSyncForm(); ?>        
    </div>
    <?php
	}
	
static function ServiceDropDown(){
	$srvs = WPFB_RemoteSync::GetServiceClasses();
	$content = '';
	
	foreach($srvs as $tag => $name) {
		$logo_func = array($tag,'GetServiceLogo');
		//$style = is_callable($logo_func) ? 'style="background-image:url('.esc_attr(call_user_func($logo_func)).');"' : '';
		$content .= '<option value="'.$tag.'">'.esc_attr($name).'</option>';
	}
	return $content;	
}

static function NewSyncForm()
{	
	?>
<div class="form-wrap">	
	<h2><?php _e('New Cloud Sync', WPFB) ?></h2>
	<form action="<?php echo remove_query_arg(array('action','service_class')) ?>" method="post" class="validate">
		<input type="hidden" name="action" value="new-rsync" />
		<div class="form-field form-required">	
			<label for="new-rsync-service"><?php _e('Cloud Service'); ?>:</label>
			<select id="new-rsync-service" name="service_class"><?php echo self::ServiceDropDown(); ?></select>
			 <a href="<?php echo esc_attr(admin_url('admin.php?page=wpfilebase_manage&action=install-extensions')); ?>">See Extensions</a> for more services
			<p><?php _e('Select the service you would like to sync with.',WPFB); ?></p>
		</div>	
		<div class="form-field form-required">		
			<label for="new-rsync-name">Name</label>
			<input id="new-rsync-name" name="name" type="text" style="width: 120px;" />
			<p><?php _e('An identifier or short description',WPFB); ?></p>
		</div>			
		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e("Continue") ?>" /></p>
	</form>
</div>
	<?php
}
}


if(!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    
class WPFB_RemoteSync_List_Table extends WP_List_Table {
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'sync',     //singular name of the listed records
            'plural'    => 'syncs',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    function column_default($item, $column_name){
    	return '???';
    }    
    
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item->GetId()
        );
    }
    
    function column_title($item){
        
        //Build row actions
        $actions = array(
        	'sync'    => sprintf('<a href="?page=%s&action=%s&rsync_id=%s">Sync</a>',$_REQUEST['page'],'rsync',$item->GetId()),
            'edit'      => sprintf('<a href="?page=%s&action=%s&rsync_id=%s">Edit</a>',$_REQUEST['page'],'edit-rsync',$item->GetId()),
            'delete'    => sprintf('<a href="?page=%s&action=%s&rsync_id=%s">Delete</a>',$_REQUEST['page'],'delete-rsync',$item->GetId()),
        );
        
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item->GetTitle(),
            /*$2%s*/ $item->GetId(),
            /*$3%s*/ $this->row_actions($actions)
        );
    }
    
    function column_service($item) {
		$logo_func = array(get_class($item),'GetServiceLogo');
		$logo = is_callable($logo_func) ? '<img src="'.esc_attr(call_user_func($logo_func)).'" style="max-width:48px;" />' : '';
		return $logo.$item->GetServiceName();
	 }
	function column_account($item) { return $item->GetAccountName(); }
	function column_remote_path($item) { return $item->GetRemotePath(); }
	function column_cat($item) { return is_null($item->GetCat()) ? '-' : $item->GetCat()->GetTitle(); }
	function column_last_sync_time($item) { return ($item->GetLastSyncTime()==0) ? __('Never') : date(get_option('date_format'), $item->GetLastSyncTime()); }
	function column_num_files($item) { return $item->GetNumFiles(); }
	
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'     => __('Name'),
        	'service'		=> __('Service'),
        	'account'		=> __('Account'),
        	'remote_path' => __('Remote Path'),
            'cat'    	=> 'Root Category',
        	'last_sync_time'	=> 'Sync Time',
				'num_files' => 'Num. Files',
        );
        return $columns;
    }
    
    function get_bulk_actions() {
        $actions = array(
        	'sync'    => __('Sync',WPFB),
            'delete'    => __('Delete')
        );
        return $actions;
    }
    
    
    function process_bulk_action() {
        if(empty($_REQUEST['sync']) || !is_array($_REQUEST['sync'])) return;
    	switch($this->current_action()) {
    		case 'delete':
    			foreach($_REQUEST['sync'] as $id)
    				WPFB_RemoteSync::DeleteSync($id);
    			break;
    			
    		case 'sync':
  				wpfb_loadclass('ProgressReporter');
				$progress_reporter = new WPFB_ProgressReporter();
    			foreach($_REQUEST['sync'] as $id) {
    				if(!is_null($rsync = WPFB_RemoteSync::GetSync($id)))
						$rsync->Sync(true, $progress_reporter);
    			}
    			$progress_reporter->ChangedFilesReport();
    			break;
    	}        
    }
    
    
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();        
        
        $data = WPFB_RemoteSync::GetSyncs();        

        $total_items = count($data);
        $this->items = $data;
        
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $total_items,                     //WE have to determine how many items to show on a page
            'total_pages' => 1   //WE have to calculate the total number of pages
        ) );
    }
}