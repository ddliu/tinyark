<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */
class ViewTest extends PHPUnit_Framework_TestCase{
    protected function getView()
    {
        return new ArkView(array(
            'dir' => dirname(__FILE__).'/view'
        ));
    }

    public function testSingle()
    {
        $level = ob_get_level();
        $text = $this->getView()->render('layout.html.php', null, true);
        $this->assertRegExp('/#layout_header\s*#layout_content\s*#layout_footer/', $text);
        $this->assertEquals(ob_get_level(), $level);
    }

    public function testInherit()
    {
        $level = ob_get_level();
        $text = $this->getView()->render('index.html.php', null, true);
        $this->assertRegExp('/#layout_header\s*#index_content\s*#layout_footer/', $text);
        $this->assertEquals(ob_get_level(), $level);
    }

    public function testAssign()
    {
        $text = $this->getView()->render('assign.html.php', array('name' => 'World'), true);
        $this->assertEquals($text, 'Hello World');
    }
}