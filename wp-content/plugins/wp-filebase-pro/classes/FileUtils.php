<?php class WPFB_FileUtils {

static function GetFileSize($file)
{
	$fsize = filesize($file);
	
	// If the result is negative...
	if ($fsize < 0 || $fsize > 1200000000) // cannot rely on big file sizes, or negative!!
	{
		// If the platform is Windows...
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			// Try using the NT substition modifier %~z
			$size = trim(exec("for %F in (\"".$file."\") do @echo %~zF"));

			// If the return is blank, zero, or not a number
			if (!$size || !ctype_digit($size))
			{
				// Use the Windows COM interface
				$fsobj = new COM('Scripting.FileSystemObject');
				if (dirname($file) == '.')
					$file = ((substr(getcwd(), -1) == DIRECTORY_SEPARATOR) ? getcwd().basename($file) : getcwd().DIRECTORY_SEPARATOR.basename($file));
				if($fsobj) {
					$f = $fsobj->GetFile($file);
					return $f->Size;
				}
			}
			// Otherwise, return the result of the 'for' command
			if(is_numeric($size) && $size > 0)
				return $size;
		} else {
			// If the platform is not Windows, use the stat command (should work for *nix and MacOS)
			$size = trim(`stat -c%s "{$file}"`);
			if(is_numeric($size) && $size > 0)
				return $size;
		}
	}

	// Otherwise, return the result of the filesize() call
	return $fsize;
}

static function CreateThumbnail($src_img, $max_size)
{
	$ext = trim(strtolower(strrchr($src_img, '.')),'.');
	
	$extras_dir = WPFB_PLUGIN_ROOT . 'extras/';
	$tmp_img = $src_img.'_thumb.jpg';
	$tmp_del = true;
	
	switch($ext) {
		case 'bmp':
			if(@file_exists($extras_dir . 'phpthumb.functions.php') && @file_exists($extras_dir . 'phpthumb.bmp.php'))
				{
					@include_once($extras_dir . 'phpthumb.functions.php');
					@include_once($extras_dir . 'phpthumb.bmp.php');

					if(class_exists('phpthumb_functions') && class_exists('phpthumb_bmp'))
					{
						$phpthumb_bmp = new phpthumb_bmp();

						$im = $phpthumb_bmp->phpthumb_bmpfile2gd($src_img);
						if($im) @imagejpeg($im, $tmp_img, 100);
						else return false;
					}
				}
				break;
				
		case 'pdf':
			require_once($extras_dir . 'pdf-utils.php');
			if(empty(WPFB_Core::$settings->ghostscript_path) || !pdf_thumb(WPFB_Core::$settings->ghostscript_path, $src_img, $tmp_img)) {
				// Imagick Bug: fails if filename includes utf8 chars?!?
				$tmp_img = dirname($tmp_img).'/'.urlencode(str_replace(' ','_',basename($tmp_img))).'_thumb.jpg';				
				if(!pdf_thumb_imagick($src_img, $tmp_img)) return false;
			}	
			break;
		
		case 'tiff':
		case 'tif':
		case 'psd':
		case 'cr2': // RAW
			
			if(class_exists('Imagick')) {
				$image = new Imagick($src_img);
				if($ext === 'psd')
					$image->setIteratorIndex(0);
				$image->setImageFormat('jpeg');
				$image->writeImage($tmp_img);
			} else {
				@exec("convert \"$src_img\" \"$tmp_img\"");
				if(!is_file($tmp_img))
					return false;
			}
		break;
		
		default:
				$tmp_img = $src_img;
				$tmp_del = false;
			break;
	}
	
	$tmp_size = array();
	if(!@file_exists($tmp_img) || @filesize($tmp_img) == 0 || !WPFB_FileUtils::IsValidImage($tmp_img, $tmp_size))
	{
		if($tmp_del && is_file($tmp_img)) @unlink($tmp_img);
		return false;
	}
		
	if(!function_exists('image_make_intermediate_size')) {
		require_once(ABSPATH . 'wp-includes/media.php');
		if(!function_exists('image_make_intermediate_size'))
		{
			if($tmp_del && is_file($tmp_img)) @unlink($tmp_img);
			wp_die('Function image_make_intermediate_size does not exist!');
			return false;
		}
	}
	
	$dir = dirname($src_img).'/';
	$thumb = @image_make_intermediate_size($tmp_img, $max_size, $max_size);
	
	if((!$thumb || is_wp_error($thumb)) && !empty($tmp_size) && max($tmp_size) <= $max_size) { // error occurs when image is smaller than thumb_size. in this case, just copy original
			$name = wp_basename($src_img, ".$ext");
			$new_thumb = "{$name}-{$tmp_size[0]}x{$tmp_size[1]}".strtolower(strrchr($tmp_img, '.'));
			if($tmp_del) rename($tmp_img, $dir.$new_thumb);
			else copy($tmp_img, $dir.$new_thumb);
			
			$thumb = array('file' => $new_thumb);
	}
	
	if($tmp_del && is_file($tmp_img)) unlink($tmp_img);
	
	if(!$thumb ) return false;
	
	

	rename($dir.$thumb['file'], $fn = $dir.str_ireplace(array('.pdf_thumb','.tiff_thumb','.tif_thumb','.bmp_thumb'),'',$thumb['file']));

	return $fn;
}

static function IsValidImage($img, &$img_size = null) {
	$fs = WPFB_FileUtils::GetFileSize($img);
	if($fs < 50 || $fs > 20000000) return false; // skip big files to prevent hight mem usage
	$s = @getimagesize($img);
	if($s !== false) $img_size = $s;
	return $s !== false;
}

static function FileHasImageExt($name) {	
	$name = strtolower(substr($name, strrpos($name, '.') + 1));
	return ($name == 'png' || $name == 'gif' || $name == 'jpg' || $name == 'jpeg' || $name == 'bmp' || $name == 'tif' || $name == 'tiff' || $name == 'psd' || $name == 'cr2');
}


// copy of wp's copy_dir, but moves everything
static function MoveDir($from, $to)
{
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
	
		wpfb_call('Admin','DisableTimeouts');
	
	$wp_filesystem = new WP_Filesystem_Direct(null);
	
	$dirlist = $wp_filesystem->dirlist($from);

	$from = trailingslashit($from);
	$to = trailingslashit($to);

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( 'f' == $fileinfo['type'] ) {
			if ( ! $wp_filesystem->move($from . $filename, $to . $filename, true) )
				return false;
			$wp_filesystem->chmod($to . $filename, octdec(WPFB_PERM_FILE));
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->mkdir($to . $filename, octdec(WPFB_PERM_DIR)) )
				return false;
			if(!self::MoveDir($from . $filename, $to . $filename))
				return false;
		}
	}
	
	// finally delete the from dir
	@rmdir($from);
	
	return true;
}

}