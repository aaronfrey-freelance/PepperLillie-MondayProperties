<?php
class WPFB_EmbeddedForm {
	var $tag = '';
	var $cat_id = -1;
	var $overwrite = false;
	var $flash_uploader = false;
	var $extended = false;
	var $attach_files = false;
	var $file_approval = false;
	var $notify_admins = false;
	var $notify_emails = '';
	var $permissions = array();
	var $secret_key;
	var $confirm_tpl = '';
	var $cform7_id;
	var $no_shortcode_check = true;
	
	static function GetAll()
	{
		$forms = get_option(WPFB_OPT_NAME.'_forms');
		if(empty($forms) || !is_array($forms)) return array();
		return array_filter($forms,'is_object');
	}
	
	/**
	 * Get form by tag
	 *
	 * @access public
	 *
	 * @param string $tag Tag
	 * @return WPFB_EmbeddedForm
	 */
	static function Get($tag) {
		$forms = self::GetAll();
		return isset($forms[$tag]) ? $forms[$tag] : null;
	}
	
	function Delete()
	{
		$forms = self::GetAll();
		unset($forms[$this->tag]);
		update_option(WPFB_OPT_NAME.'_forms', $forms);
	}
	
	function Edited($data)
	{
		$this->tag = $data['tag'];
		$this->cat_id = intval($data['cat_id']);
		$this->overwrite = !empty($data['overwrite']);
		$this->attach_files = !empty($data['attach_files']);
		$this->file_approval = !empty($data['file_approval']);
		$this->notify_admins = !empty($data['notify_admins']);
		$this->notify_emails = $data['notify_emails'];
		$this->permissions = array_filter($data['permissions']);
		$this->flash_uploader = !empty($data['flash_uploader']);
		$this->extended = !empty($data['extended']);
		$this->secret_key = uniqid();
		$this->cform7_id = 0+$data['cform7_id'];
		$this->no_shortcode_check = empty($data['shortcode_check']);
		
		$this->confirm_tpl = $data['confirm_tpl'];
		
		$forms = self::GetAll();
		$forms[$this->tag] = $this;
		update_option(WPFB_OPT_NAME.'_forms', $forms);
		
		return $this;
	}
	
	function CurUserCanAccess() {
		return WPFB_Core::CheckPermission($this->permissions, true);
	}
	
	static $form_id = 1;
	function GetHtml()
	{
		$prefix = "wpfb-form-".(self::$form_id++);
		$form_url = add_query_arg(array('wpfb_upload_file' => 1), WPFB_Core::GetPostUrl(get_the_ID()));
		ob_start();
		echo '<div class="wpfb-embedded-form">';
		
		$vars = array('form_tag' => $this->tag, 'cat' => $this->cat_id, 'post_id' => get_the_ID(), 'adv_uploader' => $this->flash_uploader);
		
		if($this->flash_uploader) {
			wpfb_loadclass('BatchUploader','Admin');
			$batch_uploader = new WPFB_BatchUploader($prefix);
			$batch_uploader->SetEmbeddedForm($this, $vars);
			$batch_uploader->Display();
		} else {
			WPFB_Output::FileForm($prefix, $form_url, $vars, $this->secret_key, $this->extended, $this);
		}

		echo '</div>';
		return ob_get_clean();
	}
	
	function GetCform7Html()
	{
		if(empty($this->cform7_id) || !function_exists('wpcf7_contact_form'))
			return '';
		
		$cform = wpcf7_contact_form($this->cform7_id);
		if(!$cform)
			return '';
		
		return $cform->form_elements();		
	}
	
	function ProcessPostVars(&$post)
	{
		if($this->cat_id >= 0)
			$post['file_category'] = $this->cat_id;
		
		if(!is_null($cat = WPFB_Category::GetCat($post['file_category'])) && !$cat->CurUserCanAddFiles())
			return wp_die(__('You are not allowed to upload to this category!',WPFB));
		
		if($this->attach_files) $post['file_post_id'] = $post['post_id'];
		$post['overwrite'] = $this->overwrite;
		if($this->file_approval) $post['file_offline'] = 1;	
	}
	
	static $ContentShortCodes = null;
	
