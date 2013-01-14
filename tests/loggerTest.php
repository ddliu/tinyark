<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

class LoggerTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $logger = new ArkLoggerFile(array(
            'file' => dirname(__FILE__).'/log/file.log',
        ));
        $logger->trace('trace');
        $logger->debug('debug');
        $logger->info('info');
        $logger->warn('warn');
        $logger->error('error');
        $logger->fatal('fatal');
    }
}