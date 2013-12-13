<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
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
     * Json response
     * @param mixed $data
     */
    public function renderJson($data){
        if(!is_string($data)){
            $data = json_encode($data);
        }
        return new ArkResponse($data, 200, array(
            'Content-Type' => 'application/json'
        ));
    }

    /**
     * Redirect
     * @param string $url
     */
    public function redirect($url){
        $response = new ArkResponse('', 302, array('Location' => $url));
        return $response;
    }
    
    /**
     * Check request method
     */
    public function isPost(){
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

class ArkControllerAction extends ArkController
{
}