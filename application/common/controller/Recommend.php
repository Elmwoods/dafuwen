<?php


namespace app\common\controller;

use app\common\model\Users;
use app\common\logic\Game;
use redis\Redis;
use think\Db;


class Recommend{
    protected static $user_lists;
    //定时执行
    public static function run(){
        $lists_pigs = Db::name('pig_order')->where('pay_status',1)->column('pig_id');
        $redis_lists_pigs = [];
        $redis_lists_pigs = Redis::smembers('recommend_lists_pigs');
        $result = db('user_exclusive_pig')->where(['is_able_sale'=>0])->whereNotIn('id',$lists_pigs)->whereNotIn('id',$redis_lists_pigs)->select();
        // $result = db('user_exclusive_pig')->where(['is_able_sale'=>0])->select();
//        dump($result);exit;
        foreach($result as $key => $item){
            //dump($item);
            //只能合約收益
            echo "-----------".$item['id']."------------<br/>";
            $money = self::earnings($item);
            echo "智能合约产生收益:".$money."<br/>";
            if($money){
                //智能合約收益产生团队奖
                self::recommonToTeam([['user_id'=>$item['user_id'],'money'=>$money]]);

                //处理推荐奖
                $tuiguangjiang = self::recommendAward($item['user_id'],$money);
                echo serialize($tuiguangjiang);
                //推荐奖产生团队收益
                if(!empty($tuiguangjiang)){
                    self::recommonToTeam($tuiguangjiang);
                }
            }

            //过期时间
            $s_time=strtotime(date('Y-m-d',time()));
            $e_time = strtotime('+1 day',$s_time) - 7200 - time();
            if(!in_array($item['id'],$redis_lists_pigs) && $e_time >10){
                Redis::sadd('recommend_lists_pigs',$item['id'],$e_time);
            }
            //sleep(2);
        }

    }

    //每天改变可抢购状态(定时执行)
    public static function run_goods(){
        $re =db('pig_goods')->field('id')->select();
        if($re){
            $aid = array_column($re,'id');
            db('pig_goods')->where('id','in',$aid)->update(['today_is_open'=> 0,'is_lock'=>0,'game_reset_time'=>time()]);
            foreach($re as $game){
                Redis::del('game_award_list_'.$game['id']); //中奖人
                Redis::del('game_name_pre'.$game['id']); //游戏状态
                Redis::del('flash_buy_'.date('Ymd',time()).'_'.$game['id']);
                Redis::del('suoding_'.$game['id']);
            }
        }
    }

