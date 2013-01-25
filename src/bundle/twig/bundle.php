<?php
class TwigBundle extends ArkBundle
{
    public function init()
    {
        parent::init();
        $this->app->getContainer()->register('view', array($this, 'getTwigView'));
    }

    public function getTwigView()
    {
        $view = new ArkViewTwig(
            $this->app->getResourcePath().'/view', 
            $this->config->get('twig_options', array()),
            array($this->app, 'locateView')
        );
        $view->getTwig()->addExtension(new ArkTwigExtension());

        return $view;
    }
}