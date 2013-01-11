<?php
/**
 * Tinyark Framework
 *
 * @package   Tinyark
 * @author    Dong <ddliuhb@gmail.com>
 * @copyright 2013 Dong <ddliuhb@gmail.com>
 * @license   Licensed under the MIT license(http://maxmars.net/license/MIT)
 */

/**
 * Ark app
 */
abstract class ArkApp
{
    protected $container;

    protected $bundles;

    protected $registeredBundleInfo = array();

    public $config;

    public static $instance;

    public $event;

    public function __construct()
    {
        $this->event = new ArkEvent();

        $this->event->trigger('app.before');

        self::$instance = $this;
        
        //path definations
        if (!defined('APP_DIR')) {
            define('APP_DIR', $this->getAppDir());
        }
        
        $this->loadConfigs();

        //Set default timezone
        if ($this->config->has('timezone')) {
            date_default_timezone_set($this->config->get('timezone'));
        }

        //Service container
        $this->container = new ArkContainer($this->config->get('service', array()));
        
        //Setup default services and events
        //View
        if (!$this->config->has('service.view')) {
            $this->container->register(
                'view',
                array(
                    'class' => 'ArkView',
                    'params' => array(
                        array(
                            'dir' => $this->getAppDir().'/view',
                            'extract' => true,
                            //'ext' => '.php',
                        )
                    )
                )
            );
        }

        //autoload custom classes
        if ($this->config->has('autoload.dir')) {
            foreach ($this->config->get('autoload.dir') as $dir) {
                ArkAutoload::registerDir($dir);
            }
        }

        if ($this->config->has('autoload.file')) {
            ArkAutoload::registerFile($this->config->get('autoload.file'));
        }

        //bundles
        $this->loadBundles();
        
        $this->init();
        
        //app is ready
        $this->event->trigger('app.ready');
    }
    
    /**
     * Init app
     */
    abstract protected function init();

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get application dir
     * 
     * @return string
     */
    public function getAppDir()
    {
        return $this->getPath();
    }

    public function getBundleDir()
    {
        return $this->getAppDir().'/bundle';
    }

    protected function loadBundles()
    {
        foreach($this->config->get('bundle.bundles', array()) as $v){
            $this->addBundle($v);
        }

        //auto discover
        if($this->config->get('bundle.autodiscover')){
            if($bundle_dirs = ark_sub_dirs($this->getBundleDir())){
                foreach($bundle_dirs as $name){
                    $this->addBundle($name);
                }
            }
        }
    }

    public function addBundle($v)
    {
        if(is_string($v)){
            if(preg_match('#^[a-zA-Z0-9_]$#', $v)){
                //name
                $this->registerBundleInfo($v);
            }
            else{
                //path
                $this->registerBundleInfo(null, $v);
            }
        }
        elseif(is_array($v)){
            $name = $this->registerBundleInfo(
                isset($v['name'])?$v['name']:null, 
                isset($v['path'])?$v['path']:null,
                isset($v['configs'])?$v['configs']:null
            );
            if(isset($v['start']) && $v['start']){
                $this->loadBundle($name);
            }
        }else{
            throw new Exception('Invalid bundle info');
        }
    }

    public function registerBundleInfo($name, $path = null, $configs = null)
    {
        if(!isset($this->registeredBundleInfo[$name])){
            if(null === $name && null === $path){
                return false;
            }

            if(null === $name){
                $name = dirname($path);
            }
            elseif(null === $path){
                $path = $this->getBundleDir().'/'.$name;
            }

            $this->registeredBundleInfo[$name] = array(
                'path' => $path,
                'configs' => $configs,
            );
        }

        return $name;
    }

    public function getBundle($name)
    {
        if($this->loadBundle($name)){
            return $this->bundles[$name];
        }

        return false;
    }

