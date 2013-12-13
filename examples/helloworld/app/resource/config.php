<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'UTC',

    'route' => array(
    	'mode' => 'rewrite',
        'rules' => array(
            array(
                'name' => 'default',
                'path' => '(<_controller:.+>/)?(<_action:[^/]+>)?',
                'defaults' => array(
                    '_controller' => 'default',
                    '_action' => 'index',
                )
            )
        )
    ),
);