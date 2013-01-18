<?php
return array(
    'bundle' => array(
        'bundles' => array(
            'comment' => array(
                'route_prefix' => ''
            )
        ),
    ),
    'route' => array(
        'prefix' => 'hello/',
        'defaults' => array(
        ),

        'rules' => array(
            'hello_home' => '<:bundle>',
            'hello_page' => '',
            'comments/.*' => '<comment>',
        )
    )
);