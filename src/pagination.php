<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * Simple pagination
 */
class ArkPagination
{
    /**
     * Total record number
     * @var int
     */
    protected $total;

    /**
     * Number of records in each page
     * @var int
     */
    protected $pagesize;

    /**
     * Current page
     * @var int
     */
    protected $page;

    /**
     * Total page number
     * @var int
     */
    protected $pageNumber;

    /**
     * Offset of the first record in this page(start from 0)
     * @var int
     */
    protected $offset;

    /**
     * Is page info updated
     * @var boolean
     */
    protected $fresh = true;
    
    protected $url = '?page=';

    /**
     * How does pagination look like
     * @var array
     */
    protected $tpl = array(
        'tpl' => '<div class="pagination">{LIST} {total_tpl}</div>',
        'total_tpl' => '{TOTAL} in total',
        'item_tpl' => '<a href="{LINK}">{PAGE}</a>',
        'current_tpl' => '<em class="current">{PAGE}</em>',
        'next_tpl' => '<a href="{LINK}">&gt;</a>',
        'prev_tpl' => '<a href="{LINK}">&lt;</a>',
        'first_tpl' => '<a href="{LINK}">&lt;&lt;</a>',
        'last_tpl' => '<a href="{LINK}">&gt;&gt;</a>',
        'gap_tpl' => '<span class="gap">...</span>',
    );

    /**
     * Constructor
     * @param int  $total
     * @param integer $pagesize
     * @param integer $page
     */
    public function __construct($total, $pagesize = 10, $page = 1){
        $this->total = $total;
        $this->pageSize = $pagesize;
        $this->page = $page;
    }

    /**
     * Correct and calculate page information
     */
    protected function calculate(){
        $this->pageNumber = ceil($this->total/$this->pageSize);
        if($this->page < 1){
            $this->page = 1;
        }
        elseif($this->page > $this->pageNumber){
            $this->page = $this->pageNumber;
        }
        $this->offset = ($this->page-1)*$this->pageSize;
        $this->limit = $this->pageSize;
    }
    
    public function setUrl($url){
        $this->url = $url;
    }
    
    public function getUrl($page){
        return $this->url.$page;
    }

    /**
     * $total getter
     * @return int
     */
    public function getTotal(){
        $this->checkFresh();
        return $this->total;
    }

    /**
     * $total setter
     * @param int $total
     */
    public function setTotal($total){
        $this->total = $total;
        $this->fresh = true;
    }

    /**
     * $page setter
     * @param int $page
     */
    public function setPage($page){
        $this->page = $page;
        $this->fresh = true;
    }

    /**
     * $page getter
     * @return int
     */
    public function getPage(){
        $this->checkFresh();
        return $this->page;
    }

    /**
     * $pageSize setter
     * @param int $pageSize
     */
    public function setPageSize($pageSize){
        $this->pageSize = $pageSize;
        $this->fresh = true;
    }

    /**
     * $pageSize getter
     * @return int
     */
    public function getPageSize(){
        $this->checkFresh();
        return $this->pageSize;
    }

    /**
     * $pageNumber getter
     * @return int
     */
    public function getPageNumber(){
        $this->checkFresh();
        return $this->pageNumber;
    }

    /**
     * Check if next page exists
     * @return boolean
     */
    public function hasNext(){
        $this->checkFresh();
        return $this->page < $this->pageNumber;
    }

    /**
     * Get next page
     * @return integer
     */
    public function getNext(){
        $this->checkFresh();
        return min($this->page + 1, $this->pageNumber);
    }

    /**
     * Check if previous page exist
     * @return boolean
     */
    public function hasPrev(){
        $this->checkFresh();
        return $this->page > 1;
    }

    /**
     * Get prev page
     * @return integer
     */
    public function getPrev(){
        $this->checkFresh();
        return max($this->page - 1, 1);
    }

    /**
     * $limit getter
     * @return int
     */
    public function getLimit(){
        return $this->getPageSize();
    }

    /**
     * $offset getter
     * @return int
     */
    public function getOffset(){
        $this->checkFresh();
        return $this->offset;
    }

    /**
     * Check if page info updated, run calculate if it's fresh
     */
    protected function checkFresh(){
        if($this->fresh){
            $this->calculate();
            $this->fresh = false;
        }
    }

    public function getPager(){

    }

    public function __toString(){

    }
}