    //强制处理交易(一直执行)
    public static function run_pig_order(){
        $time = time();
        $res = db('pig_order')->where(['pay_status' => 1])->where('end_time' ,'<', $time)->select();//线上打开
        if($res){
            foreach($res as $value){ //更新物品所属人
                trace(json_encode($value),'jiaoyi');
                $to_pig_user_id = empty($value['img_url']) ? $value['sell_user_id'] :  $value['purchase_user_id'];

                //付款方未上传二维码则被封号  自己的神兽自己抢不用被封号 2019/5/20 -li
                // if(empty($value['img_url']) && $value['sell_user_id'] !== $value['purchase_user_id']){
                //    db('users')->where(['user_id' =>$value['purchase_user_id']])->update(['is_lock'=>1]);
                //    Db::name('user_payment')->where(['user_id'=>$value['purchase_user_id']])->save(['status'=>1]);
                //    $data['user_id']    = $value['purchase_user_id'];
                //    $data['lock_status']= 1;
                //    $data['lock_type']  = 1;
                //    $data['lock_time']  = time();
                //    db('user_lock_log') ->add($data); //操作记录
                // }
                self::orderTrace($value,$to_pig_user_id,'force');
            }
        }
    }

 
    //智能合約收益
    public static function earnings($item){

         $earnings =0;


            $pig_goods = db('pig_goods')->where(['id' => $item['pig_id']])->field('contract_days, income_ratio, doge_money, pig_currency')->find();

            if($item['end_time'] >time()){
                $pig_order_sn = db('pig_order')->where(['pig_id' => $item['id']])->value('pig_order_sn');
                //合约到期时间大于当天时间才计算收益，到期就不再计算收益

                $earnings = $item['price'] * (($pig_goods['income_ratio'] * 0.01) / $pig_goods['contract_days']);//收益
                $doge_money = $earnings * ($pig_goods['doge_money'] * 0.01);
                $data['user_money'] = $earnings;
    
                $data['doge_money'] = $doge_money; //doge币

                if( $data['doge_money'] && !empty($item['user_id'])){
                    // $dog['doge_money'] = $data['doge_money'] ;
                    // $dog['order_sn']   = $pig_order_sn;
                    // $dog['add_time']   = time();
                    // $dog['desc'] = '增加狗狗币';
                    // $dog['type'] = 2;
                    // $dog['user_id'] = $item['user_id'];
                    // db('pig_doge_money')->add($dog);
                    $account_log=array('user_id'=>$item['user_id'],'doge_money'=>$data['doge_money'],'change_time'=>time(),'desc'=>'增加狗狗币','type'=>22,'order_sn'=>$pig_order_sn);
                    M('account_log')->add($account_log);
                }

                //给users表用户增加猪猪币
                Db::name('users')->where('user_id',$item['user_id'])->update([
                    'doge_money' => [
                        'exp',
                        'doge_money+'.$data['doge_money']
                    ]
                ]);

                if ($earnings) {
                    //记录日志并且添加用户资金
                    // self::writeLog('智能合約收益', $item['user_id'], $earnings,3, $pig_order_sn);

                    //资金流日志 2019/5/13
                    $account_log=array('user_id'=>$item['user_id'],'contract_revenue'=>$earnings,'change_time'=>time(),'desc'=>'智能合約收益','type'=>21,'order_sn'=>$pig_order_sn);
                    M('account_log')->add($account_log);
                }
            }

            if(date("Y-m-d",$item['end_time']) == date("Y-m-d",time())){ //合约期到期改变物品的价格和等级
                $_pig_new_money =  $item['price'] + ($item['price'] * ($pig_goods['income_ratio'] * 0.01));
                //截取
                $pig_new_money = sprintf('%.2f',$_pig_new_money);

                $max_large_price = Db::name('pig_goods')->where(['is_display'=>1])->max('large_price');
                if($pig_new_money >= $max_large_price){ //当前猪的價值超过或者等于最大值
                    $pig_level = Db::name('pig_goods')->where(['large_price'=>$max_large_price])->value('id'); //取当前最高等级
                }else{
                    $pig_level=Db::name('pig_goods')->where('small_price', '<=', $pig_new_money)
                        ->where(['is_display'=>1])
                        ->where('large_price', '>=', $pig_new_money)->value('id');
                }

                $datas['price'] = $pig_new_money;
                $datas['pig_id'] = $pig_level;
                $datas['is_able_sale'] = 1;
                db('user_exclusive_pig')->where(['id' => $item['id']])->update($datas);
                
                //lian 2019-3-5号创建判断合约期满后此狗是否达到销毁条件
                 $large_price = Db::name('pig_goods')->where(['is_display'=>1])->max('large_price');
                    $fission_price = config('fission_price');
                    if($pig_new_money >= $fission_price){
                        self::checkDestroyPig($item['id']);
                    }elseif($pig_new_money>=$large_price){
                        self::checkDestroyPig($item['id']);
                    }

                //lian 2019-3-5号创建判断合约期满后此狗是否达到销毁条件
            
            }
//        }
        return $earnings;
    }



    //推荐奖
    public static function recommendAward($user_id,$money){
//        dump($user_id);
//        dump($money);
        $data = [];
//        $sub_user_list = db('users')->where(['user_id' => $user_id])->field('first_leader, second_leader, third_leader')->find(); //查找出上级id
        $first_leader = db('users')->where(['user_id' => $user_id])->value('first_leader'); //查找出上级id
        $sub_user_list['first_leader'] = $first_leader;
        if($first_leader){
            $second_leadert = db('users')->where(['user_id' => $first_leader])->value('first_leader');
            $sub_user_list['second_leader'] = $second_leadert;
        }
        if(!empty($second_leadert)){
            $third_leader = db('users')->where(['user_id' => $second_leadert])->value('first_leader');
            $sub_user_list['third_leader'] = $third_leader;
        }

//        getLastSql();
//        dump($sub_user_list);exit;
        // $recommend_award = config('recommend_award'); //获取推荐奖返佣比例
         $recommend_award = self::zhituiProtion(); //获取推荐奖返佣比例
        if($sub_user_list['first_leader']){ //第一级
            $first_leader= $recommend_award['first_leader'] * 0.01;
            $_money = $first_leader * $money;
            if ($_money) {
                //记录日志并且添加用户资金
//                self::writeLog('推荐奖', $sub_user_list['first_leader'], $_money,0,0,8,0,0,0);
                accountLog($sub_user_list['first_leader'],$_money,0,'推荐奖',0,0,0,13);
                $arr['money'] = $_money;
                $arr['user_id'] = $sub_user_list['first_leader'];
                $data[] = $arr;
            }

        }


        if (!empty($sub_user_list['second_leader'])){ //第二级
            $second_leader= $recommend_award['second_leader'] * 0.01;
            $_money = $second_leader * $money;
            if ($_money) {
                //记录日志并且添加用户资金
//                self::writeLog('推荐奖', $sub_user_list['second_leader'], $_money,0,0,8,0,0,0);
                accountLog($sub_user_list['second_leader'],$_money,0,'推荐奖',0,0,0,13);
                $arr['money'] = $_money;
                $arr['user_id'] = $sub_user_list['second_leader'];
                $data[] = $arr;
            }

        }



        if (!empty($sub_user_list['third_leader'])){ //第三级
            $third_leader= $recommend_award['third_leader']* 0.01;
            $_money = $third_leader * $money;
            if ($_money) {
                //记录日志并且添加用户资金
//                self::writeLog('推荐奖', $sub_user_list['third_leader'], $_money,0,0,8,0,0,0);
                accountLog($sub_user_list['third_leader'],$_money,0,'推荐奖',0,0,0,13);
                $arr['money'] = $_money;
                $arr['user_id'] = $sub_user_list['third_leader'];
                $data[] = $arr;
            }
        }
        return $data;
    }


