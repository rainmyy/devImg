<?php
/*
* @Title	自动加载类
* @Author	Cheney chen
* @E-mail	czh419779202@163.com
* @Date		六  6/18 12:06:36 2016
*/

class autoload {
	static	$array_file = [];
	static	$match_file = [];
	static	$hostdir	= '';	
	
	public static function init() {
	
		$hostdir = __DIR__.'/base/';
		$handle = opendir($hostdir);
		
		self::$hostdir = $hostdir;
		
		while (false !== ($file = readdir($handle))) {
			$file_info = pathinfo($file);	
			
			if($file !='.' && $file != ".." && ($file_info['extension'] == 'php' || $file_info['extension'] == 'PHP')) {
				self::$array_file[] = $file;
			}
		}

		closedir($handle);	
		return self::inculdeFile();
	}	
	
	/**
	* @Title	获取可以自动加载的文件
	* @creator	Cheney chen 
	*/
	public static function getFile () {
		$matchFile = [];
		$array_file = self::$array_file;
		foreach ($array_file AS $key => $val) {
			if (preg_match('/(.*)\.class\.php/', $val, $matches)) {
				self::$match_file[] = $matches[1];		
			}
		}
	}
	
	public static function inculdeFile () {
		self::getFile();	
		$i = 0;
			
		$match_file = self::$match_file;
		
		foreach ($match_file AS $key => $val) {
			$file = self::$hostdir.$val.'.class.php';
			if (file_exists($file)) {
				include $file;
			}
		}
		
	}	
}

autoload::init();
