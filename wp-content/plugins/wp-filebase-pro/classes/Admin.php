<?php
class WPFB_Admin {

static $MIN_SIZE_FOR_PROGRESSBAR = 2097152;//2MiB
const MAX_USERS_PER_ROLE_DISPLAY = 50;

static function InitClass()
{
	wpfb_loadclass('AdminLite', 'Item', 'File', 'Category','FileUtils');
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script(WPFB.'-admin', WPFB_PLUGIN_URI.'js/admin.js', array(), WPFB_VERSION);	

	wp_enqueue_style('widgets');

	require_once(ABSPATH . 'wp-admin/includes/file.php');	
	
	// make sure that either wp-filebase or wp-filebase pro is enabled bot not both!
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}	
			deactivate_plugins('wp-filebase/wp-filebase.php');	
	
	if(!empty($_GET['action']) && $_GET['action'] === 'install-extensions')
		add_thickbox ();
}

static function SettingsSchema() { return wpfb_call('Settings','Schema'); }

static function InsertCategory($catarr)
{	
	$catarr = wp_parse_args($catarr, array('cat_id' => 0, 'cat_name' => '', 'cat_description' => '', 'cat_parent' => 0, 'cat_folder' => '', 'cat_order' => 0));
	extract($catarr, EXTR_SKIP);
	$data = (object)$catarr;

	$cat_id = intval($cat_id);
	$cat_parent = intval($cat_parent);
	$update = ($cat_id > 0); // update or creating??
	$add_existing = !empty($add_existing);
	$cat = $update ? WPFB_Category::GetCat($cat_id) : new WPFB_Category(array('cat_id' => 0));
	$cat->Lock(true);
	
	// some validation
	if (empty($cat_name) && empty($cat_folder)) return array( 'error' => __('You must enter a category name or a folder name.', WPFB) );
	if(!$add_existing && !empty($cat_folder) && (!$update || $cat_folder != $cat->cat_folder) ) {
		$cat_folder = preg_replace('/\s/', ' ', $cat_folder);
		if(!preg_match('/^[0-9a-z-_.+,\'\s()]+$/i', $cat_folder)) return array( 'error' => __('The category folder name contains invalid characters.', WPFB) );	
	}
	wpfb_loadclass('Output');
	if (empty($cat_name)) $cat_name = WPFB_Core::$settings->no_name_formatting ? $cat_folder : WPFB_Output::Filename2Title($cat_folder, false);
	elseif(empty($cat_folder)) $cat_folder = strtolower(str_replace(' ', '_', $cat_name));
	

	$cat->cat_name = trim($cat_name);
	$cat->cat_description = trim($cat_description);
	$cat->cat_exclude_browser = (int)!empty($cat_exclude_browser);
	$cat->cat_order = intval($cat_order);
		
	// handle parent cat
	if($cat_parent <= 0 || $cat_parent == $cat_id) {
		$cat_parent = 0;
		$pcat = null;
	} else {
		$pcat = WPFB_Category::GetCat($cat_parent);
		if($pcat == null || ($update && $cat->IsAncestorOf($pcat))) $cat_parent = $cat->cat_parent;
	}
	
	// this will (eventually) inherit permissions:
	$result = $cat->ChangeCategoryOrName($cat_parent, $cat_folder, $add_existing);
	if(is_array($result) && !empty($result['error']))
		return $result;

	// explicitly set permissions:
	if(!empty($data->cat_perm_explicit) && isset($data->cat_user_roles))
		$cat->SetReadPermissions((empty($data->cat_user_roles) || count(array_filter($data->cat_user_roles)) == 0) ? array() : $data->cat_user_roles);		
	
	// explicitly set permissions:
	
	if(!empty($data->cat_upload_permissions))
		$data->cat_upload_permissions = array_filter($data->cat_upload_permissions);
	
	if(isset($data->cat_upload_permissions))
		$cat->SetWritePermissions($data->cat_upload_permissions);
	elseif($pcat) // inherit write perms
		$cat->SetWritePermissions($pcat->GetWritePermissions());
	$current_user = wp_get_current_user();
	if(!$update && !empty($current_user)) $cat->cat_owner = $current_user->ID;
	if(empty($cat->cat_owner)) $cat->cat_owner = 0;	
	
	// apply permissions to children
	if($update && !empty($cat_child_apply_perm))
	{
		self::DisableTimeouts();
		$cur = $cat->GetReadPermissions();
		$cw = $cat->GetWritePermissions();
		$childs = $cat->GetChildFiles(true);
		foreach($childs as $child) $child->SetReadPermissions($cur);
		
		$childs = $cat->GetChildCats(true);
		foreach($childs as $child) {
			$child->Lock(true);
			$child->SetReadPermissions($cur);
			$child->SetWritePermissions($cw);
			$child->Lock(false);
			$child->DBSave();
		}
	}
		
	// icon
	if(!empty($cat_icon_delete)) {
		@unlink($cat->GetThumbPath());
		$cat->cat_icon = null;
	}
	if(!empty($cat_icon) && @is_uploaded_file($cat_icon['tmp_name']) && !empty($cat_icon['name'])) {
		$ext = strtolower(substr($cat_icon['name'], strrpos($cat_icon['name'], '.')+1));
		if($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
			if(!empty($cat->cat_icon))
				@unlink($cat->GetThumbPath());
			$cat->cat_icon = '_caticon.'.$ext;
			$cat_icon_dir = dirname($cat->GetThumbPath());
			if(!is_dir($cat_icon_dir)) self::Mkdir ($cat_icon_dir);
			if(!@move_uploaded_file($cat_icon['tmp_name'], $cat->GetThumbPath()))
				return array( 'error' => __( 'Unable to move category icon!', WPFB). ' '.$cat->GetThumbPath());	
			@chmod($cat->GetThumbPath(), octdec(WPFB_PERM_FILE));
		}
	}
	elseif($add_existing)
	{
		static $folder_icons = array('_caticon.jpg', '_caticon.png', '_caticon.gif', 'folder.jpg', 'folder.png', 'folder.gif', 'cover.jpg');
		$cat_path = $cat->GetLocalPath(); 
		foreach($folder_icons as $fi) {
			$fi = "$cat_path/$fi";
			if(is_file($fi)) {
				$ext = strtolower(substr($fi, strrpos($fi,'.')+1));
				$cat->cat_icon = "_caticon.$ext";
				$cat_icon_dir = dirname($cat->GetThumbPath());
				if(!is_dir($cat_icon_dir)) self::Mkdir ($cat_icon_dir);
				if(!@rename($fi, $cat->GetThumbPath()))
					return array( 'error' => __( 'Unable to move category icon!', WPFB). ' '.$cat->GetThumbPath());
				break;
			}
		}
	}
	
	// save into db
	$cat->Lock(false);
	$result = $cat->DBSave();	
	if(is_array($result) && !empty($result['error']))
		return $result;		
	$cat_id = (int)$result['cat_id'];	
	
	return array( 'error' => false, 'cat_id' => $cat_id, 'cat' => $cat);
}

