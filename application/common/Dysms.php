<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/18
 * Time: 11:37
 */

namespace app\common;

use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\Config;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;


/***
 * 阿里大于短信验证码类
 * Class Dysms
 * @package app\common
 */
class Dysms
{
    /**
     * 判断手机号码是不是合法
     * @param $mobile
     * @return bool
     */
    private function isPhoneNum($mobile){
        if(!preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
            return false;
        }
        return true;
    }

    /**
     * 发送验证码
     * @param string $mobile    接收手机号
     * @param string $code      验证码
     * @return array
     */
    public function sendMsg($mobile,$code){
        if( empty($mobile) || empty($code) ) return array('Message'=>'缺少参数','Code'=>'Error');
        if(!$this->isPhoneNum($mobile)) return array('Message'=>'无效的手机号','Code'=>'Error');

        require_once EXTEND_PATH.'/aliyun/dysms/vendor/autoload.php';
        Config::load();             //加载区域结点配置

        $config = config('dysms');       //取出参数配置

        $accessKeyId = $config['AccessKeyId'];
        $accessKeySecret = $config['AccessSecret'];
        $templateParam = array("code"=>$code);           //模板变量替换
        $signName = (empty($config['SignName'])?'阿里大于测试专用':$config['SignName']);
        $templateCode = $config['TemplateCode'];   //短信模板ID


        //短信API产品名（短信产品名固定，无需修改）
        $product = "Dysmsapi";
        //短信API产品域名（接口地址固定，无需修改）
        $domain = "dysmsapi.aliyuncs.com";
        //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）
        $region = "cn-hangzhou";

        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
        // 增加服务结点
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
        // 初始化AcsClient用于发起请求
        $acsClient= new DefaultAcsClient($profile);
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($mobile);

        // 必填，设置签名名称
        $request->setSignName($signName);

        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);

        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }

        //发起访问请求
        $acsResponse = $acsClient->getAcsResponse($request);

        //返回请求结果
        $result = json_decode(json_encode($acsResponse),true);
        return $result;
    }
}