<?php
return array(
    'charset' => 'utf-8',
    'timezone' => 'Asia/Shanghai',

	#### sections ####
	
    'bundle' => array(
        'autodiscover' => false,
        'bundles' => array(
            array(
                'path' => APP_DIR.'/../../bundles/hello',
            )
        ),
    ),
    'route' => array(
    	'rewrite' => true,
    ),
);