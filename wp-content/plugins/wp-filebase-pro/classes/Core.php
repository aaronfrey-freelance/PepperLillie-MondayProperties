<?php

class WPFB_Core {
	
static $load_js = false;
static $file_browser_search = false;
static $file_browser_item = null;
static $post_url_cache = array();
static $file_details_page = false;
static $ajax_url = '';


/**
 * WP-Filebase Settings Object
 *
 * @since 3.0.14
 * @access public
 * @var WPFB_Options
 */
static $settings;

static function PluginUrl($url) {
	return is_multisite() ? add_query_arg('blog_id', get_current_blog_id(), WPFB_PLUGIN_URI.$url) : (WPFB_PLUGIN_URI.$url);
}

static function InitClass()
{	
	self::$ajax_url = WPFB_Core::PluginUrl('wpfb-ajax.php');	
	if(defined('WPFB_NO_CORE_INIT')) return;	// used with CSS proxy
	
	//Load settings
	self::$settings = (object)get_option(WPFB_OPT_NAME);
	
	// load lang
	$lang_dir = defined('WPFB_LANG_DIR') ? ('../../'.WPFB_LANG_DIR) : basename(WPFB_PLUGIN_ROOT).'/languages';
	load_plugin_textdomain(WPFB, 'wp-content/plugins/'.$lang_dir, $lang_dir);

	add_action('parse_query', array(__CLASS__, 'ParseQuery')); // search
	add_action('wp_enqueue_scripts', array(__CLASS__, 'EnqueueScripts'));
	add_action('wp_footer', array(__CLASS__, 'Footer'));	
	add_action('generate_rewrite_rules', array(__CLASS__, 'GenRewriteRules'));
	add_action(WPFB.'_cron', array(__CLASS__, 'Cron'));
	add_action('wpfilebase_sync', array(__CLASS__, 'Sync')); // for Developers: New wp-filebase actions
	
	add_shortcode('wpfilebase', array(__CLASS__, 'ShortCode'));
	
	// for attachments and file browser
	add_filter('the_content',	array(__CLASS__, 'ContentFilter'), 10); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
	add_filter('ext2type', array(__CLASS__, 'Ext2TypeFilter'));

	add_filter('pre_set_site_transient_update_plugins', array(__CLASS__,'PreSetPluginsTransientFilter'));
	add_filter('plugins_api', array(__CLASS__,'PluginsApiFilter'), 10, 3);
	
	add_filter('get_edit_post_link', array(__CLASS__,'GetEditPostLinkFilter'));
	
	// register treeview stuff
	//wp_register_script('jquery-cookie', WPFB_PLUGIN_URI.'extras/jquery/jquery.cookie.js', array('jquery'));
	wp_register_script('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.js', array('jquery'), WPFB_VERSION);
	wp_register_script('jquery-treeview-edit', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.edit.js', array('jquery-treeview'), WPFB_VERSION);
	wp_register_script('jquery-treeview-async', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.async.js', array('jquery-treeview-edit'), WPFB_VERSION);
	wp_register_style('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.css', array(), WPFB_VERSION);

	// DataTables
	wp_register_script('jquery-dataTables', WPFB_PLUGIN_URI.'extras/jquery/dataTables/js/jquery.dataTables.min.js', array('jquery'), WPFB_VERSION);
	wp_register_style('jquery-dataTables', WPFB_PLUGIN_URI.'extras/jquery/dataTables/css/jquery.dataTables.css', array(), WPFB_VERSION);
	
	wp_register_script('jquery-dataTables-columnFilter', WPFB_PLUGIN_URI.'extras/jquery/dataTables/js/jquery.dataTables.columnFilter.js', array('jquery-dataTables'), WPFB_VERSION);

	wp_register_script(WPFB, WPFB_PLUGIN_URI.'js/common.js', array('jquery'), WPFB_VERSION); // cond loading (see Footer)
	
	if(empty(WPFB_Core::$settings->disable_css)) {
		$wpfb_css = get_option('wpfb_css');
		if($wpfb_css) { // static file?
			wp_enqueue_style(WPFB, strstr($wpfb_css,'//'), array(), WPFB_VERSION, 'all');
		} else {
			$upload_path = path_is_absolute(WPFB_Core::$settings->upload_path) ? '' : WPFB_Core::$settings->upload_path;
			wp_enqueue_style(WPFB, WPFB_Core::PluginUrl("wp-filebase_css.php?rp=$upload_path"), array(), WPFB_VERSION, 'all');
		}
	}

	self::RegisterFilePostType();
	
	$wpfb_admin_page = (is_admin() && !empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false) || defined('WPFB_EDITOR_PLUGIN');
	if($wpfb_admin_page)
		wpfb_loadclass('Admin');
	
	// live admin
	if(($wpfb_admin_page && @$_GET['page'] == 'wpfilebase_filebrowser') || ((WPFB_Core::CurUserCanCreateCat() || WPFB_Core::CurUserCanUpload()) && !is_admin())) {
		wp_enqueue_script(WPFB.'-live-admin', WPFB_PLUGIN_URI.'js/live-admin.js', array('jquery'), WPFB_VERSION);
		if(self::GetOpt('admin_bar'))
			add_action( 'admin_bar_menu', array(__CLASS__, 'AdminBar'), 80 );
		if(self::GetOpt('file_context_menu')) {
			wp_enqueue_script('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.js', array('jquery'));
			wp_enqueue_style('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.css', array(), WPFB_VERSION);
		}
		wp_enqueue_style('wpfb-live-admin', WPFB_PLUGIN_URI.'css/live-admin.css', array(), WPFB_VERSION);
	}

	// for admin
	if (current_user_can('edit_posts') || current_user_can('edit_pages'))
		self::MceAddBtns();
		
	self::DownloadRedirect();
	
	if( (WPFB_Core::$settings->frontend_upload || current_user_can('upload_files')) && (!empty($_GET['wpfb_upload_file']) || !empty($_GET['wpfb_add_cat'])))
		wpfb_call('Admin', empty($_GET['wpfb_upload_file'])?'ProcessWidgetAddCat':'ProcessWidgetUpload');
}

static function InitDirectScriptAccess()
{
	if(is_multisite() && !empty($_REQUEST['blog_id']) && get_current_blog_id() != $_REQUEST['blog_id'] ) {
		$blog_id = (int)$_REQUEST['blog_id'];
		if(!get_blog_details($blog_id, false))
			die('Blog does not exists!');
		switch_to_blog( $blog_id);
	}	
}

static function GetOpt($name = null) {	return empty($name) ? (array)WPFB_Core::$settings : (isset(WPFB_Core::$settings->$name) ? WPFB_Core::$settings->$name : null); }

static function AdminInit() { 
	wpfb_loadclass('AdminLite');
	if(!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false)
		wpfb_loadclass('Admin');
	WPFB_AdminLite::Init();
}
static function AdminMenu() {wpfb_call('AdminLite', 'SetupMenu');}
static function AdminBar() { wpfb_call('AdminBar','AdminBar'); }

static function Sync() { wpfb_call('Sync', 'Sync'); }
static function GenRewriteRules() { wpfb_call('Misc','GenRewriteRules'); }

static function GetPostId($query = null)
{
	global $wp_query, $post;
	
	if(!empty($post->ID)) return $post->ID;
	
	if(empty($query)) $query =& $wp_query;	
	
	return ((!empty($query->post)&&$query->post->ID>0) ? $query->post->ID :
			(!empty($query->queried_object_id) ? $query->queried_object_id : 
			(!empty($query->query['post_id']) ? $query->query['post_id'] : 
			(!empty($query->query['page_id'])? $query->query['page_id'] :
			0))));
}

static function ParseQuery(&$query)
{
	// conditional loading of the search hooks
	global $wp_query;
	
	if (!empty($wp_query->query_vars['s']))
			wpfb_loadclass('Search');
			
	
	if(!empty($_GET['wpfb_s']) || !empty($_GET['s'])) {
		WPFB_Core::$file_browser_search = true;		
		add_filter('the_excerpt',	array(__CLASS__, 'SearchExcerptFilter'), 100); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
	}
	
	// check if current post is file browser
	if( ($id=self::GetPostId($query)) == WPFB_Core::$settings->file_browser_post_id)
	{
		wpfb_loadclass('File','Category');
		if(!empty($_GET['wpfb_file'])) self::$file_browser_item = WPFB_File::GetFile($_GET['wpfb_file']);
		elseif(!empty($_GET['wpfb_cat'])) self::$file_browser_item = WPFB_Category::GetCat($_GET['wpfb_cat']);
		else {
			$url = (is_ssl()?'https':'http').'://'.$_SERVER["HTTP_HOST"].stripslashes($_SERVER['REQUEST_URI']);
			if( ($qs=strpos($url,'?')) !== false ) $url = substr($url,0,$qs); // remove query string	
			$path = trim(substr($url, strlen(WPFB_Core::GetPostUrl($id))), '/');
			if(!empty($path)) {
				self::$file_browser_item = WPFB_Item::GetByPath(urldecode($path));
				if(is_null(self::$file_browser_item)) self::$file_browser_item = WPFB_Item::GetByPath($path);
			}
		}
	}	
}



static function DownloadRedirect()
{
	$file = null;
	
	if(!empty($_GET['wpfb_dl'])) {
		wpfb_loadclass('File');
		$file = WPFB_File::GetFile($_GET['wpfb_dl']);
		@ob_end_clean(); // FIX: clean the OB so any output before the actual download is truncated (OB is started in wp-filebase.php)
	} elseif(!empty($_GET['wpfb_zip_cat'])) {
		if(!WPFB_Core::$settings->enable_cat_zip)
			wp_die('Cat ZIP features is disabled!');
		wpfb_loadclass('File','Category','Download');
		$cat = WPFB_Category::GetCat($_GET['wpfb_zip_cat']);
		if(is_null($cat)) return;
		WPFB_Download::DownloadCategoryZip($cat);
		exit;
	} else {
		if(!WPFB_Core::$settings->download_base || is_admin()) return;
		$dl_url_path = parse_url(home_url(WPFB_Core::$settings->download_base.'/'), PHP_URL_PATH);
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos === 0) {
			$filepath = trim(substr(stripslashes($_SERVER['REQUEST_URI']), strlen($dl_url_path)), '/');
			if( ($qs=strpos($filepath,'?')) !== false ) $filepath = substr($filepath,0,$qs); // remove query string
			if(!empty($filepath)) {
				wpfb_loadclass('File','Category');
				$file = is_null($file=WPFB_File::GetByPath($filepath)) ? WPFB_File::GetByPath(urldecode($filepath)) : $file;
			}
		}
	}
	
	if(!empty($file) && is_object($file) && !empty($file->is_file)) {
		$file->Download();		
		exit;
	} /* else { // don't set coockies anymore
		// no download, a normal request: set site visited coockie to disable referer check
		if(empty($_COOKIE[WPFB_OPT_NAME])) {
			@setcookie(WPFB_OPT_NAME, '1');
			$_COOKIE[WPFB_OPT_NAME] = '1';
		}
	} */
}

static function Ext2TypeFilter($arr) {
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}

static function SearchExcerptFilter($content)
{
	global $id;
	
	// replace file browser post content with search results
	if(WPFB_Core::$file_browser_search && $id == WPFB_Core::$settings->file_browser_post_id)
	{
		wpfb_loadclass('Search','File','Category');
		$content = '';
		WPFB_Search::FileSearchContent($content);
	}
	
	return $content;
}

static function ContentFilter($content)
{
	global $id, $wpfb_fb, $post;
	
	if(!WPFB_Core::$settings->parse_tags_rss && is_feed())
		return $content;
	
	if(is_object($post) && !post_password_required())
	{
		// TODO: file resulst are generated twice, 2nd time in the_excerpt filter (SearchExcerptFilter)
		// some themes do not use excerpts in search resulsts!!
		// replace file browser post content with search results
		if(WPFB_Core::$file_browser_search && $id == WPFB_Core::$settings->file_browser_post_id)
		{
			wpfb_loadclass('Search','File','Category');
			$content = '';
			WPFB_Search::FileSearchContent($content);
		} elseif($post->post_type === 'wpfb_filepage') {
			wpfb_loadclass('Output','File','Category');
			//self::$file_browser_item = $file;
			$file = WPFB_File::GetByPost($post->ID);
			if($file === null) {
				$content = "NULL";
			} elseif(!$file->CurUserCanAccess(true)) {
				$content = "NO ACCESS";
				if(WPFB_Core::$settings->inaccessible_redirect && !is_user_logged_in()) {
					$redirect = remove_query_arg('action');
					$login_url =  wp_login_url($redirect, true); // force re-auth
					$content = '<script type="text/javascript"> window.location = "'.esc_js($login_url).'"; </script><h1><a href="'.esc_attr($login_url).'">'.esc_html($login_url).'</a></h1>'; 
					return $content;
				}
			} else {
				self::$file_details_page = $file->file_id;
				if(is_single()) {
					$content = '';
					WPFB_Output::GenFilePage ($content, $file);
				} else {
					$content = $file->GenTpl2('filepage_excerpt');
				}
			}
		} else { // do not hanlde attachments when searching
			$single = is_single() || is_page();
			
			// the did_action check prevents JS beeing printed into the post during a pre-render (e.g. WP SEO)
			if($single && $post->ID == WPFB_Core::$settings->file_browser_post_id && did_action('wp_print_scripts')) {
				$wpfb_fb = true;
				wpfb_loadclass('Output', 'File', 'Category');
				WPFB_Output::FileBrowser($content, 0, empty($_GET['wpfb_cat']) ? 0 : intval($_GET['wpfb_cat']));
			}
		
			if(self::GetOpt('auto_attach_files') && ($single || self::GetOpt('attach_loop'))) {
				wpfb_loadclass('Output');			
				if(WPFB_Core::$settings->attach_pos == 0)
					$content = WPFB_Output::PostAttachments(true) . $content;
				else
					$content .= WPFB_Output::PostAttachments(true);
			}
		}
	}

    return $content;
}


static function ShortCode($atts, $content=null, $tag=null) {
	wpfb_loadclass('Output');
	return WPFB_Output::ProcessShortCode(shortcode_atts(array(
		'tag' => 'list', // file, fileurl, attachments
		'id' => -1,
		'path' => null,
		'tpl' => null,
		'sort' => null,
		'showcats' => false,
		'sortcats' => null,
		'num' => 0,
		'pagenav' => 1,
		'linktext' => null,
		 'expanded' => false, 		'search' => null,
		'private' => 0,
	
	), $atts), $content, $tag);
}


static function Footer() {
	global $wpfb_fb; // filebrowser loaded?
	
	// TODO: use enque and no cond loading ?
	if(!empty(self::$load_js)) {
		self::PrintJS();
	}
	
}



static function MceAddBtns() {
	add_filter('mce_external_plugins', array('WPFB_Core', 'McePlugins'));
	add_filter('mce_buttons', array('WPFB_Core', 'MceButtons'));
}
static function McePlugins($plugins) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::McePlugins($plugins); }
static function MceButtons($buttons) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::MceButtons($buttons); }

