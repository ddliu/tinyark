<?php

class ArkViewTwig implements ArkViewInterface
{
    protected $twig;

    public function __construct($path, $twig_options = null)
    {
        $loader = new Twig_Loader_Filesystem($path);
        $this->twig = new Twig_Environment($loader, $twig_options);
    }

    public function assign($key, $value = null)
    {
        if(is_array($key)){
            $this->twig->mergeGlobals($key);
        }
        else{
            $this->twig->addGlobal($key, $value);
        }
    }

    public function render($name, $variables = null, $return = false)
    {
        if($return){
            return $this->twig->render($name, $variables?$variables:array());
        }
        else{
            $this->twig->display($name, $variables?$variables:array());
        }
    }

    public function getTwig()
    {
        return $this->twig;
    }
}