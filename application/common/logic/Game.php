<?php
/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/12/10
 * Time: 9:18
 */

namespace app\common\logic;
use app\api\controller\JuHe;
use redis\Redis;
use think\Db;
use think\Debug;

class Game
{
    public $game_id ;
    protected $daojishi; //倒计时
    public $openaward ; //开奖
    protected $now_time ; //现在的时间
    protected $next_level_time ; //下一个阶段的时间
    public $gaming_model ; //现在的游戏
    protected $all_game_model ; //所有游戏模型
    protected $redis ;
    protected $game_name_status_pre ; //游戏场次状态前缀
    protected $game_name_status_expire_time ; //过期时间
    protected $game_award_list ; //
    protected $php_root; //执行路径

    public function __construct()
    {
        $this->config();
        $this->game_name_pre = 'game_name_pre';
        $this->game_name_expire_time =10800;       //三个小时
        $this->game_award_list = 'game_award_list_';
        $this->php_root = empty(config('php_root')) ? dirname(APP_PATH) : config('php_root');

    }

    //返回某一个游戏的阶段，下个阶段的时间
    public function getRuningInfo(){
        $game = $this->runing();
        $info = [];
        if(!!$game){
            $level = $this->gameTimeArea($game['start_time']);
            $info['next_time'] = $this->next_level_time;
            $info['level'] = $level;
            $info['id'] = $game['id'];
        }
        return $info;
    }

    public function getCoolTime(){
        return $this->daojishi;
    }

    //获取进行中的游戏
    public function runing(){
        //当前时间
        $now_time = date('H:i:s',strtotime(date('H:i:s')) - $this->openaward);
        $where['start_time'] = ['gt',$now_time];
        $where['today_is_open'] = 0;
        $where['is_display'] = 1;
        $pig_list = Db::name('pig_goods')->cache(true,5)->where($where)->order('start_time')->find();
        return $pig_list ;
    }

    //游戏配置
    public function config(){
        $value = Db::name('config')->where('name','daojishi')->cache(true,600)->value('value');
        $value = $value ? $value : 120;
        $this->daojishi = $value;
        $this->openaward = 60 * 5;
        $this->now_time = time();
    }

    //开奖时间
    public function getGameOpenTime($game_id){
        $start_time = Db::name('pig_goods')->where('id',$game_id)->value('start_time');

        return strtotime($start_time) + $this->openaward ;
    }

    //下个游戏执行剩余时间
    public function excute_time(){
        $pig = $this->runing();
        if(!!$pig){

            $this->game_id = $pig['id'];

            $this->gaming_model = $pig;
            //改变游戏ID
            $start_time  =strtotime('Ymd')+ strtotime($pig['start_time']) + $this->openaward;
            return $start_time  - time();
        }else{
           
            $pig_list = Db::name('pig_goods')->order('start_time')->find();
            $this->game_id = $pig_list['id'];
            $this->gaming_model = $pig_list;
            $end_time = strtotime(date('Ymd')) + strtotime(date($pig_list['start_time'])) +86400 ;
            return $end_time - time() + $this->openaward;
        }
    }


    //判断是否到了开奖时间
    public function openGame(){
        $_now_time = time();
        //游戏封闭区 凌晨0~2点不执行开奖 -2019年2月16日16:32:53
                if($this->timeStopOpenGame($_now_time)){
                    $now_time = date('H:i:s',$_now_time - $this->openaward);
                    $where['start_time'] = ['elt',$now_time];
                    $where['today_is_open'] = 0;
                    $where['is_display'] = 1;
                    $where['is_lock'] = 0;
                    $game = Db::name('pig_goods')->where($where)->order('start_time')->find();
                    if(!empty($game)){
                        //直接锁表
                        Db::name('pig_goods')->where($where)->save(['is_lock'=>1]);

                        $this->game_id = $game['id'];
                        $this->gaming_model = $game;
                $this->flashBuy();
            }
        }
       
    }

    function timeStopOpenGame($time){
        //获取当前0时0分
        $today_0 = strtotime(date('Ymd',$time));
        $sub_time = $time - $today_0;
        if($sub_time > 7200){
            return true;
        }else{
            return false;
        }
    }


