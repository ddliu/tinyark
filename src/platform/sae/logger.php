<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class ArkSaeLogger extends ArkLoggerBase
{
    protected function write($message, $level, $time)
    {
        sae_debug($this->formatMessage($message, $level, $time));
    }
}