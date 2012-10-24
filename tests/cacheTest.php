<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */
class CacheTest extends PHPUnit_Framework_TestCase{
    protected $db;
    
    public function backendTest($cache, $options = array()){
        //set
        $result = $cache->set('key1', 'value1');
        $this->assertEquals($result, true);
        
        //get
        $result = $cache->get('key1');
        $this->assertEquals($result, 'value1');
        
        //delete
        $result = $cache->delete('key1');
        $this->assertEquals($result, true);
        
        $result = $cache->get('key1');
        $this->assertEquals($result, false);
        
        $multi = array(
            'key2' => 'value2',
            'key3' => 'value3',
        );
        
        //multi-set
        $result = $cache->set($multi);
        $this->assertEquals($result, true);
        
        //multi-get
        $result = $cache->get(array_keys($multi));
        $this->assertEquals($result, $multi);
        
        $result = $cache->set('key4', 100);
        
        //inc
        $result = $cache->inc('key4', 3);
        $this->assertEquals($result, 103);
        
        //dec
        $result = $cache->dec('key4', 33);
        $this->assertEquals($result, 70);

        if(isset($options['timeout'])){
            //timeout
            $result = $cache->set('key5', 'value5', 1);
            $this->assertEquals($result, true);

            $result = $cache->get('key5');
            $this->assertEquals($result, 'value5');

            sleep(2);
            $result = $cache->get('key5');
            $this->assertEquals($result, false);
        }
    }
    
    /**
     * Array cache
     */
    public function testArrayCache(){
        $cache = new ArkCacheArray(array(
            'prefix' => 'ark_',
        ));
        
        $this->backendTest($cache);
    }
    
    /**
     * File cache
     */
    public function testFileCache(){
        $cache = new ArkCacheFile(array(
            'prefix' => 'ark_',
            'cache_dir' => dirname(__FILE__).'/cache',
        ));
        
        $this->backendTest($cache, array('timeout' => true));
    }
    
    /**
     * APC
     * apc.enable_cli should be enabled
     */
    public function testAPCCache(){
        $cache = new ArkCacheAPC(array(
            'prefix' => 'ark_',
        ));
        
        $this->backendTest($cache);
    }
    
    /**
     * Memcache
     * localhost:11211
     */
    public function testMemcacheCache(){
        $cache = new ArkCacheMemcache(array(
            'prefix' => 'ark_',
        ));
        
        $this->backendTest($cache, array('timeout' => true));
    }
}