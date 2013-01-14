<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

class ArkSaeLogger extends ArkLoggerBase
{
    protected function write($message, $level, $time)
    {
        sae_debug($this->formatMessage($message, $level, $time));
    }
}