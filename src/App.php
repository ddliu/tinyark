<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

namespace ddliu\tinyark;

/**
 * Ark app
 */
abstract class App
{
    protected $container;

    protected $bundles = array();

    protected $registeredBundleInfo = array();

    public $config;

    public static $instance;

    public $event;

    /**
     * Path to app
     * @var string
     */
    protected $path;

    /**
     * Path to app resource
     * @var string
     */
    protected $resourcePath;

    static $buildinBundles = array('twig', 'smarty', 'sae', 'ace', 'bae');

    public function __construct($path = null, $env = 'prod', $debug = false)
    {
        $this->event = new ArkEventManager();

        $this->event->dispatch('app.before', $this);

        self::$instance = $this;
        
        if(null !== $path){
            $this->path = $path;
        }

        //path definations
        if (!defined('ARK_APP_PATH')) {
            define('ARK_APP_PATH', $this->getAppPath());
        }

        if (!defined('ARK_APP_DEBUG')) {
            define('ARK_APP_DEBUG', $debug);
        }

        if (!defined('ARK_APP_ENV')) {
            define('ARK_APP_ENV', $env);
        }

        if(ARK_APP_DEBUG){
            ini_set('display_errors', 1);
            error_reporting(E_ALL^E_NOTICE);
        }
        else{
            ini_set('display_errors', 0);
            error_reporting(~E_NOTICE);
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
                    'class' => 'ArkViewPHP',
                    'params' => array(
                        array(
                            'locator' => array($this, 'locateView'),
                            //'dir' => $this->getAppPath().'/resource/view',
                            'extract' => true,
                            //'ext' => '.php',
                        )
                    )
                )
            );
        }

        //autoload custom classes
        $this->addAutoloadFromConfig($this->config->get('autoload', array()));

        // exception handler
        set_exception_handler(array($this, 'handleException'));
        $this->event->attach('app.exception', array($this, 'handleExceptionDefault'), true, ArkEventManager::PRIORITY_LOWEST);

        //bundles
        $this->loadBundles();
        $this->init();
        //app is ready
        $this->event->dispatch('app.ready', $this);
    }
    
    /**
     * Init app
     */
    abstract protected function init();

    public function addAutoloadFromConfig($configs)
    {
        if(isset($configs['dir'])){
            foreach ($configs['dir'] as $dir) {
                ArkAutoload::registerDir($dir);
            }
        }

        if(isset($configs['file'])){
            ArkAutoload::registerFile($configs['file']);
        }

        if(isset($configs['namespace'])){
            foreach ($configs['namespace'] as $namespace => $path) {
                ArkAutoload::registerNamespace($namespace, $path);
            }
        }

        if(isset($configs['prefix'])){
            foreach ($configs['prefix'] as $prefix => $path) {
                ArkAutoload::registerPrefix($prefix, $path);
            }
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get application dir
     * 
     * @return string
     */
    public function getAppPath()
    {
        if(null === $this->path){
            $this->path = $this->getClassPath();
        }
        
        return $this->path;
    }

    public function getResourcePath()
    {
        if(null === $this->resourcePath){
            $this->resourcePath = $this->getAppPath().'/resource';
        }

        return $this->resourcePath;
    }

    public function getBundleDir()
    {
        return $this->getAppPath().'/bundle';
    }


    /**
     * Load all bundles
     * @todo Manage dependencies
     */
    protected function loadBundles()
    {
        foreach($this->config->get('bundle.bundles', array()) as $v){
            $this->addBundle($v);
        }
        //auto discover
        if($this->config->get('bundle.autodiscover')){
            if($bundle_dirs = _ark_sub_dirs($this->getBundleDir())){
                foreach($bundle_dirs as $name){
                    $this->addBundle($name);
                }
            }
        }
    }

    public function addBundle($v)
    {
        if(is_string($v)){
            if(preg_match('#^[a-zA-Z0-9_]+$#', $v)){
                //name
                $name = $this->registerBundleInfo($v);
            }
            else{
                //path
                $name = $this->registerBundleInfo(null, $v);
            }
            $this->loadBundle($name);
        }
        elseif(is_array($v)){
            $name = $this->registerBundleInfo(
                isset($v['name'])?$v['name']:null, 
                isset($v['path'])?$v['path']:null,
                isset($v['configs'])?$v['configs']:null
            );
            if(!isset($v['start']) || $v['start']){
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
                throw new Exception('Invalid bundle info');
            }

            if(null === $name){
                $name = basename($path);
            }
            elseif(null === $path){
                //buildin bundle
                if(in_array($name, self::$buildinBundles)){
                    $path = ARK_PATH.'/bundle/'.$name;
                }
                else{
                    $path = $this->getBundleDir().'/'.$name;
                }
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
                throw new Exception(sprintf('Bundle not found: %s at %s', $name, $info['path']));
                
                return false;
            }

            require($info['path'].'/bundle.php');
            $classname = str_replace(' ', '', ucwords(str_replace('_', ' ', $name))).'Bundle';
            $this->bundles[$name] = new $classname($this, null, null, $info['configs']);
        }

        return true;
    }
    
    public function getConfigFile(){
        return $this->getAppPath().'/resource/config.php';
    }
    
    public function loadConfigs(){
        $configs = @include($this->getConfigFile());
        $this->config = new ArkConfig(is_array($configs)?$configs:array());

        return $this;
    }


    public function locateResource($resource)
    {
        if($resource[0] !== '@'){
            throw new Exception('Resource does not start with @');
        }

        $parts = explode('/', substr($resource, 1), 2);
        //app
        if($parts[0] === ''){
            return $this->getResourcePath().'/'.$parts[1];
        }
        else{
            $path = $this->getResourcePath().'/'.$parts[0].'Bundle/'.$parts[1];
            if(file_exists($path)){
                return $path;
            }
            else{
                return $this->getBundle($parts[0])->getResourcePath().'/'.$parts[1];
            }
        }
    }

    public function locateView($view)
    {
        if($view[0] !== '@'){
            $view = '@/'.$view;
        }
        
        if(false === $pos = strpos($view, '/')){
            return false;
        }

        return $this->locateResource(substr($view, 0, $pos).'/view'.substr($view, $pos));
    }

    protected function getClassPath(){
        static $path;
        if(null === $path){
            $reflected = new ReflectionObject($this);
            $path = dirname($reflected->getFileName());
        }

        return $path;
    }

    public function isCli(){
        return PHP_SAPI === 'cli';
    }

    /**
     * Run app
     */
    abstract public function run();

    public function handleException($exception)
    {
        $this->dispatchResponseEvent('app.exception', $this, array(
            'exception' => $exception
        ));
    }

    public function dispatchResponseEvent($event, $source = null, $data = array())
    {
        if (is_string($event)) {
            $event = new ArkEvent($event, $source, $data);
        }
        $this->event->dispatch($event, $source, $data);

        if ($event->result !== null) {
            $this->respond($event->result);
        }
    }

    public function handleExceptionDefault($exception)
    {
        if (ARK_APP_DEBUG) {
            throw $exception;
        } else {
            echo 'Error occurred';
        }

        $this->respond($resonse);
    }

    /**
     * Respond and exit
     * @param  mixed $response
     */
    public function respond($response, $exit = true)
    {
        // response
        $event = new ArkEvent('app.response', $this, $response);
        $this->event->dispatch($event);

        echo $response;

        $this->event->dispatch('app.shutdown', $this);

        $exit && exit();
    }
}