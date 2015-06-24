<?php
if($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR'] && $_SERVER['REMOTE_ADDR'] != '127.0.0.1')
	exit;

define('NGG_DISABLE_RESOURCE_MANAGER', true); // NexGen Gallery: no resource manager

ignore_user_abort(true);
error_reporting(0);

if(!empty($_POST['cb'])) // async
	header('Connection: Close');

require_once(dirname(__FILE__).'/../../../wp-load.php');
WPFB_Core::InitDirectScriptAccess();

if(!function_exists('get_current_screen')) { function get_current_screen() { return null; } }
if(!function_exists('add_meta_box')) { function add_meta_box() { return null; } }

// check if WP-Filebase is active
if(!defined('WPFB'))
	die('-1');


ignore_user_abort(true);
error_reporting(0);

$_POST = stripslashes_deep($_POST);

// validate nonce
if(empty($_POST['no'])) exit;
$nonce = $_POST['no']; unset($_POST['no']);
$nt = wp_nonce_tick();
if($nonce !== wp_hash($nt . serialize($_POST), 'nonce') && $nonce !== wp_hash(($nt-1) . serialize($_POST), 'nonce'))
	exit;

foreach($_POST['cs'] as $class_name)
	wpfb_loadclass ($class_name);

$func = unserialize($_POST['fn']);
$args = unserialize($_POST['ag']);
unset($_POST['ag']);

ob_start();
$result = call_user_func_array($func, $args);
$output = ob_end_clean();

if(empty($_POST['cb'])) {
	// blocking -> return result
	echo serialize($result);
} else {
	// async
	call_user_func(unserialize($_POST['cb']), $result);
}
