<?php


namespace app\weixin\controller;

use app\common\controller\BaseComm;

class Base extends BaseComm{

	//初始化操作
    public function _initialize()
    {
 		$this->mid = session('uid');//登陆uid
    }

} 