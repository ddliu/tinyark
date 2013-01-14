<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

class PaginationTest extends PHPUnit_Framework_TestCase{
    public function testMiddle(){
        $p = new ArkPagination(333, 5, 7);
        $this->assertEquals($p->getTotal(), 333);
        
        $this->assertEquals($p->getPage(), 7);
        
        $this->assertEquals($p->getPageSize(), 5);
        
        $this->assertEquals($p->getPageNumber(), 67);
        
        $this->assertEquals($p->hasNext(), true);
        
        $this->assertEquals($p->hasPrev(), true);
        
        $this->assertEquals($p->getOffset(), 30);
        
        $this->assertEquals($p->getLimit(), 5);
    }
    
    public function testFirst(){
        $p = new ArkPagination(333, 5, 1);
        
        $this->assertEquals($p->hasNext(), true);
        
        $this->assertEquals($p->hasPrev(), false);      
    }
    
    public function testLast(){
        $p = new ArkPagination(333, 5, 67);
        
        $this->assertEquals($p->hasNext(), false);
        
        $this->assertEquals($p->hasPrev(), true);       
    }   
}