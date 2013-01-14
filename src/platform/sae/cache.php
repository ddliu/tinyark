<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
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