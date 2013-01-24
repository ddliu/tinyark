<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

/**
 * Configuration
 */
class ArkConfig
{
    protected $configs;

    public function __construct($configs = array())
    {
        $this->configs = $configs;
    }

    /**
     * Get config with key
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if(null === $key){
            return $this->configs;
        }

        $array = &$this->configs;
        foreach(explode('.', $key) as $segment){
            if(isset($array[$segment])){
                $array = &$array[$segment];
            }
            else{
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set configuration
     * @param string|null $key   null to reset all configurations, else to set the specified key.
     * @param mixed $value
     * @return ArkConfig
     */
    public function set($key, $value)
    {
        if(null === $key){
            $this->configs = $value;
        }

        $array = &$this->configs;
        $segments = explode('.', $key);
        $segment_count = count($segments);
        foreach($segments as $i => $segment){
            if($i < $segment_count - 1){
                if(!isset($array[$segment]) || !is_array($array[$segment])){
                    $array[$segment] = array();
                }
                $array = &$array[$segment];
            }
        }

        $array[$segment] = $value;

        return $this;
    }

    /**
     * Merge configurations
     * @param  string|array|null $key   null or array to merge from the top level, else to merge from specified key
     * @param  mixed $value None array value will be appended
     * @return ArkConfig
     */
    public function merge($key, $value = null)
    {
        if(is_array($key)){
            $this->mergeRecursiveWithOverwrite($this->configs, $key);
        }
        else{
            $array = &$this->configs;
            if(null !== $key && '' !== $key){
                foreach(explode('.', $key) as $segment){
                    if(!isset($array[$segment]) || !is_array($array[$segment])){
                        $array[$segment] = array();
                    }
                    $array = &$array[$segment];
                }
            }

            if(!is_array($value)){
                $array[] = $value;
            }
            else{
                $this->mergeRecursiveWithOverwrite($array, $value);
            }
        }

        return $this;
    }

    protected function mergeRecursiveWithOverwrite(&$arr1, $arr2){
        foreach($arr2 as $k => $v){
            if(is_int($k)){
                $arr1[] = $v;
            }
            elseif(isset($arr1[$k]) && is_array($arr1[$k]) && is_array($v)){
                $arr1_child = &$arr1[$k];
                $this->mergeRecursiveWithOverwrite($arr1_child, $arr2[$k]);
            }
            else{
                $arr1[$k] = $v;
            }
        }
    }

    /**
     * Append
     * @param  string|null $key
     * @param  mixed $value
     * @return ArkConfig
     */
    public function append($key, $value)
    {
        $array = &$this->configs;
        if(null !== $key && '' !== $key){
            foreach(explode('.', $key) as $segment){
                if(!isset($array[$segment]) || !is_array($array[$segment])){
                    $array[$segment] = array();
                }
                $array = &$array[$segment];
            }
        }

        $array[] = $value;

        return $this;
    }

    /**
     * Remove config
     * @param  string $key
     */
    public function remove($key)
    {
        $array = &$this->configs;
        $segments = explode('.', $key);
        $segment_count = count($segments);
        foreach($segments as $i => $segment){
            if(!isset($array[$segment])){
                break;
            }
            if($i < $segment_count - 1){
                $array = &$array[$segment];
            }
            else{
                unset($array[$segment]);
                break;
            }
        }
    }

    /**
     * Check if config exists
     * @param  string  $key
     * @return boolean
     */
    public function has($key)
    {
        $array = &$this->configs;
        foreach(explode('.', $key) as $segment){
            if(isset($array[$segment])){
                $array = &$array[$segment];
            }
            else{
                return false;
            }
        }

        return true;
    }
}
