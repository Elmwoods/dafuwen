<?php

namespace app\api\validate;

use think\Validate;

class BankcardValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'bank_name'         =>'require',
        'owner_card_user'   =>'require',
        'card_no'           =>'require'
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'bank_name'         =>'银行名称不能为空',
        'owner_card_user'   =>'持卡人不能为空',
        'card_no'           =>'银行卡号不能为空'
    ];

}