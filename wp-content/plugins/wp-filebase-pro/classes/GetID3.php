<?php
class WPFB_GetID3 {
	static $engine;
	
	static function GetEngine()
	{
		if(!self::$engine) {
			if(!class_exists('getID3')) {
				$tmp_dir = WPFB_Core::UploadDir().'/.tmp';
				if(!is_dir($tmp_dir)) @mkdir($tmp_dir);
				define('GETID3_TEMP_DIR', $tmp_dir.'/');
				unset($tmp_dir);
				require_once(WPFB_PLUGIN_ROOT.'extras/getid3/getid3.php');		
			}

			self::$engine = new getID3;
		}
		return self::$engine;
	}
	
	private static function xml2Text($content) {
		return trim(esc_html(preg_replace('! +!', ' ',strip_tags(str_replace('<',' <',$content)))));
	}
	
	static function AnalyzeFile($file)
	{
		wpfb_call('Admin','DisableTimeouts');
		
				
		$filename = is_string($file) ? $file : $file->GetLocalPath();
		
		$info = WPFB_Core::$settings->disable_id3 ? array() : self::GetEngine()->analyze($filename);
		
		if(!empty($_GET['debug'])) {
			wpfb_loadclass('Sync');
			WPFB_Sync::PrintDebugTrace("file_analyzed_".$file->GetLocalPathRel());
		}
		$ext = strtolower(substr($filename, strrpos($filename,'.')+1));
		if($ext == "pdf") {
			require_once(WPFB_PLUGIN_ROOT . 'extras/pdf-utils.php');
			$info['pdf'] = array('page_text' => array(), 'extracted_title' => null);
			
			$pdf_pages = pdf_get_num_pages(WPFB_Core::$settings->ghostscript_path, $filename);
			if($pdf_pages <= 0) $pdf_pages = 100;
			
			// first 3 single pages 
			for($i = 1; $i <= min($pdf_pages,3); $i++)
			{
				$c = pdf2txt_gs(WPFB_Core::$settings->ghostscript_path, $filename, $i);
				if($c === false) break; // false means: page overflow, break page loop
				if(empty($c)) continue;
				$info['pdf']['page_text'][] = $c;
				if(empty($info['pdf']['extracted_title'])) {
					$ms = array();
					$cb = substr($c,0,200);
					if(preg_match("/".WPFB_Core::$settings->pdf_title_regex."/i", $cb ,$ms) > 0) {
						$ms[1] = strip_tags(trim($ms[1]));
						// on failure, the title starts with GPL Ghostscript, skip it then
						if(strpos($ms[1], "GPL Ghostscript") !== 0)
							$info['pdf']['extracted_title'] = $ms[1];
					}
				}
				unset($c);
			}
			
			// blocks of 10 pages
			$keywords = '';
			for($i = 4; $i <= $pdf_pages; $i+=10) // TODO: make option for this!
			{
				$b = min(10, $pdf_pages - $i + 1);
				$c = pdf2txt_gs(WPFB_Core::$settings->ghostscript_path, $filename, $i, $b);
				if($c === false) break; // false means: page overflow, break page loop
				if(empty($c)) continue;
				$keywords .= $c;
				$keywords .= ' ';
				unset($c);
			}
			
			if(!empty($_GET['debug'])) {
				wpfb_loadclass('Sync');
				WPFB_Sync::PrintDebugTrace("pdf2txt_".$file->GetLocalPathRel());
			}
			
			$info['keywords'] = $keywords;			
			// only use alt text extraction for small pdf files
			if($i <= 30)
				$info['keywords'] .= ' '.preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', pdf2txt_keywords($filename));
			
		} elseif($ext == "docx" || $ext == "odt" || $ext == "pptx" || $ext == "xlsx") {
			$zres = self::Unzip($filename); // this can run out of memory!
			if(!empty($zres['dir'])) {
				if(!isset($info[$ext])) $info[$ext] = array();
				
				if($ext == "pptx") {
					$i = 1;
					while(is_file($sf = $zres['dir']."/ppt/slides/slide{$i}.xml")) {
						$info[$ext]['slide_text'][$i] = self::xml2Text(file_get_contents($sf));
						$i++;
					}
				} else {
					$content_files = array(
						 'docx' => 'word/document.xml',
						 'xlsx' => 'xl\sharedStrings.xml',
						 'odt' =>  'content.xml',
					);
					if(is_file($zres['dir']."/".@$content_files[$ext]))
						$info[$ext]['words'] = self::xml2Text (file_get_contents($zres['dir']."/".@$content_files[$ext]));
				}
				self::DeleteDir($zres['dir']);
			}
		}
		return $info;
	}
	
