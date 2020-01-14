<?php

namespace app\api\validate;
use think\Validate;

class CashValidate extends Validate{
    /**
     * 验证规则
     */
    protected $rule = [
        'money'         => 'require|number|min:0.01',
        'bank_name'     =>'require',
        'bank_card'     =>'require',
        'realname'      =>'require'
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'money.require'    => '金额不能为空',
        'money.number'     => '金额必须是数字',
        'money.min'        => '金额不能为负数',
        'bank_name'        =>'银行名称不能为空',
        'bank_card'        =>'银行卡号不能为空',
        'realname'         =>'持卡人不能为空'
    ];

    /**
     * 验证场景
     */
    // protected $scene = [
        // 'add'  => ['subject_id', 'subject_id', 'content', 'author'],
        // 'edit' => ['username'],
        // 'login'=> ['username'=>'require|max:50','password'],
    // ];

}
