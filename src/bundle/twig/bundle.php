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
        $view = new ArkViewTwig($this->app->getAppPath().'/view', $this->config->get('twig_options', array()));
        $view->getTwig()->addExtension(new ArkTwigExtension());

        return $view;
    }
}