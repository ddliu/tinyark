<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class ViewTest extends PHPUnit_Framework_TestCase{
    protected function getView()
    {
        return new ArkViewPHP(array(
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
        $view = $this->getView();

        // assign global
        $view->assignGlobal('name', 'global');
        $text = $view->render('assign.html.php', null, true);
        $this->assertEquals($text, 'Hello global');

        $text = $view->render('assign.html.php', array('name' => 'World'), true);
        $this->assertEquals($text, 'Hello World');
    }
}