static function UpdateOption($name, $value = null) {
	WPFB_Core::$settings->$name = $value;
	update_option(WPFB_OPT_NAME, (array)WPFB_Core::$settings);
}

static function UploadDir() {
	static $upload_path = '';
	return empty($upload_path) ? ($upload_path = path_join(ABSPATH, empty(WPFB_Core::$settings->upload_path) ? 'wp-content/uploads/filebase' : WPFB_Core::$settings->upload_path)) : $upload_path;
}

static function GetPostUrl($id) { return isset(self::$post_url_cache[$id]) ? self::$post_url_cache[$id] : (self::$post_url_cache[$id] = get_permalink($id)); }

static function GetSortSql($sort=null, $attach_order=false, $for_cat=false)
{
	wpfb_loadclass('Output');
	$sql =  $attach_order ? ("`".($for_cat ? 'cat_order' : 'file_attach_order')."` ASC, ") : "";
	foreach(explode(',', $sort) as $s) {
		list($sf, $sd) = WPFB_Output::ParseSorting($s, $for_cat);
		$sql .= "`".esc_sql($sf)."` $sd, ";
	}
	return substr($sql, 0,-2);
}

static function EnqueueScripts()
{
	global $wp_query;
	
	if( !WPFB_Core::$settings->late_script_loading
			&& ((!empty($wp_query->queried_object_id) && $wp_query->queried_object_id == WPFB_Core::$settings->file_browser_post_id) ||
			!empty($wp_query->post) && $wp_query->post->ID == WPFB_Core::$settings->file_browser_post_id)) {
		wp_enqueue_script('jquery-treeview-async');
		wp_enqueue_style('jquery-treeview');
	}
}

