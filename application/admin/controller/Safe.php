<?php

namespace app\admin\controller;
use app\admin\model\ConfigModel;
use think\Db;

class Safe extends Base
{
    public function index(){
        $code = Db::name('config')->where(['group'=>22])->select();
        $this->assign('code',$code);
        return $this->fetch();
    }
}