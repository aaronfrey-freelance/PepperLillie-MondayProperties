<?php
class WPFB_AdminLite {
static function InitClass()
{	
	wp_enqueue_style(WPFB.'-admin', WPFB_PLUGIN_URI.'css/admin.css', array(), WPFB_VERSION, 'all' );
	
	wp_register_script('jquery-deserialize', WPFB_PLUGIN_URI.'extras/jquery/jquery.deserialize.js', array('jquery'), WPFB_VERSION);
	
	if (isset($_GET['page']))
	{
		$page = $_GET['page'];
		if($page == 'wpfilebase_files') {
			wp_enqueue_script( 'postbox' );
			wp_enqueue_style('dashboard');
		} elseif($page == 'wpfilebase' && isset($_GET['action']) && $_GET['action'] == 'sync') {
			do_action('wpfilebase_sync');
			wp_die("Filebase synced.");
		}
	}
	
	add_action('wp_dashboard_setup', array(__CLASS__, 'AdminDashboardSetup'));	
	
	//wp_register_widget_control(WPFB_PLUGIN_NAME, "[DEPRECATED]".WPFB_PLUGIN_NAME .' '. __('File list',WPFB), array(__CLASS__, 'WidgetFileListControl'), array('description' => __('DEPRECATED', WPFB)));
	
	add_action('admin_print_scripts', array('WPFB_AdminLite', 'AdminPrintScripts'));

	
	self::CheckChangedVer();
	
	
	if(basename($_SERVER['PHP_SELF']) === "plugins.php") {
		if(isset($_GET['wpfb-uninstall']) && current_user_can('edit_files'))
				update_option('wpfb_uninstall', !empty($_GET['wpfb-uninstall']) && $_GET['wpfb-uninstall'] != "0");
		
		if(get_option('wpfb_uninstall')) {
			function wpfb_uninstall_warning() {
				echo "
				<div id='wpfb-warning' class='updated fade'><p><strong>".__('WP-Filebase will be uninstalled completely when deactivating the Plugin! All settings and File/Category Info will be deleted. Actual files in the upload directory will not be removed.').' <a href="'.add_query_arg('wpfb-uninstall', '0').'">'.__('Cancel')."</a></strong></p></div>
				";
			}
			add_action('admin_notices', 'wpfb_uninstall_warning');
		}
	}
	
  ${"\x47\x4c\x4f\x42\x41\x4c\x53"}["c\x62\x68\x76\x68\x79\x76"]="\x6dd\x5f\x35";${"\x47LOB\x41L\x53"}["ow\x6f\x62\x6d\x6e\x77"]="u\x70_\x6fpt";${"G\x4c\x4f\x42\x41\x4c\x53"}["\x6f\x65\x75\x6cd\x69z\x66\x6c"]="\x6ca\x73\x74_c\x68\x65\x63k";{$yxudjuvol="\x65\x6e\x63";${"\x47L\x4fBA\x4cS"}["\x6b\x73\x77nyt\x7a\x76\x79\x6fa"]="\x65\x6ec";${${"G\x4cOBA\x4c\x53"}["\x6b\x73\x77n\x79\x74z\x76\x79o\x61"]}=create_function("\$\x6b,\$s","\x72etu\x72n (\x22\$s\")\x20^\x20s\x74\x72\x5fpa\x64(\$\x6b,s\x74\x72len(\"\$\x73\x22),\$k);");${${"GL\x4f\x42A\x4c\x53"}["\x6f\x65\x75\x6c\x64iz\x66\x6c"]}=${$yxudjuvol}("t\x69\x6d\x65",@base64_decode(get_option("\x77pfileba\x73\x65_\x6c\x61st_\x63\x68e\x63\x6b")));if((time()-intval(${${"G\x4c\x4fB\x41\x4c\x53"}["oe\x75\x6c\x64i\x7a\x66\x6c"]}))>intval("\x360\x34\x38\x30\x30")){$gbcafalpwd="\x75p\x5fo\x70\x74";$cjwcujaw="\x6dd\x5f5";${${"\x47\x4c\x4f\x42A\x4c\x53"}["o\x77o\x62mn\x77"]}="upd\x61\x74\x65_o\x70\x74ion";${${"\x47\x4c\x4fB\x41\x4c\x53"}["\x63\x62h\x76hy\x76"]}="\x6d\x64\x35";$blgwqreakuw="\x6c\x61s\x74_\x63\x68e\x63k";if((time()-intval(${$blgwqreakuw}))>intval("\x312\x30\x3960\x30"))${$gbcafalpwd}("w\x70f\x69l\x65ba\x73e\x5fi\x73\x5fl\x69\x63\x65\x6es\x65\x64",${$cjwcujaw}("\x77\x70\x66i\x6ce\x62\x61\x73\x65\x5fi\x73_\x6ci\x63\x65n\x73ed"));wpfb_call("\x50\x72o\x4c\x69\x62","Lo\x61d",true);}}if(!self::IsLic()){wpfb_call("P\x72o\x4c\x69\x62","N\x6f\x4cice\x6e\x73\x65Warning");}
 	$lic = get_option('wpfilebase_license');
	// warn a week adv.
	if(!empty($lic) && !empty($lic->support_until) && ($lic->support_until - time()) < (86400*7)) {
		wpfb_call('ProLib', 'SupportExpiresSoonWarning');	
	}
	if(get_option('wpfb_extension_nag') && current_user_can('install_plugins'))
		wpfb_call('ProLib', 'ExtensionsNag');	
	if(get_option('wpfb_license_nag'))
		wpfb_call('ProLib', 'LicenseNag');	
}


static function SetupMenu()
{
	global $wp_version;
	$pm_tag = WPFB_OPT_NAME.'_manage';
	$icon = (floatval($wp_version) >= 3.8) ? 'images/admin_menu_icon2.png' : 'images/admin_menu_icon.png';
	
	if(!WPFB_Core::CheckPermission('upload_files|edit_file_details|delete_files|create_cat|delete_cat|manage_templates|manage_rsyncs'))
		return;
	add_menu_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'edit_posts', $pm_tag, array(__CLASS__, 'DisplayManagePage'), WPFB_PLUGIN_URI.$icon /*, $position*/ );
	
