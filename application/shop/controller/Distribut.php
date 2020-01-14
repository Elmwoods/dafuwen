<?php
namespace app\shop\controller;

use app\common\logic\UsersLogic;
use app\common\model\Users;
use think\AjaxPage;
use think\Controller;
use app\shop\logic\GoodsLogic;
use app\shop\logic\SearchWordLogic;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;

class Distribut extends Base
{

    /**
     * 分销设置
     */
    public function set()
    {
        header("Location:" . U('Admin/System/index', array('inc_type' => 'distribut')));
        exit;
    }

    /**分销关系
     * @return mixed
     */
    public function tree()
    {

        $where = array();
        I('mobile') ? $where['mobile'] = I('mobile') : false;
        I('user_id') ? $where['user_id'] = I('user_id') : false;
        // $where = 'user_id > 0';
        // if ($this->request->param('user_id')) $where = "user_id = '{$this->request->param('user_id')}'";
        $list = M('users')
        ->field('user_id,nickname,mobile,level')
        ->where($where)
        ->select();

        $levelMapp = levelMapp();

        foreach ($list as $key => $value) {
            $count = $this->search_max_level_count($value['user_id']);

            $list[$key]['top_count'] = $count;
            $list[$key]['level'] = $levelMapp[$value['level']];
        }
        // pe($list);
        
        $this->assign('list', $list);
        return $this->fetch();
    }

    /*
     * 查找团队拥有的最高级数量
     */
    public function search_max_level_count($user_id){

        $childrens = $this->childrens_max_level([$user_id]);

        return count($childrens);
    }

    public function childrens_max_level($array_ids,$data = []){

        $uids = implode(',', $array_ids);

        $fids = M('users')
            ->field('user_id,level')
            ->where("first_leader", 'In', $uids)
            ->order('user_id')
            ->select();
        if (!empty($fids)) {
            $array_ids = array_column($fids, 'user_id');
  
            return $array_ids;
        } else {
            return $data;
        }
    }

    /**
     * 获取某个人下级元素
     */
    public function ajax_lower()
    {
        $id = $this->request->param('id');
        $userlevel = $this->request->param('userlevel');
        $userlevel_field = '';
        if ($userlevel == "first_leader") {
            $userlevel_field = "second_leader";
        } else if ($userlevel == "second_leader") {
            $userlevel_field = "third_leader";
        }
//        $where = '';
//        if ($userlevel == 'first_leader') $where .= "first_leader =" . $id;
//        if ($userlevel == 'second_leader') $where .= "second_leader =" . $id;
//        if ($userlevel == 'third_leader') $where .= "third_leader =" . $id;

        $levelMapp = levelMapp();


        $where = "first_leader =" . $id;
        $list = M('users')->field('user_id,nickname,mobile,level')->where($where)->select();
        $_list = array();
        foreach ($list as $key => $val) {
            $_t = $val;
            $_t['user_level'] = $userlevel_field;
            $count = $this->search_max_level_count($val['user_id']);

            $_t['top_count'] = $count;
            $_t['level'] = $levelMapp[$val['level']];

            $_list[] = $_t;
        }
        $this->assign('list', $_list);
        return $this->fetch();
    }


 

}