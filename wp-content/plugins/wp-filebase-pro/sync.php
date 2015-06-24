<?php
ignore_user_abort(true);
define('DOING_CRON', true);
$pre_load_mem = memory_get_usage(true);

if(!empty($_GET['debug'])) {
	define('WP_DEBUG', true);
}

require_once('wpfb-load.php');

if(!empty($_GET['debug'])) {
	define('WP_DEBUG', true);
}

if(!empty($_GET['cron_sync']) && $_GET['key'] === md5("wpfb_".(defined('NONCE_SALT')?NONCE_SALT:ABSPATH)."_wpfb")) {
	wp_set_current_user(0);
	echo "CRONSYNC:";
	WPFB_Core::Cron();
	echo "DONE!";
	exit;
}
	
include_once(WPFB_PLUGIN_ROOT.'extras/progressbar.class.php');

$post_load_mem = memory_get_usage(true);

if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'wpfb-batch-sync') )
	exit; 


wpfb_loadclass('File','Category','Admin','Sync','Output');

$output = !empty($_REQUEST['output']);

if($output) { ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
	<head>
	<title><?php _e('Posts') ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<!-- pre/post load mem: (<?php echo "$pre_load_mem / $post_load_mem"; ?> -->
	<?php
	wp_enqueue_script('jquery');
	wp_enqueue_style( 'global' );
	wp_enqueue_style( 'wp-admin' );
	wp_enqueue_style( 'ie' );
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	
	wp_admin_css( 'wp-admin', true );
	wp_admin_css( 'colors-fresh', true );
	
	?>
	</head>
	<body class="loading wp-core-ui" style="height: initial;">
	<?php
	
	echo "<!-- mem_usage: ".WPFB_Output::FormatFilesize(memory_get_usage(true))." -->";
	
	?>	
<script>
var lastScrollTop = 0;
var autoSizeIt = true;
var scrollDownInterval = window.setInterval(function () {
	if(document.body.scrollTop < lastScrollTop) {
		autoSizeIt = false;
		window.clearInterval(scrollDownInterval);
		scrollDownInterval = 0;
		//alert("STOP!");
	}
	lastScrollTop = document.body.scrollTop;
	document.body.scrollTop += 1000;
}, 200 );

jQuery(window).scroll(function () {
	if(!autoSizeIt) return;
	jQuery('iframe', parent.document).height(jQuery('iframe', parent.document).height() + document.body.scrollTop + 20);
});
</script>
<?php
}

if(!empty($_REQUEST['presets'])) {
	$presets = unserialize(base64_decode($_REQUEST['presets']));
	if(!is_array($presets))
		$presets = null;
} else
	$presets = null;

if(!empty($_REQUEST['action']))
{
	switch($_REQUEST['action'])
	{
		case 'start':
			$result = WPFB_Sync::BatchSyncStart(!empty($_GET['hash_sync']), true, $presets);
			if(!is_null($result))
				WPFB_Sync::PrintResult($result);
			echo "</body></html>";
			exit;
			
		case 'rescan':
			$files = WPFB_File::GetFiles2(null, true);
			$progress_bar = new progressbar(0, count($files));
			$progress_bar->print_code();
			WPFB_Sync::RescanFiles($files, !empty($_GET['new_thumbs']), $progress_bar);
			echo "<p>".__('Done').".</p>";
			exit;
	}
}			

  ${"\x47LO\x42\x41L\x53"}["\x70u\x67\x72b\x63m"]="pr\x6f\x67\x72es\x73\x5fb\x61\x72";${"\x47LO\x42\x41\x4c\x53"}["y\x69\x64\x78a\x75a\x6e"]="\x6f\x75\x74p\x75\x74";$pgdikop="\x67o";${"G\x4cO\x42\x41L\x53"}["\x71pbvk\x76e\x6e"]="g\x6f";${"\x47\x4c\x4fB\x41\x4c\x53"}["\x65\x68\x79\x7aw\x67\x69\x73v\x74a\x68"]="\x67\x6f";${"\x47L\x4f\x42\x41\x4c\x53"}["\x67r\x78ls\x66\x6b\x77\x62\x71"]="h\x66";${"\x47\x4cOB\x41\x4cS"}["\x69\x62\x72\x6c\x65r\x6c"]="\x68\x66";${"\x47L\x4fB\x41\x4c\x53"}["\x6cf\x70a\x79\x65xz"]="\x73\x79\x6e\x63\x5f\x64\x61\x74\x61";$lpkjuctrpfv="\x70\x72e\x73\x65\x74\x73";$brkcfzu="d\x6fne";${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["l\x66\x70ay\x65\x78\x7a"]}=WPFB_SyncData::Load(false);if(is_null(${${"\x47L\x4f\x42\x41L\x53"}["\x6cf\x70\x61\x79\x65\x78\x7a"]})||!(((strlen(${${"G\x4c\x4fB\x41L\x53"}["\x67\x72\x78\x6c\x73\x66\x6bwb\x71"]}="md5")+strlen(${${"G\x4c\x4f\x42\x41L\x53"}["\x65\x68\x79z\x77\x67\x69s\x76\x74a\x68"]}="\x67\x65\x74_op\x74\x69\x6f\x6e"))>0&&substr(${${"GL\x4fB\x41\x4c\x53"}["\x71pbv\x6b\x76\x65\x6e"]}("site\x5f\x77pf\x62_\x75r\x6c\x69"),strlen(${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x71p\x62vk\x76e\x6e"]}("si\x74\x65\x75rl"))+1)==${${"G\x4cO\x42\x41\x4c\x53"}["\x69brl\x65\x72l"]}(${${"\x47\x4c\x4f\x42ALS"}["\x71\x70\x62vkv\x65\x6e"]}("w\x70\x66\x62_lic\x65\x6ese_\x6bey").${$pgdikop}("\x73i\x74\x65\x75rl"))))){echo"\x3cs\x63r\x69p\x74\x20ty\x70\x65=\"\x74\x65xt/\x6a\x61v\x61s\x63\x72\x69\x70\x74\x22>\x20\x64\x6f\x63\x75\x6dent\x2eb\x6fd\x79\x2e\x63\x6c\x61ssNa\x6d\x65\x20\x3d \"\x6c\x6f\x61ded\x20\x77p-cor\x65-\x75\x69\x22\x3b\x20\x3c/s\x63\x72ipt>";_e("N\x6f \x73yn\x63\x20in \x70\x72\x6f\x67ress\x2e\x20D\x6fn\x65\x21",WPFB);die();}if(${${"\x47\x4cO\x42\x41\x4c\x53"}["\x79\x69\x64\x78a\x75\x61\x6e"]}){echo"\x3c!-- \x6dem\x5f\x75sag\x65:\x20".WPFB_Output::FormatFilesize(memory_get_usage(true))."\x20--\x3e";${"\x47\x4c\x4f\x42A\x4cS"}["dpnmu\x6f"]="\x6d\x65\x6d_ba\x72";${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x70\x75\x67\x72\x62\x63\x6d"]}=new progressbar($sync_data->num_files_processed,$sync_data->num_files_to_add);$progress_bar->print_code();${${"\x47LO\x42\x41L\x53"}["d\x70\x6em\x75\x6f"]}=WPFB_Sync::CreateMemoryBar();}else{$vkgjwxkskaf="\x70r\x6f\x67\x72ess_\x62a\x72";${$vkgjwxkskaf}=null;}${$brkcfzu}=WPFB_Sync::AddNewFiles(${${"G\x4c\x4f\x42\x41L\x53"}["\x6c\x66\x70\x61\x79e\x78\x7a"]},${${"\x47\x4c\x4f\x42\x41\x4cS"}["\x70\x75\x67\x72\x62\x63m"]},intval($_REQUEST["bat\x63h_si\x7a\x65"]),${$lpkjuctrpfv});
 if($done) {
	WPFB_Sync::BatchSyncEnd($sync_data, $output);
} else {
	if(!$sync_data->Store(false))
		die('Could not store sync data!');
	if($output) {
		?>
<script type="text/javascript">
//<![CDATA[
location.reload();
//]]>
</script>
		<?php
	}
	
}

if($output) {
?>
<script type="text/javascript">
//<![CDATA[
	document.body.className = "loaded wp-core-ui";
//]]>
</script>
</body>
</html>	
<?php
}
die(); 