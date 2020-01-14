<?php

namespace app\shop\model;

use think\Db;
use think\Model;

class SmsLog extends Model {

    private static $status = array('失败','成功');//短信发送状态
    private static $scene  = array('','用户注册','找回密码','客户下单','客户支付','商家发货','身份验证');//短信发送场景

    /**
     * 短信列表
     * @author lhk
     */
    public static function smsList($mobile){
        $query = array('mobile'=>$mobile);
        $map = array();
        if(!empty($mobile)) $map['mobile'] = array('like','%'.$mobile.'%');
        $result = Db::name('sms_log')
            -> where($map)
            -> order('add_time desc')
            -> paginate(15,false,array('query'=>$query))
            -> each(function($item,$key){
                $item['status_zn'] = self::$status[$item['status']];
                $item['scene_zn']  = self::$scene[$item['scene']];
                $item['add_time']  = date('Y-m-d H;i:s',$item['add_time']);
                return $item;
            });
        return $result;
    }


}
