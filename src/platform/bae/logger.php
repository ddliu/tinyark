<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

require_once "BaeLog.class.php";
class ArkBaeLogger extends ArkLoggerBase
{
    protected function getBaeLogLevel($level)
    {
        static $log_levels = array(
            'trace' => 8,
            'debug' => 16,
            'info' => 4,
            'warn' => 2,
            'error' => 1,
            'fatal' => 1,
        );

        return $log_levels[$level];
    }

    protected function write($message, $level, $time)
    {
        BaeLog::getInstance()->logWrite($this->getBaeLogLevel($level), $message);
    }
}