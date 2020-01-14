<?php
namespace app\reg\controller;
use think\Controller;
use app\common\logic\Code;

class User extends Controller
{

   

    //注册用户,返回信息
    public function reg(){
        
       	$first_leader = I('get.first_leader');
       	$this->assign('img_code',Code::getStatus(1));
        $this->assign('first_leader',$first_leader);
        return $this->fetch();
    }

    
}