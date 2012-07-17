<?php
//define('APPVERSION', isset($_SERVER['HTTP_APPVERSION'])?$_SERVER['HTTP_APPVERSION']:1);
//非sae环境兼容
if(!function_exists('sae_set_display_errors')){
	define('IN_SAE', false);
	function sae_set_display_errors($value){
		ini_set('display_errors', 0);
	}
	
	function sae_debug($message){
	}
}
else{
	define('IN_SAE', true);
}