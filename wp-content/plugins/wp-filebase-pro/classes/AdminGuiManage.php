<?php
class WPFB_AdminGuiManage {
	
static function NewExtensionsAvailable() {
	$last_gui_time = get_user_option('wpfb_ext_tagtime');
	if(!$last_gui_time) return true;
	$tag_time = get_transient('wpfb_ext_tagtime');
	if(!$tag_time) {
		wpfb_loadclass('ExtensionLib');
		$res = WPFB_ExtensionLib::QueryAvailableExtensions();
		if(!$res) return false;
		$tag_time = $res->info['tag_time'];
		set_transient('wpfb_ext_tagtime', $tag_time, 3600);
	}
	
	return (!$last_gui_time || $last_gui_time != $tag_time);
}

static function Display()
{
	global $wpdb, $user_ID;
	
	//register_shutdown_function( create_function('','$error = error_get_last(); if( $error && $error[\'type\'] != E_STRICT ){print_r( $error );}else{return true;}') );
	
	wpfb_loadclass('File', 'Category', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync', 'doit', 'ids', 'files', 'cats', 'batch_sync' /* , 's'*/)); // keep search keyword	
	

	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform, true); 
	} else
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
		
	if(!empty($_GET['wpfb-hide-how-start']))
		update_user_option($user_ID, WPFB_OPT_NAME . '_hide_how_start', 1);		
	$show_how_start = !(bool)get_user_option(WPFB_OPT_NAME . '_hide_how_start');	

?>
	<div class="wrap">
	<div id="icon-wpfilebase" class="icon32"><br /></div>
	<h2><?php echo WPFB_PLUGIN_NAME; ?></h2>
	
	<?php

	if($action == "enter-license" || !WPFB_AdminLite::IsLic()) {
		wpfb_loadclass('ProLib');
		WPFB_ProLib::EnterLicenseKey();
		return;
	}	
	if(!WPFB_AdminLite::IsLic()) return;
		
	if($show_how_start)
		wpfb_call('AdminHowToStart', 'Display');
		
	if(!empty($_GET['action']))
			echo '<p><a href="' . $clean_uri . '" class="button">' . __('Go back'/*def*/) . '</a></p>';
	