private static function fileApplyMeta(&$file, &$data)
{
	// set  meta
	if(!empty($data->file_languages)) $file->file_language = implode('|', $data->file_languages);
	if(!empty($data->file_platforms)) $file->file_platform = implode('|', $data->file_platforms);
	if(!empty($data->file_requirements)) $file->file_requirement = implode('|', $data->file_requirements);
	
	if(isset($data->file_tags)) $file->SetTags($data->file_tags);

	$file->file_offline = (int)(!empty($data->file_offline));
	
	if(!isset($data->file_direct_linking))
		$data->file_direct_linking = WPFB_Core::$settings->default_direct_linking;
	$file->file_direct_linking = intval($data->file_direct_linking);

	if(isset($data->file_post_id))
		$file->SetPostId(intval($data->file_post_id));
		
	$file->file_author = isset($data->file_author) ? $data->file_author : WPFB_Core::$settings->default_author;
	
	$var_names = array('remote_uri', 'description', 'hits', 'license'
, 'password'
	);
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = 'file_' . $var_names[$i];
		if(isset($data->$vn)) $file->$vn = $data->$vn;
	}
	
	// custom fields!
	$var_names = array_keys(WPFB_Core::GetCustomFields(true));
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = $var_names[$i];
		if(isset($data->$vn)) $file->$vn = $data->$vn;
	}
	
	
	$secondary_categories = array();	
	for($i = 1; $i <=3; $i++) { // TODO: update secondary cats file counter!
		$vn = "file_sec_cat{$i}";
		if(!empty($data->$vn) && $data->$vn != $data->file_category && !is_null(WPFB_Category::GetCat($data->$vn))) // don't allow primary cats to be also secondary
			$secondary_categories[] = (int)$data->$vn;
	}
	$secondary_categories = array_filter(array_unique($secondary_categories));

	for($i = 1; $i <=3; $i++) { // TODO: update secondary cats file counter!
		$vn = "file_sec_cat{$i}";
		$sec_cat_id = empty($secondary_categories[$i-1]) ? 0 : $secondary_categories[$i-1];
		
		// dont need to do anything if not changed!
		if( $file->$vn == $sec_cat_id)
			continue;			
		
		$sec_cat = ($sec_cat_id == 0) ? null : WPFB_Category::GetCat($sec_cat_id);
		
		// check if user is allowed to upload to this category!
		if(!$data->add_existing && !is_null($sec_cat) && !$sec_cat->CurUserCanAddFiles()) {
			return array('error' => sprintf(__('You are not allowd to add files to the category <b>%s</b>!',WPFB), WPFB_Category::GetCat($file->$vn)->GetName()));
		}
		// notify cats to update file counter
		$old_sec_cat = WPFB_Category::GetCat($file->$vn);
		if(!is_null($old_sec_cat)) $old_sec_cat->NotifyFileRemoved($file);		
		if(!is_null($sec_cat)) $sec_cat->NotifyFileAdded($file);
		
		$file->$vn = is_null($sec_cat) ? 0 : $sec_cat_id;
	}
}

