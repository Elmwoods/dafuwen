<?php

namespace app\api\model;

use think\Model;

class DeviceToken extends Model
{
	protected $name = 'device_token';
	
	//查找有效token记录
	public function find($token){
		return db($this->name)->where("token='{$token}' AND expires_in>=".time())->order('id desc')->find();
	}
	
	//更新token用户信息
	public function update_token($token,$data){
		return db($this->name)->where(['token'=>$token])->update($data);
	}

	//删除相同或者过期的token
	public function delete_token($token,$user_id){
		return db($this->name)->where(" user_info='' OR token='{$token}' OR user_id='{$user_id}' OR expires_in<".time())->delete();
	}

}