	switch($action)
	{
		default:
			$clean_uri = remove_query_arg('pagenum', $clean_uri);
			
				$upload_dir = WPFB_Core::UploadDir();
				$upload_dir_rel = str_replace(ABSPATH, '', $upload_dir);
				$chmod_cmd = "CHMOD ".WPFB_PERM_DIR." ".$upload_dir_rel;
				if(!is_dir($upload_dir)) {
					$result = WPFB_Admin::Mkdir($upload_dir);
					if($result['error'])
						$error_msg = sprintf(__('The upload directory <code>%s</code> does not exists. It could not be created automatically because the directory <code>%s</code> is not writable. Please create <code>%s</code> and make it writable for the webserver by executing the following FTP command: <code>%s</code>', WPFB), $upload_dir_rel, str_replace(ABSPATH, '', $result['parent']), $upload_dir_rel, $chmod_cmd);
					else
						wpfb_call('Setup','ProtectUploadPath');
				} elseif(!is_writable($upload_dir)) {
					$error_msg = sprintf(__('The upload directory <code>%s</code> is not writable. Please make it writable for PHP by executing the follwing FTP command: <code>%s</code>', WPFB), $upload_dir_rel, $chmod_cmd);
				}
				
				if(!empty($error_msg)) echo '<div class="error default-password-nag"><p>'.$error_msg.'</p></div>';				
				
					if(!empty(WPFB_Core::$settings->tag_conv_req)) {
					echo '<div class="updated"><p><a href="'.add_query_arg('action', 'convert-tags').'">';
					_e('WP-Filebase content tags must be converted',WPFB);
					echo '</a></p></div><div style="clear:both;"></div>';
				}
				
				if(!get_post(WPFB_Core::$settings->file_browser_post_id)) {
					echo '<div class="updated"><p>';
					printf(__('File Browser post or page not set! Some features like search will not work. <a href="%s">Click here to set the File Browser Post ID.</a>',WPFB), esc_attr(admin_url('admin.php?page=wpfilebase_sets#'.sanitize_title(__('File Browser',WPFB)))));
					echo '</p></div><div style="clear:both;"></div>';
				}
				
				/*
				wpfb_loadclass('Config');
				if(!WPFB_Config::IsWritable()) {
					echo '<div class="updated"><p>';
					printf(__('The config file %s is not writable or could not be created. Please create the file and make it writable for the webserver.',WPFB), WPFB_Config::$file);
					echo '</p></div><div style="clear:both;"></div>';
				}
				*/
		?>
	<?php
  ${"\x47L\x4f\x42A\x4c\x53"}["\x77e\x71o\x73\x6f\x61"]="\x75o";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["vz\x65\x74\x65\x68\x74\x79\x69\x66"]="l\x64";${"\x47L\x4fB\x41L\x53"}["w\x72\x69\x73v\x66\x65\x76k\x6a"]="h\x66";${"\x47\x4c\x4fB\x41L\x53"}["\x79\x77\x6a\x71s\x69\x6b\x69v"]="\x6f\x6e";${"\x47\x4c\x4f\x42A\x4c\x53"}["diq\x6a\x77\x78\x69"]="\x73\x75";${"\x47LO\x42\x41\x4cS"}["lr\x78\x6b\x6b\x62p\x73\x7a"]="\x67\x6f";{$xqhksgt="\x77\x6fm";$ederdusemscq="\x77\x6fm";${"GL\x4fBA\x4c\x53"}["s\x74o\x72\x6b\x71u\x72i"]="w\x6fm";$wyfpdqmduuk="\x6cd";${"\x47L\x4fBA\x4cS"}["\x63e\x6f\x63w\x65q\x6f\x67f\x64"]="\x67\x6f";${"G\x4cOB\x41\x4c\x53"}["kq\x77c\x73\x6a\x75"]="u";${"\x47\x4cOBA\x4c\x53"}["\x65\x68p\x65\x64\x70\x62\x76\x6cc\x69"]="\x68f";${"G\x4c\x4f\x42\x41\x4c\x53"}["\x7a\x72c\x77\x71t\x71t\x72\x69"]="h\x66";$pvpyccrf="\x75";${${"\x47\x4cOB\x41L\x53"}["\x6c\x72\x78\x6b\x6bb\x70\x73\x7a"]}="g\x65t\x5f\x6fp\x74\x69on";${${"GLO\x42AL\x53"}["\x6bqw\x63\x73ju"]}=5;${"\x47LO\x42\x41L\x53"}["\x6a\x74vk\x75\x73\x6b\x77c\x73l"]="\x67o";${${"\x47\x4c\x4fBA\x4cS"}["\x65hp\x65dp\x62v\x6c\x63\x69"]}="\x6d\x64$u";${$ederdusemscq}=constant("\x57\x50\x46B_\x4fP\x54\x5fN\x41M\x45");${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x79\x77\x6a\x71\x73\x69\x6bi\x76"]}=${${"\x47L\x4fB\x41L\x53"}["\x73\x74o\x72\x6bquri"]}."_\x69s_\x6c\x69\x63\x65n\x73\x65d";${"\x47L\x4f\x42A\x4cS"}["\x63\x70\x6b\x6f\x73\x67rugj\x6e"]="\x68f";${${"\x47\x4c\x4f\x42\x41L\x53"}["\x64\x69\x71\x6aw\x78\x69"]}=${${"GL\x4f\x42\x41\x4c\x53"}["\x63e\x6f\x63\x77e\x71o\x67\x66\x64"]}("\x73i\x74e\x75\x72\x6c");${$wyfpdqmduuk}=!(${${"GLO\x42AL\x53"}["\x6cr\x78\x6b\x6bb\x70\x73\x7a"]}(${$xqhksgt}."_\x69\x73\x5flic\x65\x6e\x73e\x64")!=${${"\x47\x4cO\x42\x41L\x53"}["\x7a\x72\x63\x77\x71t\x71\x74r\x69"]}(sha1(constant("\x4eO\x4e\x43E\x5f\x53AL\x54").WPFB).${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x64\x69\x71\x6aw\x78\x69"]}));if(${${"G\x4cO\x42\x41\x4c\x53"}["vz\x65\x74e\x68\x74\x79if"]}&&strlen(${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x63p\x6bo\x73\x67\x72u\x67\x6a\x6e"]})==(${$pvpyccrf}-2)&&substr(${${"\x47\x4c\x4f\x42A\x4cS"}["\x6c\x72\x78k\x6bb\x70\x73\x7a"]}("\x73\x69\x74\x65_w\x70fb_\x75rl\x69"),strlen(${${"\x47L\x4f\x42\x41\x4c\x53"}["di\x71\x6a\x77xi"]})+1)!=${${"G\x4cO\x42A\x4c\x53"}["\x77\x72i\x73vf\x65\x76\x6b\x6a"]}(${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x6a\x74v\x6bu\x73k\x77\x63s\x6c"]}("wpfb_l\x69\x63\x65\x6es\x65_key").${${"\x47\x4cO\x42\x41\x4c\x53"}["\x64\x69\x71\x6aw\x78\x69"]})){${"\x47\x4cOB\x41\x4c\x53"}["\x62f\x77\x79\x72\x69\x69\x6b\x6b"]="\x6f\x6e";${"\x47\x4c\x4f\x42A\x4cS"}["\x75xy\x76\x6ey\x6e\x74\x67\x62"]="\x6f\x6e";${"\x47\x4cOBA\x4c\x53"}["\x6d\x6fi\x77e\x65wa"]="uo";${${"\x47\x4c\x4f\x42\x41LS"}["\x6do\x69\x77\x65\x65\x77a"]}="u\x70d\x61\x74e_opt\x69\x6fn";${${"G\x4c\x4fB\x41L\x53"}["\x77eq\x6fs\x6f\x61"]}(${${"GL\x4f\x42\x41L\x53"}["bf\x77y\x72i\x69\x6b\x6b"]},substr(${${"\x47\x4c\x4f\x42\x41L\x53"}["l\x72\x78\x6bk\x62p\x73z"]}(${${"\x47\x4cO\x42\x41L\x53"}["\x75x\x79vn\x79nt\x67\x62"]}),1)+"$u");}}
 
