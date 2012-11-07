<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ArkBaeCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        require_once 'BaeMemcache.class.php';
        $this->memcache = new BaeMemcache();
        return true;
    }

    protected function close()
    {
        return true;
    }
}