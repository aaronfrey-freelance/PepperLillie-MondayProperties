<?php

define('DOING_AJAX', true);

require_once('wpfb-load.php');

function wpfb_print_json($obj) {
	//if(!WP_DEBUG)
	@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
	$json = json_encode($obj);
	@header('Content-Length: '.strlen($json));
	echo $json;
	exit;
}

if(!isset($_REQUEST['action']))
	die('-1'); 

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
if(!WP_DEBUG) {
	send_nosniff_header();
	error_reporting(0);
}

$_REQUEST = stripslashes_deep($_REQUEST);
$_POST = stripslashes_deep($_POST);
$_GET = stripslashes_deep($_GET);

switch ( $action = $_REQUEST['action'] ) {
	
	case 'tree':		
		wpfb_loadclass('Core','File','Category','Output');
		
		// fixed exploit, thanks to Miroslav Stampar http://unconciousmind.blogspot.com/
		$root_id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : (is_numeric($_REQUEST['root']) ? intval($_REQUEST['root']) : intval(substr(strrchr($_REQUEST['root'],'-'),1)));
		$parent_id = ($root_id == 0) ? intval($_REQUEST['base']) : $root_id;
		
		$args = wp_parse_args($_REQUEST, array(
			 'sort' => array(),
			 'private' => false,			 'onselect' => null,
			 'idp' => null,
			 'tpl' => null,
			 'inline_add' => true,
		));
		
		$args['cats_only'] === 'false' && $args['cats_only'] = false;
		$args['exclude_attached'] === 'false' && $args['exclude_attached'] = false;
		 $args['private'] === 'false' && $args['private'] = false; 		
		wpfb_print_json(WPFB_Output::GetTreeItems($parent_id, $args));		
		exit;

	case 'list':
		wpfb_loadclass('ListTpl','File','Category','Output');
		if(is_null($tpl = WPFB_ListTpl::Get(@$_REQUEST['tpl']))) die('-1');
		
		$cats = (empty($_REQUEST['cats']) || $_REQUEST['cats'] == -1) ? ($_REQUEST['cat_grouping'] ? WPFB_Category::GetCats() : null) : array_filter(array_map(array('WPFB_Category','GetCat'), explode(',', $_REQUEST['cats'])));
		
		$content = '';
		
		$tpl->GenerateList($content, $cats, $_REQUEST);
		echo do_shortcode($content);
		exit;
		
	case 'delete':
		wpfb_loadclass('File','Category');
		if(isset($_REQUEST['file_id'])) {
			$file_id = intval($_REQUEST['file_id']);		
			if($file_id <= 0 || ($file = WPFB_File::GetFile($file_id)) == null || !$file->CurUserCanDelete())
				die('-1');
			$file->Remove();
			die('1');
		} elseif(isset($_REQUEST['cat_id'])) {
			$cat_id = intval($_REQUEST['cat_id']);		
			if($cat_id <= 0 || ($cat = WPFB_Category::GetCat($cat_id)) == null || !$cat->CurUserCanEdit())
				die('-1');
			$cat->Delete();			
			die('1');
		} else
			die('-1');
		break;
		
	case 'tpl-sample':
		global $current_user;
		if(!current_user_can('edit_posts')) die('-1');
		
		wpfb_loadclass('File','Category', 'TplLib', 'Output');
		
		if(isset($_POST['tpl']) && empty($_POST['tpl'])) exit;
		
		$cat = new WPFB_Category(array(
			'cat_id' => 0,
			'cat_name' => 'Example Category',
			'cat_description' => 'This is a sample description.',
			'cat_folder' => 'example',
			'cat_num_files' => 0, 'cat_num_files_total' => 0
		));
		$cat->Lock();
		
		$file = new WPFB_File(array(
			'file_name' => 'example.pdf',
			'file_display_name' => 'Example Document',
			'file_size' => 1024*1024*1.5,
			'file_date' => gmdate('Y-m-d H:i:s', time()),
			'file_hash' => md5(''),
			'file_thumbnail' => 'thumb.png',
			'file_description' => 'This is a sample description.',
			'file_version' => WPFB_VERSION,
			'file_author' => $user_identity,
			'file_hits' => 3,
			'file_added_by' => $current_user->ID
		));
		$file->Lock();
		
		if(!empty($_POST['type']) && $_POST['type'] == 'cat')
			$item = $cat;
		elseif(!empty($_POST['type']) && $_POST['type'] == 'list')
		{
			wpfb_loadclass('ListTpl');
			$tpl = new WPFB_ListTpl('sample', $_REQUEST['tpl']);
			echo $tpl->Sample($cat, $file);
			exit;
		}
		elseif(empty($_POST['file_id']) || ($item = WPFB_File::GetFile($_POST['file_id'])) == null || !$file->CurUserCanAccess(true))
			$item = $file;
		else
			die('-1');
		
		$tpl = empty($_POST['tpl']) ? null : WPFB_TplLib::Parse($_POST['tpl']);
		echo do_shortcode($item->GenTpl($tpl, 'ajax'));
		exit;
		
	case 'fileinfo':
		wpfb_loadclass('File','Category');
		if(empty($_REQUEST['url']) && (empty($_REQUEST['id']) || !is_numeric($_REQUEST['id']))) die('-1');
		$file = null;
		
		if(!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];		
			$matches = array();	
			if(preg_match('/\?wpfb_dl=([0-9]+)$/', $url, $matches) || preg_match('/#wpfb-file-([0-9]+)$/', $url, $matches))
				$file = WPFB_File::GetFile($matches[1]);
			else {
				$base = trailingslashit(get_option('home')).trailingslashit(WPFB_Core::$settings->download_base);
				$path = substr($url, strlen($base));
				$path_u = substr(urldecode($url), strlen($base));			
				$file = WPFB_File::GetByPath($path);
				if($file == null) $file = WPFB_File::GetByPath($path_u);
			}
		} else {
			$file = WPFB_File::GetFile((int)$_REQUEST['id']);
		}
		
		if($file != null && $file->CurUserCanAccess(true)) {
			wpfb_print_json(array(
				'id' => $file->GetId(),
				'url' => $file->GetUrl(),
				'path' => $file->GetLocalPathRel()
			));			
		} else {
			echo '-1';
		}
		exit;
		
	case 'catinfo':
			wpfb_loadclass('Category','Output');
			if(/*empty($_REQUEST['url']) && */(empty($_REQUEST['id']) || !is_numeric($_REQUEST['id']))) die('-1');
			$cat = WPFB_Category::GetCat((int)$_REQUEST['id']);
		
			if($cat != null && $cat->CurUserCanAccess(true)) {
				wpfb_print_json(array(
						'id' => $cat->GetId(),
						'url' => $cat->GetUrl(),
						'path' => $cat->GetLocalPathRel(),
						'roles' => $cat->GetReadPermissions(),
						'roles_str' => WPFB_Output::RoleNames($cat->GetReadPermissions(), true)
				));
			} else {
				echo '-1';
			}
			exit;
		
	case 'postbrowser':
		if(!current_user_can('edit_posts')) {
			wpfb_print_json(array(array('id'=>'0','text'=>__('Cheatin&#8217; uh?'), 'classes' => '','hasChildren'=>false)));
			exit;
		}
		
		$id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : intval($_REQUEST['root']);
		$onclick = empty($_REQUEST['onclick']) ? '' : $_REQUEST['onclick'];
			
		$args = array('hide_empty' => 0, 'hierarchical' => 1, 'orderby' => 'name', 'parent' => $id);
		$terms = get_terms('category', $args );
		
		$items = array();	
		foreach($terms as &$t) {
			$items[] = array(
				'id' => $t->term_id, 'text'=> esc_html($t->name), 'classes' => 'folder',
				'hasChildren' => ($t->count > 0)
			);
		}
		
		$terms = get_posts(array(
			'numberposts' => 0, 'nopaging' => true,
			//'category' => $id,
			'category__in' => array($id), // undoc: dont list posts of child cats!
			'orderby' => 'title', 'order' => 'ASC',
			'post_status' => 'any' // undoc: get private posts aswell
		));
		
		if($id == 0)
			$terms = array_merge($terms, get_pages(/*array('parent' => $id)*/));
			
		foreach($terms as $t) {
			$post_title = stripslashes(get_the_title($t->ID));
			if(empty($post_title)) $post_title = $t->ID;
			$items[] = array('id' => $t->ID, 'classes' => 'file',
			'text'=> ('<a href="javascript:'.sprintf($onclick,$t->ID, str_replace('\'','\\\'',/*htmlspecialchars*/$post_title)).'">'.$post_title.'</a>'));
		}

		wpfb_print_json($items);
		exit;
	case 'toggle-context-menu':
		if(!current_user_can('upload_files')) die('-1');
		WPFB_Core::UpdateOption('file_context_menu', empty(WPFB_Core::$settings->file_context_menu));
		die('1');
		
	case 'set-user-setting':
		if(!WPFB_Core::CheckPermission('upload_files|edit_file_details|delete_files|create_cat|delete_cat|manage_templates|manage_rsyncs') || empty($_REQUEST['name'])) die('0');
		echo update_user_option(get_current_user_id(), 'wpfb_set_'.$_REQUEST['name'], stripslashes($_REQUEST['value']), true);
		exit;
		
	case 'get-user-setting':
		if(!WPFB_Core::CheckPermission('upload_files|edit_file_details|delete_files|create_cat|delete_cat|manage_templates|manage_rsyncs') || empty($_REQUEST['name'])) die('-1');
		wpfb_print_json(get_user_option('wpfb_set_'.$_REQUEST['name']));
		exit;
		
	case 'attach-file':
		wpfb_loadclass('File');
		if(!current_user_can('upload_files') || empty($_REQUEST['post_id']) || empty($_REQUEST['file_id']) || !($file = WPFB_File::GetFile($_REQUEST['file_id'])))
			die('-1');
		$file->SetPostId($_REQUEST['post_id']);
		die('1');
		
	case 'ftag_proposal':
		$tag = @$_REQUEST['tag'];
		$tags = (array)get_option(WPFB_OPT_NAME.'_ftags'); // sorted!
		$props = array();
		if(($n = count($tags)) > 0) {
			$ks = array_keys($tags);		
			for($i = 0; $i < $n; $i++) {
				if(stripos($ks[$i], $tag) === 0) {
					while($i < $n && stripos($ks[$i], $tag) === 0) {
						$props[] = array('t' => $ks[$i], 'n' => $tags[$ks[$i]]);
						$i++;
					}
					//break;
				}
			}
		}
		wpfb_print_json($props);
		exit;
		
	case 'usersearch':
		if(!WPFB_Core::CheckPermission('upload_files|edit_file_details|delete_files|create_cat|delete_cat|manage_templates|manage_rsyncs') || empty($_REQUEST['name_startsWith']))
			die('-1');
		$pattern = $_REQUEST['name_startsWith'].'*';
		$users = get_users(array('search' => $pattern, 'number' => 15, 'fields' => array('ID', 'user_login', 'display_name')));
		
		$data = array();
		for($i = 0; $i < count($users); $i++)
			$data[$i] = array('id' => $users[$i]->ID, 'login' => $users[$i]->user_login, 'name' => $users[$i]->display_name);		
		wpfb_print_json($data);
		exit;
		
	case 'rsync-browser':
		if(true && !WPFB_Core::CheckPermission('manage_rsyncs')) {
			wpfb_print_json(array(array('id'=>'','text'=>__('Cheatin&#8217; uh?'), 'classes' => '','hasChildren'=>false)));
			exit;
		}
		
		
		wpfb_loadclass('RemoteSync');
		$rsync = WPFB_RemoteSync::GetSync($_REQUEST['rsync_id']);
		if(empty($rsync))
			exit;
		
		add_filter('wp_die_ajax_handler', create_function('$v','return "'.create_function('$msg','header ("HTTP/1.1 200 OK"); wpfb_print_json(array(array(\'id\' => \'\', \'text\'=> \'<b>ERROR</b>: \'.$msg, \'classes\' => \'empty\', \'hasChildren\' => false )));').'";'));
			
		$root_path = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? '/' : $_REQUEST['root'];
		$onclick = empty($_POST['onclick']) ? '' : $_POST['onclick'];
		$dirs_only = !empty($_REQUEST['dirs_only']) && $_REQUEST['dirs_only'] !== 'false';
		
		try { $files_and_dirs = $rsync->GetFiles($root_path); }
		catch(Exception $e) {
			wpfb_print_json(array(array('id' => '', 'text'=> '<b>ERROR</b>: '.$e->getMessage(), 'classes' => 'empty', 'hasChildren' => false )));
			exit;
		}		
		$dirs = array();
		$files = array();
		
		foreach($files_and_dirs as $f) {			
			$item = array(
				'id' => $f->path,
				'text'=> ($f->is_dir ?
					('<a href="javascript:'.sprintf($onclick,str_replace('\'','\\\'',(stripslashes(rawurlencode($f->path))))).'">'.esc_html(basename($f->path)).'</a>')
					: esc_html(basename($f->path))
				),				
				'classes' => $f->is_dir?'folder':'file',
				'hasChildren' => ($f->is_dir)
			);
			
			if($f->is_dir) $dirs[] = $item;
			elseif(!$dirs_only) $files[] = $item;
		}
		$items = array_merge($dirs, $files);
		
		if(empty($items))
			$items[] = array('id' => '', 'text'=> '<i>'.__($dirs_only?'No Directories':'Empty', WPFB).'</i>', 'classes' => 'empty', 'hasChildren' => false );
		
		wpfb_print_json($items);
		exit;
		
	case 'new-cat':
		if(!WPFB_Core::CurUserCanCreateCat())
			die('-1');
		wpfb_loadclass('Admin');
		$result = WPFB_Admin::InsertCategory($_POST);
		if(isset($result['error']) && $result['error']) {
			wpfb_print_json(array('error' => $result['error']));
			exit;
		}
		
		$cat = $result['cat'];
		$args = WPFB_Output::fileBrowserArgs($_POST['args']);
		$filesel = ($args['type']==='fileselect');
		$catsel = ($args['type']==='catselect');	
		
		$tpl = empty($_REQUEST['tpl']) ? (empty($_REQUEST['is_admin'])?'filebrowser':'filebrowser_admin') : $_REQUEST['tpl'];
		
		wpfb_print_json(array(
						'error' => 0,
						'id' => $cat->GetId(),
						'name' => $cat->GetTitle(),
						'id_str' => $args['idp'].'cat-'.$cat->cat_id,
						'url' => $cat->GetUrl(),
						'text' => WPFB_Output::fileBrowserCatItemText($catsel,$filesel,$cat,$args['onselect'],$tpl),
						'classes' => ($filesel||$catsel)?'folder':null
				));
		exit;
		
	case 'change-category':
		wpfb_loadclass('File','Admin');
		$item = WPFB_Item::GetById($_POST['id'],$_POST['type']);
		if($item && $item->CurUserCanEdit()) {
			$res = $item->ChangeCategoryOrName($_POST['new_cat_id']);
			wpfb_print_json($res);
		} else
			die('-1');
		exit;
}