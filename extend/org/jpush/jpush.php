<?php
class Jpush_send {
        private $app_key = '17c7b3190b1250e1a10c201a';        //待发送的应用程序(appKey)，只能填一个。
        private $master_secret = '8ee1c0541b1e896e11e94921';    //主密码
        private $url = "https://api.jpush.cn/v3/push";      //推送的地址
    
        //若实例化的时候传入相应的值则按新的相应值进行
        public function __construct($app_key=null, $master_secret=null,$url=null) {
            if ($app_key) $this->app_key = $app_key;
            if ($master_secret) $this->master_secret = $master_secret;
            if ($url) $this->url = $url;
        }
        //极光推送的类
        //文档见：http://docs.jpush.cn/display/dev/Push-API-v3
        
        /**组装需要的参数
            $receive = 'all';//全部
            $receive = array('tag'=>array('2401','2588','9527'));//标签
            $receive = array('alias'=>array('93d78b73611d886a74*****88497f501'));//别名
            $content = '这是一个测试的推送数据....测试....Hello World...';
            $extras = array('type'=>1,txt=>'121313')//推送附加字段的类型
            $m_time = '86400';        //离线保留时间
        **/
        //调用推送方法
        public function send_pub($receive, $title='',$content='',$extras=[],$m_time=86400){
            $message="";//存储推送状态
            $result = $this->push($receive,$title,$content,$extras,$m_time);
            if($result){
                $res_arr = json_decode($result, true);
                if(isset($res_arr['error'])){                       //如果返回了error则证明失败
                    //echo $res_arr['error']['message'];          //错误信息
                    $error_code=$res_arr['error']['code'];             //错误码
                        switch ($error_code) {
                            case 200:
                                $message= '发送成功！';
                                break;
                            case 1000:
                                $message= '失败(系统内部错误)';
                                break;
                            case 1001:
                                $message = '失败(只支持 HTTP Post 方法，不支持 Get 方法)';
                                break;
                            case 1002:
                                $message= '失败(缺少了必须的参数)';
                                break;
                            case 1003:
                                $message= '失败('.$res_arr['error']['message'].')';
                                break;
                            case 1004:
                                $message= '失败(验证失败)';
                                break;
                            case 1005:
                                $message= '失败(消息体太大)';
                                break;
                            case 1008:
                                $message= '失败(appkey参数非法)';
                                break;
                            case 1020:
                                $message= '失败(只支持 HTTPS 请求)';
                                break;
                            case 1030:
                                $message= '失败(内部服务超时)';
                                break;
                            default:
                                $message= '失败'.$res_arr['error']['message'];//'失败(返回其他状态，目前不清楚额，请联系开发人员！)';
                                break;
                        }
                }else{
                    $message="发送成功！";
                }
            }else{      //接口调用失败或无响应
                $message='接口调用失败或无响应';
            }
            $res_arr['error']['message'] = $message;
            //var_export($res_arr);exit;
            return json_encode($res_arr);
        }
        
    
        /*  $receiver 接收者的信息
         all 字符串 该产品下面的所有用户. 对app_key下的所有用户推送消息
        tag(20个)Array标签组(并集): tag=>array('昆明','北京','曲靖','上海');
        tag_and(20个)Array标签组(交集): tag_and=>array('广州','女');
        alias(1000)Array别名(并集): alias=>array('93d78b73611d886a74*****88497f501','606d05090896228f66ae10d1*****310');
        registration_id(1000)注册ID设备标识(并集): registration_id=>array('20effc071de0b45c1a**********2824746e1ff2001bd80308a467d800bed39e');
        */
        //$content 推送的内容。
        //$m_type 推送附加字段的类型(可不填) http,tips,chat....
        //$m_txt 推送附加字段的类型对应的内容(可不填) 可能是url,可能是一段文字。
        //$m_time 保存离线时间的秒数默认为一天(可不传)单位为秒
        public function push($receiver='all',$title='',$content='',$extras=[],$m_time=86400){
            $base64=base64_encode("$this->app_key:$this->master_secret");
            $header=array("Authorization:Basic $base64","Content-Type:application/json");
            $data = array();
            $data['platform'] = 'all';          //目标用户终端手机的平台类型android,ios,winphone
            $data['audience'] = $receiver;      //目标用户
            //$data['audience']['alias'] = array('573');//单个别名推送
            $data['notification'] = array(
                    //统一的模式--标准模式
                    "alert"=>$content,
                     //安卓自定义
                    "android"=>array(
                            "alert"=>$content,
                            "title"=>$title,
                            "builder_id"=>1,
                            "extras"=>$extras
                    ),
                    //ios的自定义
                    "ios"=>array(
                            "alert"=>$content,
                            "badge"=>"1",
                            "sound"=>"default",
                            "extras"=>$extras
                    )
            );
    
            //苹果自定义---为了弹出值方便调测
            $data['message'] = array(
                    "msg_content"=>$content,
                    "extras"=>$extras
            );
    
            //附加选项
            $data['options'] = array(
                    "sendno"=>time(),
                    "time_to_live"=>$m_time, //保存离线时间的秒数默认为一天
                    "apns_production"=>false, //布尔类型   指定 APNS 通知发送环境：0开发环境，1生产环境。或者传递false和true
            );
            $param = json_encode($data);
            $res = $this->push_curl($param,$header);
            if($res){       //得到返回值--成功已否后面判断
                return $res;
            }else{          //未得到返回值--返回失败
                return false;
            }
        }
    
        //推送的Curl方法
        public function push_curl($param="",$header="") {
            if (empty($param)) { return false; }
            $postUrl = $this->url;
            $curlPost = $param;
            $ch = curl_init();                                      //初始化curl
            curl_setopt($ch, CURLOPT_URL,$postUrl);                 //抓取指定网页
            curl_setopt($ch, CURLOPT_HEADER, 0);                    //设置header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_POST, 1);                      //post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);           // 增加 HTTP Header（头）里的字段
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        // 终止从服务端进行验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $data = curl_exec($ch);                                 //运行curl
            curl_close($ch);
            return $data;
        }
    
}


//test
/*
public function pushMess(){  
    vendor('Jpush/Jpush');  
    $pushObj = new Jpush();  
    //组装需要的参数  
    $receive = 'all';     //全部  
    //$receive = array('tag'=>array('1','2','3'));      //标签  
    //$receive = array('alias'=>array('111'));    //别名  
    $title = $_POST['title'];  
    $content =  $_POST['content']; 
    $m_time = '86400';        //离线保留时间  
    $extras = array("m_type"=>1, "txt"=>'test');   //自定义数组  
    //调用推送,并处理  
    $result = $pushObj->push($receive,$title,$content,$extras,$m_time);  
    if($result){  
        $res_arr = json_decode($result, true);  
        if(isset($res_arr['error'])){   //如果返回了error则证明失败  
            //错误信息 错误码  
            $this->error($res_arr['error']['message'].'：'.$res_arr['error']['code'],'');      
        }else{  
            //处理成功的推送......  
            //可执行一系列对应操作~  
            $this->success('推送成功~');  
        }  
    }else{      //接口调用失败或无响应  
        $this->error('接口调用失败或无响应~');  
    }  
}  
*/

?>