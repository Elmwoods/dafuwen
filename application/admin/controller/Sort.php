<?php

namespace app\admin\controller;
use think\Db;

class Sort extends Base
{
    /**
     * 排位报表
     */
    public function index(){
        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $count = Db::name('users')->where('sort','neq',0)->where('user_type','neq',9)->count();  //统计数量
        $list = Db::name('users')->where('sort','neq',0)->where('user_type','neq',9)->order('sort asc')->paginate($pagesize,false,array('query' => array_splice($param,1)));
        $this->assign('list',$list);
        $this->assign('count',$count);
        $this->assign("page", $list->render());
        return $this->fetch();
    }

    /**
     * 出局报表
     */
    public function out(){
        $if_out = I('input.if_out') ? I('input.if_out') : 1;
        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $count = Db::name('users')->where('sort','neq',0)->where('user_type','neq',9)->where('if_out',$if_out)->count();  //统计数量
        $list = Db::name('users')->where('sort','neq',0)->where('user_type','neq',9)->where('if_out',$if_out)->order('sort asc')->paginate($pagesize,false,array('query' => array_splice($param,1)));
        $this->assign('list',$list);
        $this->assign('if_out',$if_out);
        $this->assign('count',$count);
        $this->assign("page", $list->render());
        return $this->fetch();
    }

}