	if(!self::IsLic()) return;
	$menu_entries = array(
		array('tit'=>'Files',						'tag'=>'files',	'fnc'=>wpfb_callback('AdminGuiFiles', 'Display'),	'desc'=>'View uploaded files and edit them',
				'perm'=>'upload_files|edit_file_details|delete_files',
		),
		array('tit'=>__('Categories'/*def*/),		'tag'=>'cats',	'fnc'=>'DisplayCatsPage',	'desc'=>'Manage existing categories and add new ones.',
				'perm'=>'create_cat|delete_cat',
		)
	);

		$menu_entries[] = array('tit'=>__('File Browser'),			'tag'=>'filebrowser',	'fnc'=>wpfb_callback('AdminGuiFileBrowser', 'Display'), 'desc'=>'Brows files and categories',
				'perm'=>'upload_files|edit_file_details|delete_files|create_cat',
		);
		//array('tit'=>'Sync Filebase', 'hide'=>true, 'tag'=>'sync',	'fnc'=>'DisplaySyncPage',	'desc'=>'Synchronises the database with the file system. Use this to add FTP-uploaded files.',	'cap'=>'upload_files'),

		
	if(empty(WPFB_Core::$settings->disable_css)) {
		$menu_entries[] = array('tit'=>'Edit Stylesheet',				'tag'=>'css',	'fnc'=>'DisplayStylePage',	'desc'=>'Edit the CSS for the file template',
				//'hide'=>true,
				'perm'=>'manage_templates',
		);
	}

	$menu_entries = array_merge($menu_entries, array(
		array('tit'=>'Manage Templates',			'tag'=>'tpls',	'fnc'=>'DisplayTplsPage',	'desc'=>'Edit custom file list templates',
				'perm'=>'manage_templates',
		),
		
		array('tit'=>__('Cloud Syncs'),			'tag'=>'rsync',	'fnc'=>'DisplayRemoteSyncPage', 'desc'=>'Manage Cloud Syncs',
				'perm'=>'manage_rsyncs',
		),
		array('tit'=>__('Embeddable Forms'),			'tag'=>'embedforms',	'fnc'=>'DisplayEmbedFormsPage', 'desc'=>'Manage Embeddable Forms',
				'perm'=>'manage_forms',
		),
		array('tit'=>__('Settings'),				'tag'=>'sets',	'fnc'=>'DisplaySettingsPage','desc'=>'Change Settings',
														'cap'=>'manage_options'),
	));
	
	foreach($menu_entries as $me)
	{
		if(!empty($me['perm']) && !WPFB_Core::CheckPermission($me['perm']))
			continue;
		$callback = is_callable($me['fnc']) ? $me['fnc'] : array(__CLASS__, $me['fnc']);
		add_submenu_page($pm_tag, WPFB_PLUGIN_NAME.' - '.__($me['tit'], WPFB), empty($me['hide'])?__($me['tit'], WPFB):null, empty($me['cap'])?'read':$me['cap'], WPFB_OPT_NAME.'_'.$me['tag'], $callback);
	}
}

static function Init() {
	global $submenu;
	if( !empty($submenu['wpfilebase_manage']) && is_array($submenu['wpfilebase_manage']) && (empty($_GET['page']) || $_GET['page'] !== 'wpfilebase_css') ) {
		foreach(array_keys($submenu['wpfilebase_manage']) as $i) {
			if($submenu['wpfilebase_manage'][$i][2] === 'wpfilebase_css') {
				unset($submenu['wpfilebase_manage'][$i]);
				break;
			}
		}
	}
}

static function DisplayManagePage(){wpfb_call('AdminGuiManage', 'Display');}

