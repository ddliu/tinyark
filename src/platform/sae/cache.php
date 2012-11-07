<?php
class ArkSaeCacheMemcache extends ArkCacheMemcache
{
    /**
     * @see http://sae.sina.com.cn/?m=devcenter&catId=201
     */
    protected function connect()
    {   
        $this->memcache = memcache_init();
    }
}