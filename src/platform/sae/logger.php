<?php
class ArkSaeLogger extends ArkLoggerBase
{
    protected function write($message, $level, $time)
    {
        sae_debug($this->formatMessage($message, $level, $time));
    }
}