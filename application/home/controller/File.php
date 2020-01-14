<?php
//文件上传处理
//
namespace app\home\controller;
use app\home\model\FileModel;
use app\home\model\PictureModel;

/**
 * 文件控制器
 * 主要用于下载模型的文件上传和下载 
 */

class File extends Base {
	/* 文件上传 */
	public function upload(){
		$return  = array('code' => 1, 'msg' => '上传成功', 'data' => '');
		/* 调用文件上传组件上传文件 */
		$File = new FileModel();

        $file_config = config('download_upload');

        //判断文件夹是否存在
        if(!file_exists($file_config['rootPath'])){
            mkdir($file_config['rootPath'],755);
        }

		$info = $File->upload(
			$_FILES,
			$file_config 
		);
        /* 记录附件信息 */
        if($info){
        	$return['code'] = 1;
        	$return = array_merge($info['download'], $return);
        } else {
            $return['code'] = 0;
            $return['msg']   = $File->getError();
        }

        /* 返回JSON数据 */
        return json($return);
    }

    /* 下载文件 */
    public function download($id = null){
        if(empty($id) || !is_numeric($id)){
            $this->error('参数错误！');
        }

        $logic = \think\Loader::model('Download', 'logic');
        if(!$logic->download($id)){
            $this->error($logic->getError());
        }

    }

    /**
     * 上传图片
     */
    public function uploadPicture(){
        //TODO: 用户登录检测

        /* 返回标准数据 */
        $return  = array('code' => 1, 'msg' => '上传成功', 'data' => '');

         $file_config = config('download_upload');

        //判断文件夹是否存在
        if(!file_exists($file_config['rootPath'])){
            mkdir($file_config['rootPath'],755);
        }
        
        /* 调用文件上传组件上传文件 */
        $Picture = new PictureModel();
        $info = $Picture->upload(
            $_FILES,
            $file_config 
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if($info){
            $return['code'] = 1;
            $return = array_merge($info['download'], $return);
        } else {
            $return['code'] = 0;
            $return['msg']   = $Picture->getError();
        }

        /* 返回JSON数据 */
        return json($return);
    }
	//图片选择器
	function uploadDialog(){
		
		return $this->fetch('uploaddialog');
	}
	function userPics(){
        $type = input('type');
		$map['token'] = get_token();
        if($type==2){
            $pagesize = 10;//每页数量
            $picList = db ( 'material_image' )->where ( $map )->field ( 'id,cover_url' )->order ( 'id desc' )->select();
            // var_dump($picList->toArray());exit;
        }else{
            $picList = db('picture')->where($map)->select();
        }

        $this->assign('type',$type);
		$this->assign('picList',$picList);
		return  view();//ajax html原样输出
	}
	

}
