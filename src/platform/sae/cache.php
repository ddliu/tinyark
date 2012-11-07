<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ArkSaeCacheMemcache extends ArkCacheMemcache
{
    /**
     * @see http://sae.sina.com.cn/?m=devcenter&catId=201
     */
    protected function connect()
    {   
        return $this->memcache = memcache_init();
    }
}