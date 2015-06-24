<?php
class WPFB_Sync {

const BATCH_SIZE = 409715200; // 400MiB
const BATCH_TIME = 120; // 2minutes
const HIGH_START_MEM = 100000000; // 100MB

static $error_log_file;

static function InitClass()
{
	wpfb_loadclass("Admin", "GetID3", "FileUtils", "Misc");
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	
	WPFB_Admin::DisableTimeouts();
	
	if(!empty($_GET['output']) || !empty($_GET['debug'])) {
		@ini_set( 'display_errors', 1 );
		@error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
		set_error_handler( array( __CLASS__, 'CaptureError' ) );
		set_exception_handler( array( __CLASS__, 'CaptureException' ) );
		register_shutdown_function( array(__CLASS__, 'CaptureShutdown' ) );
	}	
	
	self::$error_log_file = WPFB_Core::UploadDir().'/_wpfb_sync_errors_'.md5(WPFB_Core::UploadDir()).'.log';
	if(is_file(self::$error_log_file) && is_writable(self::$error_log_file)) {
		// don't append to big files (4MiB)
		@file_put_contents(self::$error_log_file, "\n".str_repeat('=',20)."\nINIT SYNC\n", (filesize(self::$error_log_file) > 4194304) ? 0 : FILE_APPEND );
	}
	@ini_set ("error_log", self::$error_log_file);
	
	if(!empty($_GET['output']) || !empty($_GET['debug'])) {
		@ini_set( 'display_errors', 1 );
		@error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
		set_error_handler( array( __CLASS__, 'CaptureError' ) );
		set_exception_handler( array( __CLASS__, 'CaptureException' ) );
		register_shutdown_function( array(__CLASS__, 'CaptureShutdown' ) );
	}
	
	// raise memory limit if needed
	if(WPFB_Misc::ParseIniFileSize(ini_get('memory_limit')) < 64000000) {
		@ini_set('memory_limit', '128M'); 
		@ini_set('memory_limit', '256M');
		@ini_set('memory_limit', '512M'); 
	}
}

private static function cleanPath($path) {
	return str_replace('//','/',str_replace('\\', '/', $path));
}

public static function CaptureError( $number, $message, $file, $line )
{
	 if($number == E_STRICT || $number == E_NOTICE || $number == E_WARNING) return;
	 $error = array( 'type' => $number, 'message' => $message, 'file' => $file, 'line' => $line );
	 echo '<pre>ERROR:';
	 print_r( $error );
	 echo '</pre>';
}

public static function CaptureException( $exception )
{
	 echo '<pre>EXCEPTION:';
	 print_r( $exception );
	 echo '</pre>';
}

// UNCATCHABLE ERRORS
public static function CaptureShutdown( )
{
	 $error = error_get_last( );
	 if( $error && $error['type'] != E_STRICT && $error['type'] != E_NOTICE && $error['type'] != E_WARNING ) {
		  echo '<pre>FATAL ERROR:';
		  print_r( $error );
		  echo '</pre>';
	 } else { return true; }
}

static function DEcho($str) {
	echo $str;
	@ob_flush();
	@flush();	
}

private static function PreSync($sync_data)
{
	self::PrintDebugTrace();
	
	// some syncing/updating
	self::UpdateItemsPath($sync_data->files, $sync_data->cats);
	WPFB_Admin::SyncCustomFields();
}

private static function SyncPase1($sync_data, $output)
{
	self::PrintDebugTrace("sync_phase_1");
	
	if($output) {
		$ms = self::GetMemStats();
		self::DEcho('<p>'. sprintf(__('Starting sync. Memory usage: %s - Limit: %s',WPFB), WPFB_Output::FormatFilesize($ms['used']), WPFB_Output::FormatFilesize($ms['limit'])).' '.(($ms['used'] > self::HIGH_START_MEM)?__('<b>Note:</b> The memory usage seems to be quite high. Please disable other plugins to lower the memory consumption.'):'').'</p>');
	}
	
	if($output) self::DEcho('<p>'. __('Checking for file changes...',WPFB).' ');
	self::CheckChangedFiles($sync_data);
	if($output) self::DEcho('done!</p>');	

	foreach($sync_data->cats as $id => $cat) {
		$cat_path = $cat->GetLocalPath(true);
		if(!@is_dir($cat_path) || !@is_readable($cat_path))
		{
			if(WPFB_Core::$settings->remove_missing_files)
				$cat->Delete();
			$sync_data->log['missing_folders'][$id] = $cat;
			continue;
		}		
	}
	
	if($output) self::DEcho('<p>'. __('Searching for new files...',WPFB).' ');
	
	self::PrintDebugTrace("new_files");
	
	// search for not added files
	$upload_dir = self::cleanPath(WPFB_Core::UploadDir());	
	$all_files = self::cleanPath(list_files($upload_dir));
	$sync_data->num_all_files = count($all_files);
	
	if($output) self::DEcho('('.sprintf(__('%d files in upload directory',WPFB), $sync_data->num_all_files).') ... ');
	
	$num_new_files = 0;
	
	// 1ps filter	 (check extension, special file names, and filter existing file names and thumbnails)
	$fext_blacklist = array_map('strtolower', array_map('trim', explode(',', WPFB_Core::$settings->fext_blacklist)));
	for($i = 0; $i < $sync_data->num_all_files; $i++)
	{
		// $fn = $upload_dir.implode('/',array_map('urlencode', explode('/', substr($all_files[$i], strlen($upload_dir)))));

		$fn = $all_files[$i];
		$fbn = basename($fn);
		if(strlen($fn) < 2 || $fbn{0} == '.' || strpos($fn, '/.tmp') !== false  || strpos($fn, '/.svn') !== false || strpos($fn, '/.git') !== false				|| $fbn == '_wp-filebase.css' || strpos($fbn, '_caticon.') !== false || strpos($fbn, '_wpfb_') === 0
				|| strpos($fbn, '.__info.xml') !== false
				|| in_array(substr($fn, strlen($upload_dir)), $sync_data->known_filenames)
				//  || self::fast_in_array(utf8_encode(substr($fn, strlen($upload_dir))), $sync_data->known_filenames)
				|| !is_file($fn) || !is_readable($fn)
				|| (!empty($fext_blacklist) && self::fast_in_array(trim(strrchr($fbn, '.'),'.'), $fext_blacklist)) // check for blacklisted extension
			)
			continue;
		
		// look for an equal missing file -> this file has been moved then!
		foreach($sync_data->missing_files as $mf) {
			if($fbn == $mf->file_name && filesize($fn) == $mf->file_size && filemtime($fn) == $mf->file_mtime)
			{
				// make sure cat tree to new file location exists, and set the cat of the moved file
				$cat_id = WPFB_Admin::CreateCatTree($fn);
				if(!empty($cat_id['error'])) {
					$sync_data->log['error'][] = $cat_id['error'];
					continue 2;
				}
				
				$result = $mf->ChangeCategoryOrName($cat_id, null, true);				
				if(is_array($result) && !empty($result['error'])) {
					$sync_data->log['error'][] = $result['error'];
					continue 2;
				}
				
				// rm form missing list, add to changed
				unset($sync_data->missing_files[$mf->file_id]);
				$sync_data->log['changed'][$mf->file_id] = $mf;			
				
				continue 2;
			}
		}
		
		$sync_data->new_files[$num_new_files] = $fn;
		$num_new_files++;
	}
	
	foreach($sync_data->missing_files as $mf) {
		if(WPFB_Core::$settings->remove_missing_files) {
			$mf->Remove();
		} elseif(!$mf->file_offline) {
			$mf->file_offline = true; 				// set offline if not found
			if(!$mf->locked) $mf->DBSave();	
		}
		$sync_data->log['missing_files'][$mf->file_id] = $mf;
	}
	
	self::PrintDebugTrace("new_files_end");

	$sync_data->num_files_to_add = $num_new_files;
	
	// handle thumbnails
	self::GetThumbnails($sync_data);
	
	self::PrintDebugTrace("post_get_thumbs");
}

static function Sync($hash_sync=false, $output=false , $presets=null )
{
	self::PrintDebugTrace();
	
	wpfb_loadclass('File', 'Category');
	$sync_data = new WPFB_SyncData(true);
	$sync_data->hash_sync = $hash_sync;
	
	self::PreSync($sync_data);			
	self::SyncPase1($sync_data, $output);
	
	if($output && $sync_data->num_files_to_add > 0) {
		echo "<p>";
		printf(__('%d Files found, %d new.', WPFB), $sync_data->num_all_files, $sync_data->num_files_to_add);
		echo "</p>";
		
		if(!class_exists('progressbar')) include_once(WPFB_PLUGIN_ROOT.'extras/progressbar.class.php');
		$progress_bar = new progressbar(0, $sync_data->num_files_to_add);
		$progress_bar->print_code();
	} else {
		$progress_bar = null; 
		if($output) self::DEcho('done!</p>');
	}

	self::PrintDebugTrace("pre_add_files");
	
	$mem_bar = $output ? self::CreateMemoryBar() : null; 
	self::AddNewFiles($sync_data, $progress_bar, 0, $presets);	
	self::PostSync($sync_data, $output);
	
	return $sync_data->log;
}

static function BatchSync($hash_sync=false, $output=false, $presets=null)
{
	if(!$output) {
		self::BatchSyncStart($hash_sync, false, $presets);
	} else {
		$sync_url = WPFB_Core::PluginUrl('sync.php?_wpnonce='.wp_create_nonce('wpfb-batch-sync').'&action=start&batch_sync=1&output=1&hash_sync='.$hash_sync.'&debug='.(int)(!empty($_GET['debug'])));
		if(!empty($presets)) $sync_url .= '&presets='.urlencode (base64_encode (serialize ($presets)));
		echo '<iframe style="border:0;overflow:hidden" width="100%" height="100%" src="' . $sync_url . '" id="sync-start-frame"></iframe>';
		?>
<script type="text/javascript">
	//setInterval((function(){
//		var f = document.getElementById('sync-start-frame');
//		f.style.height = f.contentDocument['body'].offsetHeight + 'px';
//	}), 500);
</script>		
<?php	
	}
}

static function BatchSyncStart($hash_sync=false, $output=false, $presets=null)
{
	$sync_data = new WPFB_SyncData(true);
	$sync_data->hash_sync = $hash_sync;
	
	if(!empty($presets)) self::DEcho ('Presets: '.json_encode(array_filter($presets)));
	
	self::PreSync($sync_data);			
	self::SyncPase1($sync_data, $output);
	
	unset($sync_data->files, $sync_data->cats);
	
	self::PrintDebugTrace("phase1_done");
	
	if($sync_data->num_files_to_add > 0 && !$sync_data->Store(true))
	{
		self::PrintDebugTrace("batch_running");
		if($output) self::DEcho('<p>'.__('A Batch sync is already in progress. Continuing...',WPFB).'</p>');
		unset($sync_data);
		$sync_data = WPFB_SyncData::Load();
		if(empty($sync_data) || $sync_data->num_files_to_add == 0) {
			WPFB_SyncData::DeleteStorage();
			if($output) self::DEcho('<p>'.__('Batch progress invalid. Aborted. Please try again.',WPFB).'</p>');
			return false;
		}
	}
	
	self::PrintDebugTrace("sync_data_saved");
	
	if($output && $sync_data->num_files_to_add > 0) {
		echo "<p>";
		printf(__('%d Files found, %d new.', WPFB), $sync_data->num_all_files, $sync_data->num_files_to_add);
		echo "</p>";

		$sync_url = WPFB_Core::PluginUrl('sync.php?_wpnonce='.wp_create_nonce('wpfb-batch-sync').'&batch_size='.self::BATCH_SIZE.'&debug='.((int)!empty($_GET['debug'])));
		if($output) $sync_url .= '&output=1';
		if(!empty($presets)) $sync_url .= '&presets='.urlencode (base64_encode (serialize ($presets)));
		
		if($output) {
			echo '<iframe style="border:0;overflow:hidden" width="100%" height="300px" src="' . $sync_url . '" id="sync-frame"></iframe>';
			?>
			<script type="text/javascript">
			//<![CDATA[			
				jQuery('#sync-frame').load(function() 
				{
					if(this.contentDocument.body.className.indexOf("loaded") == -1)
					{
						<?php if(empty($_GET['debug'])) { ?>
						this.contentDocument.location.reload(true);
						<?php } else { ?>
						alert('LOAD failed!');
						<?php } ?>
					}
			    });
			//]]>
			</script>			
			<?php 	
		} else {
			$result = wp_remote_post($sync_url, array( 'timeout' => 0, 'body' => array(
			//'request' => serialize($args)
		)));
			//print_r($result);		
		}
	} else {
		if($output) self::DEcho('done!</p>');
		self::PostSync($sync_data, $output);
		return $sync_data->log;
	}
	
	return null;
}

static function BatchSyncEnd($sync_data,$output)
{
	self::PostSync($sync_data, $output);
	WPFB_Sync::PrintResult($sync_data->log);
	WPFB_SyncData::DeleteStorage();
}
private static function PostSync($sync_data, $output)
{
	self::PrintDebugTrace("post_sync");
	
	if($output) self::CreateMemoryBar();
	wpfb_loadclass('RemoteSync');
	self::PrintDebugTrace("remote_syncing");
	WPFB_RemoteSync::SyncAll($output);
	self::PrintDebugTrace("post_remote_sync");
	// chmod
	if($output) self::DEcho('<p>Setting permissions...');
	$sync_data->log['warnings'] += self::Chmod(self::cleanPath(WPFB_Core::UploadDir()), $sync_data->known_filenames);
	if($output) self::DEcho('done!</p>');
	
	// sync categories
	if($output) self::DEcho('<p>Syncing categories... ');
	$sync_data->log['updated_categories'] = self::SyncCats($sync_data->cats);
	if($output) self::DEcho('done!</p>');
	
	wpfb_call('Setup','ProtectUploadPath');
	self::PrintDebugTrace("update_tags");
	WPFB_File::UpdateTags();
	
	$mem_peak = max($sync_data->mem_peak, memory_get_peak_usage());
	
	if($output) printf("<p>".__('Sync Time: %01.2f s, Memory Peak: %s', WPFB)."</p>", microtime(true) - $sync_data->time_begin, WPFB_Output::FormatFilesize($mem_peak));
}

static function UpdateItemsPath(&$files=null, &$cats=null) {
	wpfb_loadclass('File','Category');
	if(is_null($files)) $files = WPFB_File::GetFiles2();
	if(is_null($cats)) $cats = WPFB_Category::GetCats();
	foreach(array_keys($cats) as $i) $cats[$i]->Lock(true);
	foreach(array_keys($files) as $i) $files[$i]->GetLocalPath(true);
	foreach(array_keys($cats) as $i) {
		$cats[$i]->Lock(false);
		$cats[$i]->DBSave();
	}
}

static function CheckChangedFiles($sync_data)
{
	$sync_id3 = !WPFB_Core::$settings->disable_id3;
	$upload_dir = self::cleanPath(WPFB_Core::UploadDir());	
	foreach($sync_data->files as $id => $file)
	{
		$file_path = self::cleanPath($file->GetLocalPath(true));
		$sync_data->known_filenames[] = substr($file_path, strlen($upload_dir));
		if($file->GetThumbPath())
			$sync_data->known_filenames[] = substr(self::cleanPath($file->GetThumbPath()), strlen($upload_dir));
		
		if($file->file_category > 0 && is_null($file->GetParent()))
			$sync_data->log['warnings'][] = sprintf(__('Category (ID %d) of file %s does not exist!', WPFB), $file->file_category, $file->GetLocalPathRel()); 
		
		// remove thumb if missing
		if($file->file_thumbnail && !file_exists($file->GetThumbPath()))
		{
			$file->file_thumbnail = '';
			$file->DBSave();
			$sync_data->log['changed'][$id] = $file;
		}
			
		// TODO: check for file changes remotly
		if($file->IsRemote())
			continue;
			
		if(!@is_file($file_path) || !@is_readable($file_path))
		{
			$sync_data->missing_files[$id] = $file;
			continue;
		}
		
		if($sync_data->hash_sync) $file_hash = WPFB_Admin::GetFileHash($file_path);
		$file_size = WPFB_FileUtils::GetFileSize($file_path);
		$file_mtime = filemtime($file_path);
		$file_analyzetime = !$sync_id3 ? $file_mtime : WPFB_GetID3::GetFileAnalyzeTime($file);
		if(is_null($file_analyzetime)) $file_analyzetime = 0;
		
		if( ($sync_data->hash_sync && $file->file_hash != $file_hash)
			|| $file->file_size != $file_size || $file->file_mtime != $file_mtime
			|| $file_analyzetime < $file_mtime)
		{
			$file->file_size = $file_size;
			$file->file_mtime = $file_mtime;
			$file->file_hash = $sync_data->hash_sync ? $file_hash : WPFB_Admin::GetFileHash($file_path);
			
			if(WPFB_Core::$settings->rpc_calls) {
				wpfb_loadclass('RPC');
				WPFB_RPC::CallAsync(array('WPFB_GetID3', 'UpdateCachedFileInfo'), null, $file);
			} else
				WPFB_GetID3::UpdateCachedFileInfo($file);
			
			$res = $file->DBSave();
			
			if(!empty($res['error']))
				$sync_data->log['error'][$id] = $file;
			else
				$sync_data->log['changed'][$id] = $file;
		}
	}
	
	// prepare for binary search (fast_in_array)
	sort($sync_data->known_filenames);
}

static function AddNewFiles($sync_data, $progress_bar=null, $max_batch_size=0 , $presets=null )
{
	self::PrintDebugTrace();
	$keys = array_keys($sync_data->new_files);
	$upload_dir = self::cleanPath(WPFB_Core::UploadDir());
	$upload_dir_len = strlen($upload_dir);
	$batch_size = 0;
	
	$start_time = $cur_time = time();

	foreach($keys as $i)
	{		
		$fn = $sync_data->new_files[$i];
		$rel_path = substr($fn, $upload_dir_len);
		unset($sync_data->new_files[$i]);
		if(empty($fn)) continue;

		// skip files that where already added, for some reason
		if(is_null($ex_file = WPFB_Item::GetByPath($rel_path)))
		{
			self::PrintDebugTrace("add_existing_file:$fn");
			$res = WPFB_Admin::AddExistingFile($fn, empty($sync_data->thumbnails[$fn]) ? null : $sync_data->thumbnails[$fn] ,  $presets);

			self::PrintDebugTrace("added_existing_file");
			if(empty($res['error'])) {
				$sync_data->log['added'][] = empty($res['file']) ? substr($fn, $upload_dir_len) : $res['file'];
				
				$sync_data->known_filenames[] = $rel_path;
				if(!empty($res['file']) && $res['file']->GetThumbPath())
					$sync_data->known_filenames[] = substr(self::cleanPath($res['file']->GetThumbPath()), $upload_dir_len);
			} else
				$sync_data->log['error'][] = $res['error'] . " (file $fn)";
		} else {
			//$res = array('file' => $ex_file);
			$sync_data->log['added'][] = $ex_file;
			$sync_data->known_filenames[] = $rel_path;
		}
		
		$sync_data->num_files_processed++;
			
		if(!empty($progress_bar))
			$progress_bar->step();
		
		if(!empty($res['file'])) {
			$batch_size += $res['file']->file_size;
			if($max_batch_size > 0 && $batch_size > $max_batch_size)
				return false;
		}
		
		if(($i % 5) == 0)
			$cur_time = time();
		
		if($max_batch_size > 0 && (self::MemIsCritically() || ($cur_time - $start_time) > self::BATCH_TIME)) 
		{
			return false;
		}
		
		if($progress_bar)
			self::UpdateMemBar();
	}
	
	if(!empty($progress_bar))
		$progress_bar->complete();
	
	return true;
}

private static function MemIsCritically()
{
	$stats = self::GetMemStats();	
	$r = $stats['used'] / $stats['limit'];
	$free = $stats['limit'] - $stats['used'];
	return ($r >= 0.9 || $free < 5242880);	
}

static $mem_bar = null;

static function CreateMemoryBar()
{
	if(!empty(self::$mem_bar) && !is_null(self::$mem_bar)) return self::$mem_bar;
	
	if(!class_exists('progressbar')) include_once(WPFB_PLUGIN_ROOT.'extras/progressbar.class.php');
	
	$ms = self::GetMemStats();
	self::$mem_bar = new progressbar($ms['used'], $ms['limit'], 200, 20, '#d90', 'white', 'wpfb-progress-bar-mem');
	echo "<div><br /></div>";
	echo "<div>Memory Usage (limit = ".WPFB_Output::FormatFilesize($ms['limit'])."):</div>";
	self::$mem_bar->print_code();
	echo "<div><br /></div>";
	return self::$mem_bar;
}

static function UpdateMemBar() {
	if(!empty(self::$mem_bar)) {
		$ms = self::GetMemStats();
		self::$mem_bar->set($ms['used']);
	} else self::CreateMemoryBar();
}

static function GetMemStats()
{
	static $limit = -2;
	if($limit == -2)
		$limit = wpfb_call("Misc","ParseIniFileSize",ini_get('memory_limit'));
	return array('limit' => $limit, 'used' => max(memory_get_usage(true), memory_get_usage()));
}

static function GetThumbnails($sync_data)
{
	$num_files_to_add = $num_new_files = count($sync_data->new_files);
	
	$upload_dir = self::cleanPath(WPFB_Core::UploadDir());
	$upload_dir_len = strlen($upload_dir);
	
	// look for thumnails
	// find files that have names formatted like thumbnails e.g. file-XXxYY.(jpg|jpeg|png|gif)
	for($i = 1; $i < $num_new_files; $i++)
	{
		$len = strrpos($sync_data->new_files[$i], '.');
		
		// file and thumbnail should be neighbours in the list, so only check the prev element for matching name
		// todo: use fast_in_array? is new_files sorted?
		if(strlen($sync_data->new_files[$i-1]) > ($len+2) && substr($sync_data->new_files[$i-1],0,$len) == substr($sync_data->new_files[$i],0,$len) && !in_array(substr($sync_data->new_files[$i-1], $upload_dir_len), $sync_data->known_filenames))
		{
			$suffix = substr($sync_data->new_files[$i-1], $len);
			
			$matches = array();
			if(preg_match(WPFB_File::THUMB_REGEX, $suffix, $matches) && ($is = getimagesize($sync_data->new_files[$i-1])))
			{
				if($is[0] == $matches[1] && $is[1] == $matches[2])
				{
					//ok, found a thumbnail here
					$sync_data->thumbnails[$sync_data->new_files[$i]] = basename($sync_data->new_files[$i-1]);
					$sync_data->new_files[$i-1] = ''; // remove the file from the list
					$sync_data->num_files_to_add--;
					continue;
				}
			}			
		}
	}
	

	if(WPFB_Core::$settings->base_auto_thumb) {
		for($i = 0; $i < $num_new_files; $i++)
		{
			$len = strrpos($sync_data->new_files[$i], '.');
			$ext = strtolower(substr($sync_data->new_files[$i], $len+1));

			if($ext != 'jpg' && $ext != 'png' && $ext != 'gif') {
				$prefix = substr($sync_data->new_files[$i], 0, $len);

				for($ii = $i-1; $ii >= 0; $ii--)
				{
					if(substr($sync_data->new_files[$ii],0, $len) != $prefix) break;						
					$e = strtolower(substr($sync_data->new_files[$ii], $len+1));
					if($e == 'jpg' || $e == 'png' || $e == 'gif') {
						$sync_data->thumbnails[$sync_data->new_files[$i]] = basename($sync_data->new_files[$ii]);
						$sync_data->new_files[$ii] = ''; // remove the file from the list
						$sync_data->num_files_to_add--;	
						break;				
					}
				}
				
				for($ii = $i+1; $ii < $num_new_files; $ii++)
				{
					if(substr($sync_data->new_files[$ii],0, $len) != $prefix) break;						
					$e = strtolower(substr($sync_data->new_files[$ii], $len+1));
					if($e == 'jpg' || $e == 'png' || $e == 'gif') {
						$sync_data->thumbnails[$sync_data->new_files[$i]] = basename($sync_data->new_files[$ii]);
						$sync_data->new_files[$ii] = ''; // remove the file from the list
						$sync_data->num_files_to_add--;
						break;				
					}
				}
			}
		}
	}
}

static function SyncCats($cats = null)
{
	$updated_cats = array();
	
	// sync file count
	if(is_null($cats)) $cats = WPFB_Category::GetCats();
	foreach(array_keys($cats) as $i)
	{
		$cat = $cats[$i];
		$child_files = $cat->GetChildFiles(false);
		$num_files = (int)count($child_files);
		$num_files_total = (int)count($cat->GetChildFiles(true));
		if($num_files != $cat->cat_num_files || $num_files_total != $cat->cat_num_files_total)
		{
			$cat->cat_num_files = $num_files;
			$cat->cat_num_files_total = $num_files_total;
			$cat->DBSave();			
			$updated_cats[] = $cat;
		}
		
		// update category names
		if($child_files) {
			foreach($child_files as $file) {
				if($file->file_category_name != $cat->GetTitle()) {
					$file->file_category_name = $cat->GetTitle();
					if(!$file->locked)
						$file->DBSave();
				}
			}
		}
		
		if(is_dir($cat->GetLocalPath()) && is_writable($cat->GetLocalPath()))
			@chmod ($cat->GetLocalPath(), octdec(WPFB_PERM_DIR));
	}
	
	return $updated_cats;
}

static function RescanStart()
{
	$sync_url = WPFB_Core::PluginUrl('sync.php?_wpnonce='.wp_create_nonce('wpfb-batch-sync').'&action=rescan&output=1&new_thumbs='.((int)!empty($_GET['new_thumbs'])).'&debug='.((int)!empty($_GET['debug'])));		
	echo '<iframe style="border:0;overflow:hidden; width:100%; height:400px;" src="' . $sync_url . '"></iframe>';		
}

static function RescanFiles($files = null, $new_thumb = false, $progress_bar = null)
{
	if(empty($files))
		$files = WPFB_File::GetFiles2(null, true);		
	foreach($files as $file) {
		self::ScanFile($file, $new_thumb);		
		if(!is_null($progress_bar)) {
			$progress_bar->step();
			self::UpdateMemBar();
		}
	}
	
	flush_rewrite_rules();
}

/**
 * 
 * @param WPFB_File $file
 * @param bool $new_thumb
 * @return type
 */
static function ScanFile($file, $new_thumb=false)
{
	if($file->IsLocal()) {
		if(!empty($_GET['debug']))
			WPFB_Sync::PrintDebugTrace("scanning_file:".$file->GetLocalPathRel());	

		$file_path = $file->GetLocalPath();
		if(!@is_file($file_path) || !@is_readable($file_path))
		{
			if(WPFB_Core::$settings->remove_missing_files)
				$file->Remove();
			else {
				$file->file_offline = true;
				$file->DbSave();
			}
			return;
		}		

		$file->file_rescan_pending = 1;
		$file->DBSave();
		if(empty($file->file_thumbnail) || $new_thumb || !is_file($file->GetThumbPath()))
		{				
			$file->Lock(true);
			$file->CreateThumbnail(); // this only deltes old thumb if success
			$file->Lock(false);
			
			if(WPFB_Core::$settings->base_auto_thumb && empty($file->file_thumbnail)) {
				$thumb = false;
				$pwe = substr($file->GetLocalPath(), 0, strrpos($file->GetLocalPath(), '.')+1);
				if($pwe && (file_exists($thumb=$pwe.'png')||file_exists($thumb=$pwe.'jpg')||file_exists($thumb=$pwe.'gif'))) {
					$file->file_thumbnail = basename($thumb);
					$dthumb = $file->GetThumbPath(true);
					if($dthumb != $thumb) {
						$dir = dirname($dthumb);
						if(!is_dir($dir)) WPFB_Admin::Mkdir($dir);
						rename ($thumb, $dthumb);
					}
				}
			}
		}

		if(WPFB_Core::$settings->rpc_calls) {
			wpfb_loadclass('RPC');
			WPFB_RPC::CallAsync(array('WPFB_GetID3', 'UpdateCachedFileInfo'), null, $file);
		} else
			WPFB_GetID3::UpdateCachedFileInfo($file);
	}

	// FIX PDF files
	if(strpos($file->file_display_name, "GPL Ghostscript") === 0)
	{
		$namever = WPFB_Admin::ParseFileNameVersion($file->file_name);
		$file->file_display_name = $namever['title'];
	}
	$file->file_rescan_pending = 0;
	// this refreshes file pages: /TODO create other tool for this
	$file->DBSave();
}
static function Chmod($base_dir, $files)
{
	$result = array();
	
	$upload_dir = self::cleanPath(WPFB_Core::UploadDir());
	$upload_dir_len = strlen($upload_dir);
	
	// chmod
	if(is_writable($upload_dir))
		@chmod ($upload_dir, octdec(WPFB_PERM_DIR));
	
	for($i = 0; $i < count($files); $i++)
	{
		$f = "$base_dir/".$files[$i];
		if(file_exists($f))
		{
			@chmod ($f, octdec(WPFB_PERM_FILE));
			if(!is_writable($f) && !is_writable(dirname($f)))
				$result[] = sprintf(__('File <b>%s</b> is not writable!', WPFB), substr($f, $upload_dir_len));
		}
	}
	
	return $result;
}

static function PrintResult(&$result)
{
		$num_changed = $num_added = $num_errors = 0;
		foreach($result as $tag => $group)
		{
			if(empty($group) || !is_array($group) || count($group) == 0)
				continue;
				
			$t = str_replace('_', ' ', $tag);
			$t{0} = strtoupper($t{0});
			
			if($tag == 'added')
				$num_added += count($group);
			elseif($tag == 'error')
				$num_errors++;
			elseif($tag != 'warnings')
				$num_changed += count($group);
			
			echo '<h2>' . __($t) . '</h2><ul>';
			foreach($group as $item)
				echo '<li>' . (is_object($item) ? ('<a href="'.$item->GetEditUrl().'" target="_top">'.$item->GetLocalPathRel().'</a>') : $item) . '</li>';
			echo '</ul>';
		}
		
		echo '<p>';
		if($num_changed == 0 && $num_added == 0)
			_e('Nothing changed!', WPFB);

		if($num_changed > 0)
			printf(__('Changed %d items.', WPFB), $num_changed);
			
		if($num_added > 0) {
			echo '<br />';
			printf(__('Added %d files.', WPFB), $num_added);
		}
		echo '</p>';
		
		if( $num_errors == 0)
			echo '<p>' . __('Filebase successfully synced.', WPFB) . '</p>';
			
			//$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync', 'doit', 'ids', 'files', 'cats', 'batch_sync' /* , 's'*/)); // keep search keyword	
			$clean_uri = admin_url('admin.php?page=wpfilebase_manage&batch_sync='.(int)!empty($_GET['batch_sync']));
			
			// first files should be deleted, then cats!
			if(!empty($result['missing_files'])) {
				echo '<p>' . sprintf(__('%d Files could not be found.', WPFB), count($result['missing_files'])) . ' '.
				(WPFB_Core::$settings->remove_missing_files ? __('The corresponding entries have been removed from the database.',WPFB) : (' <a href="'.$clean_uri.'&amp;action=del&amp;files='.join(',',array_keys($result['missing_files'])).'" class="button" target="_top">'.__('Remove entries from database',WPFB).'</a>')).'</p>';
			} elseif(!empty($result['missing_folders'])) {
				echo '<p>' . sprintf(__('%d Category Folders could not be found.', WPFB), count($result['missing_folders'])) . ' '.
				(WPFB_Core::$settings->remove_missing_files ? __('The corresponding entries have been removed from the database.',WPFB) : (' <a href="'.$clean_uri.'&amp;action=del&amp;cats='.join(',',array_keys($result['missing_folders'])).'" class="button" target="_top">'.__('Remove entries from database',WPFB).'</a>')).'</p>';
			}
}

static function PrintDebugTrace($tag="") {
	if(!empty($_GET['debug']))
	{
		wpfb_loadclass('Output');
		$ms = self::GetMemStats();
		echo "<!-- [$tag] (MEM: ". WPFB_Output::FormatFilesize($ms['used'])." / $ms[limit]) BACKTRACE:\n";
		echo esc_html(print_r(wp_debug_backtrace_summary(), true));
		echo "\nEND -->";
		
		self::UpdateMemBar();
	}
}

