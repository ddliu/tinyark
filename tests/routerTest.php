<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class RouterTest extends PHPUnit_Framework_TestCase{

    protected $router;

    public function setup()
    {
        $this->router = new ArkRouter(array(
            array(
                'name' => 'blog_home',
                'path' => 'blog/',
                'target' => 'target_blog_home'
            ),
            array(
                'name' => 'blog_post',
                'path' => 'blog/<id:\d+>',
                'target' => 'target_blog_post',
            ),
            array(
                'name' => 'blog_archive',
                'path' => 'blog/archive/(<page:\d+>)?',
                'defaults' => array(
                    'page' => 1
                ),
                'target' => 'target_blog_archive',
            ),
            array(
                'name' => 'blog_tag',
                'path' => 'blog/tag/<slug>',
                'target' => 'target_blog_tag',
            )
        ));
    }

    public function testMatch()
    {
        $rule = $this->router->match(array(
            'method' => 'GET',
            'path' => 'nonexists/',
        ));

        $this->assertFalse($rule);

        $rule = $this->router->match(array(
            'method' => 'GET',
            'path' => 'blog/',
        ));

        $this->assertEquals($rule['name'], 'blog_home');

        $rule = $this->router->match(array(
            'method' => 'GET',
            'path' => 'blog/123',
        ));

        $this->assertEquals($rule['name'], 'blog_post');
        $this->assertEquals($rule['attrubutes']['id'], '123');

        $rule = $this->router->match(array(
            'path' => 'blog/55abc',
        ));
        $this->assertFalse($rule);

        $rule = $this->router->match(array(
            'path' => 'blog/tag/php',
        ));
        $this->assertEquals($rule['attrubutes']['slug'], 'php');

        $rule = $this->router->match(array(
            'path' => 'blog/tag/php.html',
        ));
        $this->assertFalse($rule);
    }

    public function testGenerate()
    {
        $this->assertFalse($this->router->getPath('nonexists'));

        $this->assertEquals($this->router->getPath('blog_home'), 'blog/');

        $this->assertEquals($this->router->getPath('blog_post', array(
            'id' => 5
        )), 'blog/5');

        $this->assertEquals($this->router->getPath('blog_archive', array(
            'page' => 123,
        )), 'blog/archive/123');

        $this->assertEquals($this->router->getPath('blog_archive'), 'blog/archive/1');

        $this->assertEquals($this->router->getPath('blog_tag', array(
            'slug' => 'php'
        )), 'blog/tag/php');
    }
}
