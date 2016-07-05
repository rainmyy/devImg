<?php
/*
* @Title	路由类
* @Author	Cheney chen
* @Date		六  6/18 13:16:18 2016
*/
namespace Url;
use Base;

class Url {
	public $uri = '';

	public function __construct() {
		
		$this->uri = $_SERVER['REQUEST_URI'];		
	}
	
	public function run($config) {
		$class_name = $params =  '';
		$params_arr = [];

		if (preg_match('/\/(.*)/',  $_SERVER['REQUEST_URI'], $match1)) {
			$class_name = $match1[1];
			$class_name = explode('/', $class_name);
			$matchs  = $class_name[1];
			$class_name = $class_name[0];
			
			if(preg_match('/(.*)\?/', $matchs, $func_name)) {
				$func_name = $func_name[1];
			} else {
				$func_name = $matchs;	
			}		
			
			if(preg_match('/\?(.*)/',  $matchs, $match2)) {
				$params = $match2[1];
				$params = explode('&', $params);
			
				foreach ($params AS $key => $val) {
				
					$val_arr = explode('=', $val);
					$params_arr[$val_arr[0]] = $val_arr[1];	
				}
			}
		}
		
		$file_name = __DIR__.'/base/'.$class_name.'.class.php';
		
		if ($class_name != '' && file_exists($file_name)) {
			$class_name = ucfirst($class_name);		
			$model_space = "Base\\".$class_name;
			
			$model = new $model_space($config);
			return $model->run($func_name, $params_arr);
		} else {
			print_r('文件不存在');
		}
	}	
}