static function PrintJS() {
	static $printed = false;
	if($printed) return;
	$printed = true;
	
	wp_print_scripts(WPFB);
	
	$context_menu = current_user_can('upload_files') && self::GetOpt('file_context_menu') && !defined('WPFB_EDITOR_PLUGIN') && !is_admin();
	
	$conf = array(
		'ql'=>!is_admin(), // querylinks with jQuery
		'hl'=> (int)self::GetOpt('hide_links'), // hide links
		'pl'=>(self::GetOpt('disable_permalinks') ? 0 : (int)!!get_option('permalink_structure')), // permlinks
		'hu'=> trailingslashit(home_url()),// home url
		'db'=> self::GetOpt('download_base'),// urlbase
		'fb'=> self::GetPostUrl(self::GetOpt('file_browser_post_id')),
		'cm'=>(int)$context_menu,
		'ajurl'=>WPFB_Core::$ajax_url
	);
	
	if($context_menu) {
		$conf['fileEditUrl'] = admin_url("admin.php?page=wpfilebase_files&action=editfile&file_id=");
		
		//wp_print_scripts('jquery-contextmenu');
		//wp_print_styles	('jquery-contextmenu');
	}
		
	echo "<script type=\"text/javascript\">\n//<![CDATA[\n",'wpfbConf=',json_encode($conf),';';
	
	if($context_menu) {
		echo
"wpfbContextMenu=[
	{'",__('Edit'),"':{onclick:wpfb_menuEdit,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/page_white_edit.png'}, },
	jQuery.contextMenu.separator,
	{'",__('Delete'),"':{onclick:wpfb_menuDel,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/delete_icon.gif'}}
];\n";
		
	}
	
	echo "function wpfb_ondl(file_id,file_url,file_path){ ",WPFB_Core::$settings->dlclick_js," }";	
	echo "\n//]]>\n</script>\n";
}

// OPTIMZE: not so deep function calls

// gets custom template list or single if tag specified
static function GetFileTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_file');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_file');
	return empty($tag) ? $tpls : @$tpls[$tag];
}

static function GetCatTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_cat');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_cat');
	return empty($tag) ? $tpls : @$tpls[$tag];
}

