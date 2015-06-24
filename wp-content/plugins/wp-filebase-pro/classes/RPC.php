<?php
class WPFB_RPC {
	
	static function isWPFBClass($class) { return strpos($class, 'WPFB_') === 0; }	
	static function rmClassPrefix($class) { return substr($class, 5); }
	
	public static function Call($function)
	{
		$args = func_get_args();
		array_shift($args);
		return (self::rpcCall($function, $args, false));
	}
	
	public static function CallSafe($function)
	{
		$args = func_get_args();
		array_shift($args);
		
		try {
			return (self::rpcCall($function, $args, false));
		} catch(Exception $e) {
			return call_user_func_array($function, $args);
		}
	}
	
	public static function CallAsync($function, $callback)
	{
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		return self::rpcCall($function, $args, true, $callback);
	}
	
	private static function rpcCall($func, &$args, $async, $async_callback=null)
	{
		$wpfb_classes = array_map(array(__CLASS__, 'rmClassPrefix'), array_filter(get_declared_classes(), array(__CLASS__, 'isWPFBClass')));
		
		$post_data = array('cs' => $wpfb_classes, 'fn' => serialize($func),'ag' => serialize($args));

		if(!empty($async_callback))
			$post_data['cb'] = serialize($async_callback);
		
		$post_data['no'] = wp_hash(wp_nonce_tick() . serialize($post_data), 'nonce');
		
		$response = wp_remote_post(WPFB_Core::PluginUrl('rpc.php'), array('timeout' => 60, 'blocking' => !$async, 'body' => $post_data ));

		if(is_wp_error($response))
			throw new WPFB_RPCException($response->get_error_message());
		
		if(is_array($response) && (!empty($response['errors']) || (!$async && $response['response']['code'] != 200)))
			throw new WPFB_RPCException('RPC Call Failed: '.(!empty($response['response']['code']) ? $response['response']['code'] : '').' '.@implode(', ',$response['errors']), E_USER_WARNING);
		
		if(!$async) {
			$data = unserialize($response['body']);
			unset($response);
			return $data;
		} else 
			return true;
	}
	
}

class WPFB_RPCException extends Exception {
	
	public function __construct($err = null, $isDebug = FALSE) 
	{
		if(is_null($err)) {
			$el = error_get_last();
			$this->message = $el['message'];
			$this->file = $el['file'];
			$this->line = $el['line'];
		} else
			$this->message = $err;
		self::log_error($err);
		if ($isDebug)
		{
			self::display_error($err, TRUE);
		}
	}
	
	public static function log_error($err)
	{
		error_log($err, 0);		
	}
	
	public static function display_error($err, $kill = FALSE)
	{
		print_r($err);
		if ($kill === FALSE)
		{
			die();
		}
	}
}

