<?php
abstract class WPFB_RemoteSync {
	static function InitClass()
	{
		$service_classes = array("FTPSync");
		self::RegisterServiceClasses($service_classes);
		
		do_action('wpfilebase_register_rsync_service');
	}
	
	private $id;
	private $title;
	private $remote_path;
	private $root_cat_id;
	private $no_file_scan;
	
	private $last_sync_time;
	private $num_files;
	//private $is_syncing;
	
	protected $uris_invalidated;
	private $no_remote_delete;
	
	/**
	 *
	 * @var WPFB_ProgressReporter
	 */
	protected $progress_reporter;
	
	
	function __construct($title)
	{
		$this->title = $title;
		$this->id = uniqid();
	}
	
	// Manage API
	protected function PrepareEditForm() { return true; }	
	protected function DisplayFormFields() { }	
	function GetAccountName() { return '-'; }
	function GetServiceSlug() { return strtolower(substr(get_class($this),5)); }
	function Edited($data, $invalidate_uris=false)
	{
		// check for existing remote syncs in category root
		$cat_id = intval($data['root_cat_id']);
		$cat = WPFB_Category::GetCat($cat_id);
		if(is_null($cat)) return array('err' => 'Category does not exists!');
		$pc = $cat;
		do {
			$ex = WPFB_RemoteSync::GetByCat($pc->GetId());
			if(!is_null($ex) && $ex->id != $this->id)
				return array('err' => sprintf('A Remote Sync with root category <b>%s</b> already exists. Please choose another category.', $pc->GetTitle()));
		} while(!is_null($pc = $pc->GetParent()));
		
		if($this->last_sync_time > 0 && !is_null($old_cat = WPFB_Category::GetCat($this->root_cat_id)) && !$old_cat->Equals($cat)) {
			foreach(array_merge($old_cat->GetChildFiles(), $old_cat->GetChildCats()) as $item) {
				$item->ChangeCategoryOrName($cat_id);
			}
		}
		
		if(empty($data['title']))
			return array('err' => 'Please enter a name!');
			
		$this->title = $data['title'];
		$this->root_cat_id = $cat->GetId();
		if(isset($data['remote_path']))
			$this->remote_path = untrailingslashit ($data['remote_path']);
		
		$this->no_file_scan = !empty($data['no_file_scan']);
		
		if($invalidate_uris)
			$this->uris_invalidated = true;
		
		$this->no_remote_delete = empty($data['remote_delete']);
		
		self::SaveSyncs();
		
		return array('err' => false);
	}
	
	// Sync API
	function IsReady() { return !is_null($this->GetCat()); }	
	protected function OpenConnection($for_sync=true) { return true; }
	protected function CloseConnection() {}
	
	abstract protected function GetFileList($path, $names_only=false);	
	
	abstract protected function DownloadFile($file_info, $local_path, $progress_changed_callback = null);	
	abstract protected function GetFileUri($path, &$expires=null);
	
	protected function UploadFile($local_path, $remote_path, $progress_changed_callback = null) { return null; }
	protected function CanUpload() { return false; }
	
	protected function DeleteFile($file_info) { return null; }
	protected function CanDelete() { return false; }
	
	//protected function IsSyncing() { return !empty($this->is_syncing); }
	
	
	
	
	final function GetId() { return $this->id; }
	final function GetTitle() { return $this->title; }
/**
 * Get the root category
 *
 * @return WPFB_Category The category.
 */
	final function GetCatId() { return $this->root_cat_id; }
	final function GetCat() { return WPFB_Category::GetCat($this->root_cat_id); }
	final function GetRemotePath() { return $this->remote_path; }
	final function GetLastSyncTime() { return $this->last_sync_time; }
	final function GetNumFiles() { return $this->num_files; }
	
