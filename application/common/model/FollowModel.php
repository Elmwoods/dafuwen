<?php

namespace app\common\model;

use think\Model;

/**
 * 粉丝操作
 */
class FollowModel extends Model {
	protected $name = 'public_follow';
	//,$invite=0,$first_leader=0
	function init_follow($openid, $token = '', $has_subscribe = false,$access_token='',$invite=0,$first_leader=0) {
		empty ( $token ) && $token = get_token ();

		if (empty ( $openid ) || $openid == - 1 || empty ( $token ) || $token == - 1)
			return false;
		
		$umap ['token'] = $data ['token'] = $token;
		$umap ['openid'] = $data ['openid'] = $openid;
		$datas = $data;
		$follow = db ( 'public_follow' )->where ( $data )->field ( 'uid' )->find();
		
		$uid = $follow['uid'];
		if ($uid > 0) {//登陆
			$UserComm = \think\Loader::model('UserComm','logic');
			$user_info = $UserComm->getUserAndAccountInfoById($uid);

		}else{// 自动注册
			$user = array (
					'unionid'=>'',
					'last_login_time' => time(),
					'status' => 1,
			);
			if(!empty($access_token)){//snsapi_userinfo 授权方式的access_token
				$user2 = getWeixinUserInfo2 ( $openid ,$access_token);
			}else{
				$user2 = getWeixinUserInfo ( $openid );
			}
			
			$user = array_merge ( $user, $user2 );
			// var_dump($user);exit;
			
			$AccountComm = \think\Loader::model('AccountComm','logic');
			//$user_info = $AccountComm->register($user['openid'],$user['unionid'],'',$user['headimgurl'],$user['nickname']);//注册
			$user_info = $AccountComm->register($user,$invite,$first_leader);//注册
			//var_dump($user_info);exit;
			$data['uid'] = $user_info['uid'];
			if ($has_subscribe !== false) {
				$data ['has_subscribe'] = $has_subscribe;
			}
			//更新本地用户信息
			if (!empty($data['uid'])) {//2017-08-30 17:25:00
				$userinfo = $this->_set_userinfo($user);
				$ret = \think\Loader::model('UserModel', 'model')->updateInfo($data['uid'],$userinfo,false );
			}
			/*if (!empty($data['uid'])) {
				db ( 'public_follow' )->where ( $umap )->update ( $data );
			} else {
				db ( 'public_follow' )->insert ( $data );
			}
*/
			//2017-05-05 15:09:49
			db ( 'public_follow' )->insert ( $data );

		}
 //var_dump($user_info,$follow);exit;
		if(!empty($user_info)){//设置用户session
			session('wechat_info',$user_info);
		}

		return $uid;
	}

	//处理返回用户信息
    function _set_userinfo($u){
		$userinfo ['openid'] = $u ['openid'];
        $userinfo ['nickname'] = $u ['nickname'];
        $userinfo ['head_pic'] = $u ['headimgurl'];
        $userinfo ['sex'] = $u ['sex'];
        $userinfo ['city'] = $u ['city'];
        $userinfo ['province'] = $u ['province'];
        //2018-08-17 14:30:22
        //$userinfo ['country'] = $u ['country'];
        //$userinfo ['language'] = !empty($u ['language'])?$u ['language']:'zh_CN';
        //$userinfo ['subscribe_time'] = !empty($u ['subscribe_time'])?$u ['subscribe_time']:0;
        //$userinfo ['groupid'] = !empty($u ['groupid'])?$u ['groupid']:0;

        return $userinfo;
    }
	
	/**
	 * 兼容旧的写法
	 */
	public function getFollowInfo($id, $update = false) {
		return \think\Loader::model('UserModel', 'model')->getUserInfo ( $id, $update );
	}
	function updateInfo($id, $data) {//
		return \think\Loader::model('UserModel', 'model')->updateInfo ( $id, $data );
	}
	function updateField($id, $field, $val) {
		return \think\Loader::model('UserModel', 'model')->updateInfo ( $id, array (
				$field => $val 
		) );
	}
	function set_subscribe($user_id, $has_subscribe = 1) {
		if (is_numeric ( $user_id )) {
			$map ['uid'] = $user_id;
		} else {
			$map ['openid'] = $user_id;
		}
		$token = get_token();
		if ($token && $token != '-1') {
			$map ['token'] = $token;
		}
		
		db ( 'public_follow' )->where ( $map )->setField ( 'has_subscribe', $has_subscribe );
	}
}
?>