static function GetTpls($type, $tag=null) { return ($type == 'cat') ? self::GetCatTpls($tag) : self::GetFileTpls($tag);}

static function SetFileTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_file', $tpls) : false; }
static function SetCatTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_cat', $tpls) : false; }

static function GetParsedTpl($type, $tag) {
	if(empty($tag)) return null;
	if($tag == 'default') return self::GetOpt("template_{$type}_parsed");
	$on = WPFB_OPT_NAME.'_ptpls_'.$type;
	$ptpls = get_option($on);
	if(empty($ptpls)) {
		$ptpls = wpfb_call('TplLib','Parse',self::GetTpls($type));
		update_option($on, $ptpls);
	}
	return empty($ptpls[$tag]) ? null : $ptpls[$tag];
}



static function Cron() {
	if(self::$settings->cron_sync  || !empty($_GET['cron_sync']) ) {
		wpfb_call('Sync', 'Sync');
		update_option(WPFB_OPT_NAME.'_cron_sync_time', empty($_SERVER["REQUEST_TIME"]) ? time() : $_SERVER["REQUEST_TIME"]);
	}
}

static function GetMaxUlSize() {	return wpfb_call('Misc','ParseIniFileSize', ini_get('upload_max_filesize')); }

public static function GetCustomFields($full_field_names=false, &$default_values=null) {
	$custom_fields = isset(WPFB_Core::$settings->custom_fields)?explode("\n",WPFB_Core::$settings->custom_fields):array();
	$arr = array();
	$default_values = array();
	if(empty($custom_fields[0])) return array();
	foreach($custom_fields as $cf) {
		$cfa = explode("|", $cf);
		$arr[$k = $full_field_names?('file_custom_'.trim($cfa[1])):trim($cfa[1])] = $cfa[0];
		$default_values[$k] = empty($cfa[2]) ? '' : $cfa[2];
	}
	return $arr;
}


