<?php
namespace app\reg\controller;

class  Download extends Base{


    public function downloadapp(){
        $ios_link = config('ios_link_download');
        $android_link = config('android_link_download');
        //根据手机系统跳转不同的路径
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $info['downurl'] = $ios_link;
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $info['downurl'] = $android_link;
        }else{
            $info['downurl'] = $android_link;
        }
        $info['app_name']    = config('app_name');
        $info['single_logo'] = config('app_logo');
        $info['show_logo']   = config('app_logo');
        $this->assign('info',$info);
        return $this->fetch();
    }
}