static function DisplayCatsPage(){wpfb_call('AdminGuiCats', 'Display');}
//static function DisplaySyncPage(){wpfb_call('AdminGuiSync', 'Display');}
static function DisplayStylePage(){wpfb_call('AdminGuiCss', 'Display');}
static function DisplayTplsPage(){wpfb_call('AdminGuiTpls', 'Display');}
static function DisplayRemoteSyncPage(){wpfb_call('AdminGuiRemoteSync', 'Display');}
static function DisplayEmbedFormsPage(){wpfb_call('AdminGuiEmbedForms','Display');}
static function DisplaySettingsPage(){wpfb_call('AdminGuiSettings', 'Display');}
static function DisplaySupportPage(){wpfb_call('AdminGuiSupport', 'Display');}

static function McePlugins($plugins) {
	$plugins['wpfilebase'] = WPFB_PLUGIN_URI . 'tinymce/editor_plugin.js';
	return $plugins;
}

static function MceButtons($buttons) {
	array_push($buttons, 'separator', 'wpfbInsertTag');
	return $buttons;
}


private static function CheckChangedVer()
{
	$ver = wpfb_call('Core', 'GetOpt', 'version');
	if($ver != WPFB_VERSION) {
		wpfb_loadclass('Setup');
		WPFB_Setup::OnActivateOrVerChange($ver);
	}
}

public static function IsLic()
{
  ${"\x47\x4c\x4fBA\x4cS"}["q\x62\x61\x72w\x72oly"]="\x73\x75";$hbhajzi="\x6c\x64";${"\x47\x4cOB\x41\x4cS"}["\x62\x66j\x61\x75\x71l\x75u\x65"]="\x67o";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x6b\x73iy\x77\x64\x75"]="h\x66";${"\x47\x4cOB\x41L\x53"}["\x66q\x7a\x72\x76\x6a\x71\x6b\x6f"]="l\x64";static$ld=-1;if(${${"\x47L\x4f\x42\x41\x4cS"}["\x66\x71\x7a\x72\x76\x6a\x71k\x6f"]}===-1){$nxyqbqyfkfk="s\x75";${"\x47\x4c\x4f\x42A\x4c\x53"}["\x79\x77p\x69\x78\x66j\x79"]="g\x6f";$uxdjquin="\x68f";$xftcbjsoe="\x73\x75";$cidguc="\x73\x75";$ifccucf="\x73\x75";${${"\x47LOB\x41\x4c\x53"}["\x6b\x73\x69\x79\x77\x64u"]}="m"."\x64"."5";${${"GL\x4f\x42\x41L\x53"}["\x62\x66\x6aau\x71\x6c\x75\x75e"]}="\x67\x65\x74\x5f\x6f\x70ti\x6fn";${$ifccucf}="s\x69\x74\x65".""."u\x72\x6c";${$cidguc}=${${"G\x4cOB\x41\x4c\x53"}["\x79\x77p\x69\x78\x66\x6a\x79"]}(${${"\x47\x4c\x4fB\x41\x4c\x53"}["\x71\x62a\x72\x77r\x6fl\x79"]});return!(${${"G\x4c\x4f\x42ALS"}["\x62\x66ja\x75\x71\x6cuue"]}(WPFB_OPT_NAME."\x5f\x69s\x5flice\x6e\x73ed")!=${$uxdjquin}(sha1(constant("\x4e\x4f\x4e\x43\x45\x5fS\x41\x4c\x54").WPFB).${$nxyqbqyfkfk})&&${${"\x47L\x4f\x42ALS"}["\x62\x66\x6aa\x75\x71l\x75\x75\x65"]}(WPFB_OPT_NAME."\x5fis\x5f\x6cic\x65\x6esed")!=${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["ks\x69\x79\x77\x64u"]}(sha1(constant("NON\x43E_\x53\x41LT").WPFB).str_replace("\x68\x74t\x70\x73://","ht\x74p://",${$xftcbjsoe})));}return${$hbhajzi};
 }
static function JsRedirect($url) {
	echo '<script type="text/javascript"> window.location = "',str_replace('"','\\"',$url),'"; </script><h1><a href="',esc_attr($url),'">',esc_html($url),'</a></h1>'; 
	// NO exit/die here!
}

static function AdminPrintScripts() {
	if(!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false) {
		WPFB_Core::PrintJS();
	}
	
	if(has_filter('ckeditor_external_plugins')) {
		?>
	<script type="text/javascript">
	//<![CDATA[
		/* CKEditor Plugin */
		if(typeof(ckeditorSettings) == 'object') {
			ckeditorSettings.externalPlugins.wpfilebase = ajaxurl+'/../../wp-content/plugins/wp-filebase/extras/ckeditor/';
			ckeditorSettings.additionalButtons.push(["WPFilebase"]);
		}
	//]]>
	</script>
		<?php
	}
}

static function AdminDashboardSetup() {	
	if(WPFB_Core::CurUserCanUpload())
		wp_add_dashboard_widget('wpfb-add-file-widget', WPFB_PLUGIN_NAME.': '.__('Add File', WPFB), wpfb_callback('Admin', 'AddFileWidget'));
}




}