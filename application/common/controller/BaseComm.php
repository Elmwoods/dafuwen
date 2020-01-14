<?php

namespace app\common\controller;
use think\Controller;

//公共控制器基类
class BaseComm extends Controller{

	protected $_errors = array();
	//初始化操作
    public function __construct() {
    	init_config();//初始配置表数据
        parent::__construct();
    }

    /**
     *    触发错误
     *
     *    @param     string $errmsg
     *    @return    void
     */
    function _error($msg, $obj = ''){
        if(is_array($msg)){
            $this->_errors = array_merge($this->_errors, $msg);
        }
        else{
            $this->_errors[] = compact('msg', 'obj');
        }
    }

	/**
     *    获取错误列表
     *
     *    @return    array
     */
    function get_error(){
        return $this->_errors;
    }

    //操作成功返回json
    public function json_success($msg='ok',$data=[],$pages=1){
        $return = array("status"=>1,"message"=>$msg,"data"=>$data);
        if($pages>1)$return['pages'] = $pages;
        echo json_encode($return);
        exit;
    }

    //操作失败返回json
    public function json_error($msg='failed',$data=[]){
        echo json_encode(array("status"=>0,"message"=>$msg,"data"=>$data));
        exit;
    }

    /**
     * json格式返回
     * @param $status
     * @param string $message
     * @param array $data
     * @return string
     */
    public function json_return($status, $message = '', $data = array(),$pages=1){
        $return = array();
        $return['status'] = $status;
        $return['message'] = $message;
        $return['data'] = $data;
        if($pages>1)$return['pages'] = $pages;

        echo json_encode($return);
        exit;
    }

    //接口公共参数解析
    public  function get_public_params(){
        $public_params = input('public_params/a','');
        if(!empty($public_params)){
            if(!is_array($public_params) || isset($public_params[0])){//公共参数json格式
                $public_params = json_decode(input('public_params'),true);
            }
        }
        return $public_params;
    }


}