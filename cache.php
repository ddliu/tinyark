<?php
abstract class ArkCache
{
	/**
	 * Cache options
	 *  - ttl
	 *  - prefix
	 */
	protected $options;
	
	protected $connected = false;
	
	public function __construct($options = array()){
		$this->options = $options;
	}
	
	protected function getKey($key){
		return (isset($this->options['prefix'])?$this->options['prefix']:'').$key;
	}
	
	protected function getMultiKey($keys){
		$result = array();
		foreach($keys as $key){
			$result[] = $this->options['prefix'].$key;
		}
		
		return $result;
	}
	
	protected function getMultiKV($values){
		$result = array();
		foreach($values as $key => $value){
			$result[$this->options['prefix'].$key] = $value;
		}
		
		return $result;
	}
	
	protected function revertMultiKV($values){
		if(!is_array($values)){
			return $values;
		}
		$result = array();
		$prefix_length = strlen($this->options['prefix']);
		foreach($values as $key => $value){
			$result[substr($key, $prefix_length)] = $value;
		}
		
		return $result;
	}

	/**
	 * TTL to timestamp
	 * @param integer $ttl
	 * @return inteter
	 */
	public function getExpire($ttl){
		if(!$ttl){
			return 0;
		}
		if($ttl < 946656000){
			return time() + $ttl;
		}
		else{
			return $ttl;
		}
	}
	
	/**
	 * TTL to timestamp
	 */
	public function getTTL($expires){
		if(!$expires){
			return 0;
		}
		if($expires < 946656000){
			return $expires;
		}
		else{
			return $expires - time();
		}
	}
	
	/**
	 * Switch connection state
	 * @boolean $connect
	 * @return boolean
	 */
	protected function setConnected($connect = true){
		if($connect){
			if(!$this->connected){
				if(!$this->connect()){
					return false;
				}
				else{
					$this->connected = true;
				}
			}
			
			return true;
		}
		else{
			if($this->connected){
				if(!$this->close()){
					return false;
				}
				else{
					$this->connected = false;
				}
			}
			
			return true;
		}
	}
	
	protected function connect(){
		return true;
	}
	
	protected function close(){
		return true;
	}
	
	public function __destruct(){
		$this->setConnected(false);
	}
	
	abstract public function get($key, $options = null);
	
	abstract public function set($key, $value = null, $ttl = null, $options = null);
	
	abstract public function delete($key);
	
	public function inc($key, $step = 1){
		if(false === $value = $this->get($key)){
			return false;
		}
		$value += $step;
		$this->set($key, $value);
		return $value;
	}
	
	public function dec($key, $step = 1){
		return $this->inc($key, -$step);
	}
	
	public function flush(){
		return false;
	}
}

/**
 * Array cache
 */
class ArkCacheArray extends ArkCache
{
	protected $data = array();
	
	public function get($key, $options = array()){
		if(is_array($key)){
			$result = array();
			foreach($key as $k){
				$result[$k] = isset($this->data[$k])?$this->data[$k]:false;
			}
			
			return $result;
		}
		else{
			return isset($this->data[$key])?$this->data[$key]:false;
		}
	}
	
	public function set($key, $value = null, $ttl = null, $options = null){
		if(is_array($key)){
			foreach($key as $k => $v){
				$this->data[$k] = $v;
			}
		}
		else{
			$this->data[$key] = $value;
		}
		
		return true;
	}
	
	public function delete($key){
		if(isset($this->data[$key])){
			unset($this->data[$key]);
		}
		
		return true;
	}
	
	public function inc($key, $step = 1){
		if(isset($this->data[$key])){
			$this->data[$key] += $step;
			return $this->data[$key];
		}
		else{
			return false;
		}
	}
	
	public function flush(){
		$this->data = array();
	}
}

/**
 * File cache
 * Cache options:
 *  - cache_dir
 * Cache file format:
 * <?php exit;
 * ttl
 * data(serialized)
 */
class ArkCacheFile extends ArkCache
{
	protected function getCacheFile($key){
		return $this->options['cache_dir'].'/'.(isset($this->options['prefix'])?$this->options['prefix']:'').md5($key).'.php';
	}
	
	protected function readFileCache($file){
		if(file_exists($file) && is_readable($file)){
			$content = file_get_contents($file);
			$content = explode("\n", $content, 3);
			$expires = $content[1];
			$content = $content[2];
			if($expires && $expires > time()){
				return false;
			}
			return unserialize($content);
		}
		else{
			return false;
		}
	}
	
	protected function writeFileCache($file, $data, $ttl = null){
		if(null === $ttl && isset($this->options['ttl'])){
			$ttl = $this->options['ttl'];
		}
		
		$expire = $this->getExpire($ttl);
		$content = '<?php exit;?>'."\n".$expire."\n".serialize($data);
		return file_put_contents($file, $content);
	}
	
	public function get($key, $options = null){
		if(is_array($key)){
			$result = array();
			foreach($key as $k){
				$result[$k] = $this->get($k);
			}
			
			return $result;
		}
		else{
			$file = $this->getCacheFile($key);
			return $this->readFileCache($file);
		}
	}
	
