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
        $logger = new ArkLogger(array(
            array(
                'class' => 'ArkLoggerHandlerEcho',
            ),
            array(
                'class' => 'ArkLoggerHandlerFile',
                'level' => array('debug'),
                'file' => dirname(__FILE__).'/log/debug.log',
            ),
            array(
                'class' => 'ArkLoggerHandlerErrorLog',
                'level' => 'error',
                'delay' => true,
            ),
        ));
        $logger->debug('debug', true);
        $logger->info('info');
        $logger->notice('notice');
        $logger->warning('warning');
        $logger->error('error');
        $logger->critical('critical');
        $logger->alert('alert');
        $logger->emergency('emergency');
    }
}