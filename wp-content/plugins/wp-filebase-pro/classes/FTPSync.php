<?php
class WPFB_FTPSync extends WPFB_RemoteSync {	
	
	var $ftpUser;
	var $ftpHost;
	var $ftpPort = 21;
	var $ftpPass;
	var $ftpSSL = false;
	var $ftpPasv = false;
	
	var $ftpConn;
	
	var $httpUrl;
	
	var $uriVer;
	
	var $sysType;
	var $noSlashEsc = null;
	
	static $uri_ver = 3;
	
	static function InitClass() {  }
	static function GetServiceName() { return "FTP"; }
	
	function __construct($title)
	{
		$this->uriVer = self::$uri_ver;
		parent::__construct($title);
	}
	
	protected function PrepareEditForm()
	{		
		return true;
	}
	
	function DisplayFormFields()
	{
		?>
		<tr>
			<th scope="row" valign="top"><label for="ftp-host"><?php _e('FTP Host', WPFB) ?></label></th>
			<td><input id="ftp-host" name="ftpHost" type="text" value="<?php echo esc_attr($this->ftpHost); ?>" /></td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"><label for="ftp-port"><?php _e('FTP Port', WPFB) ?></label></th>
			<td><input id="ftp-port" name="ftpPort" type="text" class="num" value="<?php echo esc_attr($this->ftpPort); ?>" /></td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"><label for="ftp-user"><?php _e('FTP User', WPFB) ?></label></th>
			<td><input id="ftp-user" name="ftpUser" type="text" value="<?php echo esc_attr($this->ftpUser); ?>" /></td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"><label for="ftp-pass"><?php _e('FTP Password', WPFB) ?></label></th>
			<td><input id="ftp-pass" name="ftpPass" type="password" value="<?php echo esc_attr($this->ftpPass); ?>" /></td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"></th>
			<td>
				<input id="ftp-ssl" name="ftpSSL" type="checkbox" value="1" <?php checked($this->ftpSSL); ?> />
				<label for="ftp-ssl"><?php _e('Secure SSL FTP', WPFB) ?></label>
			</td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"></th>
			<td>
				<input id="ftp-pasv" name="ftpPasv" type="checkbox" value="1" <?php checked($this->ftpPasv); ?> />
				<label for="ftp-pasv"><?php _e('FTP Passive Mode', WPFB) ?></label>
			</td>
		</tr>
		
		<tr>
			<th scope="row" valign="top"><label for="ftp-url"><?php _e('HTTP Url (optional)', WPFB) ?></label></th>
			<td><input id="ftp-url" name="httpUrl" type="text" value="<?php echo esc_attr($this->httpUrl); ?>" size="50"/><br />
			<?php printf(__('Enter the HTTP URL of the FTP root directory on the remote server. Example: %s. Download links are mapped using this URL base. Leave empty to use FTP URIs (ftp://...).'),'<code>http://my-external-ftp-site.com/subdir</code>') ?>
			</td>
		</tr>
		
	
		<?php
	}
	
	function Edited($data, $invalidate_uris=false)
	{
		$prev_user = $this->ftpUser;
		$this->ftpHost = $data['ftpHost'];
		$this->ftpPort = absint($data['ftpPort']);
		$this->ftpUser = $data['ftpUser'];
		$this->ftpPass = $data['ftpPass'];
		$prev_ssl = $this->ftpSSL;
		$this->ftpSSL = !empty($data['ftpSSL']);
		$this->ftpPasv = !empty($data['ftpPasv']);
		$prev_httpurl = $this->httpUrl;		
		$this->httpUrl = $data['httpUrl'];
		$res = parent::Edited($data,
				  $invalidate_uris
				  || $prev_httpurl != $this->httpUrl
				  || $prev_ssl != $this->ftpSSL
				  || empty($this->uriVer)
				  || $this->uriVer != self::$uri_ver);
		if(empty($this->remote_path) && empty($prev_user))
			$res['reload_form'] = true;
		
		$this->uriVer = self::$uri_ver;
		
		return $res;
	}
	
	function IsReady() {
		return parent::IsReady();
	}
	
	function GetAccountName()
	{
		return "$this->ftpUser @ $this->ftpHost" . ($this->ftpSSL ? " (FTPS)" : "");
	}
	
	function OpenConnection()
	{
		wpfb_loadclass('FileUtils');		
		$conn_func = $this->ftpSSL ? 'ftp_ssl_connect' : 'ftp_connect';		
		if(!function_exists($conn_func))
			throw new RemoteSyncException("Function $conn_func does not exists!");
		
		$this->uris_invalidated = ($this->uris_invalidated || empty($this->uriVer) || $this->uriVer != self::$uri_ver);
		
		if( ((int)$this->ftpPort) <= 0 ) $this->ftpPort = 21;
		
		$this->ftpConn = $conn_func($this->ftpHost, $this->ftpPort, 30);
		
		if($this->ftpConn === false)
			throw new RemoteSyncException("FTP connection to {$this->ftpHost}:{$this->ftpPort} failed!");		
		
		if(!empty($this->ftpUser) && !@ftp_login($this->ftpConn, $this->ftpUser, $this->ftpPass))
			throw new RemoteSyncException("FTP login failed!");
		
		ftp_pasv($this->ftpConn, $this->ftpPasv);
		
		$this->sysType = ftp_systype ($this->ftpConn);
				
		return true;
	}
	
