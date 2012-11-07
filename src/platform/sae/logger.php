<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ArkSaeLogger extends ArkLoggerBase
{
    protected function write($message, $level, $time)
    {
        sae_debug($this->formatMessage($message, $level, $time));
    }
}