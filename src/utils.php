<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * Help functions and shortcut functions
 */

/**
 * Get service
 * @param string $name Service name
 * @return mixed
 */
function ark($name = null){
    $container = Ark::app()->getContainer();

    if(null === $name){
        return $container;
    }

    return $container->get($name);
}

function ark_bundle($name)
{
    return Ark::app()->getBundle($name);
}

/**
 * 404 page
 */
function ark_404(){
    Ark::getHttpErrorResponse(404)->prepare()->send();
}

/**
 * Parse the query path
 * url_mod 0: pathinfo, 1: rewrite, 2: normal(?r=)
 * @param  string $key
 * @return array
 *         - mode: 0, 1, 2; unknown if not set
 *         - path: 
 *         - basename
 */
function ark_parse_query_path($key = 'r'){
    $q = array();
    $script_name = $_SERVER['SCRIPT_NAME'];
    $request_uri = $_SERVER['REQUEST_URI'];
    if(false !== $pos = strpos($request_uri, '?')){
        $request_uri = substr($request_uri, 0, $pos);
    }
    $basename = basename($script_name);

    $basename_length = strlen($basename);

    $slash_pos = strrpos($script_name, '/');
    //remove base path
    $request_uri = substr($request_uri, $slash_pos + 1);
    if($request_uri === false){
        $request_uri = '';
    }
    //pathinfo
    if(substr($request_uri, 0, $basename_length + 1) === $basename.'/' ){
        $q['mode'] = 0;
        $q['path'] = substr($request_uri, $basename_length + 1);
    }
    //rewrite
    elseif($request_uri !== '' && (substr($request_uri, 0, $basename_length) !== $basename || isset($request_uri[$basename_length]))){
        $q['mode'] = 1;
        $q['path'] = $request_uri;
    }
    //normal
    elseif(null !== $key && isset($_GET[$key])){
        $q['mode'] = 2;
        $q['path'] = $_GET[$key];
    }
    //unknown
    else{
        $q['path'] = '';
    }

    $q['basename'] = $basename;

    return $q;
}


/**
 * Route
 * @param string $path
 * @param array $config
 * @return array|boolean
 */
function ark_route($path, $config = null){
    $params = array();
    if($config){
        foreach($config as $pattern => $target){
            $pattern = '#^'.$pattern.'$#';
            if(preg_match($pattern, $path, $match)){
                foreach($match as $k => $v){
                    if(is_string($k)){
                        $params[$k] = $v;
                    }
                }
                if(!is_string($target)){
                    return array(
                        'handler' => $target,
                        'params' => $params,
                    );
                }
                $path = preg_replace($pattern, $target, $path);
                break;
            }
        }
    }
    
    if(!preg_match('#^(?<c>(\w+/)*)(?<a>\w+)?$#', $path, $match)){
        return false;
    }
    return array(
        'controller' => rtrim($match['c'], '/'),
        'action' => $match['a'] === null?'':$match['a'],
        'params' => $params,
    );
}

/**
 * Add a route pattern with callback
 * @param string $pattern
 * @param callable $callback
 */
function ark_match($pattern, $callback){
    Ark::app()->configs['route'][$pattern] = $callback;
}

/**
 * Get handler params with reflection
 * @param  mixed $handler
 * @param  array $params
 * @return array
 */
function ark_handler_params($handler, $params)
{
    $result = array();
    if(is_array($handler)){
        $clazz = get_class($handler[0]);
        $reflect = new ReflectionMethod($clazz, $handler[1]);
    }
    elseif(is_string($handler) && strpos($handler, '::')){
        list($clazz, $func_name) = explode('::', $handler, 2);
        $reflect = new ReflectionMethod($clazz, $func_name);
    }
    else{
        $reflect = new ReflectionFunction($handler);
    }
    foreach($reflect->getParameters() as $param){
        $name = $param->getName();
        $value = null;
        if(isset($params[$name])){
            $value = $params[$name];
        }
        elseif($param->isDefaultValueAvailable()){
            $value = $param->getDefaultValue();
        }
        $result[$name] = $value;
    }

    return $result;
}

/**
 * Get config
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function ark_config($key, $default = null){
    return Ark::app()->config->get($key, $default);
}

function ark_app_url($path = '', $params = null, $absolute = false, $https = null){
    return Ark::app()->appUrl($path, $params, $absolute, $https);
}

function ark_route_url($name, $params = null, $absolute = false, $https = null)
{
    return Ark::app()->routeUrl($name, $params, $absolute, $https);
}

function ark_asset_url($path, $absolute = false, $https = null){
    return Ark::app()->appUrl($path, null, $absolute, $https);
}

function ark_event(){
    call_user_func_array(array(Ark::app()->event, 'attach'), func_get_args());
}

# Autoload functions

function ark_autoload_file($class, $file = null){
    ArkAutoload::registerFile($class, $file);
}

function ark_autoload_dir($dir, $hasChild = true){
    ArkAutoload::registerDir($dir, $hasChild);
}

# io access

function ark_sub_dirs($path){
    if(!$dirh = opendir($path)){
        return false;
    }

    $dirs = array();
    while($file = readdir($dirh) !== false){
        if($file !== '.' && $file !== '..' && is_dir($path.'/'.$file)){
            $dirs[] = $file;
        }
    }

    closedir($dirh);
    return $dirs;
}