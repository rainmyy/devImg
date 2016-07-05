<?php
/*
* @Title
* @Author	Cheney chen
* @Date		六  6/18 12:29:37 2016
*/
namespace	Base;
use			Base\Db;

class Img {
	public $config = [];
	public $_file = [];
	public $md5 = '';
	public $time = '';
	public $db = null;

	public function __construct($config) {
			
		$this->config = $config;
		
		if (isset($_FILES)) {
			$this->_file = $_FILES;
		}
	
		$this->db = new DB($this->config);	
	}	
	
	public function run($func = '', $params = '') {
		
		if ($func == '') {
			print_r('传递参数错误');return;
		}
		
		return	$this->	$func($params);
	}
	
	/**
	* @Title			上传图片
	* @Params	$params 上传图片配置参数
	*/	
	public function create($params) {
		//$stor_file = $this->config['FILE_ROOT'].$this->_file['file']['name'];
		//$file_info = file_put_contents($stor_file, $this->_file['file']);
		$file = $this->getFileInfo();	
		$i = 0;
		
		if (!empty($file)) {
			
			foreach ($file AS $key => $val) {	
				$dir = $this->getFileDir($val);
				
				if ($dir) {
					if (!file_exists($dir) && !is_readable($dir)) {
						
						$file_info = move_uploaded_file($val['tmp_name'], $dir);	
						if ($file_info) {
							//将文件信息存入数据库
							$file_type	= $val['type'];
							$file_type	= explode('/', $file_type);
							$file_type	= $file_type[0];
							$pathinfo	= pathinfo($val['name']);
							$file_name	= $pathinfo['filename'];
							$md5 = md5($file_name.time());
							$file_name	= $md5.'.'.$pathinfo['extension'];
							
							//$list = $db->connect();
							$list = $this->db->add('file_data', ['file_name' => $file_name, 'type' => $file_type, 'md5' => $md5, 'size' => $val['size'], 'modified' => time(), 'story_at' => $this->time, 'created_at' => time()]);
							print_r($list);
							$i ++;
						}				
					}
				}	
			}
		}
		
		if ($i == 0) {
			print_r('文件已经存在');
		} else {
			print_r('存储成功');
		}	
	}	
	
	//通过URL查找文件 http://img.inner.dev.com/img/select?id=77&width=600&height=700	
	public function select($params) {
		if (empty($params) && $params['id'] == '') {
			print_r('参数错误');
		}
		
		$list = $this->db->view('file_data', $params['id']);
		if ($list && $list['type'] == 'image') {
			$file_dir = $this->config['FILE_ROOT'].$list['type'].'/'.$list['story_at'].'/'.$list['file_name'];
			if (file_exists($file_dir)) {
				$target_arr = [
					'width' => $params['width'],
					'height' => $params['height'],
				];

				return $this->imagecropper($file_dir, $target_arr);
			} else {
				print_r('显示错误');
			} 
		} else {
			print_r('显示错误');
		}
	}	
	
	public function imagecropper($file_dir = [], $target_arr = []) {
		$source_arr = getimagesize($file_dir);
		$source_width	= $source_arr[0];
		$source_height	= $source_arr[1];
		$source_ratio	= $source_arr[1]/$source_arr[0];
		$target_ratio	= $target_arr['height']/$target_arr['width'];
		
		if ($source_ratio > $target_ratio) {
			$cropped_width = $source_width;
			$cropped_height = $source_width*$target_ratio;
			$source_x = 0;
			$source_y = ($source_height - $cropped_width)/2;
		} elseif ($source_ratio < $target_ratio) {
			$cropped_width = $source_height/$target_ratio;
			$cropped_height = $source_height;
			$source_x = ($source_width - $cropped_width)/2;
			$source_y = 0;
		} else {
			$cropped_width = $source_width;
			$cropped_height = $source_height;
			$source_x = 0;
			$source_y = 0;
		}
		
		switch ($source_arr['mime']) {
			case 'image/gif':
				$source_image = imagecreatefromgif($file_dir);
			break;
			case 'image/jpeg':
				$source_image = imagecreatefromjpeg($file_dir);
			break;
			case 'image/png':
				$source_image = imagecreatefrompng($file_dir);
			break;
			case 'image/gd':
				$source_image = imagecreatefromgd($file_dir);
			break;
			default:
				$source_image = imagecreatefrompng($file_dir);
		}
		
		$target_image = imagecreatetruecolor($target_arr['width'], $target_arr['height']);	
		$cropped_image = imagecreatetruecolor($cropped_width, $source_height);
		
		imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
		imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_arr['width'], $target_arr['height'], $cropped_width, $cropped_height);
		
		header('Content-Type:'.$source_arr['mime']);
		switch ($source_arr['mime']) {
			case 'image/gif':
				imagegif($target_image);
			break;
			case 'image/jpeg':
				imagejpeg($target_image);
			break;
			case 'image/png':
				imagepng($target_image);
			break;
			case 'image/gd':
				imagegd($target_image);
			break;
			default:
				imagejpeg($target_image);
		}
		imagedestroy($source_image);
		imagedestroy($target_image);
		imagedestroy($cropped_image);
	}	
	
	/**
	* @Tile 生成文件存放的文件夹，两个类型：application:可执行文件, image:图片
	*/
	public function getFileDir($file_arr = []) {
		$date		= date('Ymd', time());	
		$this->time		= strtotime($date);

		$stor_file	= $this->config['FILE_ROOT'];
		$file_type	= $file_arr['type'];
		$file_type	= explode('/', $file_type);
		$file_type	= $file_type[0];
		$stor_file	= $stor_file.$file_type.'/'.$this->time;
		$pathinfo	= pathinfo($file_arr['name']);
		$file_name	= $pathinfo['filename'];
		$md5 = md5($file_name.time());
		$file_name	= $md5.'.'.$pathinfo['extension']; 
		
		if (!is_dir($stor_file) || !is_readable($stor_file)) {
			
			$oldmask = @umask(0);
			$result  = @mkdir($stor_file, 0755, TRUE);
			@umask($oldmask);
			
			if (!$result) {
				
				return FALSE;
			}
		}   
			
		return $stor_file.'/'.$file_name;	
		
	}	
	
	public function getFileInfo() {
		$file_info = $this->_file;
		$tmp_arr = [];
		$i = 0;
		
		foreach ($file_info AS $key => $val) {
			
			if (!empty($val)) {
				
				foreach ($val AS $ke => $va) {
					
					if (is_array($va)){ 
						
						foreach ($va AS $k => $v) {
							
							if ($val['error'][$k] == 0) {
								
								$tmp_arr[$k][$ke] = $v;
							}
						}
					} else {
						
						if ($val['error'] == 0) {
							
							$tmp_arr[$i+1][$ke] = $va;
						}
					}
				}
			}
			
			$i ++;
		}		
		
		return $tmp_arr;
	}
}