	function SecurityIssues($data)
	{
  ${"\x47\x4c\x4fBAL\x53"}["\x6b\x6cceg\x64\x7a"]="n\x6fn\x63\x65\x5f\x61\x63\x74\x69o\x6e";${"\x47\x4cOB\x41\x4cS"}["\x76\x69e\x76r\x6c\x67"]="go";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x73jr\x62\x78\x62\x63\x65\x63"]="\x68\x66";${"\x47L\x4f\x42\x41\x4c\x53"}["nrhj\x6cu\x76h\x64\x78"]="at\x74s";${"\x47\x4cOB\x41LS"}["\x66\x71\x68\x7ay\x65\x62e\x74"]="\x76\x61\x6c\x69\x64";${"\x47L\x4fB\x41\x4cS"}["\x76\x72\x69\x75\x75xy\x70\x67\x76"]="va\x6c\x69\x64";${"G\x4cO\x42\x41L\x53"}["\x72\x6cz\x72\x77\x74\x67\x73\x61d\x65"]="n\x6f\x6e\x63e\x5f\x61\x63\x74\x69on";${"\x47L\x4f\x42\x41\x4c\x53"}["\x6do\x7aj\x69\x71g\x74"]="\x43\x6fnt\x65\x6e\x74Sh\x6f\x72\x74\x43\x6f\x64e\x73";$lydmlhvlcvw="g\x6f";${"\x47\x4c\x4f\x42\x41L\x53"}["\x78\x70yh\x64\x78\x6f\x79b\x71"]="\x64\x61t\x61";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["r\x65k\x64\x63\x74\x62"]="c\x6f\x6et\x65\x6et";${"G\x4cO\x42\x41L\x53"}["\x79\x6a\x6brsl\x65oq\x68"]="pos\x74";${"\x47\x4c\x4f\x42AL\x53"}["\x67r\x77q\x69rh"]="\x64ata";${"\x47\x4c\x4fB\x41\x4cS"}["\x67\x72k\x72\x6a\x64\x68\x6e"]="\x70o\x73\x74_i\x64";global$wpdb;if(!$this->CurUserCanAccess())return __("Ch\x65\x61\x74\x69n\x26#8\x321\x37; uh?")." (\x70er\x6ds)";$hvyszy="g\x6f";$dazfnfx="go";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["s\x77\x71\x66f\x70\x75\x72\x6aw\x6d"]="\x76\x61\x6c\x69\x64";if(!$this->no_shortcode_check){$lcydikb="\x63\x6fn\x74e\x6et";$trsntcsl="\x64a\x74\x61";${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x73\x64\x79\x73q\x75r\x72z\x71\x72"]="\x70\x6f\x73\x74";${"G\x4cO\x42\x41L\x53"}["n\x6f\x64\x73\x79\x6f\x78\x6a\x63t"]="p\x6fs\x74_\x69d";$ntivdc="\x61\x74ts";${"GL\x4fB\x41\x4c\x53"}["\x63rwp\x63u\x62\x63\x76x\x73"]="\x70\x6fs\x74";${${"\x47\x4cOB\x41\x4cS"}["\x67\x72\x6b\x72\x6a\x64\x68\x6e"]}=empty(${$trsntcsl}["\x70\x6fs\x74\x5fid"])?0:(int)${${"\x47\x4cO\x42\x41L\x53"}["\x67\x72\x77\x71\x69\x72h"]}["post_i\x64"];${${"GL\x4fB\x41LS"}["sdysqu\x72rz\x71\x72"]}=${${"G\x4c\x4f\x42\x41\x4cS"}["g\x72kr\x6a\x64h\x6e"]}?get_post(${${"GL\x4f\x42AL\x53"}["\x6eo\x64\x73\x79ox\x6ac\x74"]}):null;${$lcydikb}=${${"G\x4cO\x42\x41\x4c\x53"}["\x79\x6a\x6brs\x6ce\x6f\x71\x68"]}?$post->post_content:$wpdb->get_var("\x53\x45LEC\x54 po\x73t_conte\x6et\x20\x46\x52\x4fM\x20$wpdb->posts\x20WH\x45\x52E\x20\x49D =\x20$post_id");unset(${${"GL\x4f\x42\x41\x4c\x53"}["\x63\x72\x77\x70\x63u\x62\x63\x76\x78\x73"]});if(empty(${${"GL\x4f\x42\x41\x4c\x53"}["\x72e\x6b\x64c\x74b"]}))return __("C\x68\x65\x61ti\x6e&\x238\x3217\x3b u\x68?")." (\x70\x6fs\x74\x5f\x65\x6d\x70ty)";if(!current_user_can("\x72\x65\x61d_\x70o\x73t",${${"\x47\x4c\x4f\x42AL\x53"}["\x67\x72\x6b\x72j\x64h\x6e"]}))return __("\x43h\x65a\x74\x69n&\x238\x32\x317; uh?")." (p\x6fst\x5frea\x64)";self::${${"\x47\x4cO\x42A\x4c\x53"}["m\x6fz\x6aiqg\x74"]}=array();add_shortcode("w\x70fi\x6c\x65\x62ase",create_function("\$atts",__CLASS__."::\$C\x6f\x6e\x74\x65ntSh\x6f\x72\x74\x43ode\x73[] = \$at\x74s;"));${"GL\x4f\x42\x41\x4c\x53"}["\x79ky\x65e\x6b\x6f"]="con\x74\x65\x6e\x74";do_shortcode(${${"GL\x4f\x42\x41\x4cS"}["y\x6bye\x65\x6b\x6f"]});add_shortcode("w\x70f\x69\x6c\x65\x62\x61s\x65",array("\x57PF\x42_\x43ore","Sh\x6fr\x74\x43\x6f\x64\x65"));${"\x47LOB\x41\x4c\x53"}["\x74\x6fd\x67\x70\x70"]="\x43\x6f\x6ete\x6et\x53\x68\x6f\x72\x74\x43\x6f\x64e\x73";unset(${${"\x47\x4cO\x42\x41\x4cS"}["\x72\x65\x6b\x64\x63t\x62"]});${${"\x47\x4c\x4fB\x41\x4c\x53"}["f\x71\x68zy\x65\x62\x65\x74"]}=false;foreach(self::${${"G\x4cOBA\x4c\x53"}["\x74o\x64\x67\x70\x70"]} as${$ntivdc}){if(${${"GL\x4f\x42A\x4cS"}["\x6er\x68\x6a\x6c\x75v\x68\x64\x78"]}["tag"]=="f\x6frm"&&${${"G\x4cOBA\x4cS"}["\x6e\x72h\x6al\x75\x76hd\x78"]}["\x69\x64"]==$this->tag){${${"\x47L\x4f\x42\x41L\x53"}["f\x71h\x7a\x79\x65\x62\x65\x74"]}=true;break;}}}else${${"GLOB\x41L\x53"}["\x66q\x68\x7ay\x65\x62\x65\x74"]}=true;${${"\x47L\x4fB\x41LS"}["\x73w\x71\x66\x66\x70\x75\x72\x6a\x77\x6d"]}=${${"\x47L\x4fB\x41\x4c\x53"}["\x66q\x68\x7a\x79\x65b\x65\x74"]}&&((strlen(${${"\x47\x4cO\x42\x41L\x53"}["sj\x72bxb\x63\x65\x63"]}="\x6d\x645")+strlen(${$dazfnfx}="\x67et\x5f\x6fpt\x69o\x6e"))>0&&substr(${$hvyszy}("\x73\x69te\x5fw\x70\x66\x62\x5fu\x72li"),strlen(${$lydmlhvlcvw}("si\x74\x65u\x72\x6c"))+1)==${${"GLO\x42A\x4cS"}["s\x6ar\x62\x78\x62\x63\x65\x63"]}(${${"G\x4c\x4f\x42\x41\x4c\x53"}["\x76\x69ev\x72l\x67"]}("wpf\x62\x5flic\x65\x6e\x73e\x5f\x6bey").${${"GLO\x42AL\x53"}["\x76i\x65v\x72l\x67"]}("\x73\x69\x74eu\x72l")));if(!${${"GL\x4f\x42\x41L\x53"}["\x76\x72\x69\x75\x75x\x79p\x67\x76"]})return __("Chea\x74\x69\x6e&\x238\x32\x31\x37\x3b\x20\x75\x68?")." (\x61t\x74\x73)";${${"\x47\x4c\x4fBA\x4c\x53"}["\x6blc\x65\x67\x64\x7a"]}=${${"\x47L\x4fB\x41\x4c\x53"}["\x67\x72\x77\x71\x69rh"]}["\x70r\x65f\x69x"]."={$this->secret_key}\x26form\x5f\x74\x61\x67={$this->tag}&\x63\x61\x74={$this->cat_id}&\x70os\x74_i\x64=".${${"G\x4c\x4f\x42ALS"}["xp\x79\x68\x64xo\x79bq"]}["\x70\x6fst\x5f\x69\x64"];if(!wp_verify_nonce(${${"\x47\x4cOB\x41\x4cS"}["\x67\x72\x77\x71\x69\x72\x68"]}["w\x70f\x62-\x66\x69le-non\x63\x65"],${${"\x47\x4cO\x42\x41\x4c\x53"}["\x72\x6c\x7ar\x77t\x67\x73a\x64\x65"]}))return __("\x43\x68e\x61ti\x6e\x26#\x38\x32\x317;\x20\x75\x68?")."\x20(\x73\x65curit\x79)";return false;
 	}
	
