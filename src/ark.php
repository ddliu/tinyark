<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * This is the bootstrap file for the framework
 */

define('ARK_MICROTIME', microtime(true));
define('ARK_TIMESTAMP', round(ARK_MICROTIME));

//Path of the framework
define( 'ARK_DIR' , dirname(__FILE__));

require_once(ARK_DIR.'/utils.php');

class Ark
{
    public static $configs;

    /**
     * Autoload framework
     */
    public static function autoloadFramework(){
        static $registered;
        if(null === $registered){
            $registered = true;
            //autoload
            spl_autoload_register('ArkAutoload::load');

            //register ark classes
            ArkAutoload::registerFile(array(
                'ArkConfig' => ARK_DIR.'/config.php',

                'ArkView' => ARK_DIR.'/view.php',
                'ArkViewHelper' => ARK_DIR.'/view.php',

                'ArkController' => ARK_DIR.'/controller.php',

                'ArkPagination' => ARK_DIR.'/pagination.php',

                'ArkCacheBase' => ARK_DIR.'/cache.php',
                'ArkCacheArray' => ARK_DIR.'/cache.php',
                'ArkCacheFile' => ARK_DIR.'/cache.php',
                'ArkCacheAPC' => ARK_DIR.'/cache.php',
                'ArkCacheMemcache' => ARK_DIR.'/cache.php',

                'ArkResponse' => ARK_DIR.'/http.php',
                'ArkRequest' => ARK_DIR.'/http.php',

                'ArkLoggerBase' => ARK_DIR.'/logger.php',
                'ArkLoggerFile' => ARK_DIR.'/logger.php',

                'ArkHttpClient' => ARK_DIR.'/httpclient.php',
            ));

        }
    }

    /**
     * Render internal templates
     * @param  String  $template
     * @param  mixed  $variables
     * @param  boolean $return
     * @return mixed
     */
    public static function renderInternal($template, $variables = null, $return = false){
        $view = new ArkView();

        return $view->render(ARK_DIR.'/internal/view/'.$template, $variables, $return);
    }

    public static function app(){
        return ArkApp::$instance;
    }
}

/**
 * Ark app
 */
abstract class ArkApp
{
    protected $container;

    public $configs;

    public static $instance;

