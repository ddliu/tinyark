<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * Cache base class
 */
abstract class ArkCacheBase
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
    
    /**
     * Key with prefix
     * @param string $key
     * @return string
     */
    protected function getKey($key){
        return (isset($this->options['prefix'])?$this->options['prefix']:'').$key;
    }
    
    /**
     * Multi-key with prefix
     * @param array $keys
     * @return array
     */
    protected function getMultiKey($keys){
        $result = array();
        foreach($keys as $key){
            $result[] = $this->options['prefix'].$key;
        }
        
        return $result;
    }
    
    /**
     * Add prefix to keys of values
     * @param array $values
     * @return array
     */
    protected function getMultiKV($values){
        $result = array();
        foreach($values as $key => $value){
            $result[$this->options['prefix'].$key] = $value;
        }
        
        return $result;
    }
    
    /**
     * Remove prefix from keys of values
     * @param array $values
     * @return array
     */
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
     * Timestamp to TTL
     * @param integer $expires
     * @return integer
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
    
    /**
     * Connect to the cache service
     * @return boolean
     */
    protected function connect(){
        return true;
    }
    
    /**
     * Close connection to the cache service
     * @return boolean
     */
    protected function close(){
        return true;
    }
    
    public function __destruct(){
        //disconnect when destruct
        $this->setConnected(false);
    }
    
    /**
     * Get cache(multi-key supported)
     * @param string|array $key
     * @param mixed $options
     * @return mixed
     */
    abstract public function get($key, $options = null);
    
    /**
     * Store cache data(multi-key supported)
     * @param string|array $key
     * @param mixed $value
     * @param integer $ttl
     * @param mixed $options
     * @return mixed
     */
    abstract public function set($key, $value = null, $ttl = null, $options = null);
    
    /**
     * Remove cached data
     * @param string $key
     * @return boolean
     */
    abstract public function delete($key);
    
    /**
     * Increment
     * @param string $key
     * @param integer $step
     * @return mixed
     */
    public function inc($key, $step = 1){
        if(false === $value = $this->get($key)){
            return false;
        }
        $value += $step;
        $this->set($key, $value);
        return $value;
    }
    
    /**
     * Decrement
     * @param string $key
     * @param integer $step
     * @return mixed
     */
    public function dec($key, $step = 1){
        return $this->inc($key, -$step);
    }
    
    /**
     * Flush all cached data
     */
    public function flush(){
        return false;
    }
}

/**
 * Array cache
 */
class ArkCacheArray extends ArkCacheBase
{
    protected $data = array();
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    public function delete($key){
        if(isset($this->data[$key])){
            unset($this->data[$key]);
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function inc($key, $step = 1){
        if(isset($this->data[$key])){
            $this->data[$key] += $step;
            return $this->data[$key];
        }
        else{
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
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
class ArkCacheFile extends ArkCacheBase
{
    /**
     * Get file path of the cache key
     * @param string $key
     * @return string
     */
    protected function getCacheFile($key){
        return $this->options['cache_dir'].'/'.(isset($this->options['prefix'])?$this->options['prefix']:'').md5($key).'.php';
    }
    
    /**
     * Get cached data from file
     * @param string $file
     * @return mixed
     */
    protected function readFileCache($file){
        if(file_exists($file) && is_readable($file)){
            $content = file_get_contents($file);
            $content = explode("\n", $content, 3);
            $expires = $content[1];
            $content = $content[2];
            if($expires && $expires < time()){
                return false;
            }
            return unserialize($content);
        }
        else{
            return false;
        }
    }
    
    /**
     * Write data to file
     * @param string $file
     * @param mixed $data
     * @param integer $ttl
     * @param boolean
     */
    protected function writeFileCache($file, $data, $ttl = null){
        if(null === $ttl && isset($this->options['ttl'])){
            $ttl = $this->options['ttl'];
        }
        
        $expire = $this->getExpire($ttl);
        $content = '<?php exit;?>'."\n".$expire."\n".serialize($data);
        return file_put_contents($file, $content);
    }
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    public function delete($key){
        $file = $this->getCacheFile($key);
        if(file_exists($file)){
            return unlink($file);
        }
        else{
            return true;
        }
    }
    
    /**
     * {@inheritdoc}
     */
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
class ArkCacheMemcache extends ArkCacheBase
{
    /**
     * @var Memcache
     * The memcache instance
     */
    protected $memcache;
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    protected function close(){
        return $this->memcache->close();
    }
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
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
            return $this->memcache->set($this->getKey($key), $value, (isset($options['compress']) && $options['compress'])?MEMCACHE_COMPRESSED:0, $this->getExpire($ttl));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key){
        if(!$this->setConnected()) return false;
        return $this->memcache->delete($this->getKey($key));
    }
    
    /**
     * {@inheritdoc}
     */
    public function inc($key, $step = 1){
        $this->setConnected();
        return $this->memcache->increment($this->getKey($key), $step);
    }
    
    /**
     * {@inheritdoc}
     */
    public function dec($key, $step = 1){
        $this->setConnected();
        return $this->memcache->decrement($this->getKey($key), $step);
    }
    
    /**
     * {@inheritdoc}
     */
    public function flush(){
        $this->setConnected();
        return $this->memcache->flush();
    }
}

/**
 * APC cache
 */
class ArkCacheAPC extends ArkCacheBase
{
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    public function delete($key){
        return apc_delete($this->getKey($key));
    }
    
    /**
     * {@inheritdoc}
     */
    public function inc($key, $step = 1){
        return apc_inc($this->getKey($key), $step);
    }
    
    /**
     * {@inheritdoc}
     */
    public function dec($key, $step = 1){
        return apc_dec($this->getKey($key), $step);
    }
    
    /**
     * {@inheritdoc}
     */
    public function flush(){
        return apc_clear_cache('user');
    }
}