    public function loadBundle($name)
    {
        if(!isset($this->bundles[$name])){
            if(!isset($this->registeredBundleInfo[$name])){
                return false;
            }

            $info = $this->registeredBundleInfo[$name];
            if(!file_exists($info['path'].'/bundle.php')){
                return false;
            }

            require($info['path'].'/bundle.php');
            $classname = str_replace(' ', '', ucwords(str_replace('_', ' ', $name))).'Bundle';
            $this->bundles[$name] = new $classname($this, $info['configs']);
        }

        return true;
    }
    
    public function getConfigFile(){
        return $this->getAppDir().'/config.php';
    }
    
    public function loadConfigs(){
        $configs = include($this->getConfigFile());
        $this->config = new ArkConfig(is_array($configs)?$configs:array());

        return $this;
    }

    protected function getPath(){
        static $dir;
        if(null === $dir){
            $reflected = new ReflectionObject($this);
            $dir = dirname($reflected->getFileName());
        }

        return $dir;
    }

    public function isCli(){
        return PHP_SAPI === 'cli';
    }

    /**
     * Run app
     */
    abstract public function run();

}

/**
 * Web app
 */
class ArkAppWeb extends ArkApp
{
    protected $request;

    public function __construct(){
        if($_SERVER['REMOTE_ADDR'] === '127.0.0.1'){
            ini_set('display_errors', 1);
            error_reporting(E_ALL^E_NOTICE);
        }
        else{
            ini_set('display_errors', 0);
            error_reporting(~E_NOTICE);
        }
        
        parent::__construct();

        $this->request = new ArkRequest();

        //parse request
        $q = ark_parse_query_path();
        define('APP_URL',$this->request->getSchemeAndHttpHost().$q['base'].'/');
    }

    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function init(){
        $this->event->bind('app.404', 'ark_404');
    }
    
    /**
     * {@inheritdoc}
     */
    public function run(){
        $q = ark_parse_query_path();

        $this->event->trigger('app.dispatch', $q);
        if(!$r = ark_route($q['path'], $this->config->get('route.rules'))){
            $this->event->trigger('app.404');
        }
        else{
            $this->container->get('event')->trigger('ark.dispatch');
            $this->dispatch($r);
        }
        $this->container->get('event')->trigger('ark.shutdown');
    }
    
    /**
     * Dispatch
     * @param array $r
     */
    protected function dispatch($r){//$controller, $action, $params){
        //extract params for named pattern
        if(isset($r['params'])){
            foreach($r['params'] as $k => $v){
                $this->request->setAttribute($k, $v);
            }
        }

        

        $handler = null;
        $handler_params = null;
        //callback handler
        if(isset($r['handler'])){
            $handler = $r['handler'];
            $handler_params = ark_handler_params($handler, $r['params']);
        }
        else{
            if($r['controller'] === ''){
                $r['controller'] = 'default';
            }
            if($r['action'] === ''){
                $r['action'] = 'index';
            }
            $controllerFile = $this->getAppDir().'/controller/'.$r['controller'].'Controller.php';
            if(file_exists($controllerFile)){
                require_once($controllerFile);
                $classname = basename($r['controller']).'Controller';
                $methodName = $r['action'].'Action';
                $o = new $classname;
                if(method_exists($o, $methodName)){
                    $handler = array($o, $methodName);
                    $handler_params = ark_handler_params($classname.'::'.$methodName, $r['params']);
                }
            }
        }
        if(null !== $handler){
            $response = call_user_func_array($handler, $handler_params);
            if ($response instanceof ArkResponse) {
                $response->prepare()->send();
            }
            elseif(null !== $response){
                echo $response;
            }
        }
        else{
            $this->container->get('event')->trigger('ark.404');
        }
    }
}

/**
 * Console app
 */
class ArkAppCli extends ArkApp
{
    /**
     * {@inheritdoc}
     */
    protected function init(){
    }
    
    /**
     * {@inheritdoc}
     */
    public function run(){
    }
}