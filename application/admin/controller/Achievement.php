<?php

namespace app\admin\controller;
use think\Db;

class Achievement extends Base
{

    /**
     * [index 业绩列表]
     * @return [type] [description]
     */
    public function index(){
        $user_id = input('user_id');
        $where=[];
        if ($user_id) {
            $where['a.user_id'] = $user_id;
        }
        $change_time = input('change_time');
        $start = '';
        if ($change_time) {
            $time=strtotime($change_time);
            $start = strtotime("+1 month",$time);
            $end = strtotime(date('Y-m-d', $start)."+1 month +1 day");
            $where['a.change_time'] = [['egt', $start], ['elt', $end]];
        }
        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $lists = Db::name('account_log')
                    ->alias('a')
                    ->join('users b','a.user_id = b.user_id')
                    ->field('SUM(a.user_money) as user_money,SUM(a.pay_points) as pay_points,a.change_time,b.user_id,b.nickname')
                    ->where('a.type','eq',2)
                    ->where($where)
                    ->order('a.user_id asc')
                    ->group('a.user_id')
                    ->paginate($pagesize,false,array('query' => array_splice($param,1)));
        $this->assign('list',$lists);
        $this->assign('user_id',$user_id);
        $this->assign('change_time',$change_time);
        $this->assign("page", $lists->render());
        return $this->fetch();
    }

    public function export_achievement(){
        $user_id = input('user_id');
        $where=[];
        if ($user_id) {
            $where['a.user_id'] = $user_id;
        }
        $change_time = input('change_time');
        $start = '';
        if ($change_time) {
            $time=strtotime($change_time);
            $start = strtotime("+1 month",$time);
            $end = strtotime(date('Y-m-d', $start)."+1 month +1 day");
            $where['a.change_time'] = [['egt', $start], ['elt', $end]];
        }
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">用户ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">用户名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">用户返佣金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">父母孝爱基金</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">时间</td>';
        $strTable .= '</tr>';
        $count = Db::name('account_log')
                    ->alias('a')
                    ->join('users b','a.user_id = b.user_id')
                    ->field('SUM(a.user_money) as user_money,SUM(a.pay_points) as pay_points,a.change_time,b.user_id,b.nickname')
                    ->where('a.type','eq',2)
                    ->where($where)
                    ->order('a.user_id asc')
                    ->group('a.user_id')
                    ->count();
        $p = ceil($count/5000);
        for($i=0;$i<$p;$i++){
            $start = $i*5000;
            $end = ($i+1)*5000;
            $list = M('account_log')->alias('a')
                    ->join('users b','a.user_id = b.user_id')
                    ->field('SUM(a.user_money) as user_money,SUM(a.pay_points) as pay_points,a.change_time,b.user_id,b.nickname')
                    ->where('a.type','eq',2)
                    ->where($where)
                    ->limit($start.','.$end)
                    ->order('a.user_id asc')
                    ->group('a.user_id')
                    ->select();
            if(is_array($list)){
                foreach($list as $k=>$val){
                    $change_time = date('Y-m-d',strtotime("last day of -1 month",$val['change_time']));
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_id'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].' </td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_points'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$change_time.'</td>';
                    $strTable .= '</tr>';
                }
                unset($list);
            }
        }
        $strTable .='</table>';
        downloadExcel($strTable,'Achievement'.$i);
        exit();
    }
    
}