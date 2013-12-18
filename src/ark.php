<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * This is the bootstrap file for the framework
 */

define('ARK_MICROTIME', microtime(true));
define('ARK_TIMESTAMP', round(ARK_MICROTIME));

//Path of the framework
define('ARK_PATH' , dirname(__FILE__));

require_once(ARK_PATH.'/utils.php');

class Ark
{
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
                'ArkEventManager' => ARK_PATH.'/event.php',
                'ArkEvent' => ARK_PATH.'/event.php',
                'ArkApp' => ARK_PATH.'/app.php',
                'ArkAppWeb' => ARK_PATH.'/app.php',
                'ArkAppCli' => ARK_PATH.'/app.php',

                'ArkConfig' => ARK_PATH.'/config.php',

                'ArkBundle' => ARK_PATH.'/bundle.php',

                'ArkViewInterface' => ARK_PATH.'/view.php',
                'ArkViewPHP' => ARK_PATH.'/view.php',
                'ArkViewHelper' => ARK_PATH.'/view.php',

                'ArkController' => ARK_PATH.'/controller.php',

                'ArkPagination' => ARK_PATH.'/pagination.php',

                'ArkCacheBase' => ARK_PATH.'/cache.php',
                'ArkCacheArray' => ARK_PATH.'/cache.php',
                'ArkCacheFile' => ARK_PATH.'/cache.php',
                'ArkCacheAPC' => ARK_PATH.'/cache.php',
                'ArkCacheMemcache' => ARK_PATH.'/cache.php',

                'ArkResponse' => ARK_PATH.'/http.php',
                'ArkRequest' => ARK_PATH.'/http.php',
                'ArkMimetype' => ARK_PATH.'/mimetype.php',

                'ArkLoggerHandlerAbstract' => ARK_PATH.'/logger.php',
                'ArkLoggerHandlerEcho' => ARK_PATH.'/logger.php',
                'ArkLoggerHandlerFile' => ARK_PATH.'/logger.php',
                'ArkLoggerHandlerErrorLog' => ARK_PATH.'/logger.php',
                'ArkLogger' => ARK_PATH.'/logger.php',

                'ArkRouter' => ARK_PATH.'/router.php',

                'ArkHttpClient' => ARK_PATH.'/httpclient.php',
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
    static public function renderInternal($template, $variables = null, $return = false){
        $view = new ArkViewPHP();

        return $view->render(ARK_PATH.'/internal/view/'.$template, $variables, $return);
    }

    static public function getHttpErrorResponse($http_code){
        $view = new ArkViewPHP();
        return new ArkResponse($view->render(ARK_PATH.'/internal/view/http_error.html.php', array(
            'code' => $http_code,
            'title' => ArkResponse::getStatusTextByCode($http_code),
            'message' => ArkResponse::getStatusMessageByCode($http_code)
        ), true), $http_code);
    }

    static public function app(){
        return ArkApp::$instance;
    }

    static public function createWebApp($path, $env = 'prod', $debug = false){
        return new ArkAppWeb($path, $env, $debug);
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

    public function register($name, $value = null){
        if(is_array($name)){
            foreach ($name as $key => $value) {
                $this->configs[$key] = $value;
            }
        }
        else{
            $this->configs[$name] = $value;
        }
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
        if(self::loadPrefix($name)){
            return true;
        }
        
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

    static public function registerPrefix($prefix, $path){
        self::$prefixes[$prefix] = $path;
    }

    static public function loadPrefix($name){
        foreach(self::$prefixes as $prefix => $path){
            $prefix_length = strlen($prefix);
            if(substr($name, 0, $prefix_length + 1) === $prefix.'_'){
                $file = $path.DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $name).'.php';
                require($file);
                return true;
            }
        }
    }
}

Ark::autoloadFramework();