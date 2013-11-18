<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @license MIT License
 */

class BundleTest extends PHPUnit_Framework_TestCase{

    protected $bundle;

    public function setup()
    {
        require_once(dirname(__FILE__).'/bundle/bundle.php');
        $this->bundle = new HelloBundle();
    }

    public function testCommon()
    {
        //name
        $this->assertEquals($this->bundle->getName(), 'hello');

        //path
        $this->assertEquals($this->bundle->getPath(), dirname(__FILE__).'/bundle');

        //config
        $this->assertEquals($this->bundle->config->get('settings.key1'), 'value1');
    }
}