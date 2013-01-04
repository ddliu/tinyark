<?php
class defaultController extends ArkController
{
    public function indexAction(){
        return $this->render('index.html.php');
    }
    
    public function blogAction($blog_id, $blog_slug){
        return 'blog:'.$blog_id.'; slug:'.$blog_slug;
    }
}