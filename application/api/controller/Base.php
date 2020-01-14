<?php
/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2018/4/12
 * Time: 10:18
 */
namespace app\api\controller;


use think\Controller;
use My\DataReturn;

class Base extends Controller{

    protected $user_id = '';
    protected $user_info = [];
    public function __construct()
    {
  
        parent::__construct();
        //处理数据
        init_config();//初始配置表数据
        //$this->hello();
        // $this->user_id = 1;
        $this->checkLogin();
    }

    public function hello(){
        echo "hello";
    }

    /*验证登录*/
    public function checkLogin()
    {
        $session = session('user');
        // dump($session);die;
        if (time() > $session['expire_time'] || !$session) DataReturn::returnBase64Json(302, '校验失败，需要重新登录!','/dist/pages/login.html');
        /*if($check !== true) DataReturn::returnBase64Json(500,$check);*/
        //获取用户的user_id
        $user_info = db('users')->where('user_id',$session['user_id'])->find();
        if (!$user_info) DataReturn::returnBase64Json(302, '获取用户信息失败','/dist/pages/login.html');
        $this->user_id = $user_info['user_id'];
        $this->user_info = $user_info;
        if ($user_info['is_lock'] == 1) {
            session(null);
            DataReturn::returnBase64Json(302, '此用户已锁定!','/dist/pages/login.html');
        }
        return true;
    }

    /*获取加密的数据*/
    public function getBase64Data()
    {
        $_data = C('encode_data');
        $decode_data = DataReturn::baseFormat($_data);  //解密
        return $decode_data;
    }

    //获取加密数据
    public function __get($name)
    {
        // TODO: Implement __get() method.
        if ($name == C('encode_data')) return $this->getBase64Data();
    }

    //配置表数据初始
    function init_config(){
        //获取配置
        $config = Cache::get('db_config_data');
        if(!$config){
            $config = api('Config/lists');
            Cache::set('db_config_data',$config);
        }//var_dump($config);exit;
        \think\Config::set($config);

    }
}