<?php
// 屏蔽错误
if($_SERVER['REMOTE_ADDR'] === '127.0.0.1'){
	error_reporting(E_ALL^E_NOTICE);
}
else{
	error_reporting(0);
}

//框架目录
define( 'ARK_DIR' , dirname(__FILE__));

//项目目录
if(!defined('APP_DIR')){
	define('APP_DIR', ARK_DIR.'/../../..');
}

define('TIMESTAMP', time());//统一的时间，防止多处使用time可能造成的冲突

//通用函数/类
require(dirname(__FILE__).'/ark.php');

//autoload
spl_autoload_register('AAutoload::load');

//register ark classes
ark_autoload_class('AView', ARK_DIR.'/view.php');
ark_autoload_class('AController', ARK_DIR.'/controller.php');

//register app classes
ark_autoload_dir(APP_DIR.'/source/controller');

//parse request
$q = ark_parse_query_path();

define('APP_URL','http://'.$_SERVER['HTTP_HOST'].$q['base'].'/');

$ARK_CONFIG = include(APP_DIR . '/source/config.php');
if(!$ARK_CONFIG){
	$ARK_CONFIG = array();
}

//Capable with different platforms
if(isset($ARK_CONFIG['platform']) && in_array($ARK_CONFIG['platform'], array('sae'))){
	require ARK_DIR.'/platform/'.$ARK_CONFIG['platform'].'.php';
}

//默认服务及事件
ark('event')->bind('ark.404', 'ark_404');
if(!isset($ARK_CONFIG['services']['view'])){
	ark()->register('view', array(
		'class' => 'AView',
		'parameters' => array(
			array(
				'dir' => APP_DIR.'/source/view',
				'extract' => true,
				'ext' => '.php',
			)
		)
	));
}

require(APP_DIR.'/source/app.php');

ark('event')->trigger('ark.ready');

if(!$r = ark_route($q['path'], ark_config('route'))){
	ark('event')->trigger('ark.404');
}
else{
	ark('event')->trigger('ark.dispatch');
	ark_dispatch($r['controller'], $r['action'], $r['params']);
}
ark('event')->trigger('ark.shutdown');