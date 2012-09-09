<?php
define('ARK_MICROTIME', microtime(true));
define('ARK_TIMESTAMP', round(ARK_MICROTIME));
if($_SERVER['REMOTE_ADDR'] === '127.0.0.1'){
	error_reporting(E_ALL^E_NOTICE);
}
else{
	error_reporting(0);
}

//Path defination
define( 'ARK_DIR' , dirname(__FILE__));

//App dir
if(!defined('APP_DIR')){
	define('APP_DIR', ARK_DIR.'/../../..');
}
if(!defined('SOURCE_DIR')){
	define('SOURCE_DIR', APP_DIR.'/source');
}
if(!defined('VENDOR_DIR')){
	define('VENDOR_DIR', SOURCE_DIR.'/vendor');
}

//Load kernel
require(dirname(__FILE__).'/ark.php');

//autoload
spl_autoload_register('ArkAutoload::load');

//register ark classes
ark_autoload_class(array(
	'ArkView' => ARK_DIR.'/view.php',
	'ArkViewHelper' => ARK_DIR.'/view.php',
	'ArkController' => ARK_DIR.'/controller.php',
	'ArkPagination' => ARK_DIR.'/pagination.php',
));

//register app classes
//ark_autoload_dir(APP_DIR.'/source/controller');

//parse request
$q = ark_parse_query_path();

define('APP_URL','http://'.$_SERVER['HTTP_HOST'].$q['base'].'/');

$ARK_CONFIG = include(APP_DIR . '/source/config.php');
if(!$ARK_CONFIG){
	$ARK_CONFIG = array();
}

//autoload custom classes
if(isset($ARK_CONFIG['autoload']['dir'])){
	foreach($ARK_CONFIG['autoload']['dir'] as $dir){
		ark_autoload_dir($dir);
	}
}
if(isset($ARK_CONFIG['autoload']['class'])){
	ark_autoload_class($ARK_CONFIG['autoload']['class']);
}

//Capable with different platforms
if(isset($ARK_CONFIG['platform']) && in_array($ARK_CONFIG['platform'], array('sae'))){
	require ARK_DIR.'/platform/'.$ARK_CONFIG['platform'].'.php';
}

//Setup default services and events
ark('event')->bind('ark.404', 'ark_404');
if(!isset($ARK_CONFIG['services']['view'])){
	ark()->register('view', array(
		'class' => 'ArkView',
		'params' => array(
			array(
				'dir' => APP_DIR.'/source/view',
				'extract' => true,
				'ext' => '.php',
			)
		)
	));
}

//Load app
require(APP_DIR.'/source/app.php');

ark('event')->trigger('ark.ready');

if(!$r = ark_route($q['path'], ark_config('route'))){
	ark('event')->trigger('ark.404');
}
else{
	ark('event')->trigger('ark.dispatch');
	ark_dispatch($r);
}
ark('event')->trigger('ark.shutdown');