	static function StoreFileInfo($file, $info)
	{
		global $wpdb;
		
		self::cleanInfoByRef($info);
		
		
		// set encoding to utf8 (required for getKeywords)
		if(function_exists('mb_internal_encoding')) {
			$cur_enc = mb_internal_encoding();
			mb_internal_encoding('UTF-8');
		}
		$keywords = array();
		self::getKeywords($info, $keywords);
		$keywords = strip_tags(join(' ', $keywords));
		$keywords = str_replace(array('\n','&#10;'),'', $keywords);
		$keywords = preg_replace('/\s\s+/', ' ', $keywords);		
		if(!function_exists('mb_detect_encoding') || mb_detect_encoding($keywords, "UTF-8") != "UTF-8")
				$keywords = utf8_encode($keywords);		
		// restore prev encoding
		if(function_exists('mb_internal_encoding'))
			mb_internal_encoding($cur_enc);
		
		// don't store keywords 2 times:
		unset($info['keywords']);
		self::removeLongData($info, 8000);		

		$data = empty($info) ? '0' : base64_encode(serialize($info));
		
		$res = $wpdb->replace($wpdb->wpfilebase_files_id3, array(
			'file_id' => (int)$file->GetId(),
			'analyzetime' => time(),
			'value' => &$data,
			'keywords' => &$keywords
		));		
		unset($data, $keywords);
		
		// check for custom_fields that are fed by %file_info/...%
		$custom_defaults = array();
		$custom_fields = WPFB_Core::GetCustomFields(true, $custom_defaults);
		$cf_changed = false;
		foreach($custom_fields as $ct => $cn) {
			$fcv = property_exists($file, $ct) ? $file->$ct : '';
			if(!empty($custom_defaults[$ct]) && preg_match('/^%file_info\\/[a-zA-Z0-9_\\/]+%$/',$custom_defaults[$ct])
					  && ($nv = $file->get_tpl_var(trim($custom_defaults[$ct],'%'))) != $fcv) {
				$file->$ct = $nv;
				$cf_changed = true;
			}
		}
		
		if($cf_changed && !$file->locked)
			$file->DbSave();
		
		return $res;
	}
	
	static function UpdateCachedFileInfo($file)
	{
		$info = self::AnalyzeFile($file);
		self::StoreFileInfo($file, $info);
		return $info;
	}
	
	// gets file info out of the cache or analyzes the file if not cached
	static function GetFileInfo($file, $get_keywords=false)
	{
		global $wpdb;
		$sql = "SELECT value".($get_keywords?", keywords":"")." FROM $wpdb->wpfilebase_files_id3 WHERE file_id = " . $file->GetId();
		if($get_keywords) {   // TODO: cache not updated if get_keywords
			$info = $wpdb->get_row($sql);
			if(!empty($info))
				$info->value = unserialize(base64_decode($info->value));
			return $info;
		}
		if(is_null($info = $wpdb->get_var($sql)))
			return self::UpdateCachedFileInfo($file);
		return ($info=='0') ? null : unserialize(base64_decode($info));
	}
	
	static function GetFileAnalyzeTime($file)
	{
		global $wpdb;
		$t = $wpdb->get_var("SELECT analyzetime FROM $wpdb->wpfilebase_files_id3 WHERE file_id = ".$file->GetId());
		if(is_null($t)) $t = 0;
		return $t;
	}
	
	private static function cleanInfoByRef(&$info)
	{
		static $skip_keys = array('getid3_version','streams','seektable','streaminfo',
		'comments_raw','encoding', 'flags', 'image_data','toc','lame', 'filename', 'filesize', 'md5_file',
		'data', 'warning', 'error', 'filenamepath', 'filepath','popm','email','priv','ownerid','central_directory','raw','apic','iTXt','IDAT');

		foreach($info as $key => &$val)
		{
			if(empty($val) || in_array(strtolower($key), $skip_keys) || strpos($key, "UndefinedTag") !== false || strpos($key, "XML") !== false)
			{
				unset($info[$key]);
				continue;
			}
				
			if(is_array($val) || is_object($val))
				self::cleanInfoByRef($info[$key]);
			else if(is_string($val))
			{
				$a = ord($val{0});
				if($a < 32 || $a > 126 || $val{0} == '?' || strpos($val, chr(01)) !== false || strpos($val, chr(0x09)) !== false)  // check for binary data
				{
					unset($info[$key]);
					continue;
				}
			}
		}
	}
	
	private static function removeLongData(&$info, $max_length)
	{
		foreach(array_keys($info) as $key)
		{				
			if(is_array($info[$key]) || is_object($info[$key]))
				self::removeLongData($info[$key], $max_length);
			else if(is_string($info[$key]) && strlen($info[$key]) > $max_length)
				unset($info[$key]);
		}
	}
	
	private static function getKeywords($info, &$keywords) {
		foreach($info as $key => $val)
		{
			if(is_array($val) || is_object($val)) {
				self::getKeywords($val, $keywords);
				self::getKeywords(array_keys($val), $keywords); // this is for archive files, where file names are array keys
			} else if(is_string($val)) {
				$val = explode(' ', strtolower(preg_replace('/\W+/u',' ',$val)));
				foreach($val as $v) {
					if(!in_array($v, $keywords))
						array_push($keywords, $v);
				}
			}
		}
		return $keywords;
	}

	private static function Unzip($filename)
	{
		global $wp_filesystem;
		if(empty($wp_filesystem) || !is_object($wp_filesystem)) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
			require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
			if ( ! defined('FS_CHMOD_DIR') )
				define('FS_CHMOD_DIR', octdec(WPFB_PERM_DIR) );
			if ( ! defined('FS_CHMOD_FILE') )
				define('FS_CHMOD_FILE', octdec(WPFB_PERM_FILE) );
			$wp_filesystem = new WP_Filesystem_Direct(null);
		}
		$dir = WPFB_Admin::GetTmpPath(basename($filename));
		
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		
		if(!function_exists('unzip_file'))
			return null;
		
		$result = unzip_file($filename, $dir);
		
		if ( is_wp_error($result) ) {
			$wp_filesystem->delete($dir, true);
			return null;
		}		
		
		$files = array_map(create_function('$fn','return substr($fn,'.strlen($dir).');'), list_files($dir));		
		$result = array('dir' => $dir, 'files' => $files);
		return $result;
	}
	
	private static function DeleteDir($dir)
	{
		global $wp_filesystem;
		$wp_filesystem->delete($dir, true);
	}
}