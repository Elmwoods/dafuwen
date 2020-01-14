<?php
/** 
 * 微信支付
 */

require 'WxPayPubHelper.class.php'; //支付基类

class miniAppPay extends Wxpay_client_pub{

    public $payment_info=array();

    function __construct($payment_info = array()){
        //
        $paymentPlugin = db('plugin')->where("code='miniAppPay' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
        $payment_info = [
            'appid'=>$config_value['appid'],
            'appsecret'=>$config_value['appsecret'],
            'mchid'=>$config_value['mchid'],
            'key'=>$config_value['key'],
        ];
        $this->payment_info = $payment_info;

        parent::__construct($payment_info);
    }

    /**
     *  生成预微信小程序支付
     *  $param array $order_info 订单信息
     */
    public function get_payform(&$order_info=[],$openid){
        if(!$order_info){
            return false;
        }

        //使用统一支付接口
        $unifiedOrder = new UnifiedOrder_pub($this->payment_info);
        $jsApi = new JsApi_pub($this->payment_info);

        //$openid = 'or-sC0dLkMnLlP445jhq-kkBiPMc';//'or-sC0RYppg_rUmZklJ4qmys4qPw';
        $unifiedOrder->setParameter("openid",$openid);
        $unifiedOrder->setParameter("body",$order_info['order_sn']);//商品描述remark
        //自定义订单号，此处仅作举例
        $unifiedOrder->setParameter("out_trade_no",$order_info['order_sn']);//商户订单号 
        $unifiedOrder->setParameter("total_fee",$order_info['order_amount']*100);//总金额(微信支付单位为分)
        $unifiedOrder->setParameter("notify_url",API_URL.'paynotify/wxpay_notify');//异步通知地址 
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型 JSAPI，NATIVE，APP
        $unifiedOrder->setParameter("attach","wx_app");//附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用 
        
        //get getPrepayId
        $prepay_id = $unifiedOrder->getPrepayId();
        $jsApi->setPrepayId($prepay_id);
        
        if (!empty($prepay_id)) {   //返回客户端的参数  
            $info['appId'] = $this->appid;
            $info['timeStamp'] = ''.time().'';//时间戳 字符串
            $info['nonceStr'] =  strtoupper($this->trimString($this->createNoncestr()));
            $info['package'] = 'prepay_id='.$prepay_id; 
            $info['signType'] = 'MD5';
            $info['paySign'] = $this->getSign($info);

            $info['order_id'] = $order_info['order_id'];
            $info['order_sn'] = $order_info['order_sn'];

            return $info;
        }else{
            return false;
        }
    }
    
    /**
     *  微信a支付通知认证
     *  $param array $order_info 信息
     */
    public function verify_notify($order_info=[], $strict = false,$action=''){
        $postXml = file_get_contents("php://input");
        $notify = json_decode(json_encode(simplexml_load_string($postXml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);//$_POST TEST
        
        $responseTxt = 'false';
        $isSignStr = 'false';
        $error_msg = '';
        $terminal = 'wap';

        if(!$notify){
            $notify = array(
                'company_id'=>'system'
            );
            $error_msg = '非法回调,数据为空';
            $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,'error');
            return false;
        }
        /* 验证通知是否可信 */
        $sign_result = $this->getSign($notify);
        if ($sign_result != $notify['sign']){
            $error_msg = '签名不可信';
            $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
            return false;
        }else{
            $isSignStr='true';
        }
        $pay_result = $this->getPayResult($notify);
        if(!$pay_result){
            $error_msg = '订单支付状态失败'.$notify['result_code'];
            $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
             return false;
        }
        /*----------本地验证开始----------*/
        /* 验证与本地信息是否匹配 */
        /* 这里不只是付款通知，有可能是发货通知，确认收货通知 */
        if($order_info['out_comm_order_sn']){
            $out_trade_no = $order_info['out_comm_order_sn'];
        }else{
            $out_trade_no = $order_info['order_sn'];
        }
        if ($out_trade_no != $notify['out_trade_no']){//order_sn old
            $error_msg = '订单不一致';
            /* 通知中的订单与欲改变的订单不一致 */
            $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
            return false;
        }
        //var_dump($order_info['order_amount'],$notify['total_fee']/100);
        //if ($order_info['order_amount'] != $notify['total_fee']/100){
            /* 支付的金额与实际金额不一致 */
        //  $error_msg = '支付的金额与实际金额不一致';
        //  $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
        //   return false;
        //}
        $responseTxt='true';
        /*----------通知验证结束----------*/
        $this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
        $notify['trade_no'] = $notify['transaction_id'];//支付平台外部订单号
        
        return array(
            'order_status'    =>  1,
            'pay_notify'=>  $notify,
        );
        // return true;
    }

    /**
     *    将验证结果反馈给网关
     *
     *    
     *    @param     bool   $result
     *    @return    void
     */
    function verify_result($result)
    {
        if ($result)
        {
            echo 'success';
        }
        else
        {
            echo 'fail';
        }
    }
    
    /**
     * 
     * 取得订单支付状态，成功或失败
     * @param array $param
     * @return array
     */
    public function getPayResult($param){

        return $param['result_code'] == 'SUCCESS' || $param['return_code'] == 'SUCCESS';
    }

    //写微信通知日志
    function _notifylog($notify,$responseTxt,$isSignStr='',$action,$error_msg='normal',$terminal='web'){
        $date = date('Y-m-d');
        $dirName = RUNTIME_PATH.'log/pay/cashier/'.$terminal.'/'.$date.'/';
        $fileName = 'response_'.$date.'.txt';
        $verify_text = "responseTxt=".$responseTxt."\n signTxt:isSign=".$isSignStr;
        $contents = http_build_query($notify);
        mkdirs($dirName);
        $responseInformation = 'Date:'.date('Y-m-d H:i:s',time()).">>>\r\n".$_SERVER['REMOTE_ADDR'].','.@$_SERVER['HTTP_USER_AGENT'].',http://'.$_SERVER['HTTP_HOST'].htmlentities($_SERVER['PHP_SELF']).'/paynotify?'.$_SERVER['QUERY_STRING']."\r\n{$action}Data:".$contents."\r\nVerify_text:".$verify_text."\r\nerror:".$error_msg."\r\n\r\n";
        
        file_put_contents($dirName.$fileName, $responseInformation, FILE_APPEND);
        //end log
    }
}
?>
