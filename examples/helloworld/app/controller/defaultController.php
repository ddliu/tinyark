<?php
class defaultController extends ArkController
{
    public function indexAction(){
        return $this->render('@/index.html.php');
    }

    public function aboutAction()
    {
        return 'hello';
    }

    public function exceptionAction()
    {
        throw new Exception("error");
    }
}