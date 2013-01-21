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
    protected $started = false;
    protected $path;
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
            //route
            $this->config->get('route')
            
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
            $this->config->get('route.defaults'),
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



    protected function registerRoute(){
        if(null === $route_prefix = $this->config->get('route_prefi'))
        $route = $this->config->get('route');
        if($reoute && )

    }

    protected function registerCommand()
    {
        
    }



    public function getConfigFile(){
        return $this->getPath().'/config.php';
    }

    protected function loadConfig(){
        $configs = include($this->getConfigFile());
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