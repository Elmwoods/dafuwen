<?php
/** 
 * 微信支付
 */

require 'WxPayPubHelper.class.php'; //支付基类

class Wxpay extends Wxpay_client_pub{

	public $payment_info=array();

	function __construct($payment_info = array()){
		parent::__construct($payment_info);
		//
		$this->payment_info = $payment_info;
	}
	
	/**
	 *	生成预微信app支付
	 *  $param array $order_info 订单信息
	 */
	public function get_payform(&$order_info=[]){
		if(!$order_info){
			return false;
		}

		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub($this->payment_info);
		$jsApi = new JsApi_pub($this->payment_info);
		
/*		if (!isset($_GET['code'])){
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(urlencode(SITE_URL.'/index.php?act=payment&op=wxpay&key='.@$_GET['key'].'&order_sn='.@$_GET['order_sn']));
			//var_dump($url);exit;
			Header("Location: $url"); exit();
		}
		//获取code码，以获取openid
	    $code = $_GET['code'];
		$jsApi->setCode($code);*/

		$openid = 'oJVuYs27qUdXsP11vR4Jj2yDLhK8';//$jsApi->getOpenId();
		$unifiedOrder->setParameter("openid",$openid);
		$unifiedOrder->setParameter("body",'Order:'.$order_info['remark']);//商品描述
		//自定义订单号，此处仅作举例
		$unifiedOrder->setParameter("out_trade_no",$order_info['order_sn']);//商户订单号 
		$unifiedOrder->setParameter("total_fee",$order_info['money']*100);//总金额(微信支付单位为分)
		$unifiedOrder->setParameter("notify_url",SITE_URL.'/api/paynotify/wxpay_notify');//异步通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型 JSAPI，NATIVE，APP
		$unifiedOrder->setParameter("attach",$order_info['order_type']);//	附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用 String(127)

		//get getPrepayId
		$prepay_id = $unifiedOrder->getPrepayId();
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		
		if (!empty($prepay_id)) {	//返回客户端的参数  
			$info['noncestr'] =  strtoupper($this->trimString($this->createNoncestr()));
			$info['appid'] = $this->_config['appid'];	
			$info['partnerid'] = $this->_config['mchid'];;
			$info['prepayid'] = $prepay_id;
			$info['timestamp'] = time();
			$info['package'] = 'Sign=WXPay'; 
			$info['jsApiParameters'] = $jsApiParameters;
			$info['sign'] = $this->getSign($info);
				
			// $info = json_encode($info);
			// $info = str_replace('null', '""', $info);

			return $info;
		}else{
			return false;
		}
	}
	
	/**
	 *	微信app支付通知认证
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
		$notify['company_id'] = $order_info['company_id'];	//
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
        if ($order_info['order_sn'] != $notify['out_trade_no']){
			$error_msg = '订单不一致';
            /* 通知中的订单与欲改变的订单不一致 */
			$this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
            return false;
        }
		//var_dump($order_info['money'],$notify['total_fee']/100);
        if ($order_info['money'] != $notify['total_fee']/100){
            /* 支付的金额与实际金额不一致 */
			$error_msg = '支付的金额与实际金额不一致';
			$this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
            return false;
        }
		$responseTxt='true';
		/*----------通知验证结束----------*/
		$this->_notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg,$terminal);
		$notify['trade_no'] = ['transaction_id'];//支付平台外部订单号
		
		return array(
            'status'    =>  ORDER_FINISHED,
			'pay_notify'=>  $notify,
        );
		// return true;
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
/*	function _notifylog($notify,$responseTxt,$isSignStr,$action,$error_msg='normal'){
		$fileName = 'response_'.date('Y-m-d').'.txt';
		$verify_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr;
		$contents = http_build_query($notify);
		$responseInformation = 'Date:'.date('Y-m-d H:i:s',time()).">>>\r\n".$_SERVER['REMOTE_ADDR'].','.$_SERVER['HTTP_USER_AGENT'].',http://'.$_SERVER['HTTP_HOST'].htmlentities($_SERVER['PHP_SELF']).'?'.$_SERVER['QUERY_STRING']."\r\n{$action}Data:".$contents."\r\nVerify_text:".$verify_text."\r\nWeberror:".$error_msg."\r\n\r\n";

		$dirName =  BASE_DATA_PATH.'/log/pay/wxpay/';
		file_put_contents($dirName.$fileName, $responseInformation, FILE_APPEND);
	}*/
}
?>