    public function __construct(){
        self::$instance = $this;

        //autoload
        Ark::autoloadFramework();
        
        //path definations
        if(!defined('APP_DIR')){
            define('APP_DIR', $this->getAppDir());
        }
        
        $this->loadConfigs();

        //Set default timezone
        if(isset($this->configs['timezone'])){
            date_default_timezone_set($this->configs['timezone']);
        }

        //Service container
        $this->container = new ArkContainer(isset($this->configs['services'])?$this->configs['services']:array());
        
        //Setup default services and events
        //View
        if(!isset($this->configs['services']['view'])){
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
        if(!isset($this->configs['services']['event'])){
            $this->container->register('event', array(
                'class' => 'ArkEvent',
            ));
        }
        
        //autoload custom classes
        if(isset($this->configs['autoload']['dir'])){
            foreach($this->configs['autoload']['dir'] as $dir){
                ArkAutoload::registerDir($dir);
            }
        }

        if(isset($this->configs['autoload']['file'])){
            ArkAutoload::registerFile($this->configs['autoload']['file']);
        }
        
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

    protected function loadBundles()
    {
        
    }
    
    public function getConfigFile(){
        return $this->getAppDir().'/config.php';
    }
    
    public function loadConfigs(){
        $configs = include($this->getConfigFile());
        $this->configs = is_array($configs)?$configs:array();

        return $this;
    }

    public function getConfig($key, $default = null)
    {
        if(isset($this->configs[$key])){
            return $this->configs[$key];
        }
        else{
            return $default;
        }
    }

    protected function getPath(){
        static $dir;
        if(null === $dir){
            $reflected = new \ReflectionObject($this);
            $dir = dirname($reflected->getFileName());
        }

        return $dir;
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
        if(!$r = ark_route($q['path'], $this->getConfig('route'))){
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
class ArkAppConsole extends ArkApp
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

/**
 * Service container
 */
class ArkContainer
{
    /**
     * service list
     */
    protected $services = array(
    );
    
    protected $configs = array();
    
    public function __construct($configs = array()){
        $this->configs = $configs;
    }

    /**
     * Get service by name
     */
    public function get($name){
        if(!isset($this->services[$name])){
            $this->initService($name);
             if(!isset($this->services[$name])){
                throw new Exception(sprintf('Service "%s" does not exist or can not be started', $name));
             }
        }

        return $this->services[$name];
    }

    public function set($name, $value){
        $this->services[$name] = $value;
    }

    public function register($name, $value){
        $this->configs[$name] = $value;
    }

    protected function initService($name){
        if(isset($this->configs[$name])){
            $service_config = $this->configs[$name];
            if(is_callable($service_config)){
                $service = call_user_func($service_config);
            }
            elseif(is_array($service_config)){
                if(isset($service_config['class'])){
                    if(isset($service_config['method'])){
                        $service = call_user_func_array(
                            $service_config['class'].'::'.$service_config['method'], 
                            isset($service_config['params'])?$service_config['params']:array()
                        );
                    }
                    else{
                        if(isset($service_config['params'])){
                            $r = new ReflectionClass($service_config['class']);
                            $service = $r->newInstanceArgs($service_config['params']);
                        }
                        else{
                            $service = new $service_config['class'];
                        }
                    }
                }
            }

            //inject container
            if(isset($service)){
                $this->set($name, $service);
                //ready event of service
                if(isset($this->services['event'])){
                    $this->get('event')->trigger($name.'.ready');
                }
            }
        }
    }
}

class ArkEvent
{
    protected $eventList = array();

    public function bind($event, $callback){
        if(!isset($this->eventList[$event])){
            $this->eventList[$event] = array($callback);
        }
        else{
            $this->eventList[$event][] = $callback;
        }
    }

    public function unbind($event){
        if(isset($this->eventList[$event])){
            unset($this->eventList[$event]);
        }
    }

    public function trigger($event){
        $args = func_get_args();
        array_shift($args);
        if(isset($this->eventList[$event])){
            foreach($this->eventList[$event] as $callback){
                if(false === call_user_func_array($callback, $args)){
                    break;
                }
            }
        }
    }
}

/**
 * Universal Autoloader
 */
class ArkAutoload
{
    static private $namespaces = array(
    );

    static private $files = array();
    
    static private $dirs = array();
    
    static private $prefixes = array();

    static public function load($name){
        //file
        if(self::loadFile($name)){
            return true;
        }
        
        //prefix
        
        //namespace
        if(self::loadNamespace($name)){
            return true;
        }
        
        //file
        if(self::loadDir($name)){
            return true;
        }

        return false;
    }

    static public function registerNamespace($namespace, $path){
        self::$namespaces[$namespace] = $path;
    }
    
    static private function loadNamespace($name){
        foreach (self::$namespaces as $namespace => $path) {
            $prefix_length = strlen($namespace);
            if(substr($name, 0, $prefix_length + 1) === $namespace.'\\'){
                $file = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($name, $prefix_length)).'.php';
                require($file);
                return true;
            }
        }
        return false;
    }

    static public function registerNamespaceOnce($namespace, $path){
        if(!isset(self::$namespaces[$namespace])){
            self::$namespaces[$namespace] = $path;       
        }
    }

    static public function registerFile($class, $file = null){
        if(is_array($class)){
            foreach($class as $k => $v){
                self::$files[$k] = $v;
            }
        }
        else{
            self::$files[$class] = $file;
        }
    }
    
    static public function loadFile($name){
        if(isset(self::$files[$name])){
            require(self::$files[$name]);
            return true;
        }
        return false;
    }
    
    static public function registerDir($dir, $hasChild = true){
        self::$dirs[$dir] = $hasChild;
    }
    
    static public function loadDir($name){
        $name_path = str_replace('_', '/', $name);
        foreach(self::$dirs as $dir => $hasChild){
            if($hasChild){
                $file = $dir.'/'.$name_path.'.php';
            }
            else{
                $file = $dir.'/'.$name.'.php';
            }
            if(file_exists($file)){
                require($file);
                return true;
            }
        }
        
        return false;
    }
}
