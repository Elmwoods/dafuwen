<?php

namespace app\admin\controller;
use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
	//图片上传
    public function upload(){

      $file = request()->file('file');


      $arrType=array('image/jpg','image/gif','image/png','image/bmp','image/jpeg');
     
      if(!is_uploaded_file($file->getInfo('tmp_name'))){ //判断上传文件是否存在     
        return $this->error('上传文件失败！');
      }
      if(!in_array($file->getInfo('type'),$arrType)){  //判断图片文件的格式     
        return $this->error('上传文件格式不对！');
      }

      $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/images');
      
      if($info){
          echo $info->getSaveName();
      }else{
          echo $file->getError();
      }
    }


    //会员头像上传
    public function uploadface(){

      $file = request()->file('file');

      $arrType=array('image/jpg','image/gif','image/png','image/bmp','image/jpeg');
     
      if(!is_uploaded_file($file->getInfo('tmp_name'))){ //判断上传文件是否存在     
        return $this->error('上传文件失败！');
      } 
      if(!in_array($file->getInfo('type'),$arrType)){  //判断图片文件的格式     
        return $this->error('上传文件格式不对！');
      }

      $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
      if($info){
            echo $info->getSaveName();
      }else{
            echo $file->getError();
      }

    }

    //图片上传
    public function upload_recharge(){
      $file = request()->file('file');

      $arrType=array('image/jpg','image/gif','image/png','image/bmp','image/jpeg');
     
      if(!is_uploaded_file($file->getInfo('tmp_name'))){ //判断上传文件是否存在     
        return $this->error('上传文件失败！');
      }
      if(!in_array($file->getInfo('type'),$arrType)){  //判断图片文件的格式     
        return $this->error('上传文件格式不对！');
      }

      $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/recharge');
      if($info){
          echo $info->getSaveName();
      }else{
          echo $file->getError();
      }
    }

}