<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'Asia/Shanghai',

	#### sections ####
	
    'bundle' => array(
        'autodiscover' => false,
        'bundles' => array(

        ),
    ),
    'route' => array(
    	'rewrite' => true,
        'rules' => array(
            'blog/(?<blog_id>\d+)\-(?<blog_slug>\w+)\.html' => 'default/blog',
            'about\.html' => 'default/about',
            'contact\.html' => 'default/contact',
            //RESTful URL
            '((\w+/)*\w+)\.(?<format>json|xml)' => '\\1',
        )
    ),
    'autoload' => array(
        'class' => array(
            'TinyDB' => APP_DIR.'/vendor/tinydb/tinydb.php',
        ),
        'dir' => array(
            APP_DIR.'/model',
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