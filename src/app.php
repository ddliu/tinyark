<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * Ark app
 */
abstract class ArkApp
{
    protected $container;

    public $config;

    public static $instance;

    public function __construct(){
        self::$instance = $this;
        
        //path definations
        if(!defined('APP_DIR')){
            define('APP_DIR', $this->getAppDir());
        }
        
        $this->loadConfigs();

        //Set default timezone
        if($this->config->has('timezone')){
            date_default_timezone_set($this->config->get('timezone'));
        }

        //Service container
        $this->container = new ArkContainer($this->config->get('service', array()));
        
        //Setup default services and events
        //View
        if(!$this->config->has('service.view')){
            $this->container->register('view', array(
                'class' => 'ArkView',
                'params' => array(
                    array(
                        'dir' => $this->getAppDir().'/view',
                        'extract' => true,
                        //'ext' => '.php',
                    )
                )
            ));
        }

        //Event dispatcher
        $this->container->register('event', array(
            'class' => 'ArkEvent',
        ));
        
        //autoload custom classes
        if($this->config->has('autoload.dir')){
            foreach($this->config->get('autoload.dir') as $dir){
                ArkAutoload::registerDir($dir);
            }
        }

        if($this->config->has('autoload.file')){
            ArkAutoload::registerFile($this->config->get('autoload.file'));
        }

        //bundles
        $this->loadBundles();
        
        $this->init();
        
        //app is ready
        $this->container->get('event')->trigger('ark.ready');
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
    public function getAppDir(){
        return $this->getPath();
    }

    public function getBundleDir()
    {
        return $this->getAppDir().'/bundle';
    }

    protected function loadBundles()
    {
        foreach($this->config->get('bundle.bundles', array()) as $v){
            if(is_string($v)){
                if(preg_match('#^[a-zA-Z0-9_]$#', $v)){
                    //name
                    $this->addBundle($v);
                }
                else{
                    //path
                    $this->addBundle(null, $v);
                }
            }
            elseif(is_array($v)){
                $this->addBundle(
                    isset($v['name'])?$v['name']:null, 
                    isset($v['path'])?$v['path']:null,
                    isset($v['start'])?$v['start']:true
                );
            }else{
                continue;
            }
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

    /**
     * Add a bundle
     * @param string|null  $name
     * @param string|null  $path
     * @param boolean $start
     * @return boolean
     */
    public function addBundle($name, $path, $start = true)
    {
        if(null === $name && null === $path){
            return false;
        }

        if(null === $name){
            $name = dirname($path);
        }
        elseif(null === $path){
            $path = $this.getBundleDir().'/'.$name;
        }

        if(!isset($this->bundles[$name])){
            if(!file_exists($path.'/bundle.php')){
                return false;
            }

            require $path.'/bundle.php';
            $classname = str_replace(' ', '', ucwords(str_replace('_', ' ', $name))).'Bundle';
            $this->bundles[$name] = new $classname();

            return true;
        }

        return false;
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
            error_reporting(E_ALL^E_NOTICE);
        }
        else{
            error_reporting(0);
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
        $this->container->get('event')->bind('ark.404', 'ark_404');
    }
    
    /**
     * {@inheritdoc}
     */
    public function run(){
        $q = ark_parse_query_path();
        if(!$r = ark_route($q['path'], $this->config->get('route.rules'))){
            $this->container->get('event')->trigger('ark.404');
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