<?php
class ArkViewSmarty implements ArkViewInterface
{
    protected $smarty;

    public function __construct($path, $smarty_options = null, $locator = null)
    {
        $this->smarty = new Smarty();
        if($path){
            $this->smarty->setTemplateDir($path.'/');
        }
        //@todo ...
    }

    public function assign($key, $value = null)
    {
        $this->smarty->assign($key, $value);
    }

    public function render($name, $variables = null, $return = false)
    {
        if(null !== $variables){
            $this->assign($variables);
        }

        if($return){
            return $this->smarty->fetch($name);
        }
        else{
            $this->smarty->display($name);
        }
    }

    public function getSmarty()
    {
        return $this->smarty;
    }
}