<?php

// 插件基类控制器
namespace app\home\controller;

use think\addons\Controller;
use think\Request;
/**
 * 扩展控制器
 * 用于调度各个扩展的URL访问需求
 */
class Addons extends Controller {
	protected $addons = null;
	protected $model;
	function _initialize() {
		parent::_initialize ();
		//$token = get_token ();

	}
	public function execute($_addons = null, $_controller = null, $_action = null) {
	}
	public function plugin($_addons = null, $_controller = null, $_action = null) {
	}
	
}