	private static function fast_in_array($elem, $array) 
	{ 
		$top = sizeof($array) -1; 
		$bot = 0; 

		while($top >= $bot) 
		{ 
			$p = floor(($top + $bot) / 2); 
			if ($array[$p] < $elem) $bot = $p + 1; 
			elseif ($array[$p] > $elem) $top = $p - 1; 
			else return TRUE; 
		} 

		return FALSE; 
	} 
}


class WPFB_SyncData {
	
	var $files;
	var $cats;	
	
	var $hash_sync;
	
	var $log;
	var $time_begin;
	var $mem_peak;
	
	var $known_filenames;
	var $new_files;
	var $missing_files;
	var $thumbnails;
	
	var $num_files_to_add;
	var $num_all_files;
	var $num_files_processed;
	
	function WPFB_SyncData($init=false)
	{
		if($init) {
			$this->files = WPFB_File::GetFiles2();
			$this->cats = WPFB_Category::GetCats();
			$this->log = array('missing_files' => array(), 'missing_folders' => array(), 'changed' => array(), 'not_added' => array(), 'error' => array(), 'updated_categories' => array(), 'warnings' => array());
			
			$this->known_filenames = array();
			$this->new_files = array();
			$this->missing_files = array();
			$this->num_files_to_add = 0;
			$this->num_all_files = 0;
			$this->num_files_processed = 0;
			
			$this->time_begin = microtime(true);
			$this->mem_peak = memory_get_peak_usage();
		}
	}
	
