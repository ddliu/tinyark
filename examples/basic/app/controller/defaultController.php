<?php
class defaultController extends ArkController
{
	public function indexAction(){
		return $this->render('index.html.php');
	}
	
	public function contactAction(){
		echo 'contact';
	}
	
	public function aboutAction(){
		echo 'about';
	}
	
	public function blogAction(){
		echo 'blog:'.$_GET['blog_id'];
	}	
}