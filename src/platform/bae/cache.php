<?php
class ArkBaeCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        require_once 'BaeMemcache.class.php';
        $this->memcache = new BaeMemcache();
    }

    protected function close()
    {
        return true;
    }
}