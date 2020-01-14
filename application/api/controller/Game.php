<?php
/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/12/11
 * Time: 10:16
 */

namespace app\api\controller;


use app\common\logic\PigFlashBuy;
use app\common\model\Users;
use My\DataReturn;
use redis\Redis;
use think\Db;
use app\common\logic\Game as GameLogic;

use app\common\controller\Recommend;

class Game extends Base
{
    public function __construct()
    {
        parent::__construct();

    }

    //暂时为测试
    public function text()
    {
        $data['id'] = 1;
        $user_info['user_id'] = 3;
        $qianggou =  'qianggou_'.date('Ymd',time()).'_'.$data['id'];
        Redis::lpush($qianggou,$user_info['user_id']);

//        $re = new Recommend();
//        $re->checkDestroyPig(8);
    }
    public function text2()
    {
        $game_id = 1;
        $qianggou =  'qianggou_'.date('Ymd',time()).'_'.$game_id;
//        $qianggou_list = json_decode(Redis::get($qianggou),true);
        $a = Redis::llen($qianggou);
        dump(array_unique(Redis::lrange($qianggou,0,$a)));

        //        $re = new Recommend();
        //        $re->checkDestroyPig(8);
    }


        //检查正在的游戏
    public function checkGame()
    {
        $game = new \app\common\logic\Game();
        $time = $game->excute_time();
        $now_game_time = strtotime($game->gaming_model['start_time']);
        //前端显示开奖剩余时间
        $plus_time = $game->excute_time() - $game->openaward;
        //id 游戏ID  time 游戏时间  openaward 开奖冷却时间
        DataReturn::returnJson(200, '', ['id' => $game->game_id, 'time' => $plus_time, 'openaward' => $game->openaward, 'cool_time' => $game->getCoolTime() + 1, 'now_game_time' => $now_game_time, 'stage' => $game->gameTimeArea($now_game_time)]);
    }

    //改变的游戏场次现状态
    public function changeGameStatus()
    {
        $data = I('data/a');
        $id = $data['id'];
        $game = new \app\common\logic\Game();
        $now_game_time = Db::name('pig_goods')->where(['id' => $id])->value('start_time');
        DataReturn::returnJson(200, '', ['stage' => $game->gameTimeArea($now_game_time)]);
    }

    //开奖查询
    public function checkOpen()
    {
        $input = input('');
        $data = $input['data'];
        $game_id = $data['id'];
        $user_id = $this->user_id;
        $status = Redis::get('game_name_pre' . $game_id); //游戏状态
        if ($status == 3) {
            $list = Redis::smembers('game_award_list_' . $game_id);
            $award_list = empty($list) ? [] : $list;
            if (in_array($user_id, $award_list)) {
                DataReturn::returnJson(200, '恭喜中奖');
            } else {
                DataReturn::returnJson(100, '很遗憾,没中奖');
            }

        } else {
            DataReturn::returnJson(201, '还没有开奖');
        }


        /*$rs = Db::name('pig_goods')->where('id',$game_id)->where('today_is_open',1)->find();
        if($rs){
            $order = Db::name('pig_order')->where('pig_level',$game_id)->whereTime('establish_time','today')->where('purchase_user_id',$user_id)->find();
            if($order){
                DataReturn::returnJson(200,'恭喜中奖');
            }else{
                DataReturn::returnJson(100,'很遗憾,没中奖');
            }

            DataReturn::returnJson(1,'开奖成功');
        }else{
            DataReturn::returnJson(201,'还没有开奖');
        }

        DataReturn::returnJson(1,'');*/
    }

    //获取已经預約了的数据
    public function isYuyueData()
    {
        $user_id = $this->user_id;
        $rs = Db::name('account_log')->whereTime('change_time', 'today')->where(['user_id' => $user_id])->where('type', 4)->column('pig_id');
//        dump($rs);exit;
        $data = !empty($rs) ? $rs : [];
        DataReturn::returnBase64Json(200, '成功', $data);

    }

