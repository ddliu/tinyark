<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
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