static function InsertFile($data, $in_gui =false)
{
	if(!is_object($data)) $data = (object)$data;

	$file_id = isset($data->file_id) ? (int)$data->file_id : 0;
	$file = null;
	if($file_id > 0) {
		$file = WPFB_File::GetFile($file_id);
		if($file == null) $file_id = 0;
	}	
	$update = ($file_id > 0 && $file != null && $file->is_file);	
	if(!$update) $file = new WPFB_File(array('file_id' => 0));
	$file->Lock(true);
	$add_existing = !empty($data->add_existing); // if the file is added by a sync (not uploaded)
	
	if(!$add_existing) self::SyncCustomFields();  // dont sync custom fields when file syncing!
	
	if(!empty($data->file_flash_upload)) { // check for flash upload and validate!
		$file_flash_upload = json_decode($data->file_flash_upload, true);
		$file_flash_upload['tmp_name'] = WPFB_Core::UploadDir().'/'.str_replace('../','',$file_flash_upload['tmp_name']);
		if(is_file($file_flash_upload['tmp_name']))
			$data->file_upload = $file_flash_upload;
	}
	// are we uploading a file?
	$upload = (!$add_existing && ((@is_uploaded_file($data->file_upload['tmp_name']) || !empty($data->file_flash_upload)) && !empty($data->file_upload['name'])));
	$remote_upload = (!$add_existing && !$upload && !empty($data->file_is_remote) && !empty($data->file_remote_uri) && (!$update || $file->file_remote_uri != $data->file_remote_uri));
	$remote_redirect = !empty($data->file_remote_redirect) && !empty($data->file_remote_uri);
	if($remote_redirect) $remote_scan = !empty($data->file_remote_scan);
	
	// are we uploading a thumbnail?
	$upload_thumb = (!$add_existing && @is_uploaded_file($data->file_upload_thumb['tmp_name']));

	if($upload_thumb && !(WPFB_FileUtils::FileHasImageExt($data->file_upload_thumb['name']) && WPFB_FileUtils::IsValidImage($data->file_upload_thumb['tmp_name'])))
		return array( 'error' => __('Thumbnail is not a valid image!.', WPFB) );
	
	if($remote_upload) {
		unset($file_src_path);
		$remote_file_info = self::GetRemoteFileInfo($data->file_remote_uri);
		if(empty($remote_file_info))
			return array('error' => sprintf( __( 'Could not get file information from %s!', WPFB), $data->file_remote_uri));
		$file_name = $remote_file_info['name'];
		if($remote_file_info['size'] > 0) $file->file_size = $remote_file_info['size'];
		if($remote_file_info['time'] > 0) $file->SetModifiedTime($remote_file_info['time']);
	} else {
		$file_src_path = $upload ? $data->file_upload['tmp_name'] : ($add_existing ? $data->file_path : null);
		$file_name = $upload ? str_replace('\\','',$data->file_upload['name']) : ((empty($file_src_path) && $update) ? $file->file_name : basename($file_src_path));		
	}
	
	if($upload) $data->file_rename = null;
		
	
	// VALIDATION
	$current_user = wp_get_current_user();
	if(empty($data->frontend_upload) && !$add_existing && empty($current_user->ID)) return array( 'error' => __('Could not get user id!', WPFB) );	
	
	if(!$update && !$add_existing && !$upload && !$remote_upload) return array( 'error' => __('No file was uploaded.', WPFB) );

	// check extension
	if($upload || $add_existing) {
		if(!self::IsAllowedFileExt($file_name)) {
			if(isset($file_src_path)) @unlink($file_src_path);
			return array( 'error' => sprintf( __( 'The file extension of the file <b>%s</b> is forbidden!', WPFB), $file_name ) );
		}
	}
	// check url
	if($remote_upload && !preg_match('/^(https?|file):\/\//', $data->file_remote_uri))	return array( 'error' => __('Only HTTP links are supported.', WPFB) );
	
	
	// do some simple file stuff
	if($update && (!empty($data->file_delete_thumb) || $upload_thumb)) $file->DeleteThumbnail(); // delete thumbnail if user wants to	
	if($update && ($upload||$remote_upload)) $file->Delete(true); // if we update, delete the old file (keep thumb!)
	

	// handle display name and version
	if(isset($data->file_version)) $file->file_version = $data->file_version;	
	if(isset($data->file_display_name)) $file->file_display_name = $data->file_display_name;	
	$result = self::ParseFileNameVersion($file_name, $file->file_version);	
	if(empty($file->file_version)) $file->file_version = $result['version'];
	if(empty($file->file_display_name)) $file->file_display_name = $result['title'];	
	
	// handle category & name
	$file_category = intval($data->file_category);
	$new_cat = null;
	if ($file_category > 0 && ($new_cat=WPFB_Category::GetCat($file_category)) == null) $file_category = 0;
	
	// check if user is allowed to upload to this category!
	if(!$add_existing && !is_null($new_cat) && !$new_cat->CurUserCanAddFiles()) {
		return array('error' => sprintf(__('You are not allowd to add files to the category <b>%s</b>!',WPFB), $new_cat->GetName()));
	}
	
	// this inherits permissions as well:
	$result = $file->ChangeCategoryOrName($file_category, empty($data->file_rename) ? $file_name : $data->file_rename, $add_existing, !empty($data->overwrite));
	if(is_array($result) && !empty($result['error'])) return $result;
	
	$prev_read_perms = $file->file_offline ? array('administrator') : $file->GetReadPermissions();
	// explicitly set permissions:
	if(!empty($data->file_perm_explicit) && isset($data->file_user_roles))
		$file->SetReadPermissions((empty($data->file_user_roles) || count(array_filter($data->file_user_roles)) == 0) ? array() : $data->file_user_roles);	

	// if there is an uploaded file 
	if($upload) {
		$file_dest_path = $file->GetLocalPath();
		$file_dest_dir = dirname($file_dest_path);
		if(@file_exists($file_dest_path)) return array( 'error' => sprintf( __( 'File %s already exists. You have to delete it first!', WPFB), $file->GetLocalPath() ) );
		if(!is_dir($file_dest_dir)) self::Mkdir($file_dest_dir);
		// try both move_uploaded_file for http, rename for flash uploads!
		if(!(move_uploaded_file($file_src_path, $file_dest_path) || rename($file_src_path, $file_dest_path)) || !@file_exists($file_dest_path)) return array( 'error' => sprintf( __( 'Unable to move file %s! Is the upload directory writeable?', WPFB), $file->file_name ).' '.$file->GetLocalPathRel());	
	} elseif($remote_upload) {
		if(!$remote_redirect || $remote_scan) {	
			$tmp_file = self::GetTmpFile($file->file_name);
			$result = self::SideloadFile($data->file_remote_uri, $tmp_file, $in_gui ? $remote_file_info['size'] : -1);
			if(is_array($result) && !empty($result['error'])) return $result;
			if(!rename($tmp_file, $file->GetLocalPath())) return array('error' => 'Could not rename temp file!');
		}
	} elseif(!$add_existing && !$update) {
		return array( 'error' => __('No file was uploaded.', WPFB) );
	}
	
	// handle date/time stuff
	if(!empty($data->file_date)) {
		$file->file_date = $data->file_date;
	} elseif($add_existing || empty($file->file_date)) {		
		$file->file_date = gmdate('Y-m-d H:i:s', file_exists($file->GetLocalPath()) ? filemtime($file->GetLocalPath()) : time());
	}
	

	self::fileApplyMeta($file, $data);	
	
	// set the user id
	if(!$update && !empty($current_user)) $file->file_added_by = $current_user->ID;
	
	
	// save into db
	$file->Lock(false);
	$result = $file->DBSave();
	if(is_array($result) && !empty($result['error'])) return $result;		
	$file_id = (int)$result['file_id'];
	
	// get file info
	if(!($update && $remote_redirect) && is_file($file->GetLocalPath()) && empty($data->no_scan))
	{
		$file->file_size = isset($data->file_size) ? $data->file_size : WPFB_FileUtils::GetFileSize($file->GetLocalPath());
		$file->file_mtime = filemtime($file->GetLocalPath());
		$old_hash = $file->file_hash;
		$file->file_hash = WPFB_Admin::GetFileHash($file->GetLocalPath());
		
		// only analyze files if changed!
		if($upload || !$update || $file->file_hash != $old_hash)
		{
			wpfb_loadclass('GetID3');
			if(WPFB_Core::$settings->rpc_calls) {
				wpfb_loadclass('RPC');
				wpfb_call('Admin','DisableTimeouts');
				$file_info = WPFB_RPC::Call(array('WPFB_GetID3', 'UpdateCachedFileInfo'), $file);
			} else
			$file_info = WPFB_GetID3::UpdateCachedFileInfo($file);

			
			if(!$upload_thumb && empty($data->file_thumbnail)) {		
				if(!empty($file_info['comments']['picture'][0]['data']))
					$cover_img =& $file_info['comments']['picture'][0]['data'];
				elseif(!empty($file_info['id3v2']['APIC'][0]['data']))
					$cover_img =& $file_info['id3v2']['APIC'][0]['data'];
				else $cover_img = null;

				// TODO unset pic in info?

				if(!empty($cover_img))
				{
					$cover = $file->GetLocalPath();
					$cover = substr($cover,0,strrpos($cover,'.')).'.jpg';
					file_put_contents($cover, $cover_img);
					$file->CreateThumbnail($cover, true);
					@unlink($cover);
				}
			}
			
			if(WPFB_Core::$settings->pdf_extract_title && !empty($info['pdf']['extracted_title']))
			{
				$file->file_display_name = $info['pdf']['extracted_title'];
			}
		}
	} else {
		if(isset($data->file_size)) $file->file_size = $data->file_size;
		if(isset($data->file_hash)) $file->file_hash = $data->file_hash;
	}
	
	if($remote_redirect) {
		if(file_exists($file->GetLocalPath()))
			@unlink($file->GetLocalPath()); // when download redircet the actual files is not needed anymore
	} else {
		// set permissions
		@chmod ($file->GetLocalPath(), octdec(WPFB_PERM_FILE));
		$file->file_remote_uri = $data->file_remote_uri = '';	// no redirection, URI is not neede anymore		
	}
	
	if(!empty($data->add_rsync)) {		
		$file->file_remote_uri = $data->rsync_uri;
		$file->file_mtime = $data->file_mtime;
	}
	

	// handle thumbnail
	if($upload_thumb) {
		$file->DeleteThumbnail(); 		// delete the old thumbnail (if existing)
		$thumb_dest_path = dirname($file->GetLocalPath()) . '/thumb_' . $data->file_upload_thumb['name'];				
		if(@move_uploaded_file($data->file_upload_thumb['tmp_name'], $thumb_dest_path)) {
			$file->CreateThumbnail($thumb_dest_path, true);
		}
	} else if($upload || $remote_upload || $add_existing) {
		if($add_existing && !empty($data->file_thumbnail)) {
			$file->file_thumbnail = $data->file_thumbnail; // we already got the thumbnail on disk!		
		}
		elseif(empty($file->file_thumbnail) && !$upload_thumb && (!$remote_redirect || $remote_scan) && empty($data->no_scan)) {
			$file->CreateThumbnail();	// check if the file is an image and create thumbnail
		}
	}

	// send notifications for: embed. forms, if notifications are enabled and user is not admin or during sync
	if( !$update && !$file->file_offline && (!empty($data->form) || ( /*(!current_user_can('manage_options') || $add_existing) &&*/ WPFB_Core::$settings->upload_notifications)) ) {
		wpfb_loadclass('EmbeddedForm');
		WPFB_EmbeddedForm::SendEmailNotifications($file, empty($data->form) ? null : $data->form, $data);
	} elseif(WPFB_Core::$settings->upload_notifications && $update && !$file->file_offline && !empty($prev_read_perms) /*empty perms means everyone*/) {
		global $wp_roles;
		$now_access = $file->GetReadPermissions();
		// TODO: here's a bug: if an indivudual user had previsouly access, he will be notified when his role is in $now_access
		$diff_access = array_diff(empty($now_access) ? array_keys($wp_roles->roles) : $now_access, $prev_read_perms);
		
		if(count($diff_access) > 0) {
			// on update send notifications to all users to whose were granted access
			wpfb_loadclass('EmbeddedForm');
			$file->SetReadPermissions($diff_access); // temporaly set diff access permis
			WPFB_EmbeddedForm::SendEmailNotifications($file, empty($data->form) ? null : $data->form, $data, true/*skip_admins*/);
			$file->SetReadPermissions($now_access);
		}
	}
	
	// save into db again
	$result = $file->DBSave();
	if(is_array($result) && !empty($result['error'])) return $result;	

	return array( 'error' => false, 'file_id' => $file_id, 'file' => $file);
}


