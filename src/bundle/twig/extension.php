<?php
class ArkTwigExtension extends Twig_Extension
{
    public function getName()
    {
        return 'ark';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('app_url', 'ark_app_url'),
            new Twig_SimpleFunction('route_url', 'ark_route_url'),
            new Twig_SimpleFunction('asset_url', 'ark_asset_url'),
            new Twig_SimpleFunction('process_time', 'ark_twig_process_time'),
            new Twig_SimpleFunction('config', 'ark_config'),
        );
    }
}

function ark_twig_process_time(){
    return microtime(true) - ARK_MICROTIME;
}