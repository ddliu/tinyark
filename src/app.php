<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * Ark app
 */
abstract class ArkApp
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

/**
 * Web app
 */
class ArkAppWeb extends ArkApp
{
    protected $request;
    public $router;

    public function __construct($path = null, $env = 'prod', $debug = false){
        if(get_magic_quotes_gpc()){
            $_GET = array_map('stripcslashes', $_GET);
            $_POST = array_map('stripcslashes', $_POST);
            $_COOKIE = array_map('stripslashes', $_COOKIE);
        }

        $this->request = new ArkRequest();
        $this->router = new ArkRouter();
        if (!defined('ARK_APP_URL')) {
            define('ARK_APP_URL', $this->request->getSchemeAndHttpHost().$this->request->getBasePath().'/');
        }
        
        parent::__construct($path, $env, $debug);

        ini_set('default_charset', $this->config->get('charset', 'utf-8'));
        $this->addRouterRules(
            $this->config->get('route.rules', array()), 
            null, 
            $this->config->get('route.requirements'),
            $this->config->get('route.defaults')
        );
    }

    /**
     * Add rules
     * @param array $rules 
     *      array(
     *          pattern => handler
     *          pattern => rule
     *          array(
     *              k => v
     *              k => v
     *          )
     *      )
     * @param string $prefix
     * @param array $requirements
     * @param array $defaults
     * @return ArkApp
     */
    public function addRouterRules($rules, $prefix = null, $requirements = null, $defaults = null)
    {
        if(null !== $prefix){
            $prefix = preg_quote($prefix, '#');
        }
        foreach ($rules as $key => $value) {
            if(is_array($value)){
                $rule = $value;
                if(is_string($key)){
                    $rule['path'] = $key;
                }
            }
            else{
                $rule = array(
                    'path' => $key,
                    'handler' => $value,
                );
            }

            //prefix
            if(null !== $prefix){
                $rule['path'] = $prefix.$rule['path'];
            }
            //requirements
            if(null !== $requirements){
                if(!isset($rule['requirements'])){
                    $rule['requirements'] = $requirements;
                }
                else{
                    $rule['requirements'] += $requirements;
                }
            }

            //defaults
            if(null !== $defaults){
                if(!isset($rule['defaults'])){
                    $rule['defaults'] = $defaults;
                }
                else{
                    $rule['defaults'] += $defaults;
                }
            }

            $this->router->addRule($rule);
        }

        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function init(){
        
        $this->event->attach('app.404', array($this, 'handle404Default'), false, ArkEventManager::PRIORITY_LOWEST);
        $this->event->attach('app.dispatch', array($this, 'dispatch'), false, ArkEventManager::PRIORITY_LOWEST);
    }

    public function handle404Default($event)
    {
        return Ark::getHttpErrorResponse(404);
    }

    public function handleExceptionDefault($exception)
    {
        $view = new ArkViewPHP();
        $http_code = 500;
        $message = ArkResponse::getStatusMessageByCode($http_code);
        if (ARK_APP_DEBUG) {
            $message .= '<br /><pre>'.$exception.'</pre>';
        }
        
        return new ArkResponse($view->render(ARK_PATH.'/internal/view/http_error.html.php', array(
            'code' => $http_code,
            'title' => ArkResponse::getStatusTextByCode($http_code),
            'message' => $message,
        ), true), $http_code);
    }

    public function forward()
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function run(){
        $q = _ark_parse_query_path($this->config->get('route.route_var', 'r'));
        $q['https'] = $this->request->isSecure();
        $q['method'] = $this->request->getMethod();

        //Request method validation
        if(!in_array($q['method'], array('GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'))){
            $response = Ark::getHttpErrorResponse(405);
            $this->respond($resonse);    
        }
        else{
            $this->dispatchResponseEvent('app.dispatch', $this, $q);
        }
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

        if ($response instanceof ArkResponse) {
            $response->prepare()->send();
        }
        else{
            echo $response;
        }

        $this->event->dispatch('app.shutdown', $this);

        $exit && exit();
    }

    public function findAction($action)
    {
        // validate action
        if (!preg_match('#^[a-zA-Z][a-zA-Z0-9_]*$#', $action['_action'])) {
            return false;
        }

        if (isset($action['_controller']) && $action['_controller'] !== '') {
            if (!preg_match('#^([a-zA-Z0-9_-]+/)*([a-zA-Z][a-zA-Z0-9_]*)$#', $action['_controller'], $match)) {
                return false;
            }
        }

        // get path
        if(!isset($action['_bundle']) || '' === $action['_bundle']){
            $path = $this->getAppPath();
        }
        elseif(!$bundle = $this->getBundle($action['_bundle'])){
            return false;
        }
        else{
            $path = $bundle->getPath();
        }

        // action file
        $class = $action['_action'].'Action';

        if (!class_exists($class)) {
            $action_file = $path.'/controller/'.$action['_controller'].'/'.$class.'.php';
            if (file_exists($action_file)) {
                require($action_file);
            }
        }

        if (class_exists($class)) {
            $instance = new $class();
            if (method_exists($instance, 'run')) {
                return array($instance, 'run');
            }
        }

        // controller file
        $class = $match[2].'Controller';

        if(!class_exists($class)){
            $controller_file = $path.'/controller/'.$action['_controller'].'Controller.php';
            if(!file_exists($controller_file)){
                return false;
            }

            require($controller_file);
        }

        $controller = new $class();
        if(!method_exists($controller, $action['_action'].'Action')){
            return false;
        }

        return array($controller, $action['_action'].'Action');
    }
    
    /**
     * Dispatch
     * @param array $r
     */
    public function dispatch($event){
        if(false !== $rule = $this->router->match($event->data)){
            //action
            if(!isset($rule['handler']) || (is_string($rule['handler']) && !function_exists($rule['handler']))){
                $action = array();
                $handler = $rule['handler'];
                if($rule['handler'][0] === '@'){
                    $parts = explode('/', $rule['handler'], 2);
                    $action['_bundle'] = substr($parts[0], 1);
                    $handler = $parts[1];
                }
                else{
                    $handler = ltrim($handler, '/');
                }

                if($handler !== ''){
                    $last_slash = strrpos($handler, '/');
                    if(false === $last_slash){
                        $action['_action'] = $handler;
                    }
                    //ends with slash
                    elseif($last_slash === strlen($handler) - 1){
                        $action['_controller'] = substr($handler, 0, -1);
                    }
                    else{
                        $action['_controller'] = substr($handler, 0, $last_slash);
                        $action['_action'] = substr($handler, $last_slash + 1);
                    }
                }

                if(!isset($action['_bundle'])){
                    if(isset($rule['attributes']['_bundle'])){
                        $action['_bundle'] = $rule['attributes']['_bundle'];
                    }
                }
                if(!isset($action['_controller']) && $action['_controller'] !== ''){
                    if(isset($rule['attributes']['_controller'])){
                        $action['_controller'] = $rule['attributes']['_controller'];
                    }
                    // else{
                    //     $action['_controller'] = 'default';
                    // }
                }
                if(!isset($action['_action'])){
                    if(isset($rule['attributes']['_action'])){
                        $action['_action'] = $rule['attributes']['_action'];
                    }
                    else{
                        $action['_action'] = 'index';
                    }
                }

                if(false === $handler = $this->findAction($action)){
                    $this->dispatchResponseEvent('app.404', $this);
                    return;
                }
            }
            //callable handler
            else{
                $handler = $rule['handler'];
            }
            
            if(isset($rule['attributes'])){
                foreach($rule['attributes'] as $k => $v){
                    $this->request->setAttribute($k, $v);
                }
            }

            $handler_params = _ark_handler_params($handler, $rule['attributes']);
            return call_user_func_array($handler, $handler_params);
        }
        else{
            $this->dispatchResponseEvent('app.404', $this);
            return;
        }
    }


    public function appUrl($path = '', $attributes = null, $absolute = false, $https = null)
    {
        $path = ltrim($path, '/');
        if($absolute){
            if($https === null ){
                $result = $this->request->getSchemeAndHttpHost();
            }
            elseif($https){
                $result = 'https://'.$this->request->getHttpHost();
            }
            else{
                $result = 'http://'.$this->request->getHttpHost();
            }
        }
        else{
            $result = '';
        }

        $result .= $this->request->getBasePath().'/'.$path;
        if(is_array($attributes)){
            $result .= '?'.http_build_query($attributes);
        }

        return $result;
    }

    public function routeUrl($name, $attributes = null, $absolute = false, $https = null)
    {
        if(false !== strpos($name, '/')){
            $name = ltrim($name, '/');
            $url = $name.($attributes?('?'.http_build_query($attributes)):'');
        }
        elseif(false === $url = $this->router->generate($name, (null !== $attributes)?$attributes:array())){
            return false;
        }

        if($absolute){
            if($https === null ){
                $result = $this->request->getSchemeAndHttpHost();
            }
            elseif($https){
                $result = 'https://'.$this->request->getHttpHost();
            }
            else{
                $result = 'http://'.$this->request->getHttpHost();
            }
        }
        else{
            $result = '';
        }
        $result .= $this->request->getBasePath().'/';
        if($url === '' || $url[0] === '?'){
            return $result;
        }

        $url_mode = $this->config->get('route.mode', 'pathinfo');
        //rewrite
        if($url_mode === 'rewrite'){
            $result .= $url;
        }
        //pathinfo
        elseif($url_mode === 'pathinfo'){
            $result .= 'index.php/'.$url;
        }
        else{
            $result .= '?'.$this->config->get('route.route_var', 'r').'='.str_replace('?', '&', $url);
        }
        
        return $result;
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