	public function set($key, $value = null, $ttl = null, $options = null){
		if(is_array($key)){
			$result = true;
			foreach($key as $k => $v){
				if(!$this->set($k, $v, $ttl, $options)){
					$result = false;
				}
			}
			return $result;
		}
		else{
			$file = $this->getCacheFile($key);
			$dirname = dirname($file);
			//ensure dir exists
			if(!is_dir($dirname)){
				if(!mkdir($dirname, 0777, true)){
					return false;
				}
			}
			
			return $this->writeFileCache($file, $value, $ttl);
		}
	}
	
	public function delete($key){
		$file = $this->getCacheFile($key);
		if(file_exists($file)){
			return unlink($file);
		}
		else{
			return true;
		}
	}
	
	public function flush(){
		$cache_dir = $this->options['cache_dir'];
		if(!$dirh = opendir($cache_dir)){
			return false;
		}
		
		if(isset($this->options['prefix'])){
			$prefix_length = strlen($this->options['prefix']);
		}
		else{
			$prefix_length = 0;
		}
		
		$rst = true;
		while(false !== $file = readdir($dirh)){
			if($file === '.' || $file === '..'){
				continue;
			}
			//cache files with different prefix should be ignored
			if($prefix_length && substr($file, 0, $prefix_length) !== $this->options['prefix']){
				continue;
			}
			
			if(!unlink($cache_dir.'/'.$file)){
				$rst = false;
			}
		}
		closedir($dirh);
		
		return $rst;
	}
}

/**
 * Memcache
 * Cache options:
 *  - host
 *  - port
 *  - timeout
 * @todo multi-server support
 */
class ArkCacheMemcache extends ArkCache
{
	protected $memcache;
	
	protected function connect(){
		if(null === $this->memcache){
			$this->memcache = new Memcache();
		}
		$host = isset($this->options['host'])?$this->options['host']:'127.0.0.1';
		$port = isset($this->options['port'])?$this->options['port']:11211;
		$params = array($host, $port);
		if(isset($this->options['timeout'])){
			$params[] = $this->options['timeout'];
		}
		
		return call_user_func_array(array($this->memcache, 'connect'), $params);
	}
	
	protected function close(){
		return $this->memcache->close();
	}
	
	public function get($key, $options = null){
		if(!$this->setConnected()) return false;
		
		if(is_array($key)){
			if(isset($this->options['prefix'])){
				$keys = $this->getMultiKey($key);
				if(false === $data = $this->memcache->get($keys, $options)){
					return false;
				}
				return $this->revertMultiKV($data);
			}
			else{
				return $this->memcache->get($key, $options);
			}
		}
		else{
			return $this->memcache->get($this->getKey($key), $options);
		}
	}
	
	public function set($key, $value = null, $ttl = null, $options = null){
		if(is_array($key)){
			$rst = true;
			foreach($key as $k => $v){
				if(!$this->set($k, $v, $ttl, $options)){
					$rst = false;
				}
			}
			return $rst;
		}
		else{
			if(!$this->setConnected()) return false;;
			return $this->memcache->set($this->getKey($key), $value, isset($options['compress'])?$options['compress']:0, $this->getExpire($ttl));
		}
	}
	
	public function delete($key){
		if(!$this->setConnected()) return false;
		return $this->memcache->delete($this->getKey($key));
	}
	
	public function inc($key, $step = 1){
		$this->setConnected();
		return $this->memcache->increment($this->getKey($key), $step);
	}
	
	public function dec($key, $step = 1){
		$this->setConnected();
		return $this->memcache->decrement($this->getKey($key), $step);
	}
	
	/**
	 * Flush all existing items at server(note that prefix will be ignored)
	 */
	public function flush(){
		$this->setConnected();
		return $this->memcache->flush();
	}
}

/**
 * APC cache
 */
class ArkCacheAPC extends ArkCache
{
	public function get($key, $options = null){
		if(is_array($key)){
			if(isset($this->options['prefix'])){
				$keys = $this->getMultiKey($key);
				$data = apc_fetch($keys, $success);
				if(!$success){
					return false;
				}
				return $this->revertMultiKV($data);
			}
			else{
				$data = apc_fetch($key, $success);
				if(!$success){
					return false;
				}
				return $data;
			}
		}
		else{
			$data = apc_fetch($this->getKey($key), $success);
			if(!$success){
				return false;
			}
			return $data;
		}
	}
	
	public function set($key, $value = null, $ttl = null, $options = null){
		if(is_array($key)){
			if(isset($this->options['prefix'])){
				$keys = $this->getMultiKV($key);
				$result = apc_store($keys, null, $this->getTTL($ttl));
			}
			else{
				$result = apc_store($key, null, $this->getTTL($ttl));
			}
			
			return !$result;
		}
		else{
			return apc_store($this->getKey($key), $value, $this->getTTL($ttl));
		}
	}
	
	public function delete($key){
		return apc_delete($this->getKey($key));
	}
	
	public function inc($key, $step = 1){
		return apc_inc($this->getKey($key), $step);
	}
	
	public function dec($key, $step = 1){
		return apc_dec($this->getKey($key), $step);
	}
	
	/**
	 * Clears the APC user cache(prefix will be ignored)
	 */
	public function flush(){
		return apc_clear_cache('user');
	}
}