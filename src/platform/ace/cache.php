<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

class ArkAceCacheMemcache extends ArkCacheMemcache
{
    protected function connect()
    {
        $this->memcache = new Memcache();
        return $this->memcache->init();
    }
}