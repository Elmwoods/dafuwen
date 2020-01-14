<?php

return [
	//'url_convert' => false,  //关闭URL自动转换（支持驼峰访问控制器）
    //模板参数替换
    'view_replace_str' => array(
    	
    	'__SITE_URL__' => SITE_URL,
    	'__STATIC__' => '/static',
        '__CSS__' => '/static/admin/css',
        '__JS__'  => '/static/admin/js',
        '__IMG__' => '/static/admin/images',		
    ),

];
