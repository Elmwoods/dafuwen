<?php
namespace app\api\controller;
use think\Exception;
use My\DataReturn;
use think\Request;
use app\home\model\PictureModel;
use app\api\controller\Addsalt;
use org\Verify;

//账户
class Payment extends Base{

    public function __construct(){
        parent::__construct();
    }

    /**
     * 账户列表
     */
    public function user_payment(){
        //分页参数设置
        $page = input('page') ? input('page'):1;
        $pagesize = config('paginate')['list_rows'];//每页数量
        $offset = $pagesize * ($page - 1);//起始页
        $where=[];
        $where['user_id']=$this->user_info['user_id'];
        $payment_list=db('user_payment')->where($where)->limit($offset, $pagesize)->order('id desc')->select();
        foreach($payment_list as $k=>$v){
            $Addsalt=new Addsalt();
            $check=$Addsalt->checkpaymentsalt($v["id"]);
            if(!$check){
                DataReturn::returnBase64Json(-1, "账号异常,请联系管理员");
            }

            switch($v['type']){
                case 1: $payment_list[$k]['pay_name']="支付宝"; break;
                case 2: $payment_list[$k]['pay_name']="微信"; break;
                case 3: $payment_list[$k]['pay_name']="银行卡"; break;
                default:$payment_list[$k]['pay_name']=" ";
            }
        }
        if ($payment_list) {
            DataReturn::returnBase64Json(200, "返回数据成功", $payment_list);
        } else {
            DataReturn::returnBase64Json(0, "暂未添加银行卡");
        }
        
    }

    /**
     * 修改账户信息
     */
    public function edit_payment(){
        if (IS_POST) {

            $post = I('post.');
            $type = $post['data']['type'] ? $post['data']['type'] : 1;
            $_type = $post['data']['c_type'];//1支付宝，2微信，3银行卡
            $c_type = '';
            switch($_type){
                case '支付宝': $c_type = 1; break;
                case '微信'  : $c_type = 2; break;
                case '银行卡': $c_type = 3; break;
            }
            $data=[];
            $data['account']=$post['data']['account'];
            $data['name']=$post['data']['name'];
            $data['mobile']=$post['data']['mobile'];
            $salt_qrcode='';
            if($c_type==3){
                $data['bank_name']=$post['data']['bank_name'];
                $data['branch_name']=$post['data']['branch_name'];
            }else{
                $imgs=$post['data']['imgs'];
                if(empty($imgs)){
                    DataReturn::returnJson(0, '二维码不能为空', []);
                }
                $data['qrcode_url'] = $imgs;
                $salt_qrcode=$imgs;
            }
            $data["paysalt"]=md5($post['data']['account'].$data['name'].$salt_qrcode.config('salt'));

            $user = session('user');

            $mobile = db('users')->where(['user_id' => $user['user_id']])->value('mobile');
            

            $sms_log = db('sms_log')->where(['mobile' => $mobile, 'status' => 1])->order('id desc')->field('code, add_time')->find();
            $sms_time_out =120;  //5分钟后失效
            $timeOut = $sms_log['add_time']+ $sms_time_out;

            $mess_open = config('mess_open');
            if($mess_open){
                $code = $post['data']['code'];
                if(empty($code)){
                    DataReturn::returnJson(0, '手机验证码不能为空', []);
                }
                if($code !== $sms_log['code']){
                    DataReturn::returnJson(0, "手机验证码不正确", []);
                }else if($timeOut < time()){
                    DataReturn::returnJson(0, "手机验证码已超时失效", []);
                }
            }
          
          	// $code_s = $post['data']['code_s'];
                
           //  if(empty($code_s)){
           //      DataReturn::returnJson(0, '验证码不能为空', []);
           //  }
          
          	// $verify = new Verify();
            
           //  if(!$verify->check($code_s)){
           //      DataReturn::returnJson(0, "验证码错误", []);            
           //  }


            if($type==2){//修改账户
                $id = $post['data']['id'];
                $flag=db('user_payment')->where(array('user_id'=>$this->user_info['user_id'],'id'=>$id))->update($data);
                if(empty($flag)){
                    DataReturn::returnJson(0,'修改失败',[]);
                }
                DataReturn::returnJson(200,'修改成功',[]);
            }else{//添加账户

                $is_have=db('user_payment')->where(array('user_id'=>$this->user_info['user_id'],'type'=>$c_type))->find();
                if($is_have){
                    DataReturn::returnJson(0,'已存在此类型账号，请勿重复添加',[]);
                }
                $data['type']=$c_type;
                $data['user_id']=$this->user_info['user_id'];
                $data['create_time']=time();
                if (M('user_payment')->add($data)) {
                    DataReturn::returnJson(200, "添加成功", []);
                } else {
                    DataReturn::returnJson(0, "操作失败", []);
                }
            }
        }
    }

