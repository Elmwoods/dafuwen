<?php

namespace app\common\logic;

/**
 * Description of SmsLogic
 *
 * 短信类
 */
use app\api\controller\JuHe;
class SmsLogic
{
    private $config;

    public function __construct()
    {
//        $this->config = tpCache('sms') ?: [];
        init_config();//初始配置表数据
    }

    /**
     * 发送短信逻辑
     * @param unknown $scene
     */
    public function sendSms($scene, $sender, $params, $unique_id=0)
    {
        $smsTemp = M('sms_template')->where("send_scene", $scene)->find();    //用户注册.
        $code = !empty($params['code']) ? $params['code'] : false;
        $consignee = !empty($params['consignee']) ? $params['consignee'] : false;
        $user_name = !empty($params['user_name']) ? $params['user_name'] : false;
        $mobile = !empty($params['mobile']) ? $params['mobile'] : false;
        $order_id = !empty($params['order_id']) ? $params['order_id'] : 0;

        $smsParams = array(
            1 => "{\"code\":\"$code\"}",                                                                                                          //1. 用户注册 (验证码类型短信只能有一个变量)
            2 => "{\"code\":\"$code\"}",                                                                                                          //2. 用户找回密码 (验证码类型短信只能有一个变量)
            3 => "{\"consignee\":\"$consignee\",\"phone\":\"$mobile\"}",                                                       //3. 客户下单
            4 => "{\"order_id\":\"$order_id\"}",                                                                                                //4. 客户支付
            5 => "{\"user_name\":\"$user_name\",\"consignee\":\"$consignee\"}",                                           //5. 商家发货
            6 => "{\"code\":\"$code\"}",                                                                                                           //6. 修改手机号码 (验证码类型短信只能有一个变量)
        );

        $smsParam = $smsParams[$scene];

        //提取发送短信内容
        $scenes = C('SEND_SCENE');
        $msg = $scenes[$scene][1];
        $params_arr = json_decode($smsParam);
        foreach ($params_arr as $k => $v) {
            $msg = str_replace('${' . $k . '}', $v, $msg);
        }

        // IP限制
        $sms_ip_array = [
            1 =>config('scene_1'),
            2 =>config('scene_2'),
            5 =>config('scene_5'),
            6 =>config('scene_6'),
        ];

        $sms_ip = request()->ip();
        $is_true = in_array($scene,[1,2,5,6]);
        if($is_true && ($sms_ip_array[$scene] > 0)){
            $sms_count = M('sms_log')->whereTime('add_time','today')->where(['scene' => $scene,'status'=>1,'sms_ip'=>$sms_ip])->count();
            if($sms_ip_array[$scene] <= $sms_count){
                return $result = ['status' => -1, 'msg' => '今日短信已到上限'];
            }
        }
        
        //发送记录存储数据库
        $log_id = M('sms_log')->insertGetId(array('mobile' => $sender, 'code' => $code, 'add_time' => time(), 'status' => 0, 'scene' => $scene, 'msg' => $msg,'sms_ip'=>$sms_ip));
        if ($sender != '' && check_mobile($sender)) {//如果是正常的手机号码才发送
            try {
//                $resp = $this->realSendSms($sender, $smsTemp['sms_sign'], $smsParam, $smsTemp['sms_tpl_code']);
                $sendJuHeSms = new JuHe;
                $resp = $sendJuHeSms->sendJuHeSms($scene,$sender,$params['code']);
            } catch (\Exception $e) {
                $resp = ['status' => -1, 'msg' => $e->getMessage()];
            }
            if ($resp['status'] == 1) {
                M('sms_log')->where(array('id' => $log_id))->update(array('status' => 1)); //修改发送状态为成功
            }else{
                M('sms_log')->where(array('id' => $log_id))->update(array('error_msg'=>$resp['msg'])); //发送失败, 将发送失败信息保存数据库
            }
            return $resp;
        } else {
           return $result = ['status' => -1, 'msg' => '接收手机号不正确['.$sender.']'];
        }

    }
    
    /*
     *发送短信
     * lico
     */
    function realSendSMS($mobile, $smsSign, $smsParam, $templateCode) {
        //时区设置：亚洲/上海
        date_default_timezone_set('Asia/Shanghai');
        \think\Loader::import('org.alisms.sendSms',EXTEND_PATH);//导入阿里大于短信类
        $c = new \SendSms;
        $config = tpCache('sms');

        //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
        $c->setAccessKeyId($config['sms_appkey']);
        //App Secret的值也是在哪里一起的 你点击查看就有了
        $c->setAccessKeySecret($config['sms_secretKey']);
        //接收短信号 必须
        $c->setPhoneNumbers($mobile);
        //短信签名 必须
        $c->setSignName($smsSign);
        //短信模板 必须
        $c->setTemplateCode($templateCode);
        //短信模板 必须
        $c->setTemplateParam($smsParam);

        $result = $c->send();

        //结果处理
        $result = (array)$result;
        if($result['Code']=='OK' && !empty($result['RequestId'])){
            return array('status' => 1, 'msg' => '短信发送成功');
        }else{
            if($result['Message']=='触发分钟级流控Permits:1'){
                $result['Message'] = '同一手机号1分钟内,不能重复获取验证码！';
            }elseif($result['Message']=='触发小时级流控Permits:5'){
                $result['Message'] = '同一手机号1小时内，不能获取超过5条短信！';
            }else{
                $result['Message'] = '同一手机号1天内，不能获取超过10条短信！';
            }
            if($result['Code']=='isv.BUSINESS_LIMIT_CONTROL'){
                return array('status' => 0, 'msg' => $result['Message']);
            }
        }

    }
}
