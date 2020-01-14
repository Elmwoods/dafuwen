<?php

// 调用方式 \think\Loader::model('UserComm','logic');//用户逻辑层
// 公共用户逻辑层（默认找当前模块的，不存在则查找公共的）
namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Log;

class UserComm
{

    /**
     * 根据用户id获取用户信息
     * @param $uid
     * @return array
     */
    public function getUserAndAccountInfoById($uid)
    {
        $result = array();
        //用户信息
        $user = db('users')->field('*,user_id as uid')->where('user_id', $uid)->find();
        
        return $user;
    }


    /**
     * 新增用户信息
     * @param $user
     * @return bool
     */
    public function addUser($user,$invite,$first_leader)
    {
        $unionid = isset($user['unionid'])?$user['unionid']:'';
        $openid = $user['openid'];
        //注册信息
        $data = [
            'unionid'=>$unionid,
            'openid'=>$openid,
            'head_pic'=>$user['headimgurl'],
            'nickname'=>$user['nickname'],
            'sex'=>$user['sex'],
            //'country'=>$user['country'],
            'province'=>$user['province'],
            'city'=>$user['city'],
            'create_time'=>time(),
            'reg_time'=>time(),
            'last_login_time'=>time(),
            'user_type'=>4,//公众号
            'is_bind_weixin'=>1,
        ];
        
        $row = db('users')->where("(unionid='{$unionid}' AND unionid!='') OR openid='{$openid}'")->find();
        if ($row) {
            return false;
        } else {
            \think\Loader::model('UsersLogic','logic')->thirdLogin($data,$invite,$first_leader);
            //db('users')->insert($data);
            return true;
        }

    }

    /**
     * 更新用户信息
     * @param $uid
     * @param $data
     * @return bool
     */
    public function editUserInfo($uid, $data)
    {
        $row = db('users')->where('user_id', $uid)->find();
        if ($row) {
            $data['update_time'] = time();
            db('users')->where('user_id', $uid)->update($data);
            return true;
        } else {
            return false;
        }

    }

}

