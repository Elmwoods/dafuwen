<?php
namespace addons\test\controller;

use think\addons\Controller;
class Action extends Controller
{
    public function link()
    { 

        return $this->fetch();
    }

}