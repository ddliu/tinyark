<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */
require dirname(__FILE__).'/../pagination.php';

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