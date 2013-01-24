<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'Asia/Shanghai',

	#### sections ####
	'bundle' => array(
        'bundles' => array(
            array(
                'name' => 'twig',
                'configs' => array(
                    'twig_options' => array(
                        'cache' => APP_PATH.'/data/cache/twig',
                    )
                )
            )
        )
    ),
    'route' => array(
    	'mode' => 'pathinfo',
        //'route_var' => 'r',
        'rules' => array(
            array(
                'name' => 'home',
                'path' => '',
                'handler' => 'default/index',
            ),
            array(
                'name' => 'blog_slug',
                'path' => 'blog/<blog_id:\d+>-<blog_slug:\w+>\.html',
                'handler' => 'default/blog',
            ),
            'about\.html' => 'default/about',
            'contact\.html' => 'default/contact',
        )
    ),
    'autoload' => array(
        'prefix' => array(
            'Twig' => '/home/dong/lib/Twig/lib',
        )
    ),
);