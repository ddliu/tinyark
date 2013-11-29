<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
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