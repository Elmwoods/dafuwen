<?php

namespace addons\test\model;

use think\Model;
use think\Db;

class DemoModel extends Model
{
    protected $name = 'ad_position';

    /**
     * 根据条件获取列表信息
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getAdAll()
    {

        return $this->order('orderby desc')->select(); 

    }



   

}