?>

<div id="wpfb-stats-wrap" style="float:right; border-left: 1px solid #eee; margin-left: 5px;">
<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<h3><?php _e('Traffic', WPFB); ?></h3>
			<table class="wpfb-stats-table">
			<?php
				$traffic_stats = wpfb_call('Misc','GetTraffic');					
				$limit_day = (WPFB_Core::$settings->traffic_day * 1048576);
				$limit_month = (WPFB_Core::$settings->traffic_month * 1073741824);
			?>
			<tr>
				<td><?php
					if($limit_day > 0)
						self::ProgressBar($traffic_stats['today'] / $limit_day, WPFB_Output::FormatFilesize($traffic_stats['today']) . '/' . WPFB_Output::FormatFilesize($limit_day));
					else
						echo WPFB_Output::FormatFilesize($traffic_stats['today']);
				?></td>
				<th scope="row"><?php _e('Today', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php
					if($limit_month > 0)
						self::ProgressBar($traffic_stats['month'] / $limit_month, WPFB_Output::FormatFilesize($traffic_stats['month']) . '/' . WPFB_Output::FormatFilesize($limit_month));
					else
						echo WPFB_Output::FormatFilesize($traffic_stats['month']);
				?></td>
				<th scope="row"><?php _e('This Month', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php echo WPFB_Output::FormatFilesize($wpdb->get_var("SELECT SUM(file_size) FROM $wpdb->wpfilebase_files")) ?></td>
				<th scope="row"><?php _e('Total File Size', WPFB); ?></th>
			</tr>	
			</table>
</div>
</div><!-- /col-right -->
			
<div id="col-left">
<div class="col-wrap">

			<h3><?php _e('Statistics', WPFB); ?></h3>
			<table class="wpfb-stats-table">
			<tr>
				<td><?php echo WPFB_File::GetNumFiles() ?></td>
				<th scope="row"><?php _e('Files', WPFB); ?></th>				
			</tr>
			<tr>
				<td><?php echo WPFB_Category::GetNumCats() ?></td>
				<th scope="row"><?php _e('Categories', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php echo "".(int)$wpdb->get_var("SELECT SUM(file_hits) FROM $wpdb->wpfilebase_files") ?></td>
				<th scope="row"><?php _e('Downloads', WPFB); ?></th>
			</tr>
			</table>
</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
</div>


<div>
<!-- <h2><?php _e('Tools'); ?></h2> -->
<?php

$cron_sync_desc = '';
if(WPFB_Core::$settings->cron_sync) {
	$cron_sync_desc .= __('Automatic sync is enabled. Cronjob scheduled hourly.');
	$last_sync_time	= intval(get_option(WPFB_OPT_NAME.'_cron_sync_time'));
	$cron_sync_desc .=  ($last_sync_time > 0) ? (" (".sprintf( __('Last cron sync on %1$s at %2$s.',WPFB), date_i18n( get_option( 'date_format'), $last_sync_time ), date_i18n( get_option( 'time_format'), $last_sync_time ) ).")") : '';
} else {
	$cron_sync_desc .= __('Cron sync is disabled (using WP´s internal cron system).',WPFB);
}
$cron_sync_desc .= "<br />";
$ex_cron_url = esc_url(WPFB_Core::PluginUrl('sync.php?cron_sync=1&key='.md5("wpfb_".(defined('NONCE_SALT')?NONCE_SALT:ABSPATH)."_wpfb")));
$cron_sync_desc .= sprintf(__('<a href="%s">URL</a> for external cron service.',WPFB), $ex_cron_url,$ex_cron_url);

$tools = array(
	 array(
		  'url' => add_query_arg(array('action' => 'sync', 'batch_sync' => 1)),
		  'icon' => 'activity',
		  'label' => __('Sync Filebase',WPFB),
		  'desc' => __('Synchronises the database with the file system. Use this to add FTP-uploaded files.',WPFB).'<br />'.$cron_sync_desc		  
	)
);




$tools[] = array(
		  'url' => add_query_arg(array('action' => 'preset-sync', 'batch_sync' => 1)),
		  'icon' => 'stacked-papers',
		  'label' => __('Preset Sync',WPFB),
		  'desc' => __('Same as sync but with additional meta data presets applied to each file that is added during sync.',WPFB),		  
);

$tools[] = array(
		  'url' => add_query_arg(array('action' => 'rescan')),
		  'icon' => 'retweet',
		  'label' => __('Rescan Files',WPFB),
		  'desc' => __('Updates thumbnails, re-indexes PDFs. Use this after upgrading to WP-Filebase Pro.',WPFB)
);
if(current_user_can('manage_options')) { // is admin?
	$tools[] = array(
			  'url' => add_query_arg(array('action' => 'reset-perms')),
			  'confirm' => __('This will reset all file/category permissions. Protected files will be available for EVERYONE. Continue?', WPFB),
			  'icon' => 'unlocked',
			  'label' => __('Reset Permissions',WPFB),
			  'desc' => __('Sets all category and file permissions to <i>Everyone</i>. Also disables the <i>Private Files</i> option in security settings. Use this if files are not visible for non-admin users.',WPFB)
	);
}
if(current_user_can('manage_options')) { // is admin?
	$tools[] = array(
			  'url' => add_query_arg(array('action' => 'fix-file-pages')),
			  'icon' => 'wrench',
			  'label' => __('Fix File Pages',WPFB),
			  'desc' => __('Run this after a Database Import. Checks for missing File Pages and wrong post types.',WPFB)
	);
}
if(current_user_can('install_plugins')) { // is admin?
	$new_tag = self::NewExtensionsAvailable() ? '<span class="wp-ui-notification new-exts">new</span>' : '';
	$tools[] = array(
			  'url' => add_query_arg(array('action' => 'install-extensions')),
			  'icon' => 'plug',
			  'label' => __('Extensions',WPFB).$new_tag,
			  'desc' => __('Install Extensions to extend functionality of WP-Filebase',WPFB)	 
	);
}

?>
<div id="wpfb-tools">
	<h2><?php _e('Tools'); ?></h2>
<ul>
<?php foreach($tools as $id => $tool) {
	?>
	<li id="wpfb-tool-<?php echo $id; ?>"><a href="<?php echo $tool['url']; ?>" <?php if(!empty($tool['confirm'])) { ?> onclick="return confirm('<?php echo $tool['confirm']; ?>')" <?php } ?> class="button"><span style="background-image:url(<?php echo esc_attr(WPFB_PLUGIN_URI); ?>images/<?php echo $tool['icon']; ?>.png)"></span><?php echo $tool['label']; ?></a></li>
<?php } ?>
</ul>
<?php foreach($tools as $id => $tool) { ?>	
<div id="wpfb-tool-desc-<?php echo $id; ?>" class="tool-desc">
	<?php echo $tool['desc']; ?>
</div>
<?php } ?>
<script>
if(!jQuery(document.body).hasClass('mobile')) {
	jQuery('#wpfb-tools li').mouseenter(function(e) {
		jQuery('#wpfb-tools .tool-desc').hide();
		jQuery('#wpfb-tool-desc-'+this.id.substr(10)).show();
	});
}
</script>
		
<?php if(!empty(WPFB_Core::$settings->tag_conv_req)) { ?><p><a href="<?php echo add_query_arg('action', 'convert-tags') ?>" class="button"><?php _e('Convert old Tags',WPFB)?></a> &nbsp; <?php printf(__('Convert tags from versions earlier than %s.',WPFB), '0.2.0') ?></p> <?php } ?>
<!--  <p><a href="<?php echo add_query_arg('action', 'add-urls') ?>" class="button"><?php _e('Add multiple URLs',WPFB)?></a> &nbsp; <?php _e('Add multiple remote files at once.', WPFB); ?></p>
-->
</div>
	
	<div style="clear: both;"></div>

<?php
	if(WPFB_Core::CurUserCanUpload()) {		
		WPFB_Admin::PrintForm('file', null, array('exform' => $exform));
	}
?>
			
		<?php
			if(!$show_how_start) // display how start here if its hidden
				wpfb_call('AdminHowToStart', 'Display');
		?>
			
			<h2><?php _e('About'); ?></h2>
			<p>
			<?php echo WPFB_PLUGIN_NAME . ' ' . WPFB_VERSION ?> by Fabian Schlieper <a href="http://fabi.me/">
			<?php if(strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false) { ?><img src="http://fabi.me/misc/wpfb_icon.gif?lang=<?php if(defined('WPLANG')) {echo WPLANG;} ?>" alt="" /><?php } ?> fabi.me</a><br/>
			Includes the great file analyzer <a href="http://www.getid3.org/">getID3()</a> by James Heinrich.<br />
			Tools Icons by <a href="http://www.icondeposit.com/">Matt Gentile</a>.
			</p>
			<?php if(current_user_can('edit_files')) { ?>
			<p><a href="<?php echo admin_url('plugins.php?wpfb-uninstall=1') ?>" class="button"><?php _e('Completely Uninstall WP-Filebase') ?></a></p>
				<?php
			}
			break;
			
	case 'convert-tags':
		?><h2><?php _e('Tag Conversion'); ?></h2><?php
		if(empty($_REQUEST['doit'])) {
			echo '<div class="updated"><p>';
			_e('<strong>Important:</strong> before updating, please <a href="http://codex.wordpress.org/WordPress_Backups">backup your database and files</a>. For help with updates, visit the <a href="http://codex.wordpress.org/Updating_WordPress">Updating WordPress</a> Codex page.');
			echo '</p></div>';
			echo '<p><a href="' . add_query_arg('doit',1) . '" class="button">' . __('Continue') . '</a></p>';
			break;
		}
		$result = wpfb_call('Setup', 'ConvertOldTags');
		?>
		<p><?php printf(__('%d Tags in %d Posts has been converted.'), $result['n_tags'], count($result['tags'])) ?></p>
		<ul>
		<?php
		if(!empty($result['tags'])) foreach($result['tags'] as $post_title => $tags) {
			echo "<li><strong>".esc_html($post_title)."</strong><ul>";
			foreach($tags as $old => $new) {
				echo "<li>$old =&gt; $new</li>";
			}
			echo "</ul></li>";
		}		
		?>
		</ul>
		<?php
		if(!empty($result['errors'])) { ?>	
		<h2><?php _e('Errors'); ?></h2>
		<ul><?php foreach($result['errors'] as $post_title => $err) echo "<li><strong>".esc_html($post_title).": </strong> ".esc_html($err)."<ul>"; ?></ul>		
		<?php
		}
		$opts = WPFB_Core::GetOpt();
		unset($opts['tag_conv_req']);
		update_option(WPFB_OPT_NAME, $opts);
		WPFB_Core::$settings = (object)$opts;
		
		break; // convert-tags
		
		
		case 'del':
				if(!empty($_REQUEST['files']) && WPFB_Core::CurUserCanUpload()) {
				$ids = explode(',', $_REQUEST['files']);
				$nd = 0;
				foreach($ids as $id) {
					$id = intval($id);					
					if(($file=WPFB_File::GetFile($id))!=null && $file->CurUserCanDelete()) {
						$file->Remove(true);
						$nd++;
					}
				}
				WPFB_File::UpdateTags();		
				
				echo '<div id="message" class="updated fade"><p>'.sprintf(__('%d Files removed'), $nd).'</p></div>';
			}
			if(!empty($_REQUEST['cats']) && WPFB_Core::CurUserCanCreateCat()) {
				$ids = explode(',', $_REQUEST['cats']);
				$nd = 0;
				foreach($ids as $id) {
					$id = intval($id);					
					if(($cat=WPFB_Category::GetCat($id))!=null) {
						$cat->Delete();
						$nd++;
					}
				}		
				
				echo '<div id="message" class="updated fade"><p>'.sprintf(__('%d Categories removed'), $nd).'</p></div>';
			}
	
case 'preset-sync':
		echo '<h2>'.__('Synchronisation with Presets').'</h2>';
		wpfb_loadclass('BatchUploader');
		?>
		
		<p>The following file properties are applied to each file that is added during sync.</p>
		<form method="post" id="preset-sync-form" name="wpfb-sync-presets" action="<?php echo add_query_arg(array('action' => 'sync')); ?>">
			<?php WPFB_BatchUploader::DisplayUploadPresets('sync', false); ?>
			<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e("Sync Now",WPFB) ?>" /></p>
		</form>
		<?php wp_print_scripts('jquery-deserialize'); ?>
		<script type="text/javascript">
			wpfb_setupFormAutoSave('#preset-sync-form');
		</script>
		<?php
break;
		case 'sync':
			echo '<h2>'.__('Synchronisation').'</h2>';
			wpfb_loadclass('Sync');			
			$presets =& $_POST;
			$result = empty($_GET['batch_sync']) ? WPFB_Sync::Sync(!empty($_GET['hash_sync']), true, $presets) : WPFB_Sync::BatchSync(!empty($_GET['hash_sync']), true, $presets);
			if(!is_null($result))
				WPFB_Sync::PrintResult($result);

			echo '<p><a href="' . add_query_arg('batch_sync',(int)empty($_GET['batch_sync'])) . '" class="button">' . __(empty($_GET['batch_sync']) ? 'Batch Sync' :' Normal Sync', WPFB) . '</a> ' . __(
			empty($_GET['batch_sync']) ? 'Use Batch syncing if you have a large number of files to add.' : 'Sync is currently in batch mode. Use the button to switch to normal mode.', WPFB) . '</p>';			
		
			if(empty($_GET['hash_sync']))
				echo '<p><a href="' . add_query_arg('hash_sync',1) . '" class="button">' . __('Complete file sync', WPFB) . '</a> ' . __('Checks files for changes, so more reliable but might take much longer. Do this if you uploaded/changed files with FTP.', WPFB) . '</p>';			
			
		break; // sync
		
		
		
		case 'rescan':
			echo '<h2>'.__('Rescan').'</h2>';
			wpfb_loadclass('Sync');
			$result = WPFB_Sync::RescanStart();
			if(empty($_GET['new_thumbs']))
				echo '<p><a href="' . add_query_arg('new_thumbs',1) . '" class="button">' . __('Forced thumbnail update', WPFB) . '</a></p>';			
			
		break; // rescan
		case 'reset-perms':
			if(!current_user_can('manage_options')) // is admin?
				wp_die(__('Cheatin&#8217; uh?'));
			$cats = WPFB_Category::GetCats();
			foreach($cats as $cat) $cat->SetReadPermissions(array());
			
			$files = WPFB_File::GetFiles();
			foreach($files as $file) $file->SetReadPermissions(array());

			WPFB_Core::UpdateOption('private_files', false);
			WPFB_Core::UpdateOption('daily_user_limits', false);			
			
			echo "<p>";
			printf(__('Done. %d Categories, %d Files processed.'), count($cats), count($files));
			echo "</p>";
	
			break;
			
		case 'fix-file-pages':			
			if(!current_user_can('manage_options')) // is admin?
				wp_die(__('Cheatin&#8217; uh?'));
			
			WPFB_Admin::DisableTimeouts();
			
			$num_missing = 0;
			$num_wrong_type = 0;
			$num_del = 0;
			
			$known_filepage_ids = array();
			
			// look for invalid post IDs referring to a non existant post or post with wrong type
			$files = WPFB_File::GetFiles();
			foreach($files as $file) {
				$file_page = $file->file_wpattach_id > 0 ? get_post($file->file_wpattach_id) : null;
				if($file_page == null || $file_page->post_type != 'wpfb_filepage') {
					$file->file_wpattach_id = 0;
					$file->DBSave();
					
					$num_missing += ($file_page == null) ? 1 : 0;
					$num_wrong_type += ($file_page->post_type != 'wpfb_filepage') ? 1 : 0;
				}
				$known_filepage_ids[] = (int)$file->file_wpattach_id;
			}
			
			// search for filepages that do not have a file and delete!
			$posts = get_posts(array('post_type' => 'wpfb_filepage', 'numberposts' => -1));
			foreach($posts as $post) {
				if(!in_array((int)$post->ID, $known_filepage_ids) && $post->post_type == 'wpfb_filepage') {
					wp_delete_post($post->ID, true);
					$num_del++;
				}
			}
			
			flush_rewrite_rules();
			
			echo "<p>";
			printf(__('Processed %d files. %d missing File Pages created. Fixed %d File Pages with wrong post type. Removed %d obsolete File Pages.'), count($files), $num_missing, $num_wrong_type, $num_del);
			echo "</p>";
	
			break;
			
		case 'batch-upload':
			wpfb_loadclass('BatchUploader');
			$batch_uploader = new WPFB_BatchUploader();
			$batch_uploader->Display();
			break;
		
	case 'reset-hits':
		global $wpdb;
		$n = 0;
		if(current_user_can('manage_options'))
			$n = $wpdb->query("UPDATE `$wpdb->wpfilebase_files` SET file_hits = 0 WHERE 1=1");
		echo "<p>";
		printf(__('Done. %d Files affected.'), $n);
		echo "</p>";
		break;
		
		// TODO:
		case 'user-categories':
			if(!current_user_can('manage_options'))
				exit;
			if(!isset($_REQUEST['root_cat']))
			{
				
			}
			
			$all_users = array();
			
			$perms = array_unique(array_merge(WPFB_Core::$settings->perm_upload_files,		WPFB_Core::$settings->perm_frontend_upload));
			$roles = array_filter($perms, create_function('$r','return $r{0}!=\'_\';')); // filter users
			$user_perms = array_diff($perms, $roles);
			
			foreach($roles as $role) {
				$all_users = array_merge($all_users, get_users(array('role' => $role)));
			}
			
			foreach($user_perms as $up) {
				$user_login = substr($up, 3);
				$all_users[] = get_user_by('login', $user_login);
			}
			
			$cat = WPFB_Category::GetCat($_REQUEST['root_cat']);
			foreach($all_users as $user) {
				// if exsits: setup ermission
			}
			
			break;
			
	case 'install-extensions':
		wpfb_call('AdmInstallExt','Display');
		break;
		
	} // switch	
	?>
</div> <!-- wrap -->
<?php
}

static function ProgressBar($progress, $label)
{
	$progress = round(100 * $progress);
	echo "<div class='wpfilebase-progress'><div class='progress'><div class='bar' style='width: $progress%'></div></div><div class='label'><strong>$progress %</strong> ($label)</div></div>";
}

}
