<?php
/*
* @Title	系统入口文档
* @Author	Cheney chen
* @Date		六  6/18 10:30:00 2016
*/
$config = array_merge_recursive(
			require(__DIR__.'/config/main.php'),
			require(__DIR__.'/config/file.php'),
			require(__DIR__.'/config/db.php')
		);
require(__DIR__.'/autoload.php');
require(__DIR__.'/Url.php');
//require(__DIR__.'/base/Img.class.php');

$model = new Url\Url;
return $model->run($config);
