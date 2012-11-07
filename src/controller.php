<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

/**
 * Controller
 */
class ArkController{
    protected $request;
    
    public function __construct(){
        $this->request = ark('request');
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
     * Send data with json result
     * @param int $errcode
     *  - 0: ok
     *  - 400: bad request
     *      - 40001: param error
     *  - 401: not authorized
     *  - 403: forbidden
     *  - 500: internal server error
     * @param string $message
     * @author dong
     */
    public function result($errcode=0,$message=null,$data=null){
        header('Content-type: application/json');
        echo json_encode(array(
            'code'=>$errcode,
            'msg'=>$message,
            'data'=>$data,
        ));
        exit;
    }
    
    /**
     * Output json data
     * @param mixed $data
     * @param boolean $formated is data formated
     */
    public function renderJson($data, $formated = true){
        header('Content-type: application/json');
        if(is_string($data) && $formated){
            echo $data;
        }
        else{
            echo json_encode($data);
        }
        exit;
    }
    
    /**
     * Redirect
     * @param string $url
     */
    public function redirect($url){
        header('location:'.$url);
        exit;
    }
    
    public function forward($r, $params){
    }
    
    /**
     * Check request method
     */
    public function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }
}