    //每个游戏的状态区间
    function gameTimeArea($game_time){
        $stage_1 = $game_time -  $this->daojishi;
        $stage_3 = $game_time +  $this->openaward;
        $now_time = $this->now_time;
        $_stage = 1;
        //$this->connection->send('区间开始');
        if($now_time < $stage_1){
            //倒计时之前
            //$this->stage1($stage_1,$id);
            $_stage = 1;
            $this->next_level_time = $stage_1 - $now_time;
            // return 1;
        }elseif($now_time >= $stage_1 && $now_time < $game_time){
            //$this->stage2($game_time);
            $_stage = 2;
            //倒计时中
            $this->next_level_time = $game_time - $now_time;

            //  return 2;
        }elseif($now_time<$stage_3 && $now_time >= $game_time ){
            //开奖中
            //$this->stage3();
            $_stage = 3;
            $this->next_level_time = $game_time - $now_time;

            //  return 3;
        }elseif($now_time > $stage_3){
             return 4;
        }
        return $_stage;
    }


    //设置所有游戏的模型
    public function setGameModel($data){
        $this->all_game_model = $data;
    }

    //将模型附加游戏阶段
    public function addGameLevel(){
        $model = $this->all_game_model;
        foreach($model as $k => $v){
            $_time =strtotime($v['start_time']);
            $model[$k]['game_level'] = $this->gameTimeArea($_time);
        }
        return $model;
    }

    //当天的游戏时间
    public function exchage_time($time){
    }


    public function setGameId($id){
        $this->game_id = $id;
    }
    //抢购
    /*
     * 优先给指定的人的猪
     *
     * */
    public function flashBuy(){
        $redis_name = $this->game_name_pre . $this->game_id;
        $this->getGameStatus();
        $game_status = Redis::get($redis_name);
        if($game_status == 1){

            //用于开奖成功的一个标识
            Redis::set('game_status_'.date('Ymd',time()).'_'.$this->game_id,1,$this->expire_time());

            $this->updateGameStatus(2);
            $pigbuy = new PigFlashBuy();
            $redis_users = [];
            $redis_users = $pigbuy->getUsers($this->game_id);
            Redis::set('users_list_queue_'.$this->game_id,$redis_users);
            $doflashbuy_root = $this->php_root;
            //exec("cd /www/wwwroot/daxiongqukuaizhu ;chown -R www:www runtime;chmod 0755 -R runtime");
            exec("cd {$doflashbuy_root};/www/server/php/70/bin/php think doflashbuy {$this->game_id} 0",$a,$b);

        }
    }

    public function updateOneData(){
        //$pig = 1;
        $pig = Db::name('user_exclusive_pig')->where('id',1)->find();
        $award_user_id = 24;

        $this->createOrder($pig,$award_user_id);
    }


