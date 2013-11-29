<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class RouterTest extends PHPUnit_Framework_TestCase{

    protected $router;

    public function setup()
    {
        $this->router = new ArkRouter(array(
            array(
                'name' => 'blog_home',
                'path' => 'blog/',
                'handler' => 'handler_blog_home'
            ),
            array(
                'name' => 'blog_post',
                'path' => 'blog/<id:\d+>',
                'handler' => 'handler_blog_post',
            ),
            array(
                'name' => 'blog_archive',
                'path' => 'blog/archive/(<page:\d+>)?',
                'defaults' => array(
                    'page' => 1
                ),
                'handler' => 'handler_blog_archive',
            ),
            array(
                'name' => 'blog_tag',
                'path' => 'blog/tag/<slug>',
                'handler' => 'handler_blog_tag',
            ),
            array(
                'name' => 'cli',
                'path' => 'cache:clear:<bundle:\w+>',
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
        $this->assertEquals($rule['attributes']['id'], '123');

        $rule = $this->router->match(array(
            'path' => 'blog/55abc',
        ));
        $this->assertFalse($rule);

        $rule = $this->router->match(array(
            'path' => 'blog/tag/php',
        ));
        $this->assertEquals($rule['attributes']['slug'], 'php');

        $rule = $this->router->match(array(
            'path' => 'blog/tag/php.html',
        ));
        $this->assertFalse($rule);

        $rule = $this->router->match(array(
            'path' => 'cache:clear:blog'
        ));
        $this->assertEquals($rule['attributes']['bundle'], 'blog');
    }

    public function testGenerate()
    {
        $this->assertFalse($this->router->generate('nonexists'));

        $this->assertEquals($this->router->generate('blog_home'), 'blog/');

        $this->assertEquals($this->router->generate('blog_post', array(
            'id' => 5
        )), 'blog/5');

        $this->assertEquals($this->router->generate('blog_archive', array(
            'page' => 123,
        )), 'blog/archive/123');

        $this->assertEquals($this->router->generate('blog_archive'), 'blog/archive/1');

        $this->assertEquals($this->router->generate('blog_tag', array(
            'slug' => 'php'
        )), 'blog/tag/php');

        $this->assertEquals($this->router->generate('blog_tag', array(
            'slug' => 'php',
            'page' => 3
        )), 'blog/tag/php?page=3');
    }
}
