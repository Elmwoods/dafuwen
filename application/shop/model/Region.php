<?php

namespace app\shop\model;

use think\Db;
use think\Model;

class Region extends Model {

    /**
     * 获取地区
     */
    public function regionList($parent_id = 0){
        $map['parent_id'] = $parent_id;
        return Db::name('region')->where($map)->select();
    }

}