    //pig模型
    //赢家
    // type 1 抢购 ,2繁殖
    //pig模型
    //赢家
    // type 1 抢购 ,2繁殖
    public function createOrder($pig,$award_user_id,$type = 2){
        try{
            Db::startTrans();
            //短信处理
            $sms = new JuHe();
            $data['sell_user_id'] = $pig['user_id'];
            $data['pig_level'] = $pig['pig_id'];
            $data['pig_price'] = $pig['price'];
            $data['pig_id'] = $pig['id'];
            //$data['user_id'] = $award_user;
            $data['establish_time'] = time();
            $data['end_time'] = time() + 3600 * 2;
            $data['pig_order_sn'] = $this->get_order_sn();
            $data['purchase_user_id'] = $award_user_id;
            $pig_order = Db::name('pig_order')->insertGetId($data);
            $pres = true;



            //这里加下盐
            $buy_type = 'createOrder';
            $res = new \app\api\controller\Addsalt();
            $pig_salt=$res->pigaddsalt($pig['user_id'],$pig_order,$pig['buy_time'],$buy_type);
            //将指定的去掉
            $save_user = Db::name('user_exclusive_pig')->where('id',$pig['id'])->where('is_able_sale',1)->save(['order_id'=>$pig_order,'pig_salt'=>$pig_salt,'buy_type'=>$buy_type,'appoint_user_id'=>0,'is_able_sale'=>0]);

            if(!$save_user)
                return ['status'=>0,'msg'=>'更新失败'];

            //将預約記錄是否抢到改一下状态
            $p_res_id = Db::name('pig_reservation')->where('pig_id',$pig['pig_id'])->where('user_id',$award_user_id)->whereTime('reservation_time','today')->value('id');
            if(!empty($p_res_id)){
                $pres = Db::name('pig_reservation')->where('pig_id',$pig['pig_id'])->where('user_id',$award_user_id)->whereTime('reservation_time','today')->save(['reservation_status'=>1]);
            }

            if($type == 1){
                //抢购后，扣除对应的福分
                $game_model = Db::name('pig_goods')->where('id',$pig['pig_id'])->find();
                //判断是否已经預約了
                $is_yuyue = $this->isYuyue($award_user_id);
                $fufen_do = true;

                if(!$is_yuyue){

                    $desc = sprintf('抢购%s消耗微分',$game_model['goods_name']);
                    $adoptive_energy = $game_model['adoptive_energy'];
                    //$desc = '抢购消耗福分';
                    //扣除福分
                    $fufen_do = accountLog($award_user_id,0,-"{$adoptive_energy}",$desc,0,0,0,4,$pig['pig_id']);
                }
            }

            if($pig_order  && $save_user ){
                //发送短信，
                $mess_open = config('mess_open');
                if($mess_open){
                    $purchase_mobile = Db::name('users')->where('user_id',$award_user_id)->value('mobile');
                    $sell_mobile = Db::name('users')->where('user_id',$pig['user_id'])->value('mobile');
                    $sms->sendJuHeSms(3,$purchase_mobile,1111);//抢购人
                    $sms->sendJuHeSms(4,$sell_mobile,1111);//出售人
                }


                if($award_user_id != $pig['user_id']){
                    $redis = new Redis();
                    $redis->sadd($this->game_award_list . $this->game_id,$award_user_id);
                }

                Db::commit();
                return ['status'=>1,'msg'=>'成功'];
            }else{
                Db::rollback();
                return ['status'=>103,'msg'=>'程序数据更新出错'];
            }
        }catch(\Exception $e){
            Db::rollback();
            trace($pig,'game');
            //trace($award_user_id,'game');
            trace($e->getmessage(),'game');

            return ['status'=>104,'msg'=>'创建订单失败'];
        }

    }

    //判断是否已经預約了--依赖setGameId
    public function isYuyue($user_id){
        $rs = Db::name('pig_reservation')->whereTime('reservation_time','today')->where(['user_id' => $user_id])->where('pig_id',$this->game_id)->column('pig_id');
        return !empty($rs) ? true : false;
    }

    /**
     * 获取订单 order_sn
     * @return string
     */
    public function get_order_sn()
    {
        $order_sn = null;
        // 保证不会有重复订单号存在
        while(true){
            $order_sn = date('YmdHis').rand(1000,9999); // 订单编号
            $order_sn_count = Db::name('pig_order')->where("pig_order_sn = ".$order_sn)->count();
            if($order_sn_count == 0)
                break;
        }
        return $order_sn;
    }



    public function updateGameStatus($status){
        $game_name = $this->game_name_pre . $this->game_id;
        Redis::set($game_name,$status);
    }

    //redis 获取场次状态--依赖setGameId
    public function getGameStatus(){
        $game_name = $this->game_name_pre . $this->game_id;
        return Redis::setnx($game_name,1,$this->game_name_expire_time);
    /*    $game_name = $this->game_name_pre . $this->game_id;
        $game = Redis::get($game_name);
        if($game){
            $json_game = json_decode($game);
            return $json_game['status'];
        }else{
            $game = Db::name('pig_goods')->
            $data['status'] = 0;//设置默认值
            $data['game_time'] = ;//设置游戏时间
            Redis::set($game_name,$status, $this->game_name_expire_time);
            return $status;
        }*/
    }


    /**
    *   开奖补偿机制
    *   当天一个小时后，若程序还未完全执行完毕，后台场次自动出现补偿开奖机制
    **/    
    public function compenStateOpenGame($game_id){

        //$this->flush_show('开奖程序将会在一分钟内重新自动启动,在启动过程中,请勿重复点击补偿开奖');
        $suoding = Redis::get('compen_suoding_'.$game_id,1) * 1;
        if($suoding > 0)
        {
            $this->flush_show('游戏正在补偿开奖，请稍后再试');
        }else{
            $this->flush_show('开奖程序将会在一分钟内重新自动启动,在启动过程中,请勿重复点击补偿开奖');
            $this->flush_show('请确保不要同时开奖两场,否则出现任何异常,概不负责');
            Redis::set('compen_suoding_'.$game_id,1,3600) ;
            Redis::lpush('reset_list',$game_id);
        }

    }

