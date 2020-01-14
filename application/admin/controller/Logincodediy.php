<?php

namespace app\admin\controller;
use app\admin\model\Node;
use think\Controller;

class Logincodediy extends Controller
{
    public function index()
    {

		$code = rand(10000000,99999999);
      	//session('loginCodeDiy',$code);
      	file_put_contents(ROOT_PATH .'/data/'. $code.'.txt',$code);
        echo $code;
    }
}