	private static $service_classes; 
	static function RegisterServiceClass($class_name)
	{
		if(!class_exists($class_name)) { // if class not found, try to load it
			if(substr($class_name, 0, 5) == "WPFB_")
				$class_name = substr($class_name, 5);
			wpfb_loadclass($class_name);
			$class_name = "WPFB_".$class_name;
		}
		
		if(empty(self::$service_classes)) self::$service_classes = array();
		self::$service_classes[] = $class_name;
		return true;
	}
	
	static function RegisterServiceClasses($class_names) {
		array_map(array(__CLASS__,'RegisterServiceClass'), $class_names);
	}
	
	static function GetServiceClasses()
	{
		$classes = array();
		foreach(self::$service_classes as $sc) {
			$classes[$sc] = call_user_func(array($sc,'GetServiceName'));
		}
		return $classes;
	}
	
	static function IsServiceClass($class) {
		return is_object($class) ? self::IsServiceClass(get_class($class)) : in_array($class, self::$service_classes, true);
	}
	
	private static $syncs = null;
	static function GetSyncs()
	{
		if(is_null(self::$syncs)) // NO caching! when saving a remote sync, force a reload of others, they might have changed
			self::$syncs = array_filter(get_option(WPFB_OPT_NAME.'_rsyncs'));
		if(empty(self::$syncs) || !is_array(self::$syncs)) self::$syncs = array();
		return array_filter(self::$syncs, array(__CLASS__,'IsServiceClass'));
	}
	
	static function SaveSyncs()
	{
		self::GetSyncs();
		update_option(WPFB_OPT_NAME.'_rsyncs', self::$syncs);
	}
	
	static function AddSync($sync)
	{
		self::GetSyncs();
		self::$syncs[$sync->id] = $sync;
		update_option(WPFB_OPT_NAME.'_rsyncs', self::$syncs);
	}
	
	final function Save()
	{
		self::AddSync($this);
	}
	
	static function DeleteSync($id)
	{
		global $wpdb;
		$rs = self::GetSyncs();
		foreach($rs as $i => $r) {
			if($r->id == $id) {
				unset(self::$syncs[$i]);
				$r->RemoveLocalFiles($r->GetLocalFiles());				
				$wpdb->query("DELETE FROM $wpdb->wpfilebase_rsync_meta WHERE rsync_id = '$id'");
			}
		}		
		self::SaveSyncs();
	}
	
/**
 * Get RemoteSync 
 *
 * @return WPFB_RemoteSync The Sync.
 */	
	static function GetSync($id)
	{
		$rs = self::GetSyncs();
		foreach($rs as $r) {
			if($r->id == $id)
				return $r;
		}
		return null;
	}
	
/**
 * Get RemoteSync by category id
 *
 * @param int $cat_id ID of category
 * @return WPFB_RemoteSync The Sync.
 */
	static function GetByCat($cat_id)
	{
		$rs = self::GetSyncs();
		foreach($rs as $r) {
			if($r->root_cat_id == $cat_id)
				return $r;
		}
		return null;
	}
	
	static function SyncAll($output=true)
	{
		$syncs = self::GetSyncs();
  		wpfb_loadclass('Sync','ProgressReporter');
		$progress_reporter = new WPFB_ProgressReporter(!$output);
    	foreach($syncs as $rsync) {
    		try {
    		if($rsync->IsReady() && !is_null($rsync->GetCat()))
				$rsync->Sync(true, $progress_reporter);
    		} catch(Exception $e) { $progress_reporter->LogException($e); }
			if($output) WPFB_Sync::UpdateMemBar();
    	}
    	
    	if($output) $progress_reporter->ChangedFilesReport();
	}	

	
	final public function GetFiles($path)
	{
		$this->OpenConnection(false);
		$files = $this->GetFileList($path, true);
		$this->CloseConnection();
		return $files;
	}
	
	final public function GetRemoteFileInfo($remote_path)
	{
		// TODO caching!
		
		$remote_path = rtrim($remote_path, '/');		
		try {
		foreach($this->GetFileList(self::dirname($remote_path)) as $fi ) {
			if(rtrim($fi->path,'/') == $remote_path)	return $fi;
		} } catch(Exception $e) { }
		return null;
	}
	
