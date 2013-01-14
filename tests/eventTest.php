<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

class EventTest extends PHPUnit_Framework_TestCase{

    protected $eventmanager;

    public function setup()
    {
        $this->eventmanager = new ArkEventManager();
    }

    public function resultHandler1($event)
    {
        $event->result .= 'a';
    }

    public function resultHandler2($event)
    {
        $event->result .= 'b';
    }

    public function testCommon()
    {
        $this->eventmanager
            ->attach('event1', array($this, 'resultHandler1'))
            ->attach('event1', array($this, 'resultHandler2'));

        $this->eventmanager->dispatch($event = new ArkEvent('event1'));

        $this->assertEquals($event->result, 'ab');
    }
}