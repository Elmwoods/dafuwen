<?php
/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/11/14
 * Time: 17:14
 */
namespace app\api\Controller;
use app\common\model\Users;
use app\common\logic\PigFlashBuy;
use app\common\logic\Game;
use GatewayWorker\Gateway;
use My\DataReturn;
use redis\Redis;
use think\Controller;
use app\common\Controller\Recommend;
use app\common\controller\BaseComm;
use think\Db;

class Test extends Controller{

    public function aaa(){
        $aa = db('pig_order')->where(['pay_status' => 1])->whereTime('establish_time' ,'today')->select();//线上打开
        echo db('pig_order')->getlastsql();
    }
    // public function user_add_flash(){
    //     if(request()->isPost()){
    //         $user_id = input('user_id');
    //         $game_id = input('id');
    //         //获取用户的信息
    //         $user = Users::get($user_id);

    //         $pig = new PigFlashBuy();
    //         $pig->setFlashName($game_id);
    //         $pig->addFlashRedis(['user_id'=>$user_id,'sort'=>$user['rule_sort']]);
    //         $join_lists = $pig->getUsers($game_id);
    //         dump($join_lists);

    //     }
    //     return $this->fetch();
    // }


    // public function openGame(){
    //     $id = input('id');
    //     $game = new Game();
    //     $game->setGameId($id);
    //     $game->flashBuy();
    // }


    // //模拟登陆
    // public function login_demo()
    // {
    //     //$data = DataReturn::baseFormat(input('data'));

    //     //返回处理的session_id
    //     $session['user_id'] = 1;
    //     $session['expire_time'] = time() + C('session.expire');
    //     session('user',$session);
    //     DataReturn::returnBase64Json(200,'登录成功');
    // }


    //redis 队列入
    // public function flash_buy(){

    //     $user = session('user');
    //     $user_info = Users::get($user['user_id']);
    //    // $data = input('data');
    //     //dump(input(''));exit;
    //     $input = input('');
    //     $data = $input['data'];
    //     $check = $this->validate($data,'PigGoods.redis_id');
    //     if($check !== true){
    //         DataReturn::returnBase64Json(0,$check);
    //     }

    //     //判断种子够不够
    //     //if($user_info[''])
    //     $this->addFlashRedis();
    //     //是否可以加入
    //     //判断场次是否存在
    //     DataReturn::returnBase64Json(1,'成功');

    // }


    // function addFlashRedis($data){
    //     $level = input('id');
    //     $redis_flash_name =  'flash_buy_'.date('Ymd',time()).'_'.$level;
    //     $redis = new Redis();
    //     //trace('redis:'.$redis_flash_name,'game');
    //     //$redis_flash_value = $redis->get($redis_flash_name);
    //     $num = $redis->llen($redis_flash_name);
    //     //trace('num:'.$num,'game');
    //     if(!$num){
    //         $add_data = $data; //新建数组
    //     }else{
    //         $flash_redis = json_decode($redis->get($redis_flash_name));
    //         $add_data = array_merge($flash_redis,$data);
    //     }

    //     //trace($add_data,'game');

    //     //做加入抢购的redis里面
    //     return $redis->set($redis_flash_name,json_encode($add_data));
    // }


    // public function iii(){
    //     return ;
    //    $data = Db::name('users')->select();
    //    foreach($data as $k =>$v){
    //        accountLog($v['user_id'],0,300,'系统补偿',0,0,0,20);
    //    }
    // }


    // 定时开奖
    public function crontab(){
        $gamer = new Game();
        $gamer->openGame();
    }

}