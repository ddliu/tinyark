<?php
/**
 * Tinyark Framework
 *
 * @copyright Copyright 2012-2013, Dong <ddliuhb@gmail.com>
 * @link http://maxmars.net/projects/tinyark Tinyark project
 * @license MIT License (http://maxmars.net/license/MIT)
 */

/**
 * Controller
 */
class ArkController{
    protected $request;
    
    public function __construct(){
        $this->request = Ark::app()->getRequest();
        $this->init();
    }
    
    protected function init(){
    }
    
    public function assign($key, $value = null){
        ark('view')->assign($key, $value);
    }
    
    public function render($name, $variables = null, $statusCode = 200){
        $basename = basename($name);
        $parts = explode('.', $basename);
        $parts_count = count($parts);
        $format = null;
        if($parts_count > 1){
            if($parts[$parts_count - 1] == 'php'){
                if($parts_count > 2){
                    $format = strtolower($parts[$parts_count - 2]);
                }
            }
            else{
                $format = strtolower($parts[$parts_count - 1]);
            }
        }

        $response = new ArkResponse(ark('view')->render($name, $variables, true), $statusCode);
        $response->setCharset(ark_config('charset', 'UTF-8'));
        if($format && $format != 'html' && $format != 'htm' && $content_type = ArkMimetype::getMimeTypeByFileExt($format)){
            $response->header('Content-Type', $content_type);
        }
        return $response;
    }
    
    /**
     * Output json data
     * @param mixed $data
     */
    public function renderJson($data){
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect
     * @param string $url
     */
    public function redirect($url){
        header('location:'.$url);
        exit;
    }
    
    /**
     * Check request method
     */
    public function isPost(){
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
