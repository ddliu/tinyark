<?php
return array(
    'route' => array(
        'prefix' => 'hello/',
        'rules' => array(
            array(
                'name' => 'hello_home',
                'path' => '',
                'handler' => 'default/index'
            ),
            array(
                'name' => 'hello_common',
                'path' => '(<_controller:\w+>/(<_action:\w+>)?)?',
            ),
            array(
                'name' => 'hello_product',
                'path' => 'product/<product_name:\w+>\.html',
                'handler' => 'default/product',
            ),
        )
    )
);