	final private function getFileTree($path, $depth=0, $progress_callback=false)
	{
		static $files;
		if($depth == 0) $files = array();
		if($progress_callback != null)
			call_user_func ($progress_callback, count($files));
		
		$fs = $this->GetFileList($path);		
		foreach($fs as $f) {
			if(!$f->is_dir) {
				// if entires with same path exists, take the newer one (Google Drive!)
				if(!isset($files[$f->path]) || ($f->mtime > $files[$f->path]->mtime))
					$files[$f->path] = $f;			
			} else {
				$this->getFileTree($f->path, $depth+1, $progress_callback);
			}
		}
		
		
		return array_values($files);
	}
	

	
	private final function createDirStructure($remote_path)
	{
		$fullpath = "";				  
		foreach (array_filter(explode("/", $remote_path)) as $part) {
			$fullpath .= "/".$part;
			if($this->GetRemoteFileInfo($fullpath) == null)
				$this->CreateDirectory($fullpath);
		}
	}
	
	/**
	 * Get RemoteSyncMeta of all files that have been deleted locally since last sync
	 *
	 * @return WPFB_RemoteSyncMeta[]
	 */
	final function GetLocallyDeletedFiles($keep_meta=false)
	{
		global $wpdb;
		$deleted = array();
		foreach($wpdb->get_results("SELECT * FROM $wpdb->wpfilebase_rsync_meta WHERE rsync_id = '".esc_sql($this->id)."' AND deleted_path <> ''") as $rmeta) {
			/* @var $rmeta WPFB_RemoteSyncMeta */
			$deleted[$rmeta->deleted_path] = $rmeta;
		}
		if(!$keep_meta)
			$wpdb->query("DELETE FROM $wpdb->wpfilebase_rsync_meta WHERE rsync_id = '".esc_sql($this->id)."' AND deleted_path <> ''");
		return $deleted;
	}
	
