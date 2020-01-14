<?php
namespace app\weixin\controller;

use app\weixin\model\PublicBindModel;
use app\common\model\PublicModel;

/**
 * 公众号管理
 */
class Publics extends Base {

	public function _initialize() {
		parent::_initialize ();

	}

	/**
	 * 显示指定模型列表数据
	 */
	public function lists() {
		$mp_idarr = db ( 'public_link' )->where ( "uid='{$this->mid}'" )->field ( 'mp_id' )->select();
		$mp_ids = [];
		foreach ($mp_idarr as $key => $row) {
			$mp_ids[] = $row['mp_id'];
		}
		$map ['id'] = 0;
		if (! empty ( $mp_ids )) {
			$map ['id'] = $map3 ['mp_id'] = array (
					'in',
					$mp_ids 
			);
			
			$list = db ( 'public_link' )->where ( $map3 )->group ( 'mp_id' )->field ( 'mp_id,count(1) as num' )->select ();
			$countArr = [];
			foreach ( $list as $vo ) {
				$countArr [$vo ['mp_id']] = $vo ['num'];
			}
		}

		// 获取公众号
		$data = db ( 'public' )->field ( true )->where ( $map )->select ();
		$list_data = [];
		foreach ( $data as $d ) {
			$d ['count'] = $countArr [$d ['id']];
			$d ['is_creator'] = $d ['uid'] == $this->mid ? 1 : 0;
			$list_data [$d ['is_creator']] [] = $d;
		}			

		$this->assign ('list_data', $list_data );
		
		return $this->fetch();
	}

	//新增公众号：步骤一
	public function step_0() {
		//是否通过开放平台扫码授权公众号
		if (config ( 'public_bind' ) &&0) {// && is_install ( 'PublicBind' )
			$PublicBind = new PublicBindModel();
			$res = $PublicBind->bind ();
			//var_dump($res);exit;
			if (! $res ['status']) {
				$this->error ( $res ['msg'] );
				exit ();
			}
			header ('Location:' .$res ['jumpURL'] );
			exit ();

		}
		//手动填写配置
		$map ['id'] = $id = input ( 'id' );
		$PublicModel = new PublicModel();
		$data =$PublicModel->where ( $map )->find ();
		if (! empty ( $data ) && $data ['uid'] != $this->mid) {
			$this->error ( '非法操作' );
		}
		
		$this->assign ( 'id', $id );

		if (request()->isPost()) {
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}

			$_POST ['token'] = $_POST ['public_id'];
			$_POST ['group_id'] = config ( 'DEFAULT_PUBLIC_GROUP_ID' );
			$_POST ['uid'] = $this->mid;
			//var_dump($_POST);exit;
			session ( 'token', $_POST ['token'] );
			
			if (empty ( $id )) {//添加
				$id = db ( 'public' )->insertGetId ( $_POST );
				if ($id) {
					// 增加公众号与用户的关联关系
					$data = [];
					$data ['uid'] = $this->mid;
					$data ['mp_id'] = $id;
					$data ['is_creator'] = 1;
					db ( 'public_link' )->insertGetId ( $data );
					// 更新缓存
			        $PublicModel->clear ( $id );

					$url = url ( 'step_1?id=' . $id );
					
					$this->success ( '添加基本信息成功！', $url );
				} else {
					$this->error ( '添加基本信息失败！');
				}
			} else {//修改
				$_POST ['id'] = $id;
				$url = url ( 'step_1?id=' . $id );
				$map = [
					'id'=>$_POST['id'],
					'token'=>input('param.token'),
				];
				$res = db ( 'public' )->where($map)->update ( $_POST );
				// 更新缓存
			    $PublicModel->clear ( $id );
				if ($res) {
					$this->success ( '保存基本信息成功！', $url );
				} elseif ($res === 0) {
					$this->success ( ' ', $url );
				} else {
					$this->success ( '保存基本信息失败！');
				}
			}
		} else {
			$data ['type'] = intval ( $data ['type'] );
			$this->assign ( 'info', $data );
			
			return $this->fetch ();
		}
	}

	//设置公众号：步骤二
	public function step_1() {
		$id = input ( 'id' );
		$this->assign ( 'id', $id );
		
		return $this->fetch ();
	}

	//设置公众号：步骤三
	function step_2() {
		$id = input ( 'id' );
		$this->assign ( 'id', $id );

		$PublicModel = new PublicModel();
		$data =$PublicModel->where ( ['id'=>$id] )->find ();
		if (empty ( $data ) || $data ['uid'] != $this->mid) {
			$this->error ( '非法操作' );
		}
		
		if (request()->isPost()) {
			// 更新缓存
			$PublicModel->clear ( $id );
			
			$_POST ['id'] = $id;
			
			foreach ( $_POST as &$v ) {
				$v = trim ( $v );
			}
			
			$map = [
				'id'=>$_POST['id'],
				//'token'=>$_POST['token'],
			];
			$res = $PublicModel->where($map)->update ( $_POST );
			if (!empty($res)) {
				$PublicModel->clear ( $data ['id'] );
				$is_audit=1;//2017-05-04 11:39:33
				if ($is_audit == 0 && ! config ( 'REG_AUDIT' )) {
					$this->success ( '提交成功！', url ( 'waitAudit', array (
							'id' => $id 
					) ) );
				} else {
					$this->success ( '保存成功！', url ( 'lists' ) );
				}
			} else {
				//$this->error ( '保存失败！');
				$this->success ( '保存成功！', url ( 'lists' ) );
			}
		} else {
			
			$this->assign ( 'info', $data );
			
			return $this->fetch ();
		}
	}


	//公众号解绑
	public function del($model = null, $ids = null) {

		if (empty ( $ids )) {
			$ids = input ( 'id' );
		}
		if (empty ( $ids )) {
			$ids = array_unique ( ( array ) input ( 'ids', 0 ) );
		}
		if (empty ( $ids )) {
			$this->error ( '请选择要操作的数据!' );
		}
		
		$map ['id'] = array (
				'in',
				$ids 
		);
		if (db('public')->where ( $map )->delete ()) {
			$map_link ['mp_id'] = array (
					'in',
					$ids 
			);
			db( 'public_link' )->where ( $map_link )->delete ();
			
			if (config ( 'public_bind' )) {
			}
			$component_key = 'component_access_token_' . config('component_appid');
			\think\Cache::rm ( $component_key);//删除公众号授权token 缓存
			//2017-04-20 13:55:44
			$PublicModel = new PublicModel();
			$PublicModel -> clear($ids,'',true);//清除当前公众号缓存key token

			$this->success ( '删除成功' );
		} else {
			$this->error ( '删除失败！' );
		}
	}


	

}