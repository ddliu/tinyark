<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
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