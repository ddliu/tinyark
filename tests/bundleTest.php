<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
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