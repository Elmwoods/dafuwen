<?php

namespace app\api\validate;

use think\Db;
use think\Validate;

class PigGoods extends Validate{
    /**
     * 验证规则
     */
    protected $rule = [
        'id'         => 'require|number|min:1|is_allow',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'id.require'    => 'id不能为空',
        'id.number'     => 'id必须是数字',
    ];

    /**
     * 验证场景
     */
     protected $scene = [
         'redis_id'  => ['id'],
     ];

    protected function is_allow($value){
        $id = Db::name('pig_goods')->where('id',$value)->value('id');
        if(!$id)
            return '传入参数不合理';
        return true;
    }

}
