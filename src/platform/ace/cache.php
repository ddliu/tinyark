<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ArkAceCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        $this->memcache = new Memcache();
        return $this->memcache->init();
    }
}