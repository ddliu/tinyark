<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

$bundle_path = dirname(__FILE__).'/..';
return array(
    'autoload' => array(
        'file' => array(
            'ArkViewTwig' => $bundle_path.'/view.php',
            'ArkTwigExtension' => $bundle_path.'/extension.php',
            'ArkTwigFileSystemLoader' => $bundle_path.'/loader.php',
        )
    ),
);