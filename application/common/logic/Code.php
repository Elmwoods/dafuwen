<?php

namespace app\common\logic;

use org\Verify;
use think\Db;


class Code
{
	//1:用户注册,2:修改登陆密码,忘记密码，修改收款支付方式,3:抢到区块猪,4:神兽订单被抢购,5:修改交易密码


	//场景转换配置ID 
	public static function change($sence){
		//$data = [1=>171,2=>172];

		//if(!in_array($sence,array_keys($data)))
		//	return 0 ;
		return 'img_code_'.$sence;
	}


	//获取某个场景的验证码的状态
	public static function getStatus($sence){
		$config_id = self::change($sence);
		$status = Db::name('config')->where('group',22)->where('name',$config_id)->value('status');
		return empty($status) ? 0 : 1;
	}

	//验证验证码
	public static function check($code , $sence){
		$open_sence = self::getStatus($sence);

		$result = ['status'=>1 , 'message'=>'验证成功'];

		if($open_sence){
			$verify = new Verify();
	        if(!$verify->check($code)){
	            $result['status'] = 0;
	            $result['message'] = '验证码错误';
	        }
		}
		return $result;
	}
    
}

