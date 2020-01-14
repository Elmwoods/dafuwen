<?php

namespace app\shop\controller;
use app\api\controller\Game;
use app\shop\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Exception;
use app\shop\logic\UsersLogic;
use think\Loader;
use app\common\controller\Logic;
use redis\Redis;

class Pig extends Base {


    public function pigList(){
        $Ad =  M('pig_goods');
        $p = $this->request->param('p');

        $count = $Ad->count();
       
        $Page  = new Page($count,10);


        $show = $Page->show();

        // $res = $Ad->order('id')->page($p.',10')->select();

        $res = $Ad->order('id')->limit($Page->firstRow.','.$Page->listRows)->select();

        $list = [];

        $rest_open_game = [];

        if($res){
            foreach ($res as $val){
                // $val['start_time'] = date("H:i",$val['start_time']);
                // $val['end_time'] = date("H:i",$val['end_time']);
                // var_dump($val['start_time'],$val['end_time']);

                $game_reset_time = strtotime("+24 hour", $val['game_reset_time']);
                //重启游戏机制
                if(!empty($val['game_reset_time'])){

                    if(time() >= $game_reset_time){

                        $val['is_restart'] = 2;

                    }else{

                        $val['is_restart'] = 1;
                    }
                }else{

                    $val['is_restart'] = 2;
                }


                //重启开奖机制
                if(time() > strtotime($val['start_time']) + 2400 && $val['today_is_open'] == 0 && $val['is_display'] == 1 && $val['is_lock'] == 1 ){
                    $val['reset_open_game'] = 1;
                }else{
                    $val['reset_open_game'] = 0;
                }


                $list[] = $val;

            }

            
        }
        
        
        $show = $Page->show();

        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function pigadd(){
        $act = I('get.act','add');
        $this->assign('act',$act);
        $id = I('get.id');
        if($id){
            $level_info = D('pig_goods')->where('id='.$id)->find();
            // $level_info['start_time'] = date("H:i",$level_info['start_time']);
            // $level_info['end_time'] = date("H:i",$level_info['end_time']);
            $this->assign('info',$level_info);
        }

        return $this->fetch();
    }

    public function pigHandle(){

        $data = I('post.');
        if($data['small_price'] <= 0 || $data['large_price'] <= 0 || $data['contract_days'] <= 0 || $data['income_ratio'] <= 0 || $data['pig_currency'] <= 0){
            $return = ['status' => 0, 'msg' => '配置值不能为空或者为0'];
            return $this->error($return['msg']);
        }
        if (!empty($data['act']) && $data['act'] == 'add') {  //添加

            $start_time = $data['start_time'];
            $end_time   = $data['end_time'];

            // var_dump($start_time);die;
            // $start=strtotime($start_time);
            // $end  = strtotime($end_time);

            // $large_price = Db::name('pig_goods')->field('small_price,large_price')->select();
           
            // foreach ($large_price as $key => $value) {
                
            //     if(intval($data['small_price']) == intval($value['small_price']) || intval($data['small_price']) == intval($value['large_price'])){
                
            //         $return = ['status' => 0, 'msg' => '金额重复，重新填写'];
            //         return $this->error($return['msg']);
                
            //     }
            //     if(intval($data['large_price']) == intval($value['small_price']) || intval($data['large_price']) == intval($value['large_price'])){

            //         $return = ['status' => 0, 'msg' => '金额重复，重新填写'];
            //         return $this->error($return['msg']);
            //     }
            // }   

            if($data['pig_currency'] <= 0){
                $return = ['status' => 0, 'msg' => '可挖HKT不能为0'];
                return $this->error($return['msg']);
            }
 
            $pig['id'] = $data['id'];
            $pig['goods_name'] = $data['goods_name'];
            $pig['small_price']= $data['small_price'];
            $pig['large_price']= $data['large_price'];
            $pig['start_time'] = $start_time;
            $pig['end_time']   = $end_time;
            $pig['reservation']= $data['reservation'];
            $pig['adoptive_energy'] = $data['adoptive_energy'];
            $pig['contract_days']= $data['contract_days'];
            $pig['income_ratio'] = $data['income_ratio'];
            $pig['pig_currency'] = $data['pig_currency'];
//            $pig['doge_money'] = $data['doge_money'];
            $pig['images'] = $data['images'];
            $r = Db::name('pig_goods')->insert($pig);

            if ($r !== false) {
                writelog(session('uid'),session('username'),'用户【'.session('username').'】添加场次成功',1);
                $return = ['status' => 1, 'msg' => '添加成功'];
            } else {
                writelog(session('uid'),session('username'),'用户【'.session('username').'】添加场次失败',2);
                $return = ['status' => 0, 'msg' => '添加失败，数据库未响应', 'result' => ''];
            }
        }

        if (!empty($data['act']) && $data['act'] == 'edit') { //编辑

            // $large_price = Db::name('pig_goods')->field('small_price,large_price')->select();
           
            // foreach ($large_price as $key => $value) {
                
            //     if(intval($data['small_price']) == intval($value['small_price']) || intval($data['small_price']) == intval($value['large_price'])){
                
            //         $return = ['status' => 0, 'msg' => '金额重复，重新填写'];
            //         return $this->error($return['msg']);
                
            //     }
            //     if(intval($data['large_price']) == intval($value['small_price']) || intval($data['large_price']) == intval($value['large_price'])){

            //         $return = ['status' => 0, 'msg' => '金额重复，重新填写'];
            //         return $this->error($return['msg']);
            //     }
            // }

            if($data['pig_currency'] <= 0){
                $return = ['status' => 0, 'msg' => '可挖HKT不能为0'];
                return $this->error($return['msg']);
            }
            
            $start_time = $data['start_time'];
            $end_time   = $data['end_time'];

            // var_dump($start_time);die;
            // $start=strtotime($start_time);
            // $end  = strtotime($end_time);


            $pig['id'] = $data['id'];
            $pig['goods_name'] = $data['goods_name'];
            $pig['small_price']= $data['small_price'];
            $pig['large_price']= $data['large_price'];
            $pig['start_time'] = $start_time;
            $pig['end_time']   = $end_time;
            $pig['reservation']= $data['reservation'];
            $pig['adoptive_energy'] = $data['adoptive_energy'];
            $pig['contract_days']= $data['contract_days'];
            $pig['income_ratio'] = $data['income_ratio'];
            $pig['pig_currency'] = $data['pig_currency'];
//            $pig['doge_money'] = $data['doge_money'];
            $pig['images'] = $data['images'];
            $old_pig = D('pig_goods')->where('id=' . $data['id'])->find(); //查询修改之前数据
            writelog(session('uid'),session('username'), //记录修改前猪的数据
                '编辑前【'.$old_pig['goods_name'].'】，
                金额:'.$old_pig['small_price'].'-'.$old_pig['large_price']
                .'，預約微分:'.$old_pig['reservation']
                .'，購置微分:'.$old_pig['adoptive_energy']
                .'，合約收益天数:'.$old_pig['contract_days']
                .'，合約收益比例/天:'.$old_pig['income_ratio']
                .'，可挖HKT:'.$old_pig['pig_currency']
//                .'，可挖DOGE:'.$old_pig['doge_money']
                ,1);
            $r = D('pig_goods')->where('id=' . $data['id'])->save($pig);
            if ($r !== false) {
                $new_pig = D('pig_goods')->where('id=' . $data['id'])->find(); //查询修改之后数据
                writelog(session('uid'),session('username'), //记录修改后猪的数据
                    '编辑后【'.$new_pig['goods_name'].'】，金额:'.$new_pig['small_price'].'-'.$new_pig['large_price']
                    .'，預約微分:'.$new_pig['reservation']
                    .'，購置微分:'.$new_pig['adoptive_energy']
                    .'，合約收益天数:'.$new_pig['contract_days']
                    .'，合約收益比例/天:'.$new_pig['income_ratio']
                    .'，可挖HKT:'.$new_pig['pig_currency']
//                    .'，可挖DOGE:'.$new_pig['doge_money']
                    ,1);
                $return = ['status' => 1, 'msg' => '编辑成功'];
            } else {
                writelog(session('uid'),session('username'),'用户【'.session('username').'】编辑【'.$pig['goods_name'].'】失败',2);
                $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
            }
        }

        if (!empty($data['act']) && $data['act'] == 'del') {  //删除

            $r = D('pig_goods')->where('id=' . $data['id'])->delete();
            if ($r !== false) {
                $return = ['status' => 1, 'msg' => '删除成功', 'result' => ''];
            } else {
                $return = ['status' => 0, 'msg' => '删除失败，数据库未响应', 'result' => ''];
            }
        }

        if($return['status']==1){
            $this->success($return['msg'],url('pig/pigList'));
        }else{
            $this->error($return['msg']);
        }
        
    }



     public function pigLog(){
        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();
    }
    public function pigindex(){
        // 搜索条件
        
        $condition = array();

        I('mobile') ? $condition['t2.mobile']  =   I('mobile') : false;  //出售人
        I('id') ? $condition['t2.user_id']  =   I('id') : false;  //出售人
        I('pig_level') ? $condition['t3.id']  =   I('pig_level') : false;  //猪等级

        // I('email') ? $condition['email'] = I('email') : false;
        // I('user_id') ? $condition['user_id'] = I('user_id') : false;
        $condition['is_able_sale'] =1;
        // $count = Db('user_exclusive_pig')->where($condition)->count();
        // $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        // foreach($condition as $key=>$val) {
        //     $Page->parameter[$key]   =   urlencode($val);
        // }
        $count = M('user_exclusive_pig t1')->join('users t2','t1.user_id = t2.user_id','LEFT')->join('pig_goods t3','t1.pig_id = t3.id','LEFT')->where($condition)->count();
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();
        // var_dump(I('mobile'));die;
         //获取订单列表
        if(empty($condition)){

            $userList = Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            
        }else{

            $userList = Db('user_exclusive_pig as t1')
                            ->field('t1.*')
                            ->join('users t2','t1.user_id = t2.user_id','LEFT')
                            ->join('pig_goods t3','t1.pig_id = t3.id','LEFT')
                            ->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();  
                          
        }
    
        // $userList =  Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    
        foreach ($userList as $key => $value) {
            
          $user_mobile  = Db::name('users')->field('mobile')->where(['user_id'=>$value['user_id']])->find();
          $first_mobile = Db::name('users')->field('mobile')->where(['user_id'=>$value['from_user_id']])->find();
          $pig_level = Db::name('pig_goods')->field('goods_name')->where(['id'=>$value['pig_id']])->find();
          $userList[$key]['name'] = $user_mobile['mobile']; 
          $userList[$key]['first_name'] = $first_mobile['mobile']; 
          $userList[$key]['pig_level'] = $pig_level['goods_name']; 
        }
      
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function change_pig(){
            
            $data = I('post.');

            if(!empty($data)){

                $user_s = Db::name('users')->where(['user_id'=>$data['appoint_user_id']])->find();
                
                if(empty($user_s)){
                    $return = ['status' => -1, 'msg' => '该用户不存在'];
                    $this->error($return['msg']);
                }
                if($user_s['rule_sort'] == 0){

                    $return = ['status' => -1, 'msg' => '该用户禁止排单'];
                    $this->error($return['msg']);
                }

                if($user_s){

                    M('user_exclusive_pig')->where('id',$data['id'])->update(['appoint_user_id' => $data['appoint_user_id']]);

                    $return = ['status' => 1, 'msg' => '提交成功'];
                    $this->success($return['msg']);
                }else{
                    $return = ['status' => -1, 'msg' => '操作失败'];
                    $this->error($return['msg']);
                }

            }else{

                $id = input('id');

                $exclusive_pig = Db::name('user_exclusive_pig')->where(['id'=>$id])->find();

                $this->assign('exclusive_pig',$exclusive_pig);
            }

            return $this->fetch();
    }

    public function pigOrder(){

        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);

        return $this->fetch();
    }


     /*
     *交易订单
     */
    public function ajaxindex(){


        $orderLogic = new OrderLogic();
     

        // 搜索条件
        $condition = array();

        I('mobile') ? $condition['u1.mobile']  =   I('mobile') : false;                   //出售人
        I('mobile_s') ? $condition['u2.mobile']  =   I('mobile_s') : false;                   //出售人
        I('pig_order_sn') ? $condition['t1.pig_order_sn'] = I('pig_order_sn') : false;    //订单编号
        I('pig_level') ? $condition['t3.id']  =   I('pig_level') : false;          //猪等级
        (I('pay_status') !== '') && $condition['t1.pay_status']  =   I('pay_status') ;   //订单状态
        // I('purchase_user_id') ? $condition['t3.mobile'] = I('purchase_user_id') : false; //购买人
        // I('pay_status') ? $condition['t1.pay_status'] = intval(I('pay_status')) : false;        //订单状态
        // var_dump($condition['t1.pay_status']);die;
   
        $sort_order = I('order_by','order_id DESC').' '.I('sort');

        $count = M('pig_order t1')
                ->join('users u1','t1.sell_user_id = u1.user_id','LEFT')
                ->join('users u2','t1.purchase_user_id = u2.user_id','LEFT')
                ->join('pig_goods t3','t1.pig_level = t3.id','LEFT')
                ->where($condition)->count();
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();
        
        // var_dump($condition);die;
        //获取订单列表
        // if(empty($condition)){

        //     $orderList = Db('pig_order')->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            
        //     $orderList = Db('pig_order as t1')
        //                     ->field('t1.*,u1.mobile as user_name,u2.mobile as first_name,t3.goods_name as pig_level')
        //                     ->join('users u1','t1.sell_user_id = u1.user_id','LEFT')
        //                     ->join('users u2','t1.purchase_user_id = u2.user_id','LEFT')
        //                     ->join('pig_goods t3','t1.pig_level = t3.id','LEFT')
        //                     ->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();


        // }else{
        $orderList = Db('pig_order as t1')
                    ->field('t1.*,u1.mobile as user_name,u2.mobile as first_name,t3.goods_name as pig_level')
                    ->join('users u1','t1.sell_user_id = u1.user_id','LEFT')
                    ->join('users u2','t1.purchase_user_id = u2.user_id','LEFT')
                    ->join('pig_goods t3','t1.pig_level = t3.id','LEFT')
                    ->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();  
                          
        // }
    
        // $orderList = Db('pig_order')->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        // foreach ($orderList as $key => $value) {
            
        //   $user_mobile  = Db::name('users')->field('mobile')->where(['user_id'=>$value['sell_user_id']])->find();
        //   $first_mobile = Db::name('users')->field('mobile')->where(['user_id'=>$value['purchase_user_id']])->find();
        //   $pig_level = Db::name('pig_goods')->field('goods_name')->where(['id'=>$value['pig_level']])->find();
        //   $orderList[$key]['user_name'] = $user_mobile['mobile']; 
        //   $orderList[$key]['first_name'] = $first_mobile['mobile']; 
        //   $orderList[$key]['pig_level'] = $pig_level['goods_name']; 
        // }
       
        $this->assign('orderList',$orderList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /*
     *修改时间
     */
    public function order_pig(){

            $data = I('post.');

            if(!empty($data)){
                
                $order = Db::name('pig_order')->where(['order_id'=>$data['order_id']])->find();
                
                $establish_time = $data['establish_time'];

                $time  = strtotime($establish_time);

                if($order){
                    M('pig_order')->where('order_id',$data['order_id'])->update(['establish_time' => $time]);
                    $return = ['status' => 1, 'msg' => '修改成功'];
                    $this->success($return['msg']);
                }else{
                    $return = ['status' => -1, 'msg' => '操作失败'];
                    $this->error($return['msg']);
                }

            }else{

                $order_id = input('id');
                
                $exclusive_pig = Db::name('pig_order')->where(['order_id'=>$order_id])->find();

                $this->assign('exclusive_pig',$exclusive_pig);
            }

            return $this->fetch();
    }
    /*
     *  修改指定id的详情页
     */
    public function change(){

        $id = input("id");

        $exclusive_pig = Db::name('user_exclusive_pig')->where(['id'=>$id])->find();

        $this->assign('exclusive_pig',$exclusive_pig);

        return $this->fetch();
    }

    /*
     *  修改指定id的详情页
     */
    public function change_edit(){

            $data = I('post.');


            if(!empty($data)){

                $user_s = Db::name('users')->where(['user_id'=>$data['appoint_user_id']])->find();
                
                if(empty($user_s)){
              
                    $this->error('该用户不存在');
                }
                if($user_s['rule_sort'] == 0){

                    $this->error('该用户禁止排单');
                }

                if($user_s){

                    M('user_exclusive_pig')->where('id',$data['id'])->update(['appoint_user_id' => $data['appoint_user_id']]);

                    $this->success('操作成功','pigLog');

                }else{

                    $this->error('操作失败');
                }

            }

            return $this->fetch();

    }


    /*
     *  修改猪的金额的详情页
     */
    public function edit_price(){

        $id = input("id");


        if(IS_POST){

            $data = I('post.');


            $pig_goods= Db::name('pig_goods')->where('id',$data['pig_id'])->find();


            if(floatval($pig_goods['small_price']) > $data['price']){

                $this->error('金额不能小于猪的最低價值金额','pigLog');
            }

            if($data['price'] > floatval($pig_goods['large_price'])){

                $this->error('金额不能大于猪的最高價值金额','pigLog');
            }

                if(!empty($data)){

                    $pig = Db::name('user_exclusive_pig')->where('id',$data['id'])->find();


                    $desc = '原價值金额'.$pig['price'].'元 修改为'.$data['price'].'元';
       
                    if($pig){

                        M('user_exclusive_pig')->where('id',$data['id'])->update(['price' => $data['price']]);

                        $account_log=array('user_id'=>$pig['user_id'],'user_money'=>$data['price'],'change_time'=>time(),'desc'=>$desc,'pig_id'=>$pig['pig_id']);

                        M('pig_price_log')->add($account_log);


                        $this->success('操作成功','pigLog');

                    }else{

                        $this->error('操作失败');
                    } 
                }
        }


        $exclusive_pig = Db::name('user_exclusive_pig')->where(['id'=>$id])->find();

        $this->assign('exclusive_pig',$exclusive_pig);

        return $this->fetch();
    }

     /*
     *  会员的猪
     */
     public function pigUser(){

        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();
     }

     /*
     *  会员的猪
     */
     public function piginuser(){

        $condition = array();

        $is_able_sale  = I('is_able_sale');

        // $end_time = I('end_time');

        // if(!empty($end_time)){

        //     $end_time = str_replace("+"," ",$end_time);
        //     $end_time2 = $end_time  ? $end_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        //     $end_time3 = explode(' - ',$end_time2);

        //     $condition['t1.end_time'] =  array('between',array(strtotime($end_time3[0]),strtotime($end_time3[1]." 23:59:59")));
        // }
       

        I('mobile') ? $condition['t2.mobile']  =   I('mobile') : false;  //出售人
         // I('id') ? $condition['t2.user_id']  =   I('id') : false;  //出售人id
        I('pig_level') ? $condition['t3.id']  =   I('pig_level') : false;  //猪等级

        $is_able_sale = empty($is_able_sale) ? I('is_able_sale') : $is_able_sale;

        if($is_able_sale != '') {
          $condition['t1.is_able_sale'] = $is_able_sale;
        }

        $count = M('user_exclusive_pig t1')->join('users t2','t1.user_id = t2.user_id','LEFT')->join('pig_goods t3','t1.pig_id = t3.id','LEFT')->where($condition)->count();
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();
    
         //获取会员所有的猪
        if(empty($condition)){

            $userList = Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            
        }else{

            $userList = Db('user_exclusive_pig as t1')
                            ->field('t1.*')
                            ->join('users t2','t1.user_id = t2.user_id','LEFT')
                            ->join('pig_goods t3','t1.pig_id = t3.id','LEFT')
                            ->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();  
                          
        }
    
         //$userList =  Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->fetchSql(true)->select();
         //var_dump($userList);die;
        foreach ($userList as $key => $value) {
          //查看猪是否异常
          $userList[$key]['checkstauts']=1;
          if($value["pig_salt"]==''){
             $userList[$key]['checkstauts']=-1;
          }else{

            $res= new \app\api\controller\Addsalt();
            $stautss=$res->checkpigsalt($value["id"]);
            if(!$stautss){
                $userList[$key]['checkstauts']=-1;
            }
          } 
          //查看猪是否异常
          $user_mobile  = Db::name('users')->field('mobile')->where(['user_id'=>$value['user_id']])->find();
          $first_mobile = Db::name('users')->field('mobile')->where(['user_id'=>$value['from_user_id']])->find();
          $pig_level = Db::name('pig_goods')->field('goods_name')->where(['id'=>$value['pig_id']])->find();
          $userList[$key]['name'] = $user_mobile['mobile']; 
          $userList[$key]['first_name'] = $first_mobile['mobile']; 
          $userList[$key]['pig_level'] = $pig_level['goods_name']; 
        }
      
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
     }

     /***
     *已裂变的宠物
     **/
     public function pigUserDestroy (){


        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();
     }

     /***
     *已裂变的猪ajax
     **/

     public function piginuserDestroy(){

        $condition = array();

        I('mobile') ? $condition['t2.mobile']  =   I('mobile') : false;  //出售人
        I('pig_level') ? $condition['t3.id']  =   I('pig_level') : false;  //猪等级
        I('id') ? $condition['t1.id']  =   I('id') : false;  //裂变前的宠物ID

        $condition['t1.type']='del';
        // I('is_able_sale') ? $condition['t1.is_able_sale']  =   I('is_able_sale') : false;  //猪等级

        // (I('is_able_sale') !== '') && $condition['t1.is_able_sale']  =   I('is_able_sale') ;
        // var_dump($condition);die;


        $count = M('user_exclusive_pig_del t1')->join('users t2','t1.user_id = t2.user_id','LEFT')->join('pig_goods t3','t1.pig_id = t3.id','LEFT')->where($condition)->count();
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();
    
         //获取会员所有的猪
        if(empty($condition)){

            $userList = Db('user_exclusive_pig_del')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            
        }else{

            $userList = Db('user_exclusive_pig_del as t1')
                            ->field('t1.*')
                            ->join('users t2','t1.user_id = t2.user_id','LEFT')
                            ->join('pig_goods t3','t1.pig_id = t3.id','LEFT')
                            ->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();  
                          
        }
    
        // $userList =  Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    
        foreach ($userList as $key => $value) {
            
          $user_mobile  = Db::name('users')->field('mobile')->where(['user_id'=>$value['user_id']])->find();
          $first_mobile = Db::name('users')->field('mobile')->where(['user_id'=>$value['from_user_id']])->find();
          $pig_level = Db::name('pig_goods')->field('goods_name')->where(['id'=>$value['pig_id']])->find();
          $userList[$key]['name'] = $user_mobile['mobile']; 
          $userList[$key]['first_name'] = $first_mobile['mobile']; 
          $userList[$key]['pig_level'] = $pig_level['goods_name']; 
        }
      
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
     }






    /*
     *  預約
     */   
     public function pigReserve()
     {   
        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();
     }

     public function pigsert()
     {


        $mobile  = I('mobile');     //出售人
        $pig_level = I('pig_level');//猪等级

        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);

        $this->assign('start_time',$create_time3[0]);
        $this->assign('end_time',$create_time3[1]);
        $reservation_status = empty($reservation_status) ? I('reservation_status') : $reservation_status;

        $where = [];

        if($reservation_status != '') {
          $where['t1.reservation_status'] = $reservation_status;
        }
        $mobile && $where['t2.mobile'] = $mobile;
        $pig_level && $where['t3.id'] = $pig_level;
        $where['t1.reservation_time'] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]." 23:59:59")));

        $count = Db::name('pig_reservation')->alias('t1')->join('__USERS__ t2', 't2.user_id = t1.user_id', 'INNER')->join('pig_goods t3', 't3.id = t1.pig_id')->where($where)->count();
        
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();

        $userList = Db::name('pig_reservation')->alias('t1')->field('t1.*,t2.nickname,t2.mobile,t3.goods_name')->join('__USERS__ t2', 't2.user_id = t1.user_id', 'INNER')->join('pig_goods t3', 't3.id = t1.pig_id', 'INNER')->where($where)->order("t1.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        
        // $condition = array();

        // I('mobile') ? $condition['t2.mobile']  =   I('mobile') : false;  //出售人
        // I('pig_level') ? $condition['t3.id']  =   I('pig_level') : false;  //猪等级

        // $start_time = I("start_time") ? I("start_time")."00:00:00" : false;
        // $end_time = I("end_time") ? I("end_time")."23:59:59" : false;


        // // var_dump($start_time,$end_time);
        //  if (!empty($start_time) && empty($end_time)) {
        //    $condition['t1.reservation_time'] = ['>=',strtotime($start_time)];
        // }else if (!empty($end_time) && empty($start_time)) {
        //    $condition['t1.reservation_time'] = ['<=',strtotime($end_time)];
        // }else if(!empty($start_time) && !empty($end_time)){

        //     if($start_time == $end_time){

        //         $condition['FROM_UNIXTIME(t1.reservation_time,"%Y-%m-%d")'] = $start_time;

        //     }else{
        //         $condition["t1.reservation_time"] = ['between',[strtotime($start_time),strtotime($end_time)]];
        //     }

        // }

        // $count = M('pig_reservation t1')->join('pig_goods t3','t1.pig_id = t3.id','LEFT')->where($condition)->count();

        // $Page  = new AjaxPage($count,20);

        // $show = $Page->show();
    
        //  //获取会员所有的猪
        // if(empty($condition)){

        //     $userList = Db('pig_reservation')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            
        // }else{

        //     $userList = Db('pig_reservation as t1')
        //                     ->field('t1.*')
        //                     // ->join('users t2','t1.user_id = t2.user_id','LEFT')
        //                     ->join('pig_goods t3','t1.pig_id = t3.id','LEFT')
        //                     ->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();  
                          
        // }
    
        // // $userList =  Db('user_exclusive_pig')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        // foreach ($userList as $key => $value) {
            
        //   $user_mobile  = Db::name('users')->field('mobile')->where(['user_id'=>$value['user_id']])->find();
        //   $pig_level = Db::name('pig_goods')->field('goods_name')->where(['id'=>$value['pig_id']])->find();
        //   $userList[$key]['name'] = $user_mobile['mobile']; 
        //   $userList[$key]['pig_level'] = $pig_level['goods_name']; 
        // }
      
        // $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
     }

    /*
     *  中奖
    */   
    public function reward_log()
     {   
        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();
     }

    /*
    *   参与中奖抢购详情
    */ 
    public function reward()
     {

        $pig_level = I('pig_level');//猪等级
        $create_time = I('change_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);

        $this->assign('start_time',$create_time3[0]);
        $this->assign('end_time',$create_time3[1]);
        
        $where = [];

        $pig_level && $where['t1.pig_id'] = $pig_level;
        $where['t1.change_time'] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]." 23:59:59")));

        $count = Db::name('pig_award_log')->alias('t1')->field('t1.*')->where($where)->count();
        
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();

        $userList = Db::name('pig_award_log')->alias('t1')->field('t1.*,t2.goods_name')->join('pig_goods t2', 't2.id = t1.pig_id', 'INNER')->where($where)->order("t1.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $lis = [];
        $award= [];

        foreach ($userList as $key => $value) {
            $lis = $value['join_user_list'];
            $join_user_list= explode(',',$lis);

            if($join_user_list[0] !==''){
                $userList[$key]['join_list'] = count($join_user_list);
            
            }else{
                $userList[$key]['join_list'] = 0;
            }

            $award= $value['award_user_list'];
            $award_user_list= explode(',',$award);

            if($award_user_list[0] !==''){
                $userList[$key]['award_list'] = count($award_user_list);
            
            }else{
                $userList[$key]['award_list'] = 0;
            }

        }

        
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
     }

    public function resetGame(){
         file_put_contents(ROOT_PATH . 'data.txt',1);
        $this->success('重置游戏服务时间大约需1分钟,此过程中尽量不要再次重启');
    }
    public function restart(){

        $goods_id = I('goods_id');

        if(empty($goods_id)){
            $this->ajaxReturn(['status' => -1,'message' => '参数有误']);
        }

        $pig_goods = Db::name('pig_goods')->where('id',$goods_id)->find();

        if($pig_goods){

            db('pig_goods')->where('id',$pig_goods['id'])->update(['today_is_open'=> 0,'is_lock'=>0,'game_reset_time'=>time()]);
           
            Redis::del('game_award_list_'.$pig_goods['id']); //中奖人
            Redis::del('game_name_pre'.$pig_goods['id']); //游戏状态
            Redis::del('flash_buy_'.date('Ymd',time()).'_'.$pig_goods['id']);
            $this->ajaxReturn(['status' => 1,'message' => '重启成功']);

        }else{

            $this->ajaxReturn(['status' => -1,'message' => '重启失败']);
        }

    }


        /**
     * [pigDel 删除猪]
     * @return [type] [description]
     */
    public function pigDel()
    {
        $id = intval(input('param.id'));
        
        $pigres = Db::name('user_exclusive_pig')->where(['id'=>$id])->find(); //查看这条購置記錄

        if(!$pigres){
           return json(['code' => 0, 'data' => '', 'msg' => "找不到此记录"]); 
        }

        $indate=[];
        $indate["user_id"]= $pigres["user_id"];
        $indate["order_id"]= $pigres["order_id"];
        $indate["pig_id"]= $pigres["pig_id"];
        $indate["is_able_sale"]= $pigres["is_able_sale"];
        $indate["price"]= $pigres["price"];
        $indate["from_user_id"]= $pigres["from_user_id"];
        $indate["appoint_user_id"]= $pigres["appoint_user_id"];
        $indate["buy_time"]= $pigres["buy_time"];
        $indate["end_time"]= $pigres["end_time"];
        $indate["del_id"]= $pigres["id"];
        $indate["deltime"]=date('Y-m-d H:i:s');
        Db::startTrans();
        try{
         
         $delres = Db::name('user_exclusive_pig_delete')->insertGetId($indate);

         $res = Db::name('user_exclusive_pig')->delete($id);

          Db::commit();
       } catch (\Exception $e) {
           // 回滚事务
           Db::rollback();
           return json(['code' => 0, 'data' => '', 'msg' => "操作异常"]);     
                
       }

        return json(['code' => 1, 'data' => $id, 'msg' => "删除猪成功"]);
    }

    /*补偿未开奖处理*/
    public function OpenGamecompenState(){
        $game_id = I('id');
        $game_start_time = Db::name('pig_goods')->where('id',$game_id)->where(['today_is_open'=>0,'is_display'=>1,'is_lock'=>1])->value('start_time');
        if(time() > strtotime($game_start_time) + 2400){
            $game = new \app\common\logic\Game();
            $rs  = $game->compenStateOpenGame($game_id);
            if($rs){
                $this->ajaxReturn(['status' => 1,'message' => '游戏补偿成功']);
            }else{
                $this->ajaxReturn(['status' => -1,'message' => '游戏补偿失败']);
            }
        }else{
            $this->ajaxReturn(['status' => -1,'message' => '错误']);
        }
    }

    /*
     *  填写核销课程原因
     */
    public function pig_delete(){

        $id = input("id");

        $exclusive_pig = Db::name('user_exclusive_pig')->where(['id'=>$id])->find();


        $this->assign('exclusive_pig',$exclusive_pig);

        return $this->fetch();
    }

    /*
     *  销毁课程
     */
    public function xiaohui_pig(){

            $data = I('post.');



            if(!empty($data)){

                $user_pig = Db::name('user_exclusive_pig')->where(['id'=>$data['id']])->find();

                $order = Db::name('pig_order')->where(['order_id'=>$user_pig['order_id']])->value('pay_status');

                if($order !== 2){

                    $this->error('宠物订单未交易完，销毁失败');

                }

                $admin_id =$_SESSION['think']['admin_id'];

                if(empty($data['content'])){

                   $this->error('请填写销毁原因');
                }
                
                if($user_pig){

                    Db::startTrans();

                    $list = [];

                    $list['user_id'] = $user_pig['user_id'];
                    $list['order_id']= $user_pig['order_id'];
                    $list['pig_id']  = $user_pig['pig_id'];
                    $list['is_able_sale'] = $user_pig['is_able_sale'];
                    $list['price']   = $user_pig['price'];
                    $list['from_user_id'] = $user_pig['from_user_id'];
                    $list['appoint_user_id'] = $user_pig['appoint_user_id'];
                    $list['buy_time'] = $user_pig['buy_time'];
                    $list['end_time'] = $user_pig['end_time'];
                    $list['del_id']   = $user_pig['id'];
                    $list['deltime']  = time();

                    $res1 = Db::name('user_exclusive_pig_delete')->add($list);
                    
                    $res2 = Db::name('send_mail')->insert(['user_id'=>$user_pig['user_id'],'admin_id'=>$admin_id,'delete_pig_id'=>$res1,'content'=>$data['content'],'create_time'=>time()]);

                    $res3 = Db::name('user_exclusive_pig')->delete(['id'=>$data['id']]);

                    if ($res1 && $res2 && $res3) {
                    // 提交事务
                        Db::commit();
                    $this->success('销毁成功','pigUser');

                    }else{
                        Db::rollback();
                        $this->error('提交失败');
                    }

                }else{

                    $this->error('操作失败');
                }

            }

            return $this->fetch();

    }


    public function user_pig_delete(){

        $pig = Db::name('pig_goods')->field('goods_name,id')->select();

        $this->assign('pig',$pig);
        return $this->fetch();

     }

     public function pig_delete_isuser(){

        $mobile  = I('mobile');     //出售人
        $pig_level = I('pig_level');//猪等级
        $del_id = I('del_id');//猪等级

        $where = [];

        $mobile && $where['t2.mobile'] = $mobile;
        $pig_level && $where['t3.id'] = $pig_level;
        $del_id && $where['t1.del_id'] = $del_id;
        
        $count = Db::name('user_exclusive_pig_delete')->alias('t1')->join('__USERS__ t2', 't2.user_id = t1.user_id', 'INNER')->join('pig_goods t3', 't3.id = t1.pig_id')->where($where)->count();
        
        $Page  = new AjaxPage($count,20);

        $show = $Page->show();

        $userList = Db::name('user_exclusive_pig_delete')->alias('t1')
                    ->field('t1.*,t2.nickname,t2.mobile,t3.goods_name')
                    ->join('__USERS__ t2', 't2.user_id = t1.user_id', 'INNER')
                    ->join('pig_goods t3', 't3.id = t1.pig_id', 'INNER')
                    // ->join('send_mail p4', 'p4.delete_pig_id = t1.pig_id', 'INNER')
                    ->where($where)->order("t1.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();

     }


     public function pig_user_add()
    {


        $data = I('post.');

        if ($data) {


            if (empty($data['user_id'])) {

                $this->error('用户ID不为空！');
            }

            $users = Db::name('users')->where(['user_id' => $data['user_id']])->value('user_id');

            if (empty($users)) {

                $this->error('该用户ID不存在！');
            }

            if (empty($data['goods_sum'])) {

                $this->error('抢购场次数量不为空！');
            }

            if (empty($data['goods_id'])) {

                $this->error('抢购场次不为空！');
            }

            $pig_goods = Db::name('pig_goods')->where(['id' => $data['goods_id']])->find();

            if (empty($pig_goods)) {

                $this->error('该场次不存在！');
            }
            $c_time = time();
            $list = [];
            $list['user_id'] = $data['user_id'];
            $list['pig_id'] = $data['goods_id'];
            $list['buy_time'] = $c_time;
            $list['end_time'] = $c_time;
            $list['buy_type'] = 'generate';
            $list['is_able_sale'] = 1;
            // 启动事务
            Db::startTrans();
            try {
                $goods = 0;
                $res=new \app\api\controller\Addsalt();
                for ($i = 0; $i < $data['goods_sum']; $i++) {
                    if ($data['price'] < $pig_goods['small_price'] || $data['price'] > $pig_goods['large_price']) {
                        continue;
                    }
                    $money = $data['price'];
                    $list['price'] = $money;
                    $pigsalt=$res->pigaddsalt($data['user_id'],0,$c_time,$list['buy_type']);
                    $list['pig_salt'] = $pigsalt;
                    $goods = Db::name('user_exclusive_pig')->insertGetId($list);
                    if (!$goods) throw new \Exception("Error Processing Request", 1);
                }
            } catch(\Exception $e) {
                Db::rollback();
                $this->error('生成失败', 'pigUser');
            }
            Db::commit();
            $this->success('生成成功', 'pigUser');
        }

        $pig = Db::name('pig_goods')->field('goods_name,id,large_price,small_price')->select();

        $this->assign('pig', $pig);

        return $this->fetch();
    }

}
