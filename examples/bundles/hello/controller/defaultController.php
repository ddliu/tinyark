<?php
class defaultController extends ArkController
{
    public function indexAction()
    {
        return 'index';
    }

    public function anotherAction()
    {
        return 'another';
    }

    public function productAction($product_name)
    {
        return $product_name;
    }
}