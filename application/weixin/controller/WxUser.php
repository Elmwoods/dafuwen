<?php

namespace app\weixin\controller;
use think\Db;

class WxUser extends Base{

    public function _initialize(){
        parent::_initialize();
    }

    public function user_list(){
        $map = [];
        $start = input("start");
        $end = input("end");
        $display_name = input("display_name");
        if(!empty($start) && empty($end)){
            $map["create_time"] = array("egt",strtotime($start));
        }else if(!empty($end) && empty($start) ){
            $map["create_time"] = array("elt",strtotime($end));
        }else if(!empty($start) && !empty($end)){
            $map["create_time"][] = array("egt",strtotime($start));
            $map["create_time"][] = array("elt",strtotime($end));
        }
        if(!empty($display_name)){
            $map["display_name"] = [
                'like',
                "%$display_name%" 
            ];
        }

        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $lists = db('users')->where($map)->order('id desc')->paginate($pagesize,false,array('query' => array_splice($param,1)));
        $this->assign("lists",$lists);
        $this->assign("page", $lists->render());
        return $this->fetch("user_list");
    }

} 