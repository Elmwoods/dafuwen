<?php

namespace app\shop\logic;

use think\Model;
use think\Db;
use think\Exception;

class UsersLogic extends Model
{

    /**
     * 获取指定用户信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     *
     * @return mixed 找到返回数组
     */
    public function detail($uid, $relation = true)
    {
        $user = M('users')->where(array('user_id' => $uid))->relation($relation)->find();
        return $user;
    }

    /**
     * 改变用户信息
     * @param int $uid
     * @param array $data
     * @return array
     */
    public function updateUser($uid = 0, $data = array())
    {
        $db_res = M('users')->where(array("user_id" => $uid))->data($data)->save();
        if ($db_res) {
            return array(1, "用户信息修改成功");
        } else {
            return array(0, "用户信息修改失败");
        }
    }


    /**
     * 添加用户
     * @param $user
     * @return array
     */
    public function addUser($user)
    {
		$user_count = Db::name('users')
				->where(function($query) use ($user){
					if ($user['mobile']) {
						$query->whereOr('mobile',$user['mobile']);
					}
				})
				->count();
		if ($user_count > 0) {
			return array('status' => -1, 'msg' => '账号已存在');
		}
    	$user['password'] = encrypt($user['password']);
    	$user['reg_time'] = time();
    	$user_id = M('users')->add($user);
    	if(!$user_id){
    		return array('status'=>-1,'msg'=>'添加失败');
    	}else{
    		$pay_points = tpCache('basic.reg_integral'); // 会员注册赠送积分
    		if($pay_points > 0)
    			accountLog($user_id, 0 , $pay_points , '会员注册赠送积分'); // 记录日志流水
    		return array('status'=>1,'msg'=>'添加成功');
    	}
    }


    public function distSetUpdate($request){

        !$request['lowermoney'] && $request['lowermoney'] = 0;
        !$request['topmoney'] && $request['topmoney'] = 10000000000;

        $has = db('user_ratio')
        ->field('id')
        ->where('lowermoney','<=',$request['lowermoney'])
        ->where('topmoney','>',$request['lowermoney'])
        ->where('id','<>',$request['id'])
        ->where('user_level','=',$request['user_level'])
        ->find();
        if( $has ){
            throw new Exception("与其他区域有重复", 1);
        }
        // pe($request);

        $res = db('user_ratio')->update($request);

        return $res ;
    }

    public function distSetInsert($request){

        !$request['lowermoney'] && $request['lowermoney'] = 0;
        !$request['topmoney'] && $request['topmoney'] = 10000000000;

        $has = db('user_ratio')
        ->field('id')
        ->where('lowermoney','<=',$request['lowermoney'])
        ->where('topmoney','>',$request['lowermoney'])
        ->where('user_level','=',$request['user_level'])
        ->find();
        if( $has ){
            throw new Exception("与其他区域有重复", 1);
        }
        // pe($request);

        $request['create_time'] = time();
        $id = db('user_ratio')->insertGetId($request);

        return $id ;
    }

}