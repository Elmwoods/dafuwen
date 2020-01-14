<?php
//富文本编辑器
namespace addons\editorforadmin;

use think\Addons;

/**
 * 编辑器插件
 * 
 */
class EditorForAdmin extends Addons {
	public $info = array (
			'name' => 'EditorForAdmin',
			'title' => '后台编辑器',
			'description' => '用于增强整站长文本的输入和显示',
			'status' => 1,
			'author' => 'thinkphp',
			'version' => '0.2' 
	);
	public function install() {
		return true;
	}
	public function uninstall() {
		return true;
	}

    /**
     * 编辑器挂载的后台文档模型文章内容钩子
     *
     * @param
     *            array('name'=>'表单name','value'=>'表单对应的值')
     */
    public function adminArticleEdit($data)
    {
        $uploadDriver = strtolower(config("EDITOR_PICTURE_UPLOAD_DRIVER"));
        if ($uploadDriver == 'qiniu') {
            $driverfile = 'ueditor_qiniu';
        } else {
            $driverfile = 'ueditor';
        }
        $this->assign('driver_file', $driverfile);
        $data['is_mult'] = isset($data['is_mult'])?intval($data['is_mult']):0; // 默认不传时为0
        $data['btnClassName'] = !empty($addons_data['btnClassName'])?$addons_data['btnClassName']:'';
        $this->assign('addons_data', $data);
        $this->assign('addons_config', $this->getConfig());
        $this->assign('styleUrl', addon_url('editorforadmin://Style/get_article_style'));

        $this->fetch('content');
    }
	/**
	 * 编辑器挂载的后台文档模型文章内容钩子
	 * 
	 * @param
	 *        	array('name'=>'表单name','value'=>'表单对应的值')
	 */
	public function uploadImg($data) {
		$this->assign ( 'addons_data', $data );
		$this->assign ( 'addons_config', $this->getConfig () );
		$uploadDriver = strtolower(config("EDITOR_PICTURE_UPLOAD_DRIVER"));
		if ($uploadDriver == 'qiniu') {
		    $driverfile = 'ueditor_qiniu';
		} else {
		    $driverfile = 'ueditor';
		}
		$this->assign('driver_file', $driverfile);
		
		$this->display ( 'uploadBtn' );
	}
		
}