    //因推荐奖产生的团队奖触发控制器 --lists 奖项列表
    //lists = [['user_id'=>1,'money'=>1]]
    public static function recommonToTeam($lists){
        self::$user_lists = self::userListLine($lists[0]['user_id']);
        while(!empty($lists) && count($lists) > 0){
            $to_do_user = array_pop($lists);
            self::getUserPoint($to_do_user['user_id']);
            echo "-------------处理的U:".$to_do_user['user_id'] . "---处理的金额:".$to_do_user['money'];
            $new_lists = self::teamPrize($to_do_user['money']);

            $lists = array_merge($lists,$new_lists);
            echo "----------------------<br/>";
        }
    }

    protected static function teamPrize($money,$data = [],$already_level = []){
        //定位用户链
        $level = current(self::$user_lists);
        $user_id = key(self::$user_lists);
        $_arr = [];
        if($money > 0.01 && !!$level){

            if($level >= 3 && !in_array($level,$already_level)){

                //给这个人分成
                $earn = self::bili($level) * $money;
                echo "<br/>user_id:".$user_id . "金额:".$earn."<br/>";

                $_arr['user_id'] = $user_id;
                $_arr['money'] = $earn;
                $data[] = $_arr;
                //插入分成记录
                accountLog($user_id,$earn,0,'团队奖产生收益',0,0,0,12);
                array_push($already_level,$level);
                next(self::$user_lists);
                return self::teamPrize($money,$data,$already_level);
            }else{
                next(self::$user_lists);
                return self::teamPrize($money,$data,$already_level);
            }
        }else{
            reset(self::$user_lists);
            return $data;
        }

    }

    public static function bili($level){
        $pro = Db::name('user_level')->where('level_id',$level)->value('team_award');
        return $pro * 0.01;
    }

    //根据用户等级，获取对应的分销
    public static function zhituiProtion(){
        //['first_leader'=>50,'second_leader'=>20,'third_leader'=>10]
        //$level_data = Db::name('user_level')->where('level_id',$level_id)->find();
        $protion['first_leader'] = config('first_rate');
        $protion['second_leader'] = config('second_rate');
        $protion['third_leader'] = config('third_rate');
        return $protion;
    }

    //获取对应分奖的队列
    public static function getUserPoint($user_id){
        while( list($key,$value) = each(self::$user_lists) )
        {
            if($key == $user_id){
                //prev(self::$user_lists);
                break;
            }
        }
    }


    //用户升级
    public static function up_level($user_id){
        $user = db('users')->where(['user_id' => $user_id])->field('level,pay_points')->find();
        // $pay_points = db('recharge')->where(['user_id' => $user_id])->where(['pay_status' => 1])->sum('account');
        if($user['level'] == 1){ //升级为会员
            $upgrade_power_number = config('upgrade_power_number'); //获取升级配置
            $pay_points_level1 = $user['pay_points'];
            if($pay_points_level1 >= $upgrade_power_number){
                db('users')->where(['user_id' => $user_id])->update(['level' => 2]);
            }
        }elseif ($user['level'] == 2){ //升级初级合伙人
            $first_leader_num = db('users')->where(['first_leader' => $user_id])->count();
            $primary_condition = config('primary_condition'); //获取升级配置
            if($first_leader_num >= $primary_condition['num']  && $user['pay_points'] >= $primary_condition['integral']){
                db('users')->where(['user_id' => $user_id])->update(['level' => 3]);
            }
        }elseif ($user['level'] == 3){ //升级中级合伙人
            $first_leader_num = db('users')->where(['first_leader' => $user_id])->count();
            $intermediate_conditions = config('intermediate_conditions'); //获取升级配置
            if($first_leader_num  >= $intermediate_conditions['num']  && $user['pay_points'] >= $intermediate_conditions['integral']){
                db('users')->where(['user_id' => $user_id])->update(['level' => 4]);
            }
        }elseif ($user['level'] == 4){ //升级高级合伙人
            $first_leader_num = db('users')->where(['first_leader' => $user_id])->count();
            $advanced_conditions = config('advanced_conditions'); //获取升级配置
            if($first_leader_num >= $advanced_conditions['num']  && $user['pay_points'] >= $advanced_conditions['integral']){
                db('users')->where(['user_id' => $user_id])->update(['level' => 5]);
            }
        }
    }

