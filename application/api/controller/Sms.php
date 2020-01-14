<?php

namespace app\api\controller;
use app\common\controller\BaseComm;
use app\common\logic\UsersLogic;
use think\Db;
use My\DataReturn;
use app\api\controller\JuHe;
use app\common\logic\Code;
use org\Verify;

class Sms extends BaseComm {
    public  $send_scene;

    public function __construct()
    {
        init_config();
        // parent::__construct();
    }
    /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     */
    public function send_validate_code(){

        // try{
        $post = I('post.');
        $this->send_scene = config('SEND_SCENE');
        $scene = $post['data']['scene'];    //发送短信验证码使用场景 1:用户注册,2:修改登陆密码,3:抢到区块猪,4:神兽订单被抢购,5:修改交易密码
        $mobile = $post['data']['mobile'];
        $users = db('users')->where(['mobile'=>$mobile,])->value('mobile');
        if($scene==1 && !empty($users)){
            DataReturn::returnJson(0,'手机号已存在',[]);
        }
        if($scene==2 && empty($users)){
            DataReturn::returnJson(0,'请输入注册的手机号',[]);
        }

        //判断是否开启了图形验证码
        $is_open = Code::getStatus($post['data']['scene']);
        if($is_open){
            $code_s = $post['data']['code_s'];
            $verify = new Verify();
            if(!$verify->check($code_s)){
                DataReturn::returnJson(0, "验证码错误", []);            
            }
        }

       // dump($scene);exit;
        //判断是否存在验证码
        $data = db('sms_log')->where(array('mobile'=>$mobile, 'status'=>1))->order('id DESC')->find();
        //获取时间配置
        $sms_time_out = config('sms_time_out');
        $sms_time_out = $sms_time_out ? $sms_time_out : 120;
        //120秒以内不可重复发送
        if($data && (time() - $data['add_time']) < $sms_time_out){
            DataReturn::returnJson(0,$sms_time_out.'秒内不允许重复发送',[]);
        }

      /*  if(config('app_debug')){
           $code = 8888; 
        }else{
          //随机一个验证码
           $code = rand(1000, 9999);  
        }*/
        $code = rand(1000, 9999);
        // $code=8888;
        $params['code'] =$code;

        //发送短信
        $resp = sendSms($scene , $mobile , $params);
        if($resp['status'] == 1){
            DataReturn::returnJson(200,'发送成功,请注意查收',[]);
        }else{
            DataReturn::returnJson(0,$resp['msg'],[]);
        }
            
        // }catch(\Exception $e){
        //     DataReturn::returnJson(0,'程序出错',[]);
        // }
       
    }

    public function useSendCode(){

        $session = session('user');
        if(empty($session))
           DataReturn::returnJson(0,'用户未登陆,发送失败',[]);
       
         $mobile = db('users')->where('user_id',$session['user_id'])->value('mobile');
         $code = rand(1000, 9999);
         $scene = 6;
         //判断是否存在验证码
         $data = db('sms_log')->where(array('mobile'=>$mobile, 'status'=>1))->order('id DESC')->find();
          //获取时间配置
         $sms_time_out = config('sms_time_out');
      
         $sms_time_out = $sms_time_out ? $sms_time_out : 120;
         //120秒以内不可重复发送
         if($data && (time() - $data['add_time']) < $sms_time_out){
               DataReturn::returnJson(0,$sms_time_out.'秒内不允许重复发送',[]);
          }
        
          $params['code'] =$code; 
          //发送短信
          $resp = sendSms($scene , $mobile , $params);
      
          if($resp['status'] == 1){
                //发送成功, 修改发送状态位成功
                db('sms_log')->where(array('mobile'=>$mobile,'code'=>$code , 'status' => 0))->save(array('status' => 1));
                DataReturn::returnJson(200,'发送成功,请注意查收',[]);
            }else{
                DataReturn::returnJson(0,'发送失败',[]);
          }
    }

}
