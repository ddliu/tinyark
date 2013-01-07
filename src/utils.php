<?php
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

/**
 * 404 page
 */
function ark_404(){
    header("HTTP/1.0 404 Not Found");
    Ark::renderInternal('404.html.php');    
    exit;
}

function ark_parse_query_path(){
    static $q;
    if(null === $q){
        $q = array();
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_name_length = strlen($script_name);

        $request_uri = $_SERVER['REQUEST_URI'];

        $slash_pos = strrpos($script_name, '/');
        $base = substr($script_name, 0, $slash_pos);
        $q['base'] = $base;

        //is script name in uri?
        if(substr($request_uri, 0, $script_name_length) == $script_name){
            if(
                !isset($request_uri[$script_name_length]) 
                || 
                in_array($request_uri[$script_name_length], array('/', '?'))
            ){
                $request_basename = basename($script_name);
            }
        }
        else{
            $request_basename = null;
        }

        $urlinfo = parse_url($request_uri);
        
        if(null === $request_basename && !isset($_GET['r'])){
            $info = substr($urlinfo['path'], $slash_pos + 1);
        }
        else{
            $info = isset($_GET['r'])?$_GET['r']:'';
        }

        $q['path'] = $info;
    }
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
    if(strpos($handler, '::')){
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
        elseif($param->isDefaltValueAvailable()){
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
    return isset(Ark::app()->configs[$key])?Ark::app()->configs[$key]:$default;
}

/**
 * Generate url
 * @param string $path
 * @param mixed $params
 * @return string
 */
function ark_url($path = '', $params = null){
    $url = APP_URL;
    $rewrite = ark_config('rewrite', true);
    if($path !== ''){
        if($rewrite){
            $url.=$path;
        }
        else{
            $url.='?r='.$path;
        }
    }
    if(null !== $params){
        if(is_array($params)){
            $params = http_build_query($params);
        }
        if($rewrite){
            $url.='?'.$params;
        }
        else{
            $url.='&'.$params;
        }
    }
    
    return $url;
}

function ark_assets($path){
    return APP_URL.$path;
}

function ark_event($event, $callback){
    Ark::app()->getContainer()->get('event')->bind($event, $callback);
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