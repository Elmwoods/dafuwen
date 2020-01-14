<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = '';
	//受理商ID，身份标识
	const MCHID = '';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = '';
	const CURL_TIMEOUT = 30;
}
	
?>