    /**
     * 卡包详情
     */
    public function payment_info(){
        $post = I('post.');
        $id   = $post['data']['id'];
        if(empty($id)){
            DataReturn::returnJson(0, '返回数据失败', []);
        }
        $payment_info=db('user_payment')->where(array('user_id'=>$this->user_info['user_id'],'id'=>$id))->find();
        switch($payment_info['type']){
            case 1: $payment_info['pay_name']="支付宝"; break;
            case 2: $payment_info['pay_name']="微信"; break;
            case 3: $payment_info['pay_name']="银行卡"; break;
            default:$payment_info['pay_name']=" ";
        }
        DataReturn::returnBase64Json(200, "返回数据成功", $payment_info);
    }

     /*
     * base64格式上传图片
     */
    public function upload_base64_paycode(){

        $post = request()->param(); 
        $bg = $post['data']['img'];//获取图片流
        $save_url = '/uploads/paycode/' . date('Y', time()) . '/' . date('m-d', time());
        try {
            $result = upload_base64($bg,$save_url);
            if (checkHex('.'.$result) !== 0) {
                unlink('.'.$result);
                throw new \Exception("上传文件格式非法", 1);
            }
        } catch (\Exception $e) {
            DataReturn::returnJson(0, $e->getMessage(), []);
        }
        DataReturn::returnJson(200, "上传成功", $result);
    }

    /**
     * 删除收款方式
     */
    public function del_payment(){
        $post = I('post.');
        $id   = $post['data']['id'];
        if(empty($id)){
            DataReturn::returnJson(0, '请选择要删除的收款方式', []);
        }
        $res = db('user_payment')->where(array('user_id'=>$this->user_id,'id'=>$id))->delete();
        if ($res) {
            DataReturn::returnBase64Json(1, "删除成功", []);
        }else{
            DataReturn::returnBase64Json(0, "删除失败", []);
        }
    }

    //上传头像
    public function uploadimage(){
        if ($_FILES['head_pic']['tmp_name']) {
            if($_FILES['head_pic']['name'] == 'blob'){
                //给ios传的base64一个后缀名
                $_FILES['head_pic']['name'] = "blob.png";
            }
            $file = $this->request->file('head_pic');
            $image_upload_limit_size = config('image_upload_limit_size');
            $validate = ['size' => $image_upload_limit_size, 'ext' => 'jpg,png,jpeg'];
            $dir = 'uploads/paycode/';
            if (!($_exists = file_exists($dir))) {
                $isMk = mkdir($dir,0777,true);
            }
            $parentDir = date('Ymd');
            $info = $file->validate($validate)->move($dir, true);
            if ($info) {
                
                $return['imgpath'] = '/' . $dir . $parentDir . '/' . $info->getFilename();



                DataReturn::returnJson(200, "上传成功", $return);
                //exit(json_encode(['status'=>"success",'msg'=>"上传成功",'data'=>$return]));
                // DataReturn::returnJson('success', '上传成功', $return);
            } else {
                DataReturn::returnJson(0, "上传失败", []);
                // DataReturn::returnJson('error', '上传失败');
            }
        }
    }

}
