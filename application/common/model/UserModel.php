<?php

namespace app\common\model;

use think\Model;

//用户模型类

class UserModel extends Model {

	protected $name = 'users' ;
	
	/**
	 * 获取用户全部信息
	 */
	public function getUserInfo($uid, $update = false) {
		if (! ($uid > 0))
			return false;
		
		$key = 'getUserInfo_' . $uid;
		$userInfo = \think\Cache::get( $key );
		
		if ($userInfo === false || $update) {
			// 获取用户基本信息
			$userInfo = $this->find ( $uid )->toArray();
			// 公众号管理员信息
			$manager = (array)db( 'manager' )->where ( "uid='$uid'" )->field ( true )->find ();
			$userInfo = array_merge ( $userInfo, $manager );
			
			// 获取用户组信息
			$userInfo ['groups'] = array ();
			$prefix = config('database.prefix');
			$groups = db()->table ( $prefix . 'wx_user_group_access a' )->where ( "a.uid='$uid' and g.status='1'" )->join ( $prefix . "auth_group g ", "a.group_id=g.id" )->field ( 'a.group_id,g.title,g.type' )->select ();

			foreach ( $groups as $g ) {
				// $g ['icon'] = get_cover_url ( $g ['icon'] );
				$userInfo ['groups'] [$g ['group_id']] = $g;
			}
			
			// 公众号粉丝信息
			$userInfo ['tokens'] = array ();
			$tokens = db( 'public_follow' )->where ( "uid='$uid'" )->field ( true )->select ();
			foreach ( $tokens as $t ) {
				$userInfo ['tokens'] [$t ['token']] = $t ['openid'];
				$userInfo ['remarks'] [$t ['token']] = $t ['remark'];
				$userInfo ['has_subscribe'] [$t ['token']] = $t ['has_subscribe'];
			}
			
			// 是否为系统管理员
			// $userInfo ['is_root'] = is_administrator ( $uid );
			$userInfo ['headimgurl'] = empty ( $userInfo ['headimgurl'] ) ? SITE_URL . '/static/images/logo.jpg' : $userInfo ['headimgurl'];
			
			$sexArr = array (
					0 => '保密',
					1 => '男',
					2 => '女' 
			);
			$sexArr2 = array (
					0 => 'Ta',
					1 => '他',
					2 => '她' 
			);

			$userInfo ['sex_name'] = @$sexArr[$userInfo ['sex']];
			$userInfo ['sex_alias'] = @$sexArr2[$userInfo ['sex']];
			$userInfo = $this->_deal_nickname ($userInfo, 1 );
			
			// 获取标签信息
			$tag_map ['uid'] = $uid;
			$userInfo ['tag_ids'] = db( 'user_tag_link' )->where ( $tag_map )->column ( 'tag_id' );
			if (! empty ( $userInfo ['tag_ids'] )) {
				$tag_map2 ['id'] = array (
						'in',
						$userInfo ['tag_ids'] 
				);
				$titles = db( 'user_tag' )->where ( $tag_map2 )->column ( 'title' );
				$userInfo ['tag_titles'] = implode ( ',', $titles );
			}
			
			\think\Cache::set ( $key, $userInfo, 86400 );
		}
		
		$token = session ('token');
		if ($token && ! empty ( $userInfo ['remarks'] [$token] )) {
			$userInfo ['nickname'] = $userInfo ['remarks'] [$token];
		}
		
		return $userInfo;
	}

	function _deal_nickname($data, $type = 0) {
		if (isset ( $data ['nickname'] )) {
			$data ['nickname'] = deal_emoji ( $data ['nickname'], $type );
		}
		
		return $data;
	}

	//更新用户信息
	function updateInfo($uid, $save,$is_syc=true) {
		if (empty ( $uid ))
			return false;
		//2018-08-17 14:34:48
		//$save = $this->_deal_nickname ( $save );
		$map ['user_id'] = $uid;
		$res = $this->where ( $map )->update ( $save );
		if ($res && $is_syc) {
			$this->getUserInfo ( $uid, true );
		}
		return $res;
	}

}