	final function Sync($batch, $progress_reporter) {	
		wpfb_call('Admin','DisableTimeouts');
		wpfb_loadclass('GetID3', 'Sync','Output');
		
		//$this->is_syncing = true;
		$this->progress_reporter = $progress_reporter;
		
		$cat = $this->GetCat();
		if(is_null($cat)) {
			$progress_reporter->LogError('Category does not exists or is not set in RemoteSync settings!');
			return false;
		}
		$cat_path = $cat->GetLocalPath();
		$cat_path_rel = $cat->GetLocalPathRel();
		
		$progress_reporter->Log(sprintf(__('Remote Sync <b>%s</b> on service <b>%s</b> with account <b>%s</b>.',WPFB),$this->GetTitle(), $this->GetServiceName(), $this->GetAccountName()));
		
		$this->OpenConnection(true);
		
		
		$file_count_field = $progress_reporter->InitProgressField('Files found: %#%');
		$progress_reporter->Log('Retrieving remote file tree, this can take some time...', true);		
		$remote_files = $this->getFileTree($this->remote_path, 0, array($progress_reporter,'SetField'));
		$this->num_files = count($remote_files);
		$progress_reporter->SetField($this->num_files);
		
		$local_files_left = $this->GetLocalFiles();
		$local_files_deleted = $this->no_remote_delete ? array() : $this->GetLocallyDeletedFiles(); // TODO: nor working!
		$deleted_files = 0;
		
		$changed_files = array();
		$uri_updates = array();
		$local_remote_rel_paths = array();
		$n_mod = $n_new = 0;
		foreach($remote_files as $rf)
		{
			if(!WPFB_Admin::IsAllowedFileExt($rf->path)) continue;
			
			$remote_path_rel = substr($rf->path, strlen($this->remote_path));
			$local_path = str_replace('//','/',$cat_path.'/'.$remote_path_rel);
			$local_path_rel = str_replace('//','/',$cat_path_rel.'/'.$remote_path_rel);
			$local_remote_rel_paths[] = $local_path_rel;
			
			if(!$this->no_remote_delete && $this->CanDelete() && isset($local_files_deleted[$local_path_rel]))
			{
				$progress_reporter->Log('Deleting '.$local_path_rel.'.');
				$this->DeleteFile($rf);
				$deleted_files++;
				continue;
			}
			
			$local_file = WPFB_Item::GetByPath($local_path_rel);			
			if(is_null($local_file)) {
				$dir = dirname($local_path); 
				if(!is_dir($dir)) WPFB_Admin::Mkdir($dir);
				$changed_files[] = array('meta' => $rf, 'path' => $local_path, 'file' => null);
				$n_new++;
			} elseif($local_file->is_file) {
				$rsync_meta = $local_file->GetRemoteSyncMeta();
				if(empty($rsync_meta) || $rsync_meta->rev != $rf->rev || $local_file->file_size != $rf->size || $local_file->file_mtime != $rf->mtime) {
					if(!empty($_GET['debug'])) echo "$rsync_meta->rev != $rf->rev || $local_file->file_size != $rf->size || $local_file->file_mtime != $rf->mtime";
					$changed_files[] = array('meta' => $rf, 'path' => $local_path, 'file' => $local_file);
					$n_mod++;
				} elseif($this->uris_invalidated)
					$uri_updates[] = array('meta' => $rf, 'path' => $local_path, 'file' => $local_file);
				unset($local_files_left[$local_file->file_id]); // remove from left files
			} else {
				$progress_reporter->Log('<br />'.sprintf(__('Warning: Could not add remote file %s. A category with the same path (%s) already exists.'), $rf->path, $local_file->GetLocalPathRel()));
			}
		}
		
		$upload_files = array();
		
		// check for files to upload
		if($this->CanUpload()) {
			foreach($cat->GetChildFiles(true) as $local_file) {
				if(!in_array($local_file->GetLocalPathRel(), $local_remote_rel_paths) && file_exists($local_file->GetLocalPath()))
					$upload_files[] = $local_file;
			}
		}
		
		$progress_reporter->Log('done!');
		$progress_reporter->Log(sprintf(__('Number of new Files: %d'), $n_new));
		$progress_reporter->Log(sprintf(__('Number of modified Files: %d'), $n_mod));
		$progress_reporter->Log(sprintf(__('Number of deletions: %d'), count($local_files_left)+$deleted_files));
		if(count($upload_files) > 0) $progress_reporter->Log(sprintf(__('Number of Uploads: %d'), count($upload_files)));
		if(count($uri_updates) > 0) $progress_reporter->Log(sprintf(__('Number of URI updates: %d'), count($uri_updates)));
		
		if(count($changed_files) > 0)
		{
			$bytes_to_download = 0;
			$bytes_downloaded = 0;
			foreach($changed_files as $cf) $bytes_to_download += $cf['meta']->size;
			
			$progress_reporter->Log($this->no_file_scan ? __('Adding files...') : sprintf(__('Downloading temporary files (total size: %s)'), WPFB_Output::FormatFilesize($bytes_to_download)));
			$progress_reporter->InitProgress($bytes_to_download);
			
			WPFB_Sync::PrintDebugTrace("rsync_pre_loop");
			
			foreach($changed_files as $cf)
			{
				if(is_file($cf['path']))
					unlink($cf['path']);
				if(is_dir($cf['path'])) {
					$progress_reporter->LogError("Path $cf[path] is a directory. Skipping download!");
					continue;
				}
				
				try {
					$uri_expires = 0;
					$file_uri = $this->GetFileUri($cf['meta']->path, $uri_expires);
						
					if(!$this->no_file_scan) {
						WPFB_Sync::PrintDebugTrace("rsync_download:".$cf['meta']->path);
						$this->DownloadFile($cf['meta'], $cf['path'], array($progress_reporter, 'SetSubProgress'));
						WPFB_Sync::PrintDebugTrace("rsync_download_done");
					}
					
					if(empty($cf['file'])) // file is new	
					{
						WPFB_Sync::PrintDebugTrace("rsync_add_file");
						
						$result = WPFB_Admin::AddRemoteSyncFile($cf['path'], $cf['meta'], $file_uri, $this->no_file_scan);
						if(!empty($res['error']) || empty($result['file']) || !is_object($result['file'])) {
							$progress_reporter->LogError(empty($result['error']) ? ("Skipping file ".$cf['path']) : $result['error']);
						} elseif(!$result['file']->SetRemoteSyncMeta($cf['meta']->rev, $this->id, $uri_expires)) {							
								$progress_reporter->LogError('Could not store rsync meta!');
						} else {
							$progress_reporter->FileChanged($result['file'], 'added');
						}
					}
					else // file has changed
					{
						WPFB_Sync::PrintDebugTrace("rsync_update_file");
						$file =& $cf['file'];
						
						// this is copied form Sync.php: (TODO: put this in a single function)
						$file->file_size = $cf['meta']->size;
						$file->file_mtime = $cf['meta']->mtime;
						if(!$this->no_file_scan) {
							$file->file_hash = WPFB_Admin::GetFileHash($cf['path']);
						
							WPFB_GetID3::UpdateCachedFileInfo($file);
						}
						
						$file->file_remote_uri = $file_uri;
						if(!$file->SetRemoteSyncMeta($cf['meta']->rev, $this->id, $uri_expires))
							$progress_reporter->LogError('Could not store rsync meta!');
						
						$file->DBSave();
	
						$progress_reporter->FileChanged($file, 'changed');
					}				
				} catch(Exception $e) {
					$progress_reporter->LogException($e);
				}

				// since we only link to the file, we dont need a local copy
				if(!$this->no_file_scan)
					@unlink($cf['path']);
				
				$bytes_downloaded += $cf['meta']->size;
				
				$progress_reporter->SetProgress($bytes_downloaded);
			}
		}
		
		if(count($upload_files) > 0) {
			WPFB_Sync::PrintDebugTrace("uploading_files");
			foreach($upload_files as $local_file)
			{
				try {
					$upload_path = str_replace('//','/',trailingslashit($this->remote_path).substr($local_file->GetLocalPathRel(), strlen($cat_path_rel)));
					$progress_reporter->Log('Uploading '.$local_file->GetLocalPathRel().' to '.$upload_path);
					
					// check if dir. create function exists, and create structure if needed!
					if(method_exists ($this, 'CreateDirectory'))
					{
						$upload_dir = self::dirname($upload_path);
						if($upload_dir != '/')
							$this->createDirStructure(self::dirname($upload_path));
					}

					
					$rf = $this->UploadFile($local_file->GetLocalPath(), $upload_path );

					$expires = 0; // "make" the file remote
					$local_file->file_remote_uri = $this->GetFileUri($upload_path, $expires);
					$local_file->file_mtime = $rf->mtime;
					$local_file->SetRemoteSyncMeta($rf->rev, $this->id, $expires);
					$local_file->DBSave();

					@unlink($local_file->GetLocalPath());
				} catch(Exception $e) {
					$progress_reporter->LogException($e);
				}
			}
		}				
					
		
		WPFB_Sync::PrintDebugTrace("updating_uris");
		
		if(count($uri_updates) > 0) {
			$progress_reporter->Log(__('Updating URIs...',WPFB));
			$progress_reporter->InitProgress(count($uri_updates));
			$i = 0;
			foreach($uri_updates as $uu) { try {
				$this->RefreshDownloadUri($uu['file']);
				$progress_reporter->SetProgress(++$i);
			} catch(Exception $e) {
				$progress_reporter->LogError('Failed to refresh URI of '.$uu['file']->GetLocalPathRel());
				$progress_reporter->LogException($e);
			}
			}
		}
		$this->uris_invalidated = false;
		
		// delete files
		if(count($local_files_left) > 0)
		{
			$progress_reporter->Log(__('Removing local files that have been remotely deleted...',WPFB));
			$this->RemoveLocalFiles($local_files_left);
		}
		
		
		WPFB_Sync::PrintDebugTrace("rsync_post_loop");
		
		$this->CloseConnection();
		
		$this->progress_reporter = null;
		
		$this->last_sync_time = time();
		$this->Save();
		
		//if(!$batch) {
			wpfb_loadclass('Sync');
			WPFB_Sync::SyncCats($this->GetCat()->GetParents());
		//}
		
		$progress_reporter->Log('Done!');
	}
	
