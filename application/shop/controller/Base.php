<?php


namespace app\shop\controller;

use app\common\controller\BaseComm;

class Base extends BaseComm{

	//初始化操作
    public function _initialize()
    {

 		$this->mid = session('uid');//登陆uid
        if(!session('uid')||!session('username')){
            $this->redirect(url('/admin/login/index'));
        }
    }

	public function ajaxReturn($data,$type = 'json'){
            exit(json_encode($data));
    }

}