static function GetOldCustomCssPath($path=null) {
	$path = empty($path) ? self::UploadDir() : (ABSPATH .'/'.trim(str_replace('\\','/',str_replace('..','', $path)),'/'));
	return @is_dir($path) ? "$path/_wp-filebase.css" : null;
}

static function CreateTplFunc($parsed_tpl) {	return create_function('$f', "return ($parsed_tpl);"); }

static function CheckPermission($perms, $allow_everyone=false, $user=null)
{	
	$user = is_null($user) ? wp_get_current_user() : (empty($user->roles) ? new WP_User($user) : $user);
	$user->get_role_caps();
			
	if(user_can($user, 'manage_options')) // is admin?
		return true;
	
	if(!is_array($perms)) {
		if(strpos($perms,'|') !== false) {
			$perms = explode('|', $perms);
			return (count(array_filter(array_map(array(__CLASS__, __FUNCTION__), $perms))) > 0); 
		}
		$perms = WPFB_Core::GetOpt("perm_".$perms);
		if(!is_array($perms)) return false;
	}
	
	$perms = array_filter($perms);
	
	if(empty($perms) && $allow_everyone)
		return true;	
		
	foreach($user->roles as $ur) { // check user roles against item roles
		if(in_array($ur, $perms)) return true;
	}
	
	if(in_array("_u_".$user->user_login, $perms)) return true;
	
	return false;
}