	final function RefreshDownloadUri($file, $connect=false)
	{
		$path = $this->remote_path.'/'.substr($file->GetLocalPathRel(), strlen($this->GetCat()->GetLocalPathRel()));
		$path = trim(str_replace('//','/',$path).'/');
		$expires = 0;
		if($connect) $this->OpenConnection (false);
		$file->file_remote_uri = $this->GetFileUri($path, $expires);
		if($connect) $this->CloseConnection ();
		$file->SetRemoteSyncMeta($file->GetRemoteSyncMeta()->rev, $this->id, $expires);
		$file->DBSave();
	}
	
	final function GetLocalFiles()
	{
		global $wpdb;
		return WPFB_File::GetFiles("LEFT JOIN $wpdb->wpfilebase_rsync_meta as rmeta ON (rmeta.`file_id` = `$wpdb->wpfilebase_files`.`file_id`) WHERE rmeta.rsync_id = '".esc_sql($this->id)."'");
	}
	
	final function RemoveLocalFiles($files)
	{
		foreach($files as $file)
		{
			//$parent = $file->GetParent();
			$file->Remove();
			/*while($parent && $parent->cat_num_files_total <= 0)
			{
				$parent->Delete(); // also remove category if left empty!
				$parent = $parent->GetParent();
			} */
		}
		
		// delete all empty cats
		if(($cat=$this->GetCat())) {
			$cats = $cat->GetChildCats(true);
			foreach($cats as $cat) {
				if($cat->cat_num_files_total <= 0)
					$cat->Delete();
			}
		}
	}
	
