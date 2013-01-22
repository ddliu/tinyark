<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

/**
 * The Router
 *
 *  rule:
 *      - path: user/<name>/(<page>)?
 *      - requirements: array(
 *          'name' => '[a-zA-Z_-]+',
 *      )
 *      - defaults: array(
 *          'name' => 'tt',
 *          'page' => 1,
 *      )
 *      - method: 'GET|POST',
 *      - https: true,
 *      - name: 
 *      - handler: 
 *
 *  request:
 *      - path
 *      - method
 *      - https
 */
class ArkRouter
{
    protected $rules = array();

    protected $names = array();

    protected $matchPatterns = array();

    protected $generatePaths = array();
    
    /**
     * Constructor add initial rules to list
     * @param array $rules
     */
    public function __construct($rules = array())
    {
        if(null !== $rules){
            foreach($rules as $rule){
                $this->addRule($rule);
            }
        }
    }

    /**
     * Add rule to list
     * @param array $rule
     */
    public function addRule($rule)
    {
        $this->rules[] = $rule;
        if(isset($rule['name'])){
            $this->names[$rule['name']] = count($this->rules) - 1;
        }

        return $this;
    }

    /**
     * Match a request with rules
     * @param  array $request
     * @return array|false
     */
    public function match($request)
    {
        foreach($this->rules as $k => $rule){
            if(false !== $attributes = $this->matchRule($request, $rule, $k)){
                if(is_array($attributes)){
                    $rule['attributes'] = $attributes;
                }

                return $rule;
            }
        }

        return false;
    }

    /**
     * Match a request with specified rule
     * @param  array $request
     * @param  array $rule
     * @param  integer $index
     * @return boolean|array Returns boolean on success or failure, array if we got any attrubutes
     */
    public function matchRule($request, $rule, $index = null)
    {
        //https
        if(isset($rule['https']) && $rule['https'] != $request['https']){
            return false;
        }

        //method
        if(isset($rule['method'])){
            if(!isset($request['method']) || !preg_match('#'.$rule['method'].'#', $request['method'])){
                return false;
            }
        }

        //path
        if(isset($rule['path'])){
            if(null !== $index){
                if(!isset($this->matchPatterns[$index])){
                    $this->matchPatterns[$index] = $this->regexpForMatch($rule['path'], isset($rule['requirements'])?$rule['requirements']:array());
                }
                $pattern = $this->matchPatterns[$index];                
            }
            else{
                $pattern = $this->regexpForMatch($rule['path'], isset($rule['requirements'])?$rule['requirements']:array());
            }

            if(!preg_match('#^'.$pattern.'$#', $request['path'], $match)){
                return false;
            }

            $attributes = isset($rule['defaults'])?$rule['defaults']:array();

            //Only fetch named matches as attributes
            foreach($match as $k => $v){
                if(!is_int($k)){
                    $attributes[$k] = $v;
                }
            }

            return $attributes;
        }
        else{
            return isset($rule['defaults'])?$rule['defaults']:true;
        }
    }

    private $matchRequirements;
    protected function regexpForMatch($path, $requirements){
        $this->matchRequirements = $requirements;
        return preg_replace_callback('#<([a-zA-Z0-9_]+)(:[^>]+)?>#', array($this, 'regexpForMatchCallback'), $path);
    }

    private function regexpForMatchCallback($match){
        if(isset($match[2])){
            $pattern = substr($match[2], 1);
        }
        elseif(isset($this->matchRequirements[$match[1]])){
            $pattern = $this->matchRequirements[$match[1]];
        }
        else{
            //default pattern
            $pattern = '[^/\.,;?\n]+';
        }

        //return named pattern
        return '(?<'.$match[1].'>'.$pattern.')';
    }

    protected function pathForGenerate($path){
        return str_replace('\\', '', preg_replace('#<([a-zA-Z0-9_]+):[^>]+>#', '<\\1>', str_replace(array('(', ')', '?'), '', $path)));
    }

    /**
     * Generate path with specified rule name
     * @param  string $name Name of registered rule
     * @param  array  $attributes
     * @return string|false
     */
    public function generate($name, $attributes = array())
    {
        if(!isset($this->names[$name])){
            return false;
        }

        $index = $this->names[$name];
        if(!isset($this->generatePaths[$index])){
            if(!isset($this->rules[$index]['path'])){
                return false;
            }
            $this->generatePaths[$index] = $this->pathForGenerate($this->rules[$index]['path']);
        }
        $replace_attributes = $attributes + (isset($this->rules[$index]['defaults'])?$this->rules[$index]['defaults']:array());
        $reserved_attributes = array();

        $replace = array();
        foreach($replace_attributes as $key => $value){
            if(false !== strpos($this->generatePaths[$index], '<'.$key.'>')){
                $replace['<'.$key.'>'] = $value;
            }
            else{
                if(isset($attributes[$key])){
                    $reserved_attributes[$key] = $value;
                }
            }
        }

        return strtr($this->generatePaths[$index], $replace).($reserved_attributes?'?'.http_build_query($reserved_attributes):'');
    }
}
