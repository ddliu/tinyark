<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * ArkBundle
 */
class ArkBundle
{
    protected $app;
    protected $reflected;
    protected $started = false;
    protected $path;
    protected $name;
    public $config;

    public function __construct($app = null, $start = true)
    {
        if(null !== $app){
            $this->app = $app;
        }

        $this->loadConfig();

        if($start){
            $this->start();
        }
    }

    /**
     * Start bundle
     */
    public function start()
    {
        if(!$this->started){
            $this->init();
            if($this->app->isCli()){
                $this->initCli();
            }
            else{
                $this->initWeb();
            }
            $this->started = true;
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

    }

    protected function initCli(){

    }

    protected function getApp(){

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
