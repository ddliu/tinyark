<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class ArkAceCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        $this->memcache = new Memcache();
        return $this->memcache->init();
    }
}