	final function DisplayEditForm() {
		?><h2><?php _e('Remote Sync Settings', WPFB) ?></h2><?php
		
		if($this->PrepareEditForm()) {	
		?>	
		<form action="<?php echo admin_url('admin.php?page='.$_GET['page']); ?>" method="post">
		<input type="hidden" name="rsync_id" value="<?php echo $this->id ?>" />
		<input type="hidden" name="action" value="edited-rsync" />
		<table class="form-table">
			<tr class="form-field">
				<th scope="row" valign="top"><label for="rsync-name"><?php _e('Name', WPFB) ?></label></th>
				<td width="100%">
					<input id="rsync-name" name="title" type="text" value="<?php echo esc_attr($this->title); ?>" />
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="rsync-cat"><?php _e('Category', WPFB) ?></label></th>
				<td width="100%">
					<select name="root_cat_id" id="rsync-cat" class="postform wpfb-cat-select">
					<?php echo WPFB_Output::CatSelTree(array('selected'=>$this->root_cat_id, 'add_cats' => true)) ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"></th>
				<td>
					<input id="rsync-no-scan" name="no_file_scan" type="checkbox" value="1" <?php checked($this->no_file_scan) ?> />
					<label for="rsync-no-scan"><?php _e('Don\'t generate thumbnails and scan files for ID3 tags. This will make sync much faster, since files are not temporarily downloaded.', WPFB) ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"></th>
				<td>
					<input id="rsync-remote-delete" name="remote_delete" type="checkbox" value="1" <?php checked(!@$this->no_remote_delete) ?> />
					<label for="rsync-remote-delete"><?php _e('Delete files from Cloud if removed locally', WPFB) ?></label>
				</td>
			</tr>
			<?php  if($this->IsReady()) { ?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="rsync-remote-path"><?php _e('Remote Path', WPFB) ?></label></th>
				<td width="100%">
					<input id="rsync-remote-path" name="remote_path" type="text" value="<?php echo esc_attr($this->remote_path); ?>" <?php disabled($this->last_sync_time > 0); ?> />
					<?php if(!$this->last_sync_time) { ?> <br />Enter the remote path to sync or select a directory below.<br />
					<div style="width: 300px; height: 200px; overflow: auto; border: 1px solid #ddd; background-color: #fff;"><?php $this->PrintBrowser('rsync-remote-path'); ?></div>
					<br /><a href="" id="rsync-browser-refresh">Refresh</a>
					<?php } ?> 
				</td>
			</tr>	
		<?php } $this->DisplayFormFields(); ?>
		</table>
		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr(__('Save Changes')); ?>" /></p>
		</form>
		<?php
		}		
	}
	