    //抢购处理函数
    //抢购处理函数
    public function flashBuyDo($game_id,$ii = 0){
        $_resdis = new Redis();
        $flashtodo =  $_resdis->get('suoding_'.$game_id) * 1;
        if($flashtodo > 0 && $ii == 0){
            return false;
        }

        $_resdis->set('suoding_'.$game_id,1);
        //记录PID
        $_resdis->set('process_game_'.$game_id,getmypid(),$this->expire_time());


        $this->setGameId($game_id);
        //获取所有参与的用户
        $pigbuy = new PigFlashBuy();
        $redis_users = [];
        $redis_users = $_tmp =$pigbuy->getUsers($this->game_id);
        $user_lists = $join_user_list = !$redis_users ? [] : array_column($redis_users,'user_id');
        $i = [];
        //trace($redis_users,'game');
        //获取当前游戏的模型
        //找到成熟的猪
        $pig_lists = Db::name('user_exclusive_pig')->where('pig_id',$this->game_id)->where(['is_able_sale'=>1,'pig_lock'=>0])->order('appoint_user_id desc')->limit(20)->select();

        //还有神兽需要处理的
        if(!empty($pig_lists)){

            foreach($pig_lists as $k =>$pig){
                if(count($user_lists) <= 0 || !$user_lists){
                    //供过于求， 继续走交易流程 --2019-1-14 16:50:48
                    //抢到的人生成订单
                    trace('场次:'.$game_id.'-进入继续繁殖'.$pig['id'],'game');

                    $this->createOrder($pig,$pig['user_id'],2);
                    continue;
                }else{
                    //是否有人被指定的
                    if($pig['appoint_user_id'] && in_array($pig['appoint_user_id'],$user_lists)){
                        foreach($user_lists as $k => $v){
                            if($v == $pig['appoint_user_id']){
                                unset($user_lists[$k]);
                            }
                        }
                        $award_user = $pig['appoint_user_id'];
                    }else{
                        $award_user = array_shift($user_lists);
                        //若果中奖人是自己，且中奖参与人数中还有人的话，那么将自己与下个人的位置调换
                        if($pig['user_id'] == $award_user && count($user_lists) > 0){
                            $position_user = $award_user;
                            $award_user = array_shift($user_lists);
                            array_unshift($user_lists,$position_user);
                        }
                    }

                    if(!empty($award_user)){
                        //抢到的人生成订单

                        $rs = $this->createOrder($pig,$award_user,1);
                        if($rs['status'] == 0){
                            array_unshift($user_lists,$award_user);
                            continue;
                        }elseif($rs['status'] == 103 ||$rs['status'] ==104 ){
                            //异常报告,出现此处有可能出现程序异常 或者 服务异常
                            //异常报告后此处，此人暂时不做处理
                            continue;
                        }


                        $i[] = $award_user;
                        trace('场次:'.$game_id.'-猪:'.$pig['id'].'-抢购人:'.$award_user,'game');

                    }
                }
            }
            if(!empty($i)){
                foreach($i as $k=>$v){
                    $_redis_users = $pigbuy->getUsers($this->game_id);
                    $key = array_search( $v,array_column($_redis_users, 'user_id'));
                    unset($_redis_users[$key]);
                    $pigbuy->resetFlashUser($_redis_users,$this->game_id);
                }
            }
            $php_root = $this->php_root ;
            exec("cd {$php_root};/www/server/php/70/bin/php think doflashbuy {$this->game_id} 1",$a,$b);

        }else{
            $_resdis->set('suoding_'.$this->game_id,0);
            //游戏结束
            $name = $pigbuy->getFlashName($this->game_id);
            $redis = new Redis();
            $redis->del($name);
            //开奖状态更新
            Db::name('pig_goods')->where(['id' =>$this->game_id])->update(['today_is_open' => 1]);
            //退預約积分-当天-场次
            $query = Db::name('pig_reservation')->where(['pig_id'=>$this->game_id]);

            $buy_pig_user_list = Redis::smembers($this->game_award_list . $this->game_id);
            if(count($buy_pig_user_list) > 0){
                $query->whereNotIn('user_id',$buy_pig_user_list);
            }
            $no_buy_pig = $query->whereTime('reservation_time','today')->select();
            $qianggou =  'qianggou_'.date('Ymd',time()).'_'.$this->game_id;

//            $qianggou_list = json_decode(Redis::lpop($qianggou),true);
            $qianggou_list = array_unique(Redis::lrange($qianggou));
            if($no_buy_pig){
                foreach($no_buy_pig as $key => $value){
                    if(in_array($value['user_id'],$qianggou_list)){
                        accountLog($value['user_id'],0,$value['pay_points'],'抢购失败,預約退回微分',0,0,0,4,$this->game_id);

                    }
                    //unset($user_lists[$value['user_id']]);
                }
            }

            $this->updateGameStatus(3);
            $join_u = Redis::get('users_list_queue_'.$this->game_id);

            

            $user_ls = $join_user_list = !$join_u ? [] : array_column($join_u,'user_id');
            //加入一些处理的记录-仅供查找处理
            $data_log['join_user_list'] = implode(',', !empty($user_ls) ?$user_ls:[] );
            $data_log['award_user_list'] = implode(',',!empty($buy_pig_user_list) ?$buy_pig_user_list:[] );
            $data_log['pig_list'] = implode(',',!empty($send_user) ?$send_user:[]);
            $data_log['pig_id'] = $this->game_id;
            $data_log['change_time'] = time();
            Db::name('pig_award_log')->insertGetId($data_log);
            //再次重复处理猪的状态，以免未处理成功
            //db('user_exclusive_pig')->where('id','in',$send_user)->update(['is_able_sale'=>0,'appoint_user_id'=>0]);
        }



    }

