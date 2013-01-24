<?php
$bundle_path = dirname(__FILE__);
return array(
    'autoload' => array(
        'file' => array(
            'ArkViewTwig' => $bundle_path.'/view.php',
            'ArkTwigExtension' => $bundle_path.'/extension.php',
        )
    ),
);