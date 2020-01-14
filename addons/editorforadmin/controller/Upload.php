<?php

//编辑器插件
namespace addons\editorforadmin\controller;

use think\addons\Controller;
use think\Log;

class Upload extends Controller {
	public $uploader = null;
	private $token = '';
	
	function _initialize()
    {
    	parent::_initialize();

    	$this->token = get_token().'/';//根据微信用户公众号的token

    }

	/* 上传图片 */
	public function upload() {
		session('upload_error', null);
		/* 上传配置 */

		$setting = config ( 'editor_upload' );
		$setting['savePath'] = $this->token;
//var_dump($setting);exit;
		/* 调用文件上传组件上传文件 */
		$setting['callback'] = array($this, 'isFile');
		$setting['removeTrash'] = array($this, 'removeTrash');
		$driver = config ( 'editor_picture_upload_driver' );
		$Upload = new \org\Upload($setting, $driver);
        $info   = $Upload->upload($_FILES);

		if ($info) {
			foreach ( $info as &$file ) {
				$file ['rootpath'] = ltrim ( $setting ['rootPath'], "." );
				
				/* 已经存在文件记录 */
				if (isset ( $file ['id'] ) && is_numeric ( $file ['id'] )) {
					$file ['path'] =  ltrim ( $file ['path'], "." );
					continue;
				}
				
				/* 记录文件信息 */
				$file ['path'] = ltrim ( $setting ['rootPath'], "." ) . $file ['savepath'] . $file ['savename'];
				$file ['status'] = 1;
				$file ['create_time'] = time();

				 $data = [
                    'path'=>$file['path'] ,
                    'token'=>$this->token ,
                    'md5'=>$file['md5'] ,
                    'sha1'=>$file['sha1'] ,
                    'create_time'=>time(),
                    'status'=>'1'
                ]; //var_dump($this);exit;
				if ($id = db('picture')->insertGetId($data)) {
					$file ['id'] = $id;
				}
			}
		}
		session('upload_error', $Upload->getError());
		return $info;
	}
	
	// keditor编辑器上传图片处理
	public function ke_upimg() {
		/* 返回标准数据 */
		$return = array (
				'error' => 0,
				'info' => '上传成功',
				'data' => '' 
		);
		$info = $this->upload ();
		$img = $info ['imgFile'] ['path'];
		/* 记录附件信息 */
		if ($img) {
			$return ['id'] = $info ['imgFile'] ['id'];
			$return ['url'] = $img;
			unset ( $return ['info'], $return ['data'] );
		} else {
			$return ['error'] = 1;
			$return['message']   = session('upload_error');
		}
		
		/* 返回JSON数据 */
		exit ( json_encode ( $return ) );
	}
	
	// ueditor编辑器上传图片处理
	public function ue_upimg() {
		$info = $this->upload ();
		
		$img = $info ['file'] ['path'];
		
		$return = array ();
		$return ['id'] = $info ['file'] ['id'];
		$return ['url'] = SITE_URL.$img;
		$title = htmlspecialchars ( @$_POST ['pictitle'], ENT_QUOTES );

		$return ['title'] = $title;
		$return ['original'] = $info ['file'] ['name'];
		$return['state'] = ($img)? 'SUCCESS' : session('upload_error');
		/* 返回JSON数据 */
		echo json_encode ( $return );
	}
	// ueditor编辑器在线管理处理
	// 扫描目录下（包括子文件夹）的图片并返回
	public function ue_mgimg() {
		$setting = config ( 'editor_upload' );
		$imgRootPath = $setting ['rootPath'].$this->token;
		$paths = array (
				'' 
		);
		$files = array ();

        foreach ( $paths as $path){
            $tmp = $this->getfiles ( $imgRootPath );
            if($tmp){
                $files = array_merge($files,$tmp);
            }
        }
        if ( !count($files) ) return;
        rsort($files,SORT_STRING);
        $str = "";
        foreach ( $files as $file ) {
            $str .= $file . "ue_separate_ue";
        }
        echo $str;
        
	}
	
	/**
	 * 遍历获取目录下的指定类型的文件
	 *
	 * @param
	 *        	$path
	 * @param array $files        	
	 * @return array
	 */
	function getfiles($path, &$files = array()) {
		if (! is_dir ( $path ))
			return null;
		$handle = opendir ( $path );
		while ( false !== ($file = readdir ( $handle )) ) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . '/' . $file;
				if (is_dir ( $path2 )) {
					$this->getfiles ( $path2, $files );
				} else {
					if (preg_match ( "/\.(gif|jpeg|jpg|png|bmp)$/i", $file )) {
						// $files[] = '/dev/'.$path2;
						// $files [] =  SITE_URL.'/' .ltrim ( ltrim ( $path2, '.' ), '/' );
						$files [] =  '/' .ltrim ( ltrim ( $path2, '.' ), '/' );
					}
				}
			}
		}
		return $files;
	}
	
	/**
	 * 检测当前上传的文件是否已经存在
	 *
	 * @param array $file
	 *        	文件上传数组
	 * @return boolean 文件信息， false - 不存在该文件
	 */
	public function isFile($file) {
		if (empty ( $file ['md5'] )) {
			throw new \Exception ( '缺少参数:md5' );
		}
		/* 查找文件 */
		$map = array (
				'md5' => $file ['md5'],
				'sha1' => $file ['sha1'] 
		);
		return db ( 'picture' )->field ( true )->where ( $map )->find ();
	}
}
