<?php
class ArkRequest
{
    public function getIP()
    {

    }

    public function isSecure(){}

    public function getParam()
    {

    }

    public function getQuery($name, $default = null)
    {
        return isset($_GET[$name])?$_GET[$name]:$default;
    }

    public function getPost($name, $default = null)
    {
        return isset($_POST[$name])?$_POST[$name]:$default;
    }


}