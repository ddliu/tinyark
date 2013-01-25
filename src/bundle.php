<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

/**
 * ArkBundle
 */
class ArkBundle
{
    protected $dependencies;
    protected $app;
    protected $reflected;

    /**
     * Path to bundle
     * @var string
     */
    protected $path;

    /**
     * Path to bundle resources
     * @var string
     */
    protected $resourcePath;

    protected $name;
    public $config;

    public function __construct($app, $name = null, $path = null, $configs = null)
    {
        $this->app = $app;

        if(null !== $name){
            $this->name = $name;
        }
        if(null !== $path){
            $this->path = $path;
        }
        $this->loadConfig();
        if(null !== $configs){
            $this->config->merge($configs);
        }

        //autoload
        $this->app->addAutoloadFromConfig($this->config->get('autoload', array()));

        //dependencies
        if($dependencies = $this->getDependencies()){
            foreach($dependencies as $v){
                $this->app->addBundle($v);
            }
        }

        $this->init();
        if($this->app->isCli()){
            $this->initCli();
        }
        else{
            $this->initWeb();
        }
    }

    /**
     * Init bundle
     *
     * Put your startup code here
     */
    protected function init(){

    }

    protected function initWeb(){
        $this->app->addRouterRules(
            $this->config->get('route.rules', array()), 
            $this->config->get('route.prefix'),
            $this->config->get('route.requirements'),
            $this->config->get('route.defaults', array()) + array('_bundle' => $this->getName())
        );
    }

    protected function initCli(){

    }

    protected function getApp(){
        return $this->app;
    }

    public function getDependencies()
    {
        return $this->config->get('bundle', $this->dependencies);
    }

    protected function registerCommand()
    {
        
    }

    protected function loadConfig(){
        $config_file = $this->locateResource('@'.$this->getName().'/config.php');
        $configs = @include($config_file);
        $this->config = new ArkConfig(is_array($configs)?$configs:array());
    }

    /**
     * Get root path of bundle
     * @return string
     */
    public function getPath(){
        if(null === $this->path){
            $this->path = dirname($this->getReflected()->getFileName());
        }
        
        return $this->path;
    }

    public function getResourcePath()
    {
        if(null === $this->resourcePath){
            $this->resourcePath = $this->getPath().'/resource';
        }

        return $this->resourcePath;
    }

    public function locateResource($resource)
    {
        if($resource[0] !== '@'){
            throw new Exception('Resource does not start with @');
        }

        $parts = explode('/', substr($resource, 1), 2);
        //app
        if($parts[0] === ''){
            return $this->app->getResourcePath().'/'.$parts[1];
        }
        elseif($parts[1] === '.'){
            return $this->getResourcePath().'/'.$parts[1];
        }
        else{
            $path = $this->getResourcePath().'/'.$parts[0].'Bundle/'.$parts[1];
            if(file_exists($path)){
                return $path;
            }
            else{
                if($parts[0] === $this->getName()){
                    return $this->getResourcePath().'/'.$parts[1];
                }
                else{
                    return $this->app->getBundle($parts[0])->getResourcePath().'/'.$parts[1];
                }
            }
        }
    }

    public function locateView($view)
    {
        if(false === $pos = strpos($view, '/')){
            return false;
        }

        return $this->locateResource(substr($view, 0, $pos).'/view'.substr($view, $pos));
    }

    /**
     * Get short name of bundle
     * @return string
     */
    public function getName(){
        if(null === $this->name){
            //Note that ReflectionObject::getShortName() requires PHP5.3
            $class_name = $this->getReflected()->getName();
            if($slash_pos = strrpos($class_name, '\\')){
                $class_name = substr($class_name, $slash_pos + 1);
            }
            $len = strlen($class_name);
            if(substr($class_name, -6) === 'Bundle'){
                $class_name = substr($class_name, 0, -6);
            }
            $this->name = strtolower(preg_replace('#([A-Z][a-z0-9]+)([A-Z])#', '\\1_\\2', $class_name));
        }

        return $this->name;
    }

    protected function getReflected(){
        if(null === $this->reflected){
            $this->reflected = new ReflectionObject($this);
        }

        return $this->reflected;
    }
}


class ArkBundleSimple extends ArkBundle
{
    public function __construct($app, $configs, $name, $path)
    {
        $this->name = $name;
        $this->$path = $path;

        parent::__construct($app, $configs);
    }
}