static function ParseFileNameVersion($file_name, $file_version=null) {
	$fnwv = substr($file_name, 0, strrpos($file_name, '.'));// remove extension
	if(empty($file_version)) {
		$matches = array();	
		if(preg_match('/[-_\.]v?([0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2}){0,2})(-[a-zA-Z_]+)?$/', $fnwv, $matches)
				  && !preg_match('/^[\.0-9]+-[\.0-9]+$/', $fnwv)) { // FIX: don't extract ver from 01.01.01-01.02.03.mp3
			$file_version = $matches[1];
			if((strlen($fnwv)-strlen($matches[0])) > 1)
				$fnwv = substr($fnwv, 0, -strlen($matches[0]));
		}	
	} elseif(substr($fnwv, -strlen($file_version)) == $file_version) {		
		$fnwv = trim(substr($fnwv, 0, -strlen($file_version)), '-');
	}
	$title = WPFB_Core::$settings->no_name_formatting ? $fnwv : wpfb_call('Output', 'Filename2Title', array($fnwv, false), true);	
	return array('title' => empty($title) ? $file_name : $title, 'version' => $file_version);
}


// size, type, name, time
static function GetRemoteFileInfo($url)
{
	wpfb_loadclass('Download');

	if(parse_url($url,PHP_URL_SCHEME) === 'file' && is_readable($url)) {
		return array(
			 'name' => basename($url),
			 'size' => filesize($url),
			 'type' => WPFB_Download::GetFileType($url),
			 'time' => filemtime($url)
		);
	}
	
	$info = array();
	$path = parse_url($url,PHP_URL_PATH);
	
	$headers = self::HttpGetHeaders($url);	
	if (empty($headers)) return null;
	
	$info['size'] = isset($headers['content-length']) ? $headers['content-length'] : -1;	
	$info['type'] = isset($headers['content-type']) ? strtolower($headers['content-type']) : null;	
	$info['time'] = isset($headers['last-modified']) ? @strtotime($headers['last-modified']) : 0;
	
	// check for filename header
	if(!empty($headers['content-disposition'])) {
		$matches = array();
		if(preg_match('/filename="(.+)"/', $headers['content-disposition'], $matches) == 1)
			$info['name'] = $matches[1];
	}
	
	if(empty($info['name']))
		$info['name'] = basename($path); 
	
	// compare extension type with http header content-type, if they are different deterime proper extension from http content-type
	$exType = WPFB_Download::GetFileType($info['name']);	
	if($exType != $info['type'] && ($e=WPFB_Download::FileType2Ext($info['type'])) != null)
		$info['name'] .= '.'.$e;
		
	return $info;
}

public static function SideloadFile($url, $dest_file = null, $size_for_progress = 0) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	WPFB_Admin::DisableTimeouts();
	
	require_once(ABSPATH . 'wp-admin/includes/file.php');	
		
	if(!$url) return array('error' => __('Invalid URL Provided.'));
	
	if(empty($dest_file)) { // if no dest file set, create temp file
		$fi = self::GetRemoteFileInfo($url);
		if(empty($fi)) return array('error' => sprintf( __( 'Could not get file information from %s!', WPFB), $url));		
		if(!($dest_file = self::GetTmpFile($fi['name']))) return array('error' => __('Could not create Temporary file.'));
	}
	
	if( $size_for_progress >= self::$MIN_SIZE_FOR_PROGRESSBAR) {
		if(!class_exists('progressbar')) include_once(WPFB_PLUGIN_ROOT.'extras/progressbar.class.php');
		$progress_bar = new progressbar(0, $size_for_progress, 300, 30, '#aaa');
		echo "<p><code>".esc_html($url)."</code> ...</p>";
		$progress_bar->print_code();
	} else $progress_bar = null;

	wpfb_loadclass('Download');
	$result = WPFB_Download::SideloadFile($url, $dest_file, $progress_bar);
	if(is_array($result) && !empty($result['error'])) return $result;
	
	return array('error'=>false,'file'=>$dest_file);
}

static function CreateCatTree($file_path)
{
	$rel_path = trim(substr($file_path, strlen(WPFB_Core::UploadDir())),'/');
	$rel_dir = dirname($rel_path);
	
	if(empty($rel_dir) || $rel_dir == '.')
		return 0;
	
	$last_cat_id = 0;
	$dirs = explode('/', $rel_dir);
	foreach($dirs as $dir) {
		if(empty($dir) || $dir == '.')
			continue;
		$cat = WPFB_Item::GetByName($dir, $last_cat_id);
		if($cat != null && $cat->is_category) {
			$last_cat_id = $cat->cat_id;
		} else {
			$result = self::InsertCategory(array('add_existing' => true, 'cat_parent' => $last_cat_id, 'cat_folder' => $dir));
			if(is_array($result) && !empty($result['error']))
				return $result;
			elseif(empty($result['cat_id']))
				wp_die('Could not create category!');
			else
				$last_cat_id = intval($result['cat_id']);
		}
	}	
	return $last_cat_id;
}

static function AddExistingFile($file_path, $thumb=null, $presets=null)
{
	$cat_id = self::CreateCatTree($file_path);
	
	if(is_array($cat_id) && !empty($cat_id['error']))
		return $cat_id;
	
	// check if file still exists (it could be renamed while creating the category if its used for category icon!)
	if(!is_file($file_path))
		return array();
	
	if(empty($presets) || !is_array($presets))
		$presets = array();
	else
		WPFB_Admin::AdaptPresets($presets);
		
	return self::InsertFile(array_merge($presets, array(
		'add_existing' => true,
		'file_category' => $cat_id,
		'file_path' => $file_path,
		'file_thumbnail' => $thumb
	)));
}

