<?php

$config = [
	'url_convert' => false,  //关闭URL自动转换（支持驼峰访问控制器）
];


$shop_config = include_once 'html.php';
return array_merge($config,$shop_config);