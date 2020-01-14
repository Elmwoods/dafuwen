<?php
namespace app\api\controller;


use My\DataReturn;
use think\Db;
//use org\WXBiz\errorCode;

class JuHe
{

    protected $sendUrl = 'http://v.juhe.cn/sms/send';

    protected $key = " ";

    public function __construct($key = "", $sendUrl = "")
    {   
        init_config();
        $this->sendUrl = empty($sendUrl) ? $this->sendUrl : $sendUrl;
        $this->key = empty($key) ? config('mess_key') : $key;
    }

    /**
     * @param bool $params [请求的参数]
     * @param int $ispost [是否采用POST形式]
     * @param bool $arr [是否转译为数组]
     * @return bool|array
     */
    public function sendSms($params=false, $ispost=1, $arr = true){



        $httpInfo = array();
        $ch = curl_init();
        $url = $this->sendUrl;
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            $params['key'] = $this->key;
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $arr ? json_decode($response,true) : $response;
    }


    public function Demo(){
        $smsConf = array(
            'mobile'    => '13766312996', //接受短信的用户手机号码
            'tpl_id'    => '121563', //您申请的短信模板ID，根据实际情况修改
            'tpl_value' =>'#code#=12345' //您设置的模板变量，根据实际情况修改   eg:#code#=1234&#company#=聚合数据
        );

        $result = (new JuHe())->sendSms($smsConf);//请求发送短信
        if($result){
            $error_code = $result['error_code'];
            if($error_code == 0){
                //状态为0，说明短信发送成功
                echo "短信发送成功,短信ID：".$result['result']['sid'];
            }else{
                //状态非0，说明失败
                $msg = $result['reason'];
                echo "短信发送失败(".$error_code.")：".$msg;
            }
        }else{
            //返回内容异常，以下可根据业务逻辑自行修改
            echo "请求发送短信失败";
        }
        exit();
    }

    /**
     * 调用发送验证码接口
     * @param string $mobile  手机号码
     * @param string $captcha 手机验证码
     * @param string $tplId 短信模板Id
     * @return bool
     * @throws JsonException
     */
    public static function sendJuHeSms($scene,$mobile,$captcha){
        
        $mess_tpid = config('mess_tpid');
        //加入图形验证码
        $img_code = Db::name('config')->where('name','img_code_'.$scene)->value('status');

        if($scene == 1){       // 1注册
            $tplId = $mess_tpid[0];
        }elseif ($scene == 2){ // 2修改登陆密码
            $tplId = $mess_tpid[1];
        }elseif ($scene == 3){ // 3抢到区块猪
            $tplId = $mess_tpid[3];
        }elseif ($scene == 4){ // 4神兽订单被抢购
            $tplId = $mess_tpid[4];
        }elseif ($scene == 5){ // 5修改交易密码
            $tplId = $mess_tpid[1];
        }elseif ($scene == 6){ // 6添加银行卡
            $tplId = $mess_tpid[2];
        }

        $smsConf = [
            'mobile'=>$mobile, //手机号
            'tpl_id'=>$tplId, //短信模板id
            'tpl_value'=>"#code#={$captcha}&#company#=聚合数据"
        ];

        //开启了图形验证码
        if($img_code){

        }

        $result = (new JuHe())->sendSms($smsConf);//请求发送短信
        if($result){
            $error_code = $result['error_code'];

            if($error_code == 0){
                return array('status' => 1, 'msg' => '短信发送成功');
            }else{
                //$errData = ErrorCode::MOBILE_CAPTCHA_SEND_ERROR;
                //$errData['message'] = $result['reason'];
                return array('status' => 0, 'msg' => $result['reason']);
            }
        }else{
            //返回内容异常，以下可根据业务逻辑自行修改
            //throw new JsonException(ErrorCode::NOT_NETWORK);
            return array('status' => 0, 'msg' => '短信发送失败');
        }
    }


//    /**
//     * 发送短信通知[出售]
//     * @param string $mobile  手机号码
//     * @param string $tplId 短信模板Id
//     * @return bool
//     * @throws JsonException
//     */
//    public static function sendNoticeSms($mobile,$tplId = '121563'){
//        $smsConf = [
//            'mobile'=>$mobile,
//            'tpl_id'=>$tplId,
//            'tpl_value'=>"#company#=聚合数据"
//        ];
//
//        $result = (new JuHe())->sendSms($smsConf);//请求发送短信
//        if($result){
//            $error_code = $result['error_code'];
//
//            if($error_code == 0){
//                return true;
//            }else{
//                $errData = ErrorCode::MOBILE_CAPTCHA_SEND_ERROR;
//                $errData['message'] = $result['reason'];
//                throw new JsonException($errData);
//            }
//        }else{
//            //返回内容异常，以下可根据业务逻辑自行修改
//            throw new JsonException(ErrorCode::NOT_NETWORK);
//        }
//    }
//
//    /**
//     * 发送短信通知[购买]
//     * @param string $mobile  手机号码
//     * @param string $tplId 短信模板Id
//     * @return bool
//     * @throws JsonException
//     */
//    public static function sendNoticeSmsTwo($mobile,$tplId = '121562'){
//        $smsConf = [
//            'mobile'=>$mobile,
//            'tpl_id'=>$tplId,
//            'tpl_value'=>"#company#=聚合数据"
//        ];
//
//        $result = (new JuHe())->sendSms($smsConf);//请求发送短信
//        if($result){
//            $error_code = $result['error_code'];
//
//            if($error_code == 0){
//                return array('status' => 1, 'msg' => '短信发送成功');
//            }else{
//                $errData = ErrorCode::MOBILE_CAPTCHA_SEND_ERROR;
//                $errData['message'] = $result['reason'];
//                throw new JsonException($errData);
//            }
//        }else{
//            //返回内容异常，以下可根据业务逻辑自行修改
//            throw new JsonException(ErrorCode::NOT_NETWORK);
//        }
//    }
}