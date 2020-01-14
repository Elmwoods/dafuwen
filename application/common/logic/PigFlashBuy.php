<?php
/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/11/26
 * Time: 13:43
 */

namespace app\common\logic;


use redis\Redis;

class PigFlashBuy
{
    //加入redis抢购名
    public $redis_flash_name ;
    //protected $redis ;
    function __construct()
    {
        //$this->redis = new Redis();
    }


    //抢购算法
    public function open(){

    }

    //设置当前的抢购场次
    public function setFlashName($id){
        $redis_flash_name =  'flash_buy_'.date('Ymd',time()).'_'.$id;
        $this->redis_flash_name = $redis_flash_name;
    }
    //设置当前的抢购场次
    public function getFlashName($id){
        $redis_flash_name =  'flash_buy_'.date('Ymd',time()).'_'.$id;
        return $redis_flash_name;
    }
    //获取某场次的队列
    public function getUsers($id){
        $redis_flash_name =  'flash_buy_'.date('Ymd',time()).'_'.$id;
        $redis_flash_name_list =  'flash_buy_'.date('Ymd',time()).'_'.$id.'_list';
        $list_num = Redis::llen($redis_flash_name_list);
        //修复排序倒序的问题2019年7月18日14:13:573
        $user_lists = !empty(json_decode(Redis::get($redis_flash_name),true)) ? json_decode(Redis::get($redis_flash_name),true): [];

        if($list_num > 0){
                $tmp_user_lists = [];
                for($i = 0 ;$i< $list_num ; $i++){
                    array_unshift($tmp_user_lists,json_decode(Redis::lpop($redis_flash_name_list),true));
                }

                $user_lists = array_merge($user_lists,$tmp_user_lists);
                Redis::set($redis_flash_name,json_encode($user_lists));
        }

        //过滤重复的
        $arr = [];
        if(!empty($user_lists)){
            foreach($user_lists as $key => $value){
                if(in_array($value['user_id'],$arr)){
                    unset($user_lists[$key]);
                }else{
                    $arr[] = $value['user_id'];
                }
            }

            $reload_user_lists = [];
            //重装顺序
            foreach ($user_lists as $key => $value) {
                if(!in_array($value['sort'],array_keys($reload_user_lists))){
                    $reload_user_lists[$value['sort']] = [$value];
                }else{
                    array_push($reload_user_lists[$value['sort']],$value);
                }
            }

            //重新根据key排序
            krsort($reload_user_lists);

            //重新合并二位数组
            return $this->sort_array($reload_user_lists);
            //排序
        }
        return $user_lists;
    }

    function sort_array($array){
        return array_reduce($array, function ($result, $value) {
            return array_merge($result, array_values($value));
        }, array());
    }

    //加入抢购队列..依赖setFlashName
    public function addFlashRedis($data){
        Redis::lpush($this->redis_flash_name.'_list',json_encode($data));

       // $redis = $this->redis;
        //return  true;

        $redis_flash_name = $this->redis_flash_name . '_bak';

        $num = Redis::get($redis_flash_name);
        //dump($num);
        //dump($this->redis_flash_name);

        if(!$num){
            $add_data[] = $data; //新建数组
        }else{
            $flash_redis = json_decode(Redis::get($redis_flash_name),true);
            array_push($flash_redis,$data);
            $add_data = $flash_redis;
        }
        //做加入抢购的redis里面
        return Redis::set($redis_flash_name,json_encode($add_data),86400);
    }

    //重置抢购的人
    public function resetFlashUser($data,$game_id){
        $_data = empty($data) ? [] : $data;
        $name = $this->getFlashName($game_id);
        return Redis::set($name,json_encode($_data));
    }
}