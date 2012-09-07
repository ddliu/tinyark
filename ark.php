<?php
/**
 * Get service
 * @param string $name Service name
 * @return mixed
 */
function ark($name = null){
	static $container;
	if(null === $container){
		$service_configs = ark_config('services', array());
		$container = new AContainer($service_configs);
		
		//register internal service
		$container->set('event', new AEvent());
	}
	
	//return container if service not specified
	if(null === $name){
		return $container;
	}
	
	return $container->get($name);
}


/**
 * Service container
 */
class AContainer
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

    public function register($name, $value){
        $this->configs[$name] = $value;
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

class AEvent
{
    protected $eventList = array();

    public function bind($event, $callback){
        if(!isset($this->eventList[$event])){
            $this->eventList[$event] = array($callback);
        }
        else{
            $this->eventList[$event][] = $callback;
        }
    }

    public function unbind($event){
        if(isset($this->eventList[$event])){
            unset($this->eventList[$event]);
        }
    }

    public function trigger($event){
        $args = func_get_args();
        array_shift($args);
        if(isset($this->eventList[$event])){
            foreach($this->eventList[$event] as $callback){
                if(false === call_user_func_array($callback, $args)){
                    break;
                }
            }
        }
    }
}

/**
 * Universal Autoloader
 */
class AAutoload
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

    static public function registerFile($class, $file){
        self::$files[$class] = $file;
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
}

#Shortcut functions


/**
 * 404 page
 */
function ark_404(){
	header("HTTP/1.0 404 Not Found");
	$file = APP_DIR.'/404.php';
	if(file_exists($file)){
		require($file);
	}
	
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
 * Dispatch
 * @param array $r
 */
function ark_dispatch($r){//$controller, $action, $params){
	//extract params for named pattern
	if(isset($r['params'])){
		foreach($r['params'] as $k => $v){
			if($_SERVER['REQUEST_METHOD'] == 'POST'){
				$_POST[$k] = $v;
			}
			else{
				$_GET[$k] = $v;
			}
			$_REQUEST[$k] = $v;
		}
	}
	//callback handler
	if(isset($r['handler'])){
		call_user_func($r['handler']);
		return true;
	}

	if($r['controller'] === ''){
		$r['controller'] = 'default';
	}
	if($r['action'] === ''){
		$r['action'] = 'index';
	}

	$controllerFile = APP_DIR.'/source/controller/'.$r['controller'].'Controller.php';
	if(!file_exists($controllerFile)){
		ark('event')->trigger('ark.404');
	}
	else{
		require_once($controllerFile);
		$classname = basename($r['controller']).'Controller';
		$methodName = $r['action'].'Action';
		$o = new $classname;
		if(!method_exists($o, $methodName)){
			ark('event')->trigger('ark.404');
		}
		else{
			call_user_func(array($o, $methodName));
		}
	}
}

/**
 * Add a route pattern with callback
 * @param string $pattern
 * @param callable $callback
 */
function ark_match($pattern, $callback){
	global $ARK_CONFIG;
	$ARK_CONFIG['route'][$pattern] = $callback;
}

/**
 * Get config
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function ark_config($key, $default = null){
	global $ARK_CONFIG;
	return isset($ARK_CONFIG[$key])?$ARK_CONFIG[$key]:$default;
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
	ark('event')->bind($event, $callback);
}

function ark_autoload_class($class, $file = null){
	if(is_array($class)){
		foreach($class as $k => $v){
			AAutoload::registerFile($k, $v);
		}
	}
	else{
		AAutoload::registerFile($class, $file);
	}
}

function ark_autoload_dir($dir, $hasChild = true){
	AAutoload::registerDir($dir, $hasChild);
}