static function PreSetPluginsTransientFilter($value)
{
	if(!isset($value->response) || !is_array($value->response))
		return $value;
	$wpfb_dir = basename(WPFB_PLUGIN_ROOT);
	$lvi = wpfb_call('ExtensionLib','GetLatestVersionInfoExt');
	if(!empty($lvi)) $value->response = array_merge($value->response, $lvi);
	return $value;
}

static function PluginsApiFilter($value, $action=null, $args=null)
{
	if(!is_object($args)) $args = (object)$args;
	return ($action === 'plugin_information' && ($args->slug === "wp-filebase-pro"  ||  strncmp($args->slug, "wpfb-", 5) === 0))
			  ? wpfb_call('ExtensionLib','GetApiPluginInfo', $args->slug)
			  : $value;
}

static function GetEditPostLinkFilter($link, $post_id=0)
{
	global $post;
	return ($post && is_object($post) && $post->post_type === 'wpfb_filepage'
			  && !is_null($file = wpfb_call('File','GetByPost',$post->ID)))
			  ? ($file->GetEditUrl().'&redirect_to='.urlencode(get_permalink($post->ID)))
			  : $link;
}
static function RegisterFilePostType()
{
	register_post_type( 'wpfb_filepage',
		array(
			'labels' => array(
				'name' => __( 'Files',WPFB ),
				'singular_name' => __( 'File', WPFB )
			),
			'show_ui' => false,
			'show_in_menu' => false,
			'public' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => WPFB_Core::$settings->file_page_url_slug, 'with_front' => WPFB_Core::$settings->file_page_url_wfront), //WPFB_Core::$settings->file_post_type_slug),
			'supports' => array('comments' => WPFB_Core::$settings->file_page_comments),
			'can_export' => false,
			'exclude_from_search' => !WPFB_Core::$settings->search_integration,
			//'_edit_link' => true,
		)
	);
	
	 // register_taxonomy_for_object_type( 'post_tag', 'wpfb_filepage' ); TODO
}

static function CurUserCanCreateCat()
{
	return (WPFB_Core::CheckPermission('create_cat'));
}

static function CurUserCanUpload()
{
	return (WPFB_Core::CheckPermission('upload_files'));
}

}
