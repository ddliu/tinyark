<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'Asia/Shanghai',

	#### sections ####
	
    'bundle' => array(
        'autodiscover' => false,
        'bundles' => array(
            array(
                'path' => APP_PATH.'/../../bundles/hello'
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
    'autoload' => array(
        'class' => array(
            'TinyDB' => APP_PATH.'/vendor/tinydb/tinydb.php',
        ),
        'dir' => array(
            APP_PATH.'/model',
        ),
    ),
    'services' => array(
        'db' => array(
            'class' => 'TinyDB',
            'params' => array(
                'mysql:host=localhost;dbname=sakila',
                'root',
                '1qaz2wsx',
            ),
        ),
    ),
);