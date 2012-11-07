<?php
class ArkAceCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        $this->memcache = new Memcache();
        return $this->memcache->init();
    }
}