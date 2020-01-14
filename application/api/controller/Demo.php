<?php
//测试控制器
namespace app\api\controller;
use app\common\model\Users;
use My\DataReturn;
use redis\Redis;
use think\Controller;
use app\common\controller\Recommend;
use app\common\controller\BaseComm;
use app\common\logic\Game;
use think\Db;
class Demo{

    public function test2(){
        init_config();//初始配置表数据
        Recommend::run();
//        $result = db('user_exclusive_pig')->field('user_id')->select();
//        //        dump($result);dump($res);exit;
//        $res = 1;
//       de($result);

//        $d['time'] = time();
//        $d['center'] = '测试';
//        db('test')->add($d);
    }
    public function test3(){  //强制交易执行
        Recommend::run_pig_order();
        //        $result = db('user_exclusive_pig')->field('user_id')->select();
        //        //        dump($result);dump($res);exit;
        //        $res = 1;
        //       de($result);

        //        $d['time'] = time();
        //        $d['center'] = '测试';
        //        db('test')->add($d);
    }
    public function test4(){  //重置游戏 改变可以抢状态
        Recommend::run_goods();
        //        $result = db('user_exclusive_pig')->field('user_id')->select();
        //        //        dump($result);dump($res);exit;
        //        $res = 1;
        //       de($result);

        //        $d['time'] = time();
        //        $d['center'] = '测试';
        //        db('test')->add($d);
    }
    //需要检查登录的页面
    public function test()
    {


        parent::__construct();
        //不需验证登录的方法
        $nologin = ['recommendAward'];
        if(!in_array(ACTION_NAME,$nologin))
        {
//            $this->checkLogin();
        }
        $logic = new Game();
//        $a = $logic->flashBuy();
//        $a = $logic->excute_time();
//        dump($a);exit;
//          Recommend::recommendAward(3);
//          Recommend::time();
//          Recommend::time();
//          Recommend::earnings(1);
//            Recommend::teamAward(10,100);
//          Recommend::up_member(1);
//          Recommend::up_primary(1);
//          Recommend::up_intermediate(1);
//          Recommend::up_advanced(1);
    }

    public function test1(){
        $user_id = 10;
        $earnings = 100;

        $earnings_lists = Recommend::recommendAward($user_id,$earnings);
        Recommend::recommonToTeam($earnings_lists);
    }


    //收益￥￥￥
    public function earnings(){
        $user_id = 16;
         $pig_list = db('pig_order')->where(['user_id' => $user_id])->select(); //找出购买的
        dump($pig_list);
         foreach($pig_list as $value){
             if($value['pig_id']){
                $pig_goods = db('pig_goods')->where(['id' => $value['pig_id']])->field('contract_days, income_ratio, doge_money, pig_currency')->find();
                $earnings = $value['pig_price'] * (($pig_goods['income_ratio'] * 0.01) / $pig_goods['contract_days']);//收益
                $doge_money = $earnings * (($pig_goods['doge_money'] * 0.01) /$pig_goods['contract_days'] );
                $data['user_money'] = $earnings;
                $data['pig_currency'] = $pig_goods['pig_currency']; //pig币
                $data['doge_money'] = $doge_money; //doge币
//                dump($pig_list);
                //dump($data);
                //dump($value['pig_price']);
               // dump(($pig_goods['income_ratio'] * 0.01) / $pig_goods['contract_days']);
//                dump(($pig_goods['income_ratio'] * 0.01));
                //$res = db('users')->where(['user_id' => $user_id])->update($data);



             }

         }
    }

}