	final protected function PrintBrowser($path_input_id)
	{
		wp_print_scripts('jquery-treeview-async');
		wp_print_styles('jquery-treeview');
	?>
	
<ul id="rsync-browser" class="filetree">
</ul>
<ul class="treeview" id="filetree-loading" style="margin: 30px;"><li class="placeholder"></li></ul>
<script type="text/javascript">
//<![CDATA[
function initRsyncBrowser() {
	jQuery("#rsync-browser").empty().treeview({
		url: "<?php echo WPFB_Core::$ajax_url ?>",
		ajax: {
			data: { action: "rsync-browser", rsync_id: '<?php echo  $this->id; ?>', onclick: "selectDir('%s')", dirs_only: false },
			type: "post", complete: browserAjaxComplete,
		},
		animated: "medium"
	});
}

jQuery(document).ready(function(){
	initRsyncBrowser();	
	jQuery('#rsync-browser-refresh').click(function(e) {
		initRsyncBrowser();
		return false;
	});
});

function selectDir(path)
{
	jQuery('#<?php echo $path_input_id ?>').val(path);
}

function browserAjaxComplete(jqXHR, textStatus)
{
	jQuery('#filetree-loading').hide();
	if(textStatus != "success")
	{
		//alert("AJAX Request error: " + textStatus);
	}
}
//]]>
</script>
	<?php 
	}
	
	protected static function dirname($path) {
		$path = rtrim($path, '/');
		$p = strrpos($path, '/');
		if($p <= 0) return '/';
		return substr($path, 0, $p);
	}
	
	protected static function findBy($field_name, $field_value, $items)
	{
		$r = array_filter($items, create_function('$o', 'return $o'.(is_object(reset($items))?('->'.$field_name):('[\''.$field_name.'\']')).' == \''.$field_value.'\';'));
		return reset($r);
	}
}


class WPFB_RemoteFileInfo {
	var $path;
	var $size;
	var $mtime;
	var $rev;
	var $is_dir;
	
	var $display_name;
}

class WPFB_RemoteSyncMeta {
  var $file_id;
  var $rev;
  var $rsync_id;
  var $uri_expires;
  var $deleted_path;
}

class RemoteSyncException extends Exception {

	public function __construct($err = null, $isDebug = FALSE)
	{
		if(is_null($err)) {
			$el = error_get_last();
			$this->message = $el['message'];
			$this->file = $el['file'];
			$this->line = $el['line'];
		} else
			$this->message = $err;
		self::log_error($err);
		if ($isDebug)
		{
			self::display_error($err, TRUE);
		}
	}

	public static function log_error($err)
	{
		error_log($err, 0);
	}

	public static function display_error($err, $kill = FALSE)
	{
		print_r($err);
		if ($kill === FALSE)
		{
			die();
		}
	}
}