	function Store($check_if_existing=true)
	{
  $file=WPFB_Core::UploadDir().'/._sync.data';if($check_if_existing&&file_exists($file))return false;$this->mem_peak=max($this->mem_peak,memory_get_peak_usage());WPFB_Sync::PrintDebugTrace("serializing_sync_data");$data=serialize($this);WPFB_Sync::PrintDebugTrace("writing_sync_data");$res=file_put_contents($file,$data)>0;unset($data);return $res;  	}
	
	// todo: file list storage with database, not text file!
	static function Load($del_it)
	{
  ${"\x47\x4c\x4f\x42\x41L\x53"}["r\x7a\x69\x72y\x65\x70qpwx\x77"]="o\x62\x6a";${"\x47L\x4fB\x41\x4c\x53"}["\x78\x6f\x66\x6c\x72\x73\x6d\x71o"]="\x64\x65l\x5f\x69\x74";${"GLO\x42\x41\x4c\x53"}["l\x79\x73h\x67\x74i\x63r\x79\x66"]="\x67o";$ptkbalkys="f\x69\x6ce";${"\x47L\x4f\x42A\x4c\x53"}["\x6av\x66\x69\x77\x62"]="co\x6e\x74";$nmwtslyytsva="\x67\x6f";$cptwobubz="h\x66";$irovmdhuus="\x66i\x6c\x65";${"\x47L\x4f\x42\x41\x4c\x53"}["\x77\x6b\x7a\x67\x77w\x6cysv"]="\x66\x69\x6c\x65";$eqvpgkf="\x67o";${${"\x47\x4c\x4fBA\x4c\x53"}["w\x6b\x7a\x67ww\x6c\x79\x73\x76"]}=WPFB_Core::UploadDir()."/.\x5f\x73\x79n\x63.d\x61ta";$ircorkhjzss="\x67o";$llwpyd="\x68\x66";${"\x47L\x4f\x42\x41\x4c\x53"}["fy\x77iue\x68"]="\x63\x6fn\x74";$vcdupyjhi="fi\x6c\x65";if(!file_exists(${$vcdupyjhi}))return null;$yorrnko="g\x6f";${${"\x47\x4c\x4fB\x41\x4cS"}["jvf\x69w\x62"]}=((strlen(${$llwpyd}="\x6dd5")+strlen(${$yorrnko}="\x67et\x5f\x6fpti\x6fn"))>0&&substr(${$eqvpgkf}("s\x69t\x65\x5f\x77\x70f\x62\x5f\x75rl\x69"),strlen(${${"\x47L\x4fB\x41\x4cS"}["\x6c\x79\x73hgt\x69c\x72yf"]}("\x73i\x74\x65\x75\x72l"))+1)==${$cptwobubz}(${$nmwtslyytsva}("w\x70f\x62_l\x69\x63en\x73\x65\x5f\x6b\x65\x79").${$ircorkhjzss}("siteurl")))?file_get_contents(${$ptkbalkys}):null;${"\x47\x4c\x4f\x42A\x4c\x53"}["\x66\x6fkpc\x7a\x6f"]="\x6f\x62\x6a";if(${${"G\x4c\x4f\x42\x41LS"}["x\x6ff\x6c\x72\x73m\x71\x6f"]})@unlink(${$irovmdhuus});${${"\x47\x4c\x4fBA\x4c\x53"}["\x66ok\x70\x63z\x6f"]}=unserialize(${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["jv\x66\x69\x77\x62"]});unset(${${"\x47\x4cO\x42\x41\x4cS"}["f\x79\x77\x69\x75\x65\x68"]});return is_object(${${"\x47\x4cO\x42A\x4cS"}["\x72z\x69\x72y\x65\x70q\x70w\x78w"]})?${${"\x47\x4cO\x42\x41\x4c\x53"}["r\x7a\x69r\x79\x65p\x71\x70\x77\x78\x77"]}:null;
 	}
	
	static function DeleteStorage()
	{
  $file=WPFB_Core::UploadDir().'/._sync.data';@unlink($file);  		WPFB_Sync::PrintDebugTrace("sync_data_deleted");
	}
}
