<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ArkLoggerBase
{
    function __construct($options = array()) {
        $this->options = $options;
    }

    abstract public function log($message, $level);

    public function warn($message)
    {
        return $this->log($message, 'WARN');
    }
}

class ArkLoggerFile extends ArkLoggerBase
{

}