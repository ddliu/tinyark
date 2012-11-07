<?php
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