<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'Asia/Shanghai',

	#### sections ####
	
    'bundle' => array(
        'autodiscover' => false,
        'bundles' => array(
            array(
                'path' => APP_PATH.'/../../bundles/hello',
                'configs' => array(
                    'route' => array(
                        'prefix' => 'hello-bundle/',
                    )
                )
            ),
        ),
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
);