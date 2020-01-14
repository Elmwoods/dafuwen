<?php

$config = [
	'url_convert' => false,  //关闭URL自动转换（支持驼峰访问控制器）
    //模板参数替换
    'view_replace_str' => array(
    	
    	'__SITE_URL__' => SITE_URL,
    	'__STATIC__' => '/static',
        '__CSS__' => '/static/admin/css',
        '__JS__'  => '/static/admin/js',
        '__IMG__' => '/static/admin/images',
        '__SHOP_URL__' => '/static/shop',
        '__SHOP_CSS__' => '/static/shop/css',
        '__SHOP_JS__'  => '/static/shop/js',
        '__SHOP_IMG__' => '/static/shop/images',
		//'__SHOP_SITE_URL__' => '/shop',		
    ),
	

];

$shop_config = include_once APP_PATH.'/shop_config.php';
return array_merge($config,$shop_config);