    /**
     * 增加用户资金+积分并且记录日志
     *
     * @param string $desc       记录备注
     * @param int    $user_id    用户id
     * @param double $user_money 增加额度数量
     *
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function writeLog($desc, $user_id, $user_money,$type ,$pig_order_sn) {

        //日志记录
        $data = [
            'user_id'     => $user_id,
            'contract_revenue'  => $user_money,
            'add_time' => time(),
            'type'        => $type,
            'order_sn'        => $pig_order_sn,
            'desc'        => $desc,

        ];
        Db::name('pig_doge_money')->insert($data);
    }


    //上级链条以及等级
    public static function userListLine($user_id,$data = []){
        $user_info = Users::get($user_id);
        $arr = [];
        if(!!$user_info){
            $data[$user_id] = $user_info['level'];
            //$_data = array_merge($data,$arr);
            //array_push($data,$arr);
            return self::userListLine($user_info['first_leader'],$data);
        }
        return $data;
    }


    //查看符合销毁的pig
    public static  function  checkDestroyPig($id){
         $id=intval($id);
//         $large_price = Db::name('pig_goods')->where('is_display',1)->max('large_price'); //查出pig最大价格区间
         $pigres = Db::name('user_exclusive_pig')->where(['id'=>$id])->find(); //查看这条購置記錄
         if(!$pigres){
            return;
         }

//         if($pigres["price"]>=$large_price){  //如果此拥有的pig大于最大区间，就进行销毁并分配订单
              $configres = Db::name("Config")->where(array('name'=>'destroycopy'))->field('value')->find(); //找出配置裂变为多少个
              $num= intval(trim($configres["value"]));
              if($num<2){
                 $num=2;
              }
              $mean_price= $pigres["price"]/$num;
            
              $pig_goods = Db::name('pig_goods')
                    ->where('small_price', '<=', $mean_price)
                    ->where('large_price', '>=', $mean_price)
                    ->find();

              if(!$pig_goods){   
                 $min_price = Db::name('pig_goods')->min('small_price'); //查出pig最小价格区间
                 $pig_goods = Db::name('pig_goods')
                    ->where('small_price', '=', $min_price)
                    ->find();
               }  
            //启动事务 
            Db::startTrans();
            try{
             for($i=1;$i<=$num;$i++){
                $data = [];
                $data['user_id']    = $pigres["user_id"];
                $data['pig_id']     = $pig_goods['id'];
                $data['price']      = $mean_price;
                $data['buy_time']   = time();
                // $data['end_time']   = time()+24*3600*$pig_goods['contract_days'];
                $data['end_time']   = time();
                $data['is_able_sale']   = 1;
                $pig_id = Db::name('user_exclusive_pig')->insertGetId($data);
                //生成系统订单
                $game = new Game();
                $pig_order_sn = $game->get_order_sn();
                $order = [];
                $order['establish_time']   = time();
                $order['pig_order_sn']     = $pig_order_sn;
                $order['pay_status']       = 2;
                $order['sell_user_id']     = 0;//系统
                $order['purchase_user_id'] = $pigres["user_id"];
                $order['pig_level']        = $pig_goods['id'];
                $order['pig_price']        = $mean_price;
                $order['pig_id']           = $pig_id; //猪所属用户的ID
                $order['end_time']         = time();
                $order_id = Db::name('pig_order')->insertGetId($order);

                
                $buy_type='Destroyauto';
                $res=new \app\api\controller\Addsalt();
                $pig_salt=$res->pigaddsalt($data['user_id'],$order_id,$data['buy_time'],$buy_type);

                //更新目前的用户所属猪的对应的Order_id    
                Db::name('user_exclusive_pig')->where(['id'=>$pig_id])->save(['order_id'=>$order_id,'buy_type'=>$buy_type,'pig_salt'=>$pig_salt]);
                
                //记进记录表
                $data["delid"]=$pig_id;
                $data["type"]="autoadd";
                $data["deltime"]=time();
                $data["order_id"]=$order_id;
                $logres = Db::name('user_exclusive_pig_del')->insertGetId($data);

             }
                //销毁记录
                $res = Db::name('user_exclusive_pig')->delete($id);
                //记进记录表  状态为销毁
                $del=[];
                $del["delid"]=$id;
                $del["user_id"]=$pigres["user_id"];
                $del["order_id"]=$pigres["order_id"];
                $del["pig_id"]=$pigres["pig_id"];
                $del["is_able_sale"]=$pigres["is_able_sale"];
                $del["price"]=$pigres["price"];
                $del["from_user_id"]=$pigres["from_user_id"];
                $del["appoint_user_id"]=$pigres["appoint_user_id"];
                $del["buy_time"]=$pigres["buy_time"];
                $del["end_time"]=$pigres["end_time"];
                $del["type"]="del";
                $del["deltime"]=time();
                $logres = Db::name('user_exclusive_pig_del')->insertGetId($del);
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                
            }
//         }

    }


    /**
    *order 订单模型
    *to_pig_user_id 分配给的用户
    *buy_type 分配类型
    **/
    public static function orderTrace($order, $to_pig_user_id, $buy_type) {

        $order_id = $order['order_id'];
        $is_to_pig = 1 ;
        //交易过程中,获得猪的人和出售猪都是自己,那么该猪继续可出售状态,并且购入时间和收益结束时间不变
        //自己得到自己的不能够获得猪猪币
        if($order['sell_user_id'] == $to_pig_user_id){
            $is_able_sale = 1;
            $is_to_pig = 0;
            $buy_time = Db::name('user_exclusive_pig')->where(['id'=>$order['pig_id']])->value('buy_time');
            
            // //付款方未上传二维码则被封号  自己的神兽自己抢不用被封号 2019/5/20 -li
            // if(empty($order['img_url'])){
            //    db('users')->where(['user_id' =>$order['purchase_user_id']])->update(['is_lock'=>1]); 
            // }

        }else{
            $contract_days = db('pig_goods') ->where(['id' =>$order['pig_level']]) ->value('contract_days');
            $end_time = $contract_days * (3600*24);
            $is_able_sale = 0;
            $buy_time = time(); //开始时间
            $data['end_time'] = time() + $end_time; //结束时间
            

            if ($buy_type == 'force') {
                //释放宠物
                $game = new Game();
                $game->release_handle($to_pig_user_id,$order['pig_level'],$order_id);
            }


            // //付款方未上传二维码则被封号  自己的神兽自己抢不用被封号 2019/5/20 -li
            // if(empty($order['img_url'])){
            //    db('users')->where(['user_id' =>$order['purchase_user_id']])->update(['is_lock'=>1]); 
            // }
        }

        $data['buy_time'] = $buy_time; //开始时间
        $data['user_id'] = $to_pig_user_id;
        $data['is_able_sale'] = $is_able_sale;
        $data['order_id'] = $order_id;
        $data['from_user_id'] = $order['sell_user_id'];
        $data['buy_type'] = $buy_type;


        $res = new\app\api\controller\Addsalt();
        $data['pig_salt'] = $res->pigaddsalt($data['user_id'], $data['order_id'], $buy_time, $data['buy_type']);

        Db::startTrans();

        $r1 = db('user_exclusive_pig')->where(['id' =>$order['pig_id']])->update($data);
        $r2 = db('pig_order')->where(['order_id' =>$order['order_id']])->update(['pay_status' =>2]);
        $r3 = true;
        if($is_to_pig){
            //交易完成增加猪猪币
            $pig_currency = db('pig_goods')->where(['id' =>$order['pig_level']])->value('pig_currency');
            $pig['pig_currency'] = $pig_currency;
            $pig['change_time'] = time();
            $pig['desc'] = '增加HKT币';
            $pig['type'] = 23;
            $pig['user_id'] = $order['purchase_user_id'];
            $pig['order_sn'] = $order['pig_order_sn'];
            db('account_log') ->add($pig);
              //给users表用户增加猪猪币
            $r3 = Db::name('users') ->where('user_id', $order['purchase_user_id'])->update(['pig_currency' =>['exp', 'pig_currency+'.$pig_currency]]);
        }

         if($r1 && $r2 && $r3){
                echo "强制交易执行成功";
                Db::commit();
                return true;
            }else{
                echo "强制交易执行失败";
                Db::rollback();
                return false;
         }
    }

}