    //預約
    public function yuyue()
    {
        //定点flash_buy_point
        $game_id = input('data.id');

        $game_info = Db::name('pig_goods')->where('id', $game_id)->find();
        if (!$game_info) {
            DataReturn::returnBase64Json(0, '游戏不存在');
        }

        $need_point = $game_info['reservation'];
        //判断余额是否足够
        $user = Users::get($this->user_id);
//        dump($config);exit;
        if ($user['pay_points'] < $need_point) {
            DataReturn::returnBase64Json(0, '微分不足,預約失败');
        }

        if (time() > strtotime($game_info['start_time'])) {
            DataReturn::returnBase64Json(0, '今天已过預約时间,預約失败');
        }

        //今天场次是否已預約
        $game = new GameLogic();
        $game->setGameId($game_id);
        $is_yy = $game->isYuyue($this->user_id);
        if ($is_yy) {
            DataReturn::returnBase64Json(0, '今天已经預約了！');
        }

        $value = Db::name('pig_goods')->where('id', $game_id)->value('reservation');
        //流水记录
        $rs = accountLog($this->user_id, 0, "-{$value}", '預約消费微分', 0, 0, 0, 4, $game_id);
        //預約記錄
        Db::name('pig_reservation')->insert(['reservation_time' => time(), 'pig_id' => $game_id, 'user_id' => $this->user_id, 'pay_points' => $value]);

        if ($rs) {
            DataReturn::returnBase64Json(1, '預約成功');
        } else {
            DataReturn::returnBase64Json(0, '預約失败了');
        }
    }

    //开抢触发
    public function flash_buy()
    {

        try {
            $input = input('');
            $data = $input['data'];

            $user_info = Db::name('users')->where(['user_id' => $this->user_id])->find();

            if ($user_info['real_name'] && $user_info['identity']) {
            } else {
                DataReturn::returnBase64Json(-1, '请先實名認證', []);
            }

            //DataReturn::returnBase64Json(0,'微分不足,抢购失败啊啊啊');
            $check = $this->validate($data, 'PigGoods.redis_id');
            if ($check !== true) {
                DataReturn::returnBase64Json(0, $check);
            }

            $game_id = $data['id'];
            $redis_status = Redis::get('game_name_pre' . $game_id); //游戏状态
            $n_game_a = new \app\common\logic\Game();

            if ($redis_status == 0 && time() < $n_game_a->getGameOpenTime($game_id)) {
                $user = session('user');
                $user_info = Users::get($user['user_id']);
                $pigflashbuy = new PigFlashBuy();
                // $data = input('data');
                //dump(input(''));exit;


                //預約不需要微分
                $game = new \app\common\logic\Game();
                $game->setGameId($data['id']);
                $is_yuyue = $game->isYuyue($this->user_id);

                if (!$is_yuyue) {
                    //$config = config('flash_buy_point');
                    $config = Db::name('pig_goods')->where('id', $data['id'])->find();

                    $adoptive_energy = $config['adoptive_energy'];
                    //判断余额是否足够
                    $user = Users::get($this->user_id);
                    //        dump($config);exit;
                    if ($user['pay_points'] < $adoptive_energy) {
                        DataReturn::returnBase64Json(0, '微分不足,抢购失败');
                    }
                    //修改成抢购成功才消耗
                    //$desc = sprintf('抢购%s消耗微分',$config['goods_name']);
                    //扣除微分
                    // accountLog($this->user_id,0,-"{$adoptive_energy}",$desc,0,0,0,4,$data['id']);
                }


                //判断种子够不够1
                //if($user_info[''])
                $add_lists['user_id'] = $user_info['user_id'];
                $add_lists['sort'] = $user_info['rule_sort'];
                if ($add_lists['sort'] !== 0) { //2019/4/25 修复排单

                    //trace($add_lists,'notice');
                    $pigflashbuy->setFlashName($data['id']);
                    $qianggou =  'qianggou_'.date('Ymd',time()).'_'.$data['id'];
                    Redis::lpush($qianggou,$user_info['user_id']);
                    $redis = $pigflashbuy->addFlashRedis($add_lists);

                    if (!$redis) {
                        DataReturn::returnBase64Json(0, '加入redis失败了');
                    }
                    //是否可以加入
                    //判断场次是否存在
                    DataReturn::returnBase64Json(1, '成功');

                } else {

                    DataReturn::returnBase64Json(0, '加入redis失败了');

                }
            } else {
                //开奖处理
                DataReturn::returnBase64Json(0, '已经执行开奖了');
            }
        } catch (\Exception $e) {
            return response()->create('', '', 502);
        }


    }



}