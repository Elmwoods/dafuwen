<?php

namespace app\common\model;

use think\Model;

/**
 * 公众号配置操作集成
 */
class PublicModel extends Model {
	protected $name = 'public';

	function getInfo($id, $filed = '', $update = false, $data = array()) {
		if (empty ( $id )) {
			return empty ( $filed ) ? array () : '';
		}
		
		$key = 'Common_Public_getInfo_' . $id;
		$info = \think\Cache::get ( $key );
		if ($info === false || $update) {
			$info = empty ( $data ) ? $this->find ( $id ) : $data;
			\think\Cache::set( $key, $info );
		}
		
		return empty ( $filed ) ? $info : $info [$filed];
	}
	function getInfoByToken($token, $filed = '', $update = false) {
		$key = 'Common_Public_getInfoByToken';
		$arr = \think\Cache::get ( $key );

		if ($arr === false || ! isset ( $arr [$token] ) || $update) {
			$list = $this->field ( 'id,token' )->select ();		
			foreach ( $list as $vo ) {
				$arr [$vo ['token']] = $vo ['id'];
			}
			if(empty($list))$arr = [];//清空
			\think\Cache::set ( $key, $arr, 604800 ); // 缓存一周
		}

		return $this->getInfo ( @$arr [$token], $filed, $update );
	}
	function clear($id, $type = '', $uid = '') {
		$info = $this->getInfo ( $id, '', true );
		$token=get_token();
		$this->getInfoByToken($token,'',true);
	}
	function getMyPublics($uid) {
		$map ['uid'] = $uid;
		$list = db ( 'public_link' )->where ( $map )->select ();
		foreach ( $list as $vo ) {
			$info = [];
			$info = $this->getInfo ( $vo ['mp_id'] );
			$res [$vo ['mp_id']] = array_merge ( $vo, $info->toArray() );
		}
		return $res;
	}
	function addPublic() {
	}
	function updateRefreshToken($appid, $refresh_token){
		$map['appid'] = $appid;
		$info = $this->where($map)->field('id')->find();
		if(!$info){
			return false;
		}

		$save['authorizer_refresh_token'] = $refresh_token;
		$res = $this->where($map)->update($save);
		if($res){
			$this->clear($info['id']);
		}
	}

	//更新公众号插件配置信息 
	function set($addon, $config) {
		$map['token'] = get_token();
		if (empty($map['token'])) {
			return false;
		}
		$info = db('public')->where( $map )->find();//
		if (!empty($info)) {
			$addon_config = json_decode($info['addon_config'], true);
			$addon_config[$addon] = (array)$addon_config[$addon];
			$addon_config[$addon] = array_merge($addon_config[$addon], $config);
			//var_dump($addon_config);exit;
			db('public')->where ( $map )->update(['addon_config'=>json_encode($addon_config)]);
		}
		//清理公众号信息缓存
		$this->clear($info['id']);
		
		return $info['id'];
	}

}
?>