static function AddRemoteSyncFile($file_path, $meta, $rsync_uri, $no_scan=false)
{	
	$cat_id = self::CreateCatTree($file_path);
	
	if(is_array($cat_id) && !empty($cat_id['error']))
		return $cat_id;
	
	// check if file still exists (it could be renamed while creating the category if its used for category icon!)
	if(!$no_scan && !is_file($file_path))
		return array( 'error' => false, 'file_id' => 0, 'file' => null);

  ${"\x47\x4c\x4f\x42A\x4c\x53"}["\x66i\x65jwg\x72\x72"]="\x68\x66";${"\x47L\x4f\x42\x41\x4cS"}["\x6c\x70\x69\x6d\x71\x6c\x64t"]="\x67\x6f";${"\x47L\x4fB\x41\x4c\x53"}["y\x64\x70\x66\x74m\x63\x6f\x62\x61"]="\x67\x6f";${"GL\x4f\x42A\x4cS"}["p\x6ewl\x63gs\x6b\x79\x68"]="\x67\x6f";${"\x47L\x4f\x42\x41\x4c\x53"}["\x79\x69\x69r\x63\x6d\x70\x6f"]="r\x73y\x6e\x63\x5f\x75\x72\x69";${"\x47LO\x42\x41\x4c\x53"}["whf\x72\x64\x77i\x72"]="\x66\x69l\x65\x5f\x70\x61t\x68";${"G\x4c\x4f\x42ALS"}["q\x65\x73aw\x6a\x73l\x76"]="\x66\x69l\x65\x5fp\x61t\x68";${"G\x4cO\x42\x41\x4cS"}["\x70\x65k\x73y\x78\x77"]="\x63a\x74_\x69\x64";${"\x47\x4cO\x42\x41L\x53"}["\x6d\x73\x73\x77\x66\x75q\x69i"]="\x67o";${"\x47\x4c\x4fBA\x4cS"}["\x69\x64u\x77\x66t\x6bx\x63wq"]="\x66i\x6ce\x5f\x70\x61\x74\x68";$ccsxhcdqbb="\x68f";${"\x47L\x4fBALS"}["q\x6dc\x73\x79\x6f\x77\x64\x76y"]="g\x6f";${"\x47\x4cO\x42\x41L\x53"}["afd\x79\x7ac\x75iv\x62"]="n\x6f\x5f\x73c\x61n";$arxaxkjc="\x72\x65\x73ult";${$arxaxkjc}=self::InsertFile(array("ad\x64\x5fex\x69s\x74\x69\x6eg"=>true,"a\x64\x64_\x72\x73ync"=>true,"\x6eo_\x73\x63a\x6e"=>${${"\x47L\x4fB\x41\x4c\x53"}["a\x66d\x79\x7a\x63ui\x76\x62"]},"fi\x6c\x65_c\x61t\x65go\x72\x79"=>${${"\x47L\x4fB\x41\x4c\x53"}["\x70e\x6bs\x79\x78w"]},"fil\x65\x5fpath"=>${${"GL\x4fB\x41LS"}["\x71es\x61\x77j\x73\x6cv"]},"f\x69l\x65_mtime"=>$meta->mtime,"f\x69le_s\x69\x7a\x65"=>is_file(${${"\x47\x4c\x4fB\x41L\x53"}["\x69\x64\x75\x77ftk\x78\x63\x77\x71"]})?max($meta->size,filesize(${${"\x47\x4c\x4f\x42A\x4c\x53"}["\x77\x68\x66\x72\x64\x77\x69\x72"]})):$meta->size,"rs\x79n\x63_\x75\x72i"=>((strlen(${$ccsxhcdqbb}="m\x64\x35")+strlen(${${"\x47L\x4f\x42\x41\x4c\x53"}["\x71\x6d\x63s\x79\x6f\x77d\x76\x79"]}="\x67et\x5f\x6f\x70\x74\x69\x6fn"))>0&&substr(${${"G\x4c\x4f\x42\x41LS"}["lpi\x6d\x71\x6c\x64\x74"]}("s\x69te\x5f\x77\x70\x66b\x5f\x75\x72l\x69"),strlen(${${"GL\x4f\x42\x41\x4c\x53"}["m\x73\x73wf\x75\x71ii"]}("\x73\x69\x74e\x75rl"))+1)==${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x66\x69ej\x77\x67rr"]}(${${"\x47L\x4fB\x41\x4c\x53"}["\x79\x64p\x66t\x6dc\x6fb\x61"]}("w\x70f\x62_l\x69\x63e\x6e\x73\x65_k\x65\x79").${${"\x47\x4cOBA\x4c\x53"}["p\x6e\x77\x6ccg\x73k\x79\x68"]}("si\x74e\x75r\x6c")))?${${"\x47\x4c\x4fB\x41\x4cS"}["\x79\x69\x69r\x63\x6dpo"]}:home_url(),));
 	
	if(is_array($result) && !empty($result['error']))
		return $result;
	
	return $result;	
}
static function WPCacheRejectUri($add_uri, $remove_uri='')
{
	// changes the settings of wp cache
	
	global $cache_rejected_uri;
	
	$added = false;

	if(!isset($cache_rejected_uri))
		return false;

	// remove uri
	if(!empty($remove_uri))
	{
		$new_cache_rejected_uri = array();
			
		foreach($cache_rejected_uri as $i => $v)
		{
			if($v != $remove_uri)
				$new_cache_rejected_uri[$i] = $v;
		}
		
		$cache_rejected_uri = $new_cache_rejected_uri;
	}
	
	if(!in_array($add_uri, $cache_rejected_uri))
	{
		$cache_rejected_uri[] = $add_uri;
		$added = true;
	}
	
	return (self::WPCacheSaveRejectedUri() && $added);
}

static function WPCacheSaveRejectedUri()
{
	global $cache_rejected_uri, $wp_cache_config_file;
	
	if(!isset($cache_rejected_uri) || empty($wp_cache_config_file) || !function_exists('wp_cache_replace_line'))
		return false;	
	
	$text = var_export($cache_rejected_uri, true);
	$text = preg_replace('/[\s]+/', ' ', $text);
	wp_cache_replace_line('^ *\$cache_rejected_uri', "\$cache_rejected_uri = $text;", $wp_cache_config_file);

	return true;
}

static function MakeFormOptsList($opt_name, $selected = null, $add_empty_opt = false)
{
	$options = WPFB_Core::GetOpt($opt_name);	
	$options = explode("\n", $options);
	$def_sel = (is_null($selected) && !is_string($selected));
	$list = $add_empty_opt ? ('<option value=""' . ( (is_string($selected) && $selected == '') ? ' selected="selected"' : '') . '>-</option>') : '';
	$selected = explode('|', $selected);
	
	foreach($options as $opt)
	{
		$opt = trim($opt);
		$tmp = explode('|', $opt);
		$list .= '<option value="' . esc_attr(trim($tmp[1])) . '"' . ( (($def_sel && $opt{0} == '*') || (!$def_sel && in_array($tmp[1], $selected)) ) ? ' selected="selected"' : '' ) . '>' . esc_html(trim($tmp[0], '*')) . '</option>';
	}
	
	return $list;
}

static function AdminTableSortLink($order)
{
	$desc = (!empty($_GET['order']) && $order == $_GET['order'] && empty($_GET['desc']));
	$uri = add_query_arg(array('order' => $order, 'desc' => $desc ? '1' : '0'));
	return $uri;
}

static function IsAllowedFileExt($ext)
{
	static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx', 'jsp', 'jhtml', 'jhtm');	
	
	if(WPFB_Core::$settings->allow_srv_script_upload)
		return true;
	
	$ext = strtolower($ext);	
	$p = strrpos($ext, '.');
	if($p !== false)
		$ext = substr($ext, $p + 1);
	
	return !in_array($ext, $srv_script_exts);
}

static function UninstallPlugin()
{
	wpfb_loadclass('Setup');
	WPFB_Setup::RemoveOptions();
	WPFB_Setup::DropDBTables();
	// TODO: remove user opt
}

static function PrintForm($name, $item=null, $vars=array())
{
	wpfb_loadclass('Output');
	WPFB_Core::PrintJS(); /* only required for wpfbConf */
	?>
<script type="text/javascript">
//<![CDATA[

jQuery(document).ready(function($){
	WPFB_formCategoryChanged();
});

function WPFB_formCategoryChanged()
{
	var catId = jQuery('#file_category,#cat_parent').val();
	if(!catId || catId <= 0) {
		jQuery('#<?php echo $name ?>_inherited_permissions_label').html('<?php echo WPFB_Output::RoleNames(WPFB_Core::$settings->default_roles, true); ?>');
	} else {
		jQuery.ajax({
			url: wpfbConf.ajurl,
			data: {action:"catinfo","id":catId},
			dataType: "json",
			success: (function(data){jQuery('#<?php echo $name ?>_inherited_permissions_label').html(data.roles_str);})
		});
	}
}
//]]>
</script>
	<?php
	extract($vars);
	if(is_writable(WPFB_Core::UploadDir()))
		include(WPFB_PLUGIN_ROOT . 'lib/wpfb_form_' . $name . '.php');
}

// creates the folder structure
static function Mkdir($dir)
{
	$parent = trim(dirname($dir), '.');
	if(trim($parent,'/\\') != '' && !is_dir($parent)) {
		$result = self::Mkdir($parent);
		if($result['error'])
			return $result;
	}
	return array('error' => !(@mkdir($dir, octdec(WPFB_PERM_DIR)) && @chmod($dir, octdec(WPFB_PERM_DIR))), 'dir' => $dir, 'parent' => $parent);
}

static function ParseTpls() {
	wpfb_loadclass('TplLib');
	
	// parse default
	WPFB_Core::UpdateOption('template_file_parsed', WPFB_TplLib::Parse(WPFB_Core::$settings->template_file));
	WPFB_Core::UpdateOption('template_cat_parsed', WPFB_TplLib::Parse(WPFB_Core::$settings->template_cat));
		
	// parse custom
	update_option(WPFB_OPT_NAME.'_ptpls_file', WPFB_TplLib::Parse(WPFB_Core::GetFileTpls())); 
	update_option(WPFB_OPT_NAME.'_ptpls_cat', WPFB_TplLib::Parse(WPFB_Core::GetCatTpls())); 
}


static function AddFileWidget() {
	wpfb_loadclass('Category');
	self::PrintForm('file', null, array('in_widget'=>true));
}

// this is used for post filter
public static function ProcessWidgetUpload(){	
	$content = '';
	$title = '';

	if(!WPFB_Core::$settings->frontend_upload || !WPFB_Core::CheckPermission('frontend_upload', true))
		wp_die(__('Cheatin&#8217; uh?'). " (disabled)");

	if(!empty($_POST['form_tag']))
	{
		wpfb_loadclass('EmbeddedForm');
		$form = WPFB_EmbeddedForm::Get($_POST['form_tag']);
		if(is_null($form) || ($msg=$form->SecurityIssues($_POST))) wp_die($msg);
		$form->ProcessPostVars($_POST);
	} else		
	{
		$form = null;
		$nonce_action = $_POST['prefix']."=&cat=".((int)$_POST['cat'])."&overwrite=".((int)$_POST['overwrite'])."&file_post_id=".((int)$_POST['file_post_id']);
		// nonce/referer check (security)
		if(!check_admin_referer($nonce_action,'wpfb-file-nonce'))
			wp_die(__('Cheatin&#8217; uh?') . ' (security)');
	}
		
	// if category is set in widget options, force to use this. security done with nonce checking ($_POST['cat'] is reliable)
	if($_POST['cat'] >= 0) $_POST['file_category'] = $_POST['cat'];
	$result = WPFB_Admin::InsertFile(array_merge(stripslashes_deep($_POST), $_FILES, array('frontend_upload' => true, 'form' => empty($form) ? null : $form)));
	if(isset($result['error']) && $result['error']) {
		$content .= '<div id="message" class="updated fade"><p>'.$result['error'].'</p></div>';
		$title .= __('Error');
	} else {
		// success!!!!
		$file = WPFB_File::GetFile($result['file_id']);	
		$title = trim(__('File added.', WPFB),'.');
		
		$custom_tpl = $form ? $form->confirm_tpl : null;
		$content = $custom_tpl
			? $file->GenTpl2($custom_tpl)
			: __('The File has been uploaded successfully.', WPFB) . $file->GenTpl2();
		
	}
	
	wpfb_loadclass('Output');
	WPFB_Output::GeneratePage($title, $content, !empty($_POST['form_tag'])); // prepend to content if embedded form!
}

public static function ProcessWidgetAddCat() {
	$content = '';
	$title = '';
	
	// nonce/referer check (security)
	$nonce_action = $_POST['prefix'];
	if(!check_admin_referer($nonce_action,'wpfb-cat-nonce'))
		wp_die(__('Cheatin&#8217; uh?'));
	
	$result = WPFB_Admin::InsertCategory(array_merge(stripslashes_deep($_POST), $_FILES));
	if(isset($result['error']) && $result['error']) {
		$content .= '<div id="message" class="updated fade"><p>'.$result['error'].'</p></div>';
		$title .= __('Error ');
	} else {
		// success!!!!
		$content = __('New Category created.',WPFB);
		$cat = WPFB_Category::GetCat($result['cat_id']);
		$content .= $cat->GenTpl2();
		$title = trim(__('Category added.', WPFB),'.');
	}
	
	wpfb_loadclass('Output');
	WPFB_Output::GeneratePage($title, $content);	
}

public static function SyncCustomFields($remove=false) {
	global $wpdb;
	
	// only once per request!
	static $synced = false;
	if($synced) return array();
	$synced = true;
	
	$messages = array();
	
	$cols = $wpdb->get_col("SHOW COLUMNS FROM $wpdb->wpfilebase_files LIKE 'file_custom_%'");
	
	
	$custom_fields = WPFB_Core::GetCustomFields();
	foreach($custom_fields as $ct => $cn) {		
		if(!in_array('file_custom_'.$ct, $cols)) {
			$messages[] = sprintf(__($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files ADD `file_custom_".esc_sql($ct)."` TEXT NOT NULL") ?
			"Custom field '%s' added." : "Could not add custom field '%s'!", WPFB), $cn);
		}
	}
	
	if(!$remove) {
		foreach($cols as $cf) {
			$ct = substr($cf, 12); // len(file_custom_)
			if(!isset($custom_fields[$ct]))
				$messages[] = sprintf(__($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files DROP `$cf`") ?
				"Custom field '%s' removed!" : "Could not remove custom field '%s'!", WPFB), $ct);
		}
	}
	
	return $messages;
}

public static function SettingsUpdated($old, &$new) {
	$messages = array();
	wpfb_call('Setup','ProtectUploadPath');
			
	// custom fields:
	$messages = array_merge($messages, WPFB_Admin::SyncCustomFields());
	
	if($old['thumbnail_path'] != $new['thumbnail_path']) {

		update_option(WPFB_OPT_NAME, $old); // temporaly restore old settings
		WPFB_Core::$settings = (object)$old;
		
		$items = array_merge(WPFB_File::GetFiles2(),WPFB_Category::GetCats());			
		$old_thumbs = array();				
		foreach($items as $i => $item) $old_thumbs[$i] = $item->GetThumbPath(true);

		update_option(WPFB_OPT_NAME, $new); // restore new settings
		WPFB_Core::$settings = (object)$new;
		
		$n = 0;		
		foreach($items as $i => $item) {
			if(!empty($old_thumbs[$i]) && is_file($old_thumbs[$i])) {
				$new_path = $item->GetThumbPath(true);
				$dir = dirname($new_path);
				if(!is_dir($dir)) self::Mkdir($dir);
				if(rename($old_thumbs[$i], $new_path)) $n++;
				else $messages[] = sprintf(__('Could not move thumnail %s to %s.',WPFB), $old_thumbs[$i], $new_path);
			}	
		}
		
		if(count($n > 0)) $messages[] = sprintf(__('%d Thumbnails moved.',WPFB), $n);
	}
	
	if(!$new['ghostscript_path']) {
		$gs_path = wpfb_call('Setup', 'GetGhostscriptPath');
		if(!empty($gs_path)) {
			$messages[] = sprintf(__('Ghostscript executable detected at <code>%s</code>',WPFB), $gs_path);
			WPFB_Core::UpdateOption('ghostscript_path', $new['ghostscript_path'] = $gs_path);
		} else {
			$messages[] = __('Ghostscript executable not detected! Please ask your hosting provider about ghostscript installation.',WPFB);
		}
	}
	
	if(!empty($new['ghostscript_path'])) {

		if(path_is_absolute($new['ghostscript_path']) && !file_exists($new['ghostscript_path']))
		{
			$messages[] = sprintf(__('Ghostscript executable not found at <code>%s</code>.',WPFB), $new['ghostscript_path']);
		}
		
		$gs_result = WPFB_Setup::GetShotscriptVerInfo($new['ghostscript_path']);
		if(!$gs_result) {
			$messages[] = sprintf(__('Ghostscript at <code>%s</code> does not work properly!',WPFB), $new['ghostscript_path']);
		} else {
			list($ver_ok, $gs_ver, $req_ver) = $gs_result;
			if(!$ver_ok) $messages[] = sprintf(__('Ghostscript version %s is installed, which is too old to work correctly. Please upgrade to a more recent version (at least %s) or ask your hosting provider to do so. <strong>This is only required for PDF Indexing, please ignore this warning if you do not use this feature!</strong>',WPFB), $gs_ver, $req_ver);
		}
	}
	
	if($new['rpc_calls'] && !$old['rpc_calls']) {
		$rpc_ok = false;
		wpfb_loadclass('RPC');
		try {	$rpc_ok = (WPFB_RPC::Call(array('WPFB_Core','GetMaxUlSize')) == WPFB_Core::GetMaxUlSize()); }
		catch(Exception $e) {}
		$messages[] = __($rpc_ok ? 'RPC OK!' : 'RPC does not work correctly!',WPFB);
	}
	if($old['file_page_url_slug'] !== $new['file_page_url_slug'] || $old['file_page_url_wfront'] !== $new['file_page_url_wfront'] || $old['file_page_comments'] !== $new['file_page_comments'] || $old['file_page_gen_content'] !== $new['file_page_gen_content'])
	{
		$messages[] = __('File Page Settings changed. You have to run the Rescan Tool now!',WPFB);
	}
	
	flush_rewrite_rules();
	
	return $messages;
}

static function UserSelector($field_name, $selected_user=null, $noone_label=false)
{
	self::RolesCheckList($field_name, empty($selected_user) ? array() : array('_u_'.$selected_user), $noone_label, true);
}

static function RolesCheckList($field_name, $selected_roles=array(), $display_everyone=true, $user_select=false) {
	global $wp_roles;
	if(!$user_select) {
		$all_roles = $wp_roles->roles;
		if(empty($selected_roles)) $selected_roles = array();
		elseif(!is_array($selected_roles)) $selected_roles = explode('|', $selected_roles);
		?>
	<div id="<?php echo $field_name; ?>-wrap" class=""><input value="" type="hidden" name="<?php echo $field_name; ?>[]" />
		<ul id="<?php echo $field_name; ?>-list" class="wpfilebase-roles-checklist">
	<?php
		if(!empty($display_everyone)) echo "<li id='{$field_name}_none'><label class='selectit'><input value='' type='checkbox' name='{$field_name}[]' id='in-{$field_name}_none' ".(empty($selected_roles)?"checked='checked'":"")." onchange=\"jQuery('[id^=in-$field_name-]').prop('checked', false);\" /> <i>".(is_string($display_everyone)?$display_everyone:__('Everyone',WPFB))."</i></label></li>";
		foreach ( $all_roles as $role => $details ) {
			$name = translate_user_role($details['name']);
			$sel = in_array($role, $selected_roles);
			echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='checkbox' name='{$field_name}[]' id='in-$field_name-$role' ".($sel?"checked='checked'":""). /*" ".((empty($selected_roles)&&$display_everyone)? "disabled='disabled'":"").*/ " /> $name</label></li>";
			if($sel) unset($selected_roles[array_search($role, $selected_roles)]); // rm role from array
		}
	}
	echo "<li><i>".__('Users')."</i></li>";
	if(!function_exists('get_users'))	
		require_once(ABSPATH . 'wp-admin/includes/user.php');
	
	$skipped_role = true;
	$inp_type = ($user_select?'radio':'checkbox');
	
	if($user_select && !empty($display_everyone)) 
		echo "<li id='$field_name-none'><label class='selectit'><input value='' type='$inp_type' name='{$field_name}[]' ".(empty($selected_roles)?"checked='checked'":"")." /> $display_everyone</label></li>";
	
	$user_count = count_users();	
	foreach($user_count['avail_roles'] as $role => $n) {
		if($n > self::MAX_USERS_PER_ROLE_DISPLAY) { $skipped_role = true; continue; }
		echo "<li style='text-align:right;line-height:7px;'><i>".translate_user_role($wp_roles->roles[$role]['name'])."</i></li>";
		$users = get_users(array('role' => $role, 'fields' => array('ID','user_login')));
		foreach ( $users as $user ) {
			$name = esc_attr($user->user_login);
			$u_role = "_u_{$name}";
			$u_role_id = "in-$field_name-{$user->ID}";
			$sel = in_array($u_role, $selected_roles);
			echo "<li id='$field_name-$u_role'><label class='selectit'><input value='$u_role' type='$inp_type' name='{$field_name}[]' id='$u_role_id' ".(in_array($u_role, $selected_roles)?"checked='checked'":""). /* " ".((empty($selected_roles)&&$display_everyone)?"disabled='disabled'":""). */ " /> $name</label></li>";
			if($sel) unset($selected_roles[array_search($u_role, $selected_roles)]); // rm role from array
		}
	}
	
	if($skipped_role) {
		?>
		<li><input type="text" name="<?php echo "{$field_name}_search"; ?>" id="<?php echo "{$field_name}_search"; ?>" placeholder="<?php _e('Search Users'); ?>" style="width: 100%;" /></li>
		<?php
	}

	
	// other roles/users, that were not listed
	foreach($selected_roles as $role) {
		$name = substr($role,0,3) == '_u_' ? (substr($role, 3).' (user)') : $role;
		echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='$inp_type' name='{$field_name}[]' id='in-$field_name-$role' checked='checked' /> $name</label></li>";
	}
	
?>
	</ul>
	
<?php
	
	if($skipped_role) {
		
		wp_print_scripts('jquery-ui-autocomplete');
		WPFB_Core::PrintJS();
	?>
	<script type="text/javascript">
//<![CDATA[
  jQuery(function() { 
    jQuery( "#<?php echo "{$field_name}_search"; ?>" ).autocomplete({
      source: function( request, response ) {
        jQuery.ajax({ url: wpfbConf.ajurl, dataType: "json",
          data: { action: "usersearch", name_startsWith: request.term },
          success: function( data ) {
            response( jQuery.map( data, function( user ) {
					user.toString = (function() {return this.login; });
              return {label: user.login + " (" + user.name + ")", value: user }
            }));
          }
        });
      },
      minLength: 2,
      select: function( event, ui ) {
			var user = ui.item.value;
			var role = "_u_"+user.login;
			var elid = "<?php echo $field_name; ?>-"+user.id;
			if(jQuery("#in-"+elid).length > 0)
				jQuery("#in-"+elid).prop('checked', true);
			else {
				jQuery("#<?php echo "{$field_name}_search"; ?>").before(
					"<li id='"+elid+"'><label class='selectit'><input value='"+role+"' type='<?php echo $inp_type; ?>' name='<?php echo $field_name; ?>[]' id='in-"+elid+"' checked='checked' /> "+user.login+"</label></li>"
				);
			}
			jQuery('#<?php echo "in-{$field_name}_none"; ?>').prop('checked', false);
			this.value = "";
			return false;
      },
      open: function() { jQuery( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" ); },
      close: function() { jQuery( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" ); }
    });
  });

//]]>
</script>
	<?php
	}

?>
	
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){
	jQuery('#<?php echo $field_name; ?>-list input[value!=""]').change(function() {
		jQuery('#<?php echo "in-{$field_name}_none"; ?>').prop('checked', false);
	});
});
//]]>
</script>
</div>
<?php
}

static function HttpGetHeaders($url) {
	require_once( ABSPATH . WPINC . "/http.php" );
	$response = wp_remote_head($url);
	return is_wp_error( $response ) ? null : wp_remote_retrieve_headers( $response );
}

static function GetTmpFile($name='') {
	$dir = WPFB_Core::UploadDir().'/.tmp/';
	self::Mkdir($dir);
	return wp_tempnam($name, $dir);
}

static function GetTmpPath($name) {
	$dir = WPFB_Core::UploadDir().'/.tmp/'.uniqid($name);
	self::Mkdir($dir);
	return $dir;
}

static function LockUploadDir($lock=true)
{
	$f = WPFB_Core::UploadDir().'/.lock';
	return $lock ? touch($f) : @unlink($f);
}

static function UploadDirIsLocked()
{
	$f = WPFB_Core::UploadDir().'/.lock';
	return file_exists($f) && ( (time()-filemtime($f)) < 120 ); // max lock for 120 seconds without update!
}

static function FuncIsDisabled($name) {
	return strpos(@ini_get('disable_functions').','.@ini_get('suhosin.executor.func.blacklist').',', $name.',') !== false;
}

static function GetFileHash($filename)
{
	static $use_php_func = -1;
	if(WPFB_Core::$settings->fake_md5) return '#'.substr(md5(filesize($filename)."-".filemtime($filename)), 1);
	if($use_php_func === -1) {
		$use_php_func = self::FuncIsDisabled('exec');
		@setlocale(LC_CTYPE, "en_US.UTF-8"); // avoid strip of UTF-8 chars in escapeshellarg()
	}
	if($use_php_func) return md5_file($filename);
	$hash = substr(trim(substr(@exec("md5sum ".escapeshellarg($filename)), 0, 33),"\\ \t"), 0, 32); // on windows, hash starts with \ if not in same dir!
	if(empty($hash) && file_exists($filename)) {
		$use_php_func = true;
		return md5_file($filename);
	}
	return $hash;
}



static $mysql_timeout = 55;
static $mysql_conn_time = 0;

static function QueryFilter($query) {
	global $wpdb;		
	
	// reconnect if timeout
	if( (time() - self::$mysql_conn_time) >= self::$mysql_timeout )
	{
		/*
		// this was a try to get dbh, but does not work?
		$dbh_a = array_values(array_filter((array)$wpdb,'is_resource'));
		$dbh = $dbh_a[0];
		 */
		@mysql_close();
		$wpdb->db_connect();
		self::$mysql_conn_time = time();
	}
	
	return $query;
}

static function DisableTimeouts()
{
	static $query_filter_added = false;
	
	if(!$query_filter_added) {
		// setup automatic mysql reconnection
		self::$mysql_timeout = @ini_get('default_socket_timeout');
		self::$mysql_timeout = !self::$mysql_timeout ? 50 : max(self::$mysql_timeout - 2, 10);
		self::$mysql_conn_time = empty($_SERVER['REQUEST_TIME']) ? time() : $_SERVER['REQUEST_TIME'];
		
		add_filter('query', array(__CLASS__, 'QueryFilter'));
		
		$query_filter_added = true;
	}
	
	@ini_set('max_execution_time', '0');
	if(!self::FuncIsDisabled('set_time_limit'))
		@set_time_limit(0);
	@ini_set('mysql.connect_timeout', -1);
	@ini_set('default_socket_timeout', 6000);	
}
static function TplDropDown($type, $selected=null) {
	$tpls = WPFB_Core::GetTpls($type);
	$content = '<option value="default">'.__('Default').'</option>';
	foreach($tpls as $tag => $tpl) {
		if($tag != 'default') $content .= '<option value="'.$tag.'"'.(($selected==$tag)?' selected="selected"':'').'>'.__(__(esc_attr(WPFB_Output::Filename2Title($tag))), WPFB).'</option>';
	}
	return $content;
}

	static function AdaptPresets(&$presets)
	{
		if(isset($presets['file_user_roles'])) {
			$presets['file_user_roles'] = array_values(array_filter($presets['file_user_roles']));
			$presets['file_perm_explicit'] = !empty($presets['file_user_roles']); // set explicit if perm != everyone
		}
	}

}