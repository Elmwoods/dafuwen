<?php


namespace app\api\controller;
use think\Controller;
use app\common\logic\Game;
use My\DataReturn;
use think\Db;
use app\common\logic\Code;

class Nologin extends Controller{

    public function __construct()
    {
        //处理数据
        init_config();//初始配置表数据
    }

    //配置表数据初始
    public function init_config(){
        //获取配置
        $config = Cache::get('db_config_data');
        if(!$config){
            $config = api('Config/lists');
            Cache::set('db_config_data',$config);
        }//var_dump($config);exit;
        \think\Config::set($config);
    }

	public function pig_goods(){
        $game = new Game();
        $_data = db('pig_goods')->where(['is_display'=>1])->select();
        $game->setGameModel($_data);
        $data = $game->addGameLevel();
        $list = [];
        foreach ($data as $key => $value) {
        	$value['start_time'] = substr($value['start_time'], 0,5);
            $value['end_time']   = substr($value['end_time'], 0,5);
            $value['small_price']   = substr($value['small_price'],0,strpos($value['small_price'], '.'));
        	$value['large_price']   = substr($value['large_price'],0,strpos($value['large_price'], '.'));
        	$list['list'][] = $value;
        }
        $list['banner'] = config('top_banner');
        DataReturn::returnJson(200, '数据返回成功',$list);
    }

    //配置  网站logo...提币页面的简介...
    public function config(){
        $logo = config('store_logo');
        $recharge_desc = config('recharge_desc');//提币页面的简介
        $pig_fee       = config('pig_fee');//pig币手续费
        $doge_fee      = config('doge_fee');//doge币手续费
        $mess_open     = config('mess_open');//短信开关 


        $data= [
            'logo'           => $logo,
            'recharge_desc'  => $recharge_desc,
            'pig_fee'        => $pig_fee,
            'doge_fee'       => $doge_fee,
            'mess_open'      => $mess_open,
        ];

        //获取某个页面配置项
        $page = $this->page();
        $data =  array_merge($data,$page);    

        DataReturn::returnJson(200, '数据返回成功',$data);
    }


    public function page(){
        $pages =  input('data.pages');
        $result['code_open'] = 0;
        switch ($pages) {
            case 'register':
                $result['code_open'] = Code::getStatus(1);
                break;
            case 'set_pwd':
                $result['code_open'] = Code::getStatus(5);
                break;
            case 'forgot_password':
                $result['code_open'] = Code::getStatus(2);
                break;   
            default:
                
                break;
        }

        return $result;
    }

    public function update(){
        //$game = new \app\common\logic\Game();
        //$game->updateOneData();
        $this->timeStopOpenGame(time());
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

} 