	function CloseConnection()
	{
		ftp_close($this->ftpConn);
		$this->ftpConn = null;
	}
	
	function GetFileList($root_path, $names_only=false)
	{
		$root_path = trailingslashit($root_path);
		$files = array();		
		
		
		if($this->noSlashEsc === true) {
			$raws = ftp_rawlist($this->ftpConn, $root_path);
		} else {
			$raws = ftp_rawlist($this->ftpConn, str_replace(' ','\\ ',$root_path));		
			if(!is_array($raws) && strpos($root_path,' ') !== false) {
				$raws = ftp_rawlist($this->ftpConn, $root_path);
				if(is_array($raws))
					$this->noSlashEsc = true;
			}
		}
		
		if(!is_array($raws))
			throw new RemoteSyncException("FTP: Could not list files in direcory $root_path ($raws). Try enabling passive mode.");	
		
		foreach($raws as $r)
		{
			$r = trim($r);
			//drwxr-x---    2 0        8            4096 Jun 14  2009 atd
			$raw = array();
			if(preg_match('/^[a-z-]+\s+[0-9]+\s+\S+\s+\S+\s+([0-9]+)\s+([a-z]+\s+[0-9]+\s+[0-9:]+)\s+(.+)$/i', $r, $raw) && $raw[3] !== '.' && $raw[3] !== '..' && $raw[3] != '.htaccess')
			{
				$f = new WPFB_RemoteFileInfo();
				$f->rev = md5($r);
				$f->size = $raw[1];
				$f->mtime = strtotime($raw[2]);
				$f->path = $root_path.$raw[3];
				$f->is_dir = (strtolower($r{0}) == 'd');
				$files[] = $f;
			}		
		}
		
		return $files;
	}
	
	function DownloadFile($file_info, $local_path, $progress_changed_callback = null)
	{
		$ret = ftp_nb_get($this->ftpConn, $local_path, $file_info->path, FTP_BINARY);
		$t = 0;
		while ($ret == FTP_MOREDATA) {
			if( (time() - $t) >= 1) {
				$t = time();
				if(!empty($progress_changed_callback)) {
			  		call_user_func($progress_changed_callback, @WPFB_FileUtils::GetFileSize($local_path), $file_info->size);
			  	}
			}
			$ret = ftp_nb_continue($this->ftpConn);
		}
		
		if($ret != FTP_FINISHED)
			throw new RemoteSyncException();
	}
	
	function mkdir($remote_path)
	{
		$fullpath = "";				  
		foreach (array_filter(explode("/", $remote_path)) as $part) {
			$fullpath .= "/".$part;
			if (!@ftp_chdir($this->ftpConn, $fullpath) && !@ftp_mkdir($this->ftpConn, $fullpath))
				throw new RemoteSyncException("FTP: Cannot create directory ".$fullpath);
		}
		return @ftp_chdir($this->ftpConn, "/");
	}
	
	function UploadFile($local_path, $remote_path, $progress_changed_callback = null) {
		$remote_dir = self::dirname($remote_path);
		$this->mkdir($remote_dir);
		$ret = ftp_nb_put($this->ftpConn, $remote_path, $local_path, FTP_BINARY);
		$size = @WPFB_FileUtils::GetFileSize($local_path);
		if(!empty($progress_changed_callback)) call_user_func($progress_changed_callback, 0, $size);
		while ($ret == FTP_MOREDATA) {
			$ret = ftp_nb_continue($this->ftpConn);
		}
		if(!empty($progress_changed_callback)) call_user_func($progress_changed_callback, $size, $size);
		
		if(is_null($fi = $this->GetRemoteFileInfo($remote_path)))
			throw new RemoteSyncException("FTP: Could not get details of uploaded file!");
		
		return $fi;
	}
	
	protected function CanUpload() {
		return true;
	}
	
	private static function urlencodeFtpPath($path)
	{
		return implode('/', array_map('rawurlencode', explode('/',str_replace('//','/',str_replace('\\','/',$path)))));
		
	}
	
	function GetFileUri($path, &$expires=null)
	{
		$expires = time() + 3600 * 24 * 356 * 2; // 2 years
		$host = $this->ftpHost;
		if($this->ftpPort != 21) $host .= ":".$this->ftpPort;
		if(empty($this->httpUrl))
			return untrailingslashit("ftp://".$host. self::urlencodeFtpPath($path));
		else {
			$plen = strlen($this->GetRemotePath());
			
			if(strpos($this->httpUrl, '://') === false)
				$this->httpUrl = "http://".$this->httpUrl;
			
			return substr($this->httpUrl, 0, 9).untrailingslashit(str_replace('//','/',substr($this->httpUrl, 9).'/'.self::urlencodeFtpPath(substr($path, $plen))));
		}
	}
}