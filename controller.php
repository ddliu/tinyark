<?php
/**
 * 页面
 * 模板/请求/返回等页面相关
 * @author dong
 */
class AController{
	public function __construct(){
		$this->init();
	}
	
	protected function init(){
	}
	
	public function assign($key, $value = null){
		ark('view')->assign($key, $value);
	}
	
	public function render($name, $variables = null, $return = false){
		return ark('view')->render($name, $variables, $return);
	}
	
	/**
	 * json返回页面请求结果
	 * @param int $errcode
	 * 	系统errcode表：
	 * 	- 0: ok
	 * 	- 400: bad request
	 * 		- 40001: param error
	 * 	- 401: not authorized
	 * 	- 403: forbidden
	 * 	- 500: internal server error
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
	 * 输出json数据
	 * @param mixed $data
	 * @param boolean $formated 数据是否已经使用json_encode格式化
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
	 * 转向
	 * @param string $url
	 */
	public function redirect($url){
		header('location:'.$url);
		exit;
	}
	
	public function forward($r, $params){
	}
	
	/**
	 * 请求方式是否POST
	 */
	public function isPost(){
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
}
