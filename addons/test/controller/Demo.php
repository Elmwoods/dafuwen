<?php
namespace addons\test\controller;

//use think\addons\Controller;
use app\home\controller\Addons;
use addons\test\model\DemoModel;
//use Hooklife\ThinkphpWechat\Wechat;
use EasyWeChat\Message\Image;

class Demo extends Addons
{
	function _initialize() {
		parent::_initialize ();
	}
    public function index()
    { 


$Image = new Image();
$text = $Image->media(111111);
//var_dump($text);exit;

        $demo = new DemoModel();
        $lists = $demo->getAdAll();

		$this->assign('list',$lists );
        return $this->fetch();
    }



}