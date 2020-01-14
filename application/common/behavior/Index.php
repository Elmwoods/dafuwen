<?php

/**
 * Created by PhpStorm.
 * User: Xgh
 * Date: 2019/1/12
 * Time: 14:39
 */
namespace app\common\behavior;

use redis\Redis;

class Index
{
    public function run(&$params)
    {
        // 行为逻辑
        new Redis();
    }
}