    //缓存直接输出
    public function flush_show($str){
            echo $str;
            echo str_pad('',4096)."\n";    
            ob_flush();
            flush();
            echo "<br/>";

    }

    //获取当天剩余的时间
    public function expire_time(){
        return strtotime(date("Y-m-d",strtotime("+1 day"))) - time();
    }

    /**
     * 用户宠物释放
     * @param  [type] $user_id  [description]
     * @param  [type] $game_id  [description]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function release_handle($user_id,$game_id,$order_id)
    {
        //实例化模型
        $user_exclusive_pig = Db::name('user_exclusive_pig');
        $pig_release = Db::name('pig_release');
        //释放值
        $release_nums = (int)Db::name('config')->where('name','release_nums')->value('value');
        //查看释放记录
        $release_log = $pig_release->where(['user_id'=>$user_id,'pig_level'=>$game_id,'release_status'=>0])->find();
        if (!$release_log) {
            //新增一次释放记录
            
            //查看是否有锁定宠物
            $lock_pig = $user_exclusive_pig->where(['user_id'=>$user_id,'pig_id'=>$game_id,'pig_lock'=>1])->value('id');
            if (!$lock_pig) return true;

            //释放数据
            $release_adds = array(
                'user_id'   =>  $user_id,
                'pig_level' =>  $game_id,
                'order_ids' =>  $order_id,
                'pig_id'    =>  $lock_pig,
                'release_nums'  =>  1
            );
            //是否满足释放
            if ($release_nums == 1) {
                $release_adds['release_status'] = 1;
                $release_adds['release_times'] = date('Y-m-d H:i:s');
                //解锁宠物
                $bolen1 = $user_exclusive_pig->where('id',$lock_pig)->update(['pig_lock'=>0,'is_able_sale'=>1]);
                if (!$bolen1) return false;
            }
            //添加
            $boolen2 = $pig_release->add($release_adds);
            if (!$boolen2) return false;
        } else {
            //更新释放记录

            //释放数据
            $update = array(
                'release_nums'  =>  $release_log['release_nums'] + 1,
                'order_ids'     =>  $release_log['order_ids'] . ',' . $order_id
            );
            //是否满足释放
            if ($update['release_nums'] >= $release_nums) {
                $update['release_times'] = date('Y-m-d H:i:s');
                $update['release_status'] = 1;
                //解锁宠物
                $bolen1 = Db::name('user_exclusive_pig')->where('id',$release_log['pig_id'])->update(['pig_lock'=>0,'is_able_sale'=>1]);
                if (!$bolen1) return false;
            }
            //更新
            $boolen2 = $pig_release->where('id',$release_log['id'])->update($update);
            if (!$boolen2) return false;
        }
        return true;
    } 




}