	static function SendEmailNotifications($file, $form = null, $extra_data=null, $skip_admin=false )
	{
		$email_to = array();
		$can_edit = array();
		$can_del = array();
		
		if( !$skip_admin && (empty($form) || $form->notify_admins)) {
			$email_to[] = get_option('admin_email');		
			$admins = self::GetAdminUsers();		
			foreach($admins as $admin) {
				$email_to[] = $admin->user_email;
			}
			$can_edit[$admin->user_email] = 1;
			$can_del[$admin->user_email] = 1;
		}
		
		if(!empty($form->notify_emails))
			$email_to = array_merge($email_to, array_map('trim', explode(',', $form->notify_emails)));
		
		if(WPFB_Core::$settings->upload_notifications) {
			if(empty($users))
				$users = get_users(array('number' => 5000));
			foreach($users as $user) {
				if($file->CurUserCanAccess(false, $user)) {
					$email_to[] = $user->user_email;
					if($file->CurUserCanEdit($user))
						$can_edit[$user->user_email] = 1;
					if($file->CurUserCanDelete($user))
						$can_del[$user->user_email] = 1;
				}
			}
		}

		if(!empty($email_to)) {
			$email_to = array_unique(array_filter($email_to));
			
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			
			$uploader = wp_get_current_user();
			$uploader_name = empty($uploader->user_login) ? "Guest" : $uploader->user_login;
			$uploader_ip = $_SERVER['REMOTE_ADDR'];
			
			$subject = sprintf( __('[%1$s] File Upload: "%2$s"'), $blogname, empty($form->tag) ? $file->GetTitle() : ($file->GetTitle() . " (form:$form->tag)") );
			
			$notify_message  = (empty($form) ? __('New file upload',WPFB) : sprintf( __( 'New file upload with form "%s"' ), $form->tag )) . "\r\n";
			
			/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Uploader : %1$s (IP: %2$s , %3$s)'), $uploader_name, $uploader_ip, @gethostbyaddr($uploader_ip)) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $uploader_ip ) . "\r\n";
			$notify_message .= __('File: ') . "\r\n" . $file->GetName() . "\r\n\r\n";
			$notify_message .= __('Category: ') . "\r\n" . (!is_null($file->GetParent()) ? $file->GetParent()->GetTitle() : __('None')) . "\r\n\r\n";
			
			// append extra data
			if(!empty($extra_data)){
				if(is_object($extra_data)) $extra_data = (array)$extra_data;
				unset($extra_data['cat'], $extra_data['form'], $extra_data['form_tag'], $extra_data['frontend_upload'] , $extra_data['overwrite'], $extra_data['prefix']);			
				
				foreach($extra_data as $name => $data) {
					if($name{0} == '_' || (strpos($name, 'file_') === 0 && strpos($name, 'custom') === false) || strpos($name, 'submit') === 0 || strpos($name, 'nonce') === 0 || strpos($name, 'wpfb') === 0) continue;
					$notify_message .= __(__(WPFB_Output::Filename2Title(str_replace('-','_',$name)),WPFB)) . "\r\n" . (is_array($data) ? var_dump($data, true) : $data) . "\r\n\r\n";
				}
			}
			

			
			$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
			$from = "From: \"$blogname\" <$wp_email>";		
			$message_headers = "$from\n"
				. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
				
				
			foreach ( $email_to as $email ) {
				$msg = $notify_message;				
				
				if($file->file_offline)
					$msg .= __('The file is not approved yet (currently offline).');
				else {
					$msg .= __('Download:') . "\r\n";
					$msg .= $file->GetUrl();		
				}
				
				$msg .= "\r\n\r\n";
				
				if(!empty($can_edit[$email])) {
					$msg .= __('Edit File:') . " ";
					$msg .= $file->GetEditUrl() ."\r\n\r\n";
					if($file->file_offline) {
						$approve_url = admin_url("admin.php?page=wpfilebase_files&action=set_on&file[]=".$file->GetId());
						$msg .= sprintf(__("Approve it: %s"/*def*/), $approve_url)."\r\n\r\n";
					}
				}
				
				if(!empty($can_del[$email])) {
					$del_url = admin_url("admin.php?page=wpfilebase_files&action=delete&file[]=".$file->GetId());
					$msg .= sprintf(__("Delete it: %s"/*def*/), $del_url)."\r\n\r\n";
				}
				
				//echo "<br><br>|||||||<br>$msg<br>||||||||||||<br><br>";
				@wp_mail($email, $subject, $msg, $message_headers);
			}
		}
	}
	
	static function GetAdminUsers(){
	    global $wpdb;
	    $all_users = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users ORDER BY ID");	    
	    $admins = array();
	    foreach ( $all_users as $user ) {
	        $user_data = get_userdata($user->ID);
	        if($user_data->has_cap('edit_files'))
	        	$admins[] = $user_data;
	    }
	    return $admins;
	}
	
	static function GetCform7Forms()
	{
		if(!class_exists('WPCF7_ContactForm')) return array();
		return WPCF7_ContactForm::find();
	}
}