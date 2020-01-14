<?php

use app\common\controller\Logic;
use think\Db;
use think\Cache;
use taobao\AliSms;
use think\Validate;
include_once('function.php');//2018-07-04 17:23:47
include_once('mall_function.php');

define('WEITHINK_ADDON_PATH', ROOT_PATH .'/addons/');// 微信插件
const SUCCESS_CODE_STATUS = 1;//成功返回状态
const ERROR_CODE_STATUS = 0;//失败返回状态

/* 订单状态 */
//0=>待支付;1=>已支付;2=>交易完成;3=>取消订单
const ORDER_STATUS_ARR = [
    -1=>'支付失败',
    0=>'待支付',
    1=>'已支付',
    2=>'交易完成',
    3=>'取消订单',
];

////////////////////////////////////////////////////////////////////////////////////////

//配置表数据初始
function init_config(){
    //获取配置
    $config = Cache::get('db_config_data');
    if(!$config){
        $config = api('Config/lists');
        Cache::set('db_config_data',$config);
    }//var_dump($config);exit;
    \think\Config::set($config);
}

/* 取得支付方式实例 */
function get_payment_instance($code, $payment_info){
    $terminal_mode = 'web'; //实例web || wap的支付类
    if($payment_info['terminal'] == 'wap') $terminal_mode = 'wap';

    include_once(APP_PATH.'/common/PaymentBase.php');   //支付基础类
    include(EXTEND_PATH.'org/payments/'.$terminal_mode.'/'. $code .'/'.$code.'.class.php'); //支付方式类 terminal_mode

    include_once  APP_PATH."/shop/plugins/payment/{$code}/{$code}.class.php"; //

    $class_name = ucfirst($code);
    //var_dump($payment_info);
$code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    return new $class_name($payment_info);
}

/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'common';
    $callback = 'app\\' . $module . '\\api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }//var_dump($callback);exit;

    return call_user_func_array($callback, $vars);
}


/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group = 0)
{
    $list = config('config_group_list');
    return $group ? @$list[$group] : '';
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type = 0)
{
    $list = config('config_type_list');
    return $list[$type];
}


/**
 * 发送短信(参数：签名,模板（数组）,模板ID，手机号)
 */
function sms($signname = '', $param = [], $code = '', $phone)
{
    $alisms = new AliSms();
    $result = $alisms->sign($signname)->data($param)->code($code)->send($phone);
    return $result['info'];
}


/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        @unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (@rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}


//时间格式化1
function formatTime($time)
{
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int)($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int)($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int)($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int)($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}

//时间格式化2
function pincheTime($time)
{
    $today = strtotime(date('Y-m-d')); //今天零点
    $here = (int)(($time - $today) / 86400);
    if ($here == 1) {
        return '明天';
    }
    if ($here == 2) {
        return '后天';
    }
    if ($here >= 3 && $here < 7) {
        return $here . '天后';
    }
    if ($here >= 7 && $here < 30) {
        return '一周后';
    }
    if ($here >= 30 && $here < 365) {
        return '一个月后';
    }
    if ($here >= 365) {
        $r = (int)($here / 365) . '年后';
        return $r;
    }
    return '今天';
}

// 创建多级目录
function mkdirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}

/**
 * 时间戳格式化
 *
 * @param int $time
 * @return string 完整的时间显示
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    if (empty ($time))
        return '';

    $time = $time === NULL ? time() : intval($time);
    return date($format, $time);
}


function get_img_html($cover_id) {
    $url = get_cover_url ( $cover_id );

    return url_img_html ( $url );
}
function url_img_html($url) {
    if (empty ( $url ))
        return '';

    return '<img class="list_img" src="' . $url . '" >';
}


/**
 * 获取文档封面图片
 *
 * @param int $cover_id
 * @param string $field
 * @return 完整的数据 或者 指定的$field字段值
 */
function get_cover($cover_id, $field = null)
{
    if (empty ($cover_id))
        return false;

    $key = 'picture_' . $cover_id;
    $picture = Cache::get(($key));

    if (!$picture) {
        $map ['status'] = 1;
        $token = get_token();
        $picture = db('picture')->where($map)->getById($cover_id);//图片库

        Cache::set($key, $picture, 86400);
    }

    if (empty ($picture))
        return '';

    return empty ($field) ? $picture : $picture [$field];
}

function get_cover_url($cover_id,$is_return=0,$width = '', $height = '')
{
    $info = get_cover($cover_id);
    $thumb = '';
    if ($width > 0 && $height > 0) {//七牛处理
        $thumb = "?imageMogr2/thumbnail/{$width}x{$height}";
    } elseif ($width > 0) {
        $thumb = "?imageMogr2/thumbnail/{$width}x";
    } elseif ($height > 0) {
        $thumb = "?imageMogr2/thumbnail/x{$height}";
    }
    if ($width || $height) {
        $path = '';
        if ($info['url']) {
            $path = mk_rule_image($info['url'], $width, $height);
        } else {
            if (empty ($info ['path']))
                return '';
            $path = mk_rule_image($info['path'], $width, $height);
        }
        return $path . $thumb;
    } else {
        if (@$info ['url'])
            return $info ['url'] . $thumb;

        $url = @$info ['path'];
        if (empty ($url))
            return '';
        if($is_return==1){
            return $url . $thumb;
        }else{
            return SITE_URL . $url . $thumb;
        }
    }

}


/**
 * 系统非常规MD5加密方法
 *
 * @param string $str
 *          要加密的字符串
 * @return string
 */
function think_md5($str, $key = '')
{
    empty ($key) && $key = config('data_auth_key');
    return '' === $str ? '' : md5(sha1($str) . $key);
}

//@param object $data
function objToAarray($data)
{

    return json_decode(json_encode($data), true);
}

/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 *
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey = "", $pCondition = "")
{
    $result = array();
    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = ( array )$temp_array;
            }
            if (("" != $pCondition && $temp_array [$pCondition [0]] == $pCondition [1]) || "" == $pCondition) {
                $result [] = ("" == $pKey) ? $temp_array : isset ($temp_array [$pKey]) ? $temp_array [$pKey] : "";
            }
        }
        return $result;
    } else {
        return false;
    }
}


/**
 * ************************************************************
 *
 * 使用特定function对数组中所有元素做处理
 *
 * @param
 *          string &$array 要处理的字符串
 * @param string $function
 *          要执行的函数
 * @return boolean $apply_to_keys_also 是否也应用到key上
 * @access public
 *
 *         ***********************************************************
 */
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die ('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array [$key], $function, $apply_to_keys_also);
        } else {
            $array [$key] = $function ($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function ($key);
            if ($new_key != $key) {
                $array [$new_key] = $array [$key];
                unset ($array [$key]);
            }
        }
    }
    $recursive_counter--;
}

/**
 * ************************************************************
 *
 * 将数组转换为JSON字符串（兼容中文）
 *
 * @param array $array
 *          要转换的数组
 * @return string 转换得到的json字符串
 * @access public
 *
 *         ***********************************************************
 */
function wf_json($array)
{
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

// 以POST方式提交数据
function post_data($url, $param, $is_file = false, $return_array = true)
{
    set_time_limit(0);
    if (!$is_file && is_array($param)) {
        $param = wf_json($param);
    }
    if ($is_file) {
        $header [] = "content-type: multipart/form-data; charset=UTF-8";
    } else {
        $header [] = "content-type: application/json; charset=UTF-8";
    }
    $ch = curl_init();

    if (class_exists('\CURLFile')) { // php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        if (!empty($param['media']) && !is_object($param['media'])) {
            $param['media'] = new CURLFile(ltrim($param['media'], '@'));//5.5以上去除@
        }
    } else {
        if (defined('CURLOPT_SAFE_UPLOAD')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $flat = curl_errno($ch);
    if ($flat) {
        $data = curl_error($ch);
        addWeixinLog($flat, 'post_data flat');
        addWeixinLog($data, 'post_data msg');
    }

    curl_close($ch);
    // if($is_file)var_dump($res);
    $return_array && $res = json_decode($res, true);

    // var_dump($res);exit;
    return $res;
}

// 分割函数，同时支持常见的按空格、逗号、分号、换行进行分割
function wp_explode($string, $delimiter = "\s,;\r\n")
{
    if (empty ($string))
        return array();

    // 转换中文符号
    // $string = iconv ( 'utf-8', 'gbk', $string );
    // $string = preg_replace ( '/\xa3([\xa1-\xfe])/e', 'chr(ord(\1)-0x80)', $string );
    // $string = iconv ( 'gbk', 'utf-8', $string );

    $arr = preg_split('/[' . $delimiter . ']+/', $string);
    return array_unique(array_filter($arr));
}

// 获取显示确定规格图片
/*
 * http://img.baidu.com/hi/jx2/j_0002.gif
 * http://img1.gtimg.com/auto/pics/hv1/156/84/2125/138199701.jpg
 * /uploads/Editor/gh_dd85ac50d2dd/2016-08-26/57bfa4a23fba5.png
 */
function mk_rule_image($imgurl, $w, $h)
{

    if (preg_match('#^/uploads/picture/#i', $imgurl) || preg_match('#^/static/icon/#i', $imgurl)) { // 内部图片
        $imgurl = '.' . $imgurl;
        $filename = basename($imgurl);
        $filename_ex = explode('.', $filename);
        $dirname = dirname($imgurl);
        $dirname_new = $dirname . '/' . $filename_ex [0] . "_$w" . "X$h." . $filename_ex [1];
        if (file_exists($dirname_new)) {
            return str_replace('./uploads', SITE_URL . '/uploads', $dirname_new);
        }

        file_exists($imgurl) && $imginfo = getimagesize($imgurl); // 图片存在并获取到信息

        if ($imginfo) { // 规格图片存在
            if ($imginfo [0] > $w || $imginfo ['1'] > $h) {
                //生成缩略图
                $re = \think\Image::open($imgurl);

                $res = \think\Image::thumb($w, $h)->save($dirname_new);

            } else {
                return SITE_URL . $imgurl;
            }
        }
        return str_replace('./uploads', SITE_URL . '/uploads', $dirname_new);
    }
    if (preg_match('#^(http|https)://#i', $imgurl)) { // 外部
        $imgurl1 = $imgurl;
        $imginfo = getimagesize($imgurl); // 图片存在并获取到信息
        // dump($imginfo);
        $url_info = parse_url($imgurl);
        $filename = basename($url_info ['path']);
        $filename_ex = explode('.', $filename);
        $dirname = './uploads/picture';
        $dirname_new = $dirname . '/' . think_md5($filename_ex [0] . $url_info ['query']) . "_$w" . "X$h." . 'jpg'; // $filename_ex[1];
        $imgurl = SITE_URL . '/uploads/picture/' . think_md5($filename_ex [0] . $url_info ['query']) . "_$w" . "X$h." . 'jpg';
        if (file_exists($dirname_new)) {
            return $imgurl;
        }
        if ($imginfo) { // 规格图片存在
            if ($imginfo [0] > $w || $imginfo [1] > $h) {

                $save_filename = './uploads/picture/' . $filename;
                $res = getImg($imgurl1, $save_filename);

                $re = \think\Image::open($save_filename);

                $res = \think\Image::thumb($w, $h)->save($dirname_new);
                unlink($save_filename);
            } else {
                getImg($imgurl1, $dirname_new);
                $imgurl = SITE_URL . $dirname_new;
            }
        }
        return $imgurl;
    }
}

// 全局的安全过滤函数
function safe($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    // 只保留链接
    $link_tags = '<a>';
    // 只保留图片
    $image_tags = '<img>';
    // 只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    // 标题摘要基本格式
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike><section><header><footer><article><nav><audio><video>';
    // 兼容Form格式
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    // 内容等允许HTML的格式
    $html_tags = $base_tags . '<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    // 全HTML格式
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    // 过滤标签
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = strip_tags($text, ${$type . '_tags'});

    // 过滤攻击代码
    if ($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat [0], $mat [1] . $mat [3], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat [0], $mat [1] . $mat [3], $text);
        }
    }
    return $text;
}

/**
 * 用SHA1算法生成安全签名
 */
function getSHA1($array) {
    // 排序
    sort ( $array, SORT_STRING );
    $str = implode ( $array );
    return sha1 ( $str );
}

/* 根据id获取fiel路径 */
function get_file_url($id)
{
    if (empty ($id))
        return false;

    $key = 'File_' . $id;
    $file = think\Cache::get($key);

    if (!$file) {
        $file = db('file')->where(array(
            'id' => $id
        ))->find();
        think\Cache::set($key, $file, 86400);
    }

    if (empty ($file))
        return '';

    $info = $file;
    if (!empty($info ['url']))
        return $info ['url'];

    $url = $info ['savepath'];
    if (empty ($url))
        return '';

    return SITE_URL . '/uploads/download/' . $info ['savepath'] . $info ['savename'];
}


// 防超时的file_get_contents改造函数
function wp_file_get_contents($url)
{
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 30
        )
    )); // 超时时间，单位为秒

    return file_get_contents($url, 0, $context);
}


/**
 * 获取插件的配置数组
 */
function getAddonConfig($name, $token = '') {
  static $_config = array ();
  if (isset ( $_config [$name] )) {
    return $_config [$name];
  }

  $config = array ();

  $token = empty ( $token ) ? get_token () : $token;
  // dump($token);
  if (! empty ( $token )) {
    $addon_config = get_token_appinfo ( $token, 'addon_config' );
    $addon_config = json_decode ( $addon_config, true );
    if (isset ( $addon_config [$name] ))
      $config = $addon_config [$name];
  }
  if (empty ( $config )) {//通过数据库获取

  }
  if (! $config) {
    $temp_arr = include_once WEITHINK_ADDON_PATH . strtolower($name) . '/config.php';

    foreach ( $temp_arr as $key => $value ) {//todo..
      $config [$key] = $temp_arr [$key] ['value'];
    }
  }

  $_config [$name] = $config;
  return $config;
}



//获取session_id
///home/File/uploadPicture/session_id/turatkogrv02d6gu03fc2jod11
function get_session_id()
{
    if(! session_id()){//2017-12-20 17:27:03
        @session_start();
    }
    return session_id();
}


// php获取当前访问的完整url地址
function GetCurUrl()
{
    $url = HTTP_PREFIX;
    if ($_SERVER ['SERVER_PORT'] != '80' && $_SERVER ['SERVER_PORT'] != '443') {
        $url .= $_SERVER ['HTTP_HOST'] . ':' . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'];
    } else {
        $url .= $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
    }
    // 兼容后面的参数组装
    if (stripos($url, '?') === false) {
        $url .= '?t=' . time();
    }
    return $url;
}


// 获取微信小程序access_token，自动带缓存功能
function get_wxapp_access_token(){
    $wxapp_config = config('wechat.wxapp');
    $key = 'wxapp_access_token_' . $wxapp_config['appid'];
    $res = \think\Cache::get($key);
    if($res !== false){//缓存返回
        return $res;
    }else{
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wxapp_config['appid'].'&secret='.$wxapp_config['appsecret'];
        $res = wp_file_get_contents($url);
        $res = json_decode($res, true);
        if (!empty($res['errcode']) && @$res['errcode'] == '40013') {//
            //return get_wxapp_access_token();//重复请求
            exit($res['errmsg']);
        }else{
            // var_dump($info);exit;
            $wxapp_access_token = $res['access_token'];
            if (!empty ($wxapp_access_token)) {
                \think\Cache::set($key, $wxapp_access_token, $res['expires_in'] - 200);
                return $wxapp_access_token;
            } else {
                return 0;
            }
        }
    }
}

//获取当前微信公众号的支付配置信息
function get_wxapp_pay_info(){
    $appinfo = get_token_appinfo(config('wx_public_token'));
    $payment_info = [
        'appid'=>$appinfo['appid'],
        'appsecret'=>$appinfo['secret'],
        'mchid'=>$appinfo['mchid'],
        'key'=>$appinfo['paykey'],
    ];
    return $payment_info;
}

/*
 * @通过curl方式获取指定的图片到本地
 * @ 完整的图片地址
 * @ 要存储的文件名
 */
function getImg($url = "", $filename = "")
{
    // 去除URL连接上面可能的引号
    // $url = preg_replace( '/(?:^['"]+|['"/]+$)/', '', $url );
    $hander = curl_init();
    $fp = fopen($filename, 'wb');
    curl_setopt($hander, CURLOPT_URL, $url);
    curl_setopt($hander, CURLOPT_FILE, $fp);
    curl_setopt($hander, CURLOPT_HEADER, 0);
    curl_setopt($hander, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
    curl_setopt($hander, CURLOPT_TIMEOUT, 10);
    curl_exec($hander);
    curl_close($hander);
    fclose($fp);
    Return true;
}

//url 模板参数替换
function replace_url($content)
{
    $param ['token'] = get_token ();
    $param ['openid'] = get_openid ();

    $sreach = array(
        '[website]',
        '[token]',
        '[openid]'
    );
    $replace = array(
        SITE_URL,
        $param ['token'],
        $param ['openid']
    );

    $content = str_replace($sreach, $replace, $content);
    // echo $content;exit;
    return $content;
}

//根据经纬度反查地址
//高德
/*function get_location_address($longitude,$latitude){
  $key = 'e8496e8ac4b0f01100b98da5bde96597';//高德key
  $api_url = 'http://restapi.amap.com/v3/geocode/regeo?location='.$longitude.','.$latitude.'&key='.$key.'&s=rsv3&radius=1000&extensions=base';

  $res = @file_get_contents($api_url);
  $res = json_decode($res,true);
// \think\Log::write($res);
  if($res['status']==1){
    $data = $res['regeocode'];
    $addr_info = [
      'address'=>$data['formatted_address'],
      'province'=>$data['addressComponent']['province'],
      'city'=>$data['addressComponent']['city'],
      'district'=>$data['addressComponent']['district']
    ];

    \think\Log::write($addr_info);
    return $addr_info;
  }else{
    return '';
  }
}*/

//根据经纬度反查地址
//腾讯
function get_location_address($longitude,$latitude){
    $key = 'B36BZ-L3JHG-H4VQY-ICHQW-F2SXO-CPBNB';//腾讯key
    $api_url = 'http://apis.map.qq.com/ws/geocoder/v1/?location='.$latitude.','.$longitude.'&key='.$key.'&get_poi=0';
    $res = @file_get_contents($api_url);
    $res = json_decode($res,true);
    // \think\Log::write($res);
    if($res['status']==0){
        $data = $res['result'];
        $addr_info = [
          'address'=>$data['address'],
          'province'=>$data['address_component']['province'],
          'city'=>$data['address_component']['city'],
          'district'=>$data['address_component']['district'],
          'street'=>$data['address_component']['street'],
        ];

        \think\Log::write($addr_info);
        return $addr_info;
    }else{
        return '';
    }
}

/**
* 腾讯坐标转成百度坐标
* 中国正常GCJ02坐标---->百度地图BD09坐标
* 腾讯地图用的也是GCJ02坐标
* @param double $lat 纬度
* @param double $lng 经度
*/
function qq_to_baidu_position($lat,$lng,$type=''){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z =sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        if($type==1){
            return $lat;
        }else if($type==2){
            return $lng;
        }else{
            return array('lng'=>$lng,'lat'=>$lat);
        }
}

/**
* 百度坐标转腾讯坐标
* 百度地图BD09坐标---->中国正常GCJ02坐标
* 腾讯地图用的也是GCJ02坐标
* @param double $lat 纬度
* @param double $lng 经度
* @return array();
*/
function baidu_to_qq_position($lat,$lng,$type=''){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = number_format($z * cos($theta),6);
        $lat = number_format($z * sin($theta),6);
        if($type==1){
            return $lat;
        }else if($type==2){
            return $lng;
        }else{
            return array('lng'=>$lng,'lat'=>$lat);
        }
}


/**
 * 整理菜单树方法 2017-05-04 09:56:31
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //父类
    $child = [];  //子类

    foreach($param as $key=>$vo){

        if($vo['pid'] == 0){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $base_url =  url('/',[],false,true);//域名 2018-08-02 15:34:48
            //$vo['href'] = url($vo['name']); //跳转地址
            $vo['href'] = $base_url.$vo['name']; //跳转地址
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){

            if($v['pid'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);
    return $parent;
}
// 微信端的错误码转中文解释
function error_msg($return, $more_tips = '')
{
    $msg = array(
        '-1' => '系统繁忙，此时请开发者稍候再试',
        '0' => '请求成功',
        '40001' => '获取access_token时AppSecret错误，或者access_token无效。请开发者认真比对AppSecret的正确性，或查看是否正在为恰当的公众号调用接口',
        '40002' => '不合法的凭证类型',
        '40003' => '不合法的OpenID，请开发者确认OpenID（该用户）是否已关注公众号，或是否是其他公众号的OpenID',
        '40004' => '不合法的媒体文件类型',
        '40005' => '不合法的文件类型',
        '40006' => '不合法的文件大小',
        '40007' => '不合法的媒体文件id',
        '40008' => '不合法的消息类型',
        '40009' => '不合法的图片文件大小',
        '40010' => '不合法的语音文件大小',
        '40011' => '不合法的视频文件大小',
        '40012' => '不合法的缩略图文件大小',
        '40013' => '不合法的AppID，请开发者检查AppID的正确性，避免异常字符，注意大小写',
        '40014' => '不合法的access_token，请开发者认真比对access_token的有效性（如是否过期），或查看是否正在为恰当的公众号调用接口',
        '40015' => '不合法的菜单类型',
        '40016' => '不合法的按钮个数',
        '40017' => '不合法的按钮个数',
        '40018' => '不合法的按钮名字长度',
        '40019' => '不合法的按钮KEY长度',
        '40020' => '不合法的按钮URL长度',
        '40021' => '不合法的菜单版本号',
        '40022' => '不合法的子菜单级数',
        '40023' => '不合法的子菜单按钮个数',
        '40024' => '不合法的子菜单按钮类型',
        '40025' => '不合法的子菜单按钮名字长度',
        '40026' => '不合法的子菜单按钮KEY长度',
        '40027' => '不合法的子菜单按钮URL长度',
        '40028' => '不合法的自定义菜单使用用户',
        '40029' => '不合法的oauth_code',
        '40030' => '不合法的refresh_token',
        '40031' => '不合法的openid列表',
        '40032' => '不合法的openid列表长度',
        '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
        '40035' => '不合法的参数',
        '40038' => '不合法的请求格式',
        '40039' => '不合法的URL长度',
        '40050' => '不合法的分组id',
        '40051' => '分组名字不合法',
        '40117' => '分组名字不合法',
        '40118' => 'media_id大小不合法',
        '40119' => 'button类型错误',
        '40120' => 'button类型错误',
        '40121' => '不合法的media_id类型',
        '40132' => '微信号不合法',
        '40137' => '不支持的图片格式',
        '41001' => '缺少access_token参数',
        '41002' => '缺少appid参数',
        '41003' => '缺少refresh_token参数',
        '41004' => '缺少secret参数',
        '41005' => '缺少多媒体文件数据',
        '41006' => '缺少media_id参数',
        '41007' => '缺少子菜单数据',
        '41008' => '缺少oauth code',
        '41009' => '缺少openid',
        '42001' => 'access_token超时，请检查access_token的有效期，请参考基础支持-获取access_token中，对access_token的详细机制说明',
        '42002' => 'refresh_token超时',
        '42003' => 'oauth_code超时',
        '43001' => '需要GET请求',
        '43002' => '需要POST请求',
        '43003' => '需要HTTPS请求',
        '43004' => '需要接收者关注',
        '43005' => '需要好友关系',
        '44001' => '多媒体文件为空',
        '44002' => 'POST的数据包为空',
        '44003' => '图文消息内容为空',
        '44004' => '文本消息内容为空',
        '45001' => '多媒体文件大小超过限制',
        '45002' => '消息内容超过限制',
        '45003' => '标题字段超过限制',
        '45004' => '描述字段超过限制',
        '45005' => '链接字段超过限制',
        '45006' => '图片链接字段超过限制',
        '45007' => '语音播放时间超过限制',
        '45008' => '图文消息超过限制',
        '45009' => '接口调用超过限制',
        '45010' => '创建菜单个数超过限制',
        '45015' => '回复时间超过限制',
        '45016' => '系统分组，不允许修改',
        '45017' => '分组名字过长',
        '45018' => '分组数量超过上限',
        '46001' => '不存在媒体数据',
        '46002' => '不存在的菜单版本',
        '46003' => '不存在的菜单数据',
        '46004' => '不存在的用户',
        '47001' => '解析JSON/XML内容错误',
        '48001' => 'api功能未授权，请确认公众号已获得该接口，可以在公众平台官网-开发者中心页中查看接口权限',
        '50001' => '用户未授权该api',
        '50002' => '用户受限，可能是违规后接口被封禁',
        '61451' => '参数错误(invalid parameter)',
        '61452' => '无效客服账号(invalid kf_account)',
        '61453' => '客服帐号已存在(kf_account exsited)',
        '61454' => '客服帐号名长度超过限制(仅允许10个英文字符，不包括@及@后的公众号的微信号)(invalid kf_acount length)',
        '61455' => '客服帐号名包含非法字符(仅允许英文+数字)(illegal character in kf_account)',
        '61456' => '客服帐号个数超过限制(10个客服账号)(kf_account count exceeded)',
        '61457' => '无效头像文件类型(invalid file type)',
        '61450' => '系统错误(system error)',
        '61500' => '日期格式错误',
        '61501' => '日期范围错误',
        '9001001' => 'POST数据参数不合法',
        '9001002' => '远端服务不可用',
        '9001003' => 'Ticket不合法',
        '9001004' => '获取摇周边用户信息失败',
        '9001005' => '获取商户信息失败',
        '9001006' => '获取OpenID失败',
        '9001007' => '上传文件缺失',
        '9001008' => '上传素材的文件类型不合法',
        '9001009' => '上传素材的文件尺寸不合法',
        '9001010' => '上传失败',
        '9001020' => '帐号不合法',
        '9001021' => '已有设备激活率低于50%，不能新增设备',
        '9001022' => '设备申请数不合法，必须为大于0的数字',
        '9001023' => '已存在审核中的设备ID申请',
        '9001024' => '一次查询设备ID数量不能超过50',
        '9001025' => '设备ID不合法',
        '9001026' => '页面ID不合法',
        '9001027' => '页面参数不合法',
        '9001028' => '一次删除页面ID数量不能超过10',
        '9001029' => '页面已应用在设备中，请先解除应用关系再删除',
        '9001030' => '一次查询页面ID数量不能超过50',
        '9001031' => '时间区间不合法',
        '9001032' => '保存设备与页面的绑定关系参数错误',
        '9001033' => '门店ID不合法',
        '9001034' => '设备备注信息过长',
        '9001035' => '设备申请参数不合法',
        '9001036' => '查询起始值begin不合法'
    );

    if ($more_tips) {
        $res = $more_tips . ': ';
    } else {
        $res = '';
    }
    if (isset ($msg [$return ['errcode']])) {
        $res .= $msg [$return ['errcode']];
    } else {
        $res .= $return ['errmsg'];
    }

    $res .= ', 返回码：' . $return ['errcode'];

    return $res;
}

//重置用户session信息
function set_user_info($uid,$is_update=false){
    $UserComm = \think\Loader::model('UserComm','logic');
    $user_info = $UserComm->getUserAndAccountInfoById($uid);//本地用户信息
    //var_dump($user_info);exit;
    if(!empty($user_info)){
        session('wechat_info',$user_info);//重置当前用户session
    }
}

// 获取当前用户的Token
function get_token($token = NULL)
{
    //return 'gh_8178de62fdf6';
    $stoken = session('token');
    $reset = false;
    if ($token !== NULL && $token != '-1') {//
        session('token', $token);
        $reset = true;
    } elseif (!empty (input('token')) && input('token') != '-1') {
        session('token', input('token'));
        $reset = true;
    } elseif (!empty ($_REQUEST ['publicid'])) {//获取得公众号的token
        $publicid = input('publicid');
        $PublicModel = new \app\common\model\PublicModel();
        $token = $PublicModel->getInfo($publicid, 'token');
        $token && session('token', $token);
        $reset = true;
    }
    $token = session('token');
    if (!empty ($token) && $token != '-1' && $stoken != $token) {
        session('mid', null);
    }

    if (empty ($token)) {
        if(config('wx_public_token')){//默认公众的token 2017-05-03 17:39:01
            session('token', config('wx_public_token'));
            return config('wx_public_token');
        }else{
            $token = -1;
        }
    }

    //'gh_8178de62fdf6'
    return $token;
}

//本身微信openid
function get_openid($openid = NULL,$scope='snsapi_base')
{

    $token = get_token();
    if ($openid !== NULL && $openid != '-1' && $openid != '-2') {
        session('openid_' . $token, $openid);
    } elseif (!empty (input('openid')) && input('openid') != '-1' && input('openid') != '-2') {
        session('openid_' . $token, input('openid'));
    }
    // session('openid_' . $token, null);
    $openid = session('openid_' . $token);//直接从session获取
    //var_dump(11,$openid);exit;
    $isWeixinBrowser = isWeixinBrowser();
    if ((empty ($openid) || $openid == '-1') && $isWeixinBrowser && input('openid') != '-2' && request()->isGet() && !request()->isAjax()) {

        $callback = GetCurUrl();
        $openid = OAuthWeixin($callback, $token, true,$scope);
        if ($openid != false && $openid != '-2') {
            session('openid_' . $token, $openid);
        }
    }
    if (empty ($openid)) {
        return '-1';
    }
    return $openid;

}

// 通过UID获取openid
function getOpenidByUid($uid, $token = '')
{
    empty ($token) && $token = get_token();

    $map ['uid'] = $uid;
    $map ['token'] = $token;
    $follow = db('public_follow')->where($map)->field('openid')->find();
    return $follow['openid'];
}

/*
 * 获取支付的appid的openid
 * 微信支付和红包使用
 */
function getPaymentOpenid($appId = "", $serect = "")
{
    if (empty ($appId) || empty ($serect)) {

        $openid = get_openid();
        return $openid;
        exit ();
    }
    $callback = GetCurUrl();

    $param = $appId . ':' . $serect;
    $openid = OAuthWeixin($callback, $param, true);

    return $openid;
}

// 获取当前用户的UID,方便在模型里的自动填充功能使用
function get_mid()
{
    return session('mid');
}

// 通过openid获取微信用户基本信息,此功能只有认证的服务号才能用(UnionID机制,没有关注该公众号，拉取不到其余信息)
function getWeixinUserInfo($openid)
{
    if (!config('USER_BASE_INFO')) {
        return array();
    }
    $access_token = get_access_token();
    if (empty ($access_token)) {
        return array();
    }

    $param2 ['access_token'] = $access_token;
    $param2 ['openid'] = $openid;
    $param2 ['lang'] = 'zh_CN';

    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?' . http_build_query($param2);
    $content = file_get_contents($url);
    $content = json_decode($content, true);
    return $content;
}

// 通过userinfo方式 openid获取微信用户基本信息,此功能只有认证的服务号才能用
function getWeixinUserInfo2($openid,$access_token)
{
    if (!config('USER_BASE_INFO')) {
        return array();
    }
    $param2 ['access_token'] = $access_token;
    $param2 ['openid'] = $openid;
    $param2 ['lang'] = 'zh_CN';

    $url = 'https://api.weixin.qq.com/sns/userinfo?' . http_build_query($param2);
    $content = file_get_contents($url);
    $content = json_decode($content, true);
    return $content;
}

// 获取公众号的信息
function get_token_appinfo($token = '', $field = '')
{
    empty ($token) && $token = get_token();
    if ($token != 'gh_3c884a361561') {
        $PublicModel = new \app\common\model\PublicModel();
        $info = $PublicModel->getInfoByToken($token, $field);
    }
    return $info;
}

// 通过服务号获取用户UID
function get_uid_by_openid($init = true, $openid = '')
{
    $info = get_token_appinfo();
    empty ($openid) && $openid = get_openid();
    if (!$openid) {
        return 0;
    }

    $map ['openid'] = $openid;
    $map ['token'] = $info ['token'];
    $follow = db('public_follow')->where($map)->field('uid')->find();
    $uid = $follow['uid'];
    if ($uid) {
        return $uid;
    }

    if (!$init)
        return 0;

    // 不存在就初始化
    $FollowModel = new \app\common\model\FollowModel();
    $uid = $FollowModel->init_follow($openid, $map ['token']);
    return $uid;
}

// 获取access_token，自动带缓存功能
function get_access_token($token = '', $update = false)
{
    empty ($token) && $token = get_token();

    $info = get_token_appinfo($token);

    // 微信开放平台一键绑定
    if ($token == 'gh_3c884a361561' || $info ['is_bind']) {
        $access_token = get_authorizer_access_token($info ['appid'], $info ['authorizer_refresh_token'], $update);
    } else {//公众平台接入方式
        $access_token = get_access_token_by_apppid($info ['appid'], $info ['secret'], $update);
    }
    // 自动判断access_token是否已失效，如失效自动获取新的
    if ($update == false) {
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=' . $access_token;
        $res = wp_file_get_contents($url);
        $res = json_decode($res, true);

        if (!empty($res ['errcode']) && @$res ['errcode'] == '40001') {//
            $access_token = get_access_token($token, true);
        }
    }
    // var_dump($access_token);exit;
    return $access_token;
}

function get_authorizer_access_token($appid, $refresh_token, $update)
{
    if (empty ($appid)) {
        return 0;
    }
    $key = 'authorizer_access_token_' . $appid;
    $res = \think\Cache::get($key);
    if ($res !== false && !$update)
        return $res;
    $PublicBind = new \app\weixin\model\PublicBindModel();//
    if (empty ($refresh_token)) {
        $auth_code = $PublicBind->_get_pre_auth_code();

        $info = $PublicBind->getAuthInfo($auth_code);
        $authorizer_access_token = $info ['authorization_info'] ['authorizer_access_token'];
    } else {
        $info = $PublicBind->refreshToken($appid, $refresh_token);
        // var_dump($info);exit;
        $authorizer_access_token = $info ['authorizer_access_token'];
    }

    if (!empty ($authorizer_access_token)) {
        \think\Cache::set($key, $authorizer_access_token, $info ['expires_in'] - 200);
        return $authorizer_access_token;
    } else {
        addWeixinLog($info, 'get_authorizer_access_token_fail_' . $appid);
        return 0;
    }
}

function get_access_token_by_apppid($appid, $secret, $update = false)
{
    if (empty ($appid) || empty ($secret)) {
        return 0;
    }

    $key = 'access_token_apppid_' . $appid . '_' . $secret;
    $res = \think\Cache::get($key);
    if ($res !== false && !$update)
        return $res;

    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&secret=' . $secret . '&appid=' . $appid;
    $tempArr = json_decode(wp_file_get_contents($url), true);
    if (@array_key_exists('access_token', $tempArr)) {
        \think\Cache::set($key, $tempArr ['access_token'], $tempArr ['expires_in']);
        return $tempArr ['access_token'];
    } else {
        return 0;
    }
}


//微信公众号授权
function OAuthWeixin($callback, $token = '', $is_return = false,$scope='snsapi_base')
{
    $is_stree = input('is_stree');
    if ((defined('IN_WEIXIN') && IN_WEIXIN) || isset ($is_stree) || !config('USER_OAUTH'))
        return false;

    $isWeixinBrowser = isWeixinBrowser();
    if (!$isWeixinBrowser) {
        return false;
    }
    $callback = urldecode($callback);
    if (strpos($callback, '?') === false) {
        $callback .= '?';
    } else {
        $callback .= '&';
    }
    if (!empty ($token) && strpos($token, ':') !== false) {
        $arr = explode(':', $token);
        $info ['appid'] = $arr [0];
        $info ['secret'] = $arr [1];
    } else {
        $info = get_token_appinfo($token);//获取授权公众号信息
    }
    if (empty ($info ['appid'])) {
        redirect($callback . 'openid=-2');
    }
    $param ['appid'] = $info ['appid'];
    if (input('state') != 'weiphp') {//公众平台授权
        $param ['redirect_uri'] = $callback;
        $param ['response_type'] = 'code';
        $param ['scope'] = $scope;//授权方式
        $param ['state'] = 'weiphp';
        $info ['is_bind'] && $param ['component_appid'] = config('COMPONENT_APPID');
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($param) . '#wechat_redirect';

        header('Location:' . $url);
        exit();
    } elseif (input('state') == 'weiphp') {//跳转微信获取用户openid
        if (empty (input('code'))) {
            exit ('code获取失败');
        }

        $param ['code'] = input('code');
        $param ['grant_type'] = 'authorization_code';

        if ($info ['is_bind']) {//是否一键绑定开放平台授权
            $param ['appid'] = input('appid');
            $param ['component_appid'] = config('COMPONENT_APPID');
            $PublicBindModel = new \app\weixin\model\PublicBindModel();
            $param ['component_access_token'] = $PublicBindModel->_get_component_access_token();//获取开放平台授权true

            $url = 'https://api.weixin.qq.com/sns/oauth2/component/access_token?' . http_build_query($param);
        } else {
            $param ['secret'] = $info ['secret'];

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($param);
        }

        $content = file_get_contents($url);
        $content = json_decode($content, true);
        // var_dump($param,$content);exit;
        if(!empty($content['access_token']) && $content['scope'] =='snsapi_userinfo'){//snsapi_userinfo 方式授权
            \think\Cache::set('snsapi_userinfo_access_token',$content['access_token'],6000);
        }
        if ($is_return) {
            return $content ['openid'];
        } else {
            redirect($callback . 'openid=' . $content ['openid']);
        }
    }
}


// 判断是否是在微信浏览器里
function isWeixinBrowser($from = 0)
{
    $is_stree = input('is_stree');
    if ((!$from && defined('IN_WEIXIN') && IN_WEIXIN) || isset ($is_stree))
        return true;

    // $agent = $_SERVER ['HTTP_USER_AGENT'];
    // if (! strpos ( $agent, "icroMessenger" )) {
    //   return false;
    // }
    return true;
}


/**
 * 计算两点距离
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return float
 */
function get_distance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000;

    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}

function get_week($time = "2017-04-18")
{
    $weekarray = array("日", "一", "二", "三", "四", "五", "六"); //先定义一个数组
    if(!empty($time)){
        return "星期" . $weekarray[time_format($time, "w")];
    }else{
        return false;
    }
}

/**
 * 大写转下划线加小写
 * @param $name
 * @return string
 */
function strtolower_with_underline($name)
{
    $temp_array = array();
    for ($i = 0; $i < strlen($name); $i++) {
        $ascii_code = ord($name[$i]);
        if ($ascii_code >= 65 && $ascii_code <= 90) {
            if ($i == 0) {
                $temp_array[] = chr($ascii_code + 32);
            } else {
                $temp_array[] = '_' . chr($ascii_code + 32);
            }
        } else {
            $temp_array[] = $name[$i];
        }
    }
    return implode('', $temp_array);
}

/**
 * 格式化输出数组
 * @param $array
 */
function dump_array($array){
    echo '<pre>'.print_r($array, true).'</pre>';
}

/**
 * 创建表sql
 * @param $item_array
 */
function create_table_sql($item_array){
    $sql = "CREATE TABLE `` (<br />";
    foreach ($item_array as $item){
        $sql .= '`'.strtolower_with_underline($item) ."` varchar(255) DEFAULT NULL,<br />";
    }
    $sql .= " PRIMARY KEY (`id`) <br />) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='表描述';";
    echo $sql;
}

/**
 *  数字转中文
 * @param $num
 * @param int $m
 * @return string
 */
function number2Chinese($num, $m = 1) {
    switch($m) {
        case 0:
            $CNum = array(
                array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖'),
                array('','拾','佰','仟'),
                array('','萬','億','萬億')
            );
            break;
        default:
            $CNum = array(
                array('零','一','二','三','四','五','六','七','八','九'),
                array('','十','百','千'),
                array('','万','亿','万亿')
            );
            break;
    }

    if (!is_numeric($num)) {
        return false;
    }

    $flt = '';
    if (is_integer($num)) {
        $num = strval($num);
    }else if(is_numeric($num)){
        $num = strval($num);
        $rs = explode('.',$num,2);
        $num = $rs[0];
        $flt = $rs[1];
    }

    $len = strlen($num);
    $num = strrev($num);
    $chinese = '';

    for($i = 0,$k=0;$i < $len; $i+=4,$k++){
        $tmp_str = '';
        $str = strrev(substr($num , $i,4));
        $str = str_pad($str,4,'0',STR_PAD_LEFT);
        for ($j = 0; $j < 4; $j++) {
            if($str{$j} !== '0'){
                $tmp_str .= $CNum[0][$str{$j}] . $CNum[1][4-1-$j];
            }
        }
        $tmp_str .= $CNum[2][$k];
        $chinese = $tmp_str . $chinese;
        unset($str);
    }
    if($flt !== ''){
        $str = '';
        for ($i=0; $i < strlen($flt); $i++) {
            $str .= $CNum[0][$flt{$i}];
        }
        $chinese .= "点{$str}";
    }
    return $chinese;
}

/**
 * 取出某年所有获取这一周的周一和周日到数组
 * 或者是周日和周六
 * @parame $first	//1表示每周星期一为开始时间 0表示每周日为开始时间
 * $all_week = get_all_weekdate('2014');
 */
function get_all_weekdate($year,$week='',$first=0){
    for($m=1;$m<=12;$m++){
        if($m<10){
            $day = '0'.$m;
        }else{
            $day = $m;
        }
        $month_date = $year.'-'.$day;	//月数
        $days = date('t',strtotime($month_date));	//每月天数
        for($d=1;$d<=$days;$d++){
            $date ="$year-$m-$d";
            $w = date("w",mktime(0,0,0,$m,$d,$year)); //获取当前周的第几天 周日是 0 周一 到周六是 1 -6
            if($w==1){
                $week_day = $w ? $w - $first : 6;  //如果是周日 -6天
                $weeknum = date('W',strtotime($date));
                $start_time = date("Y-m-d", strtotime("$date -".$week_day." days"));//本周开始时间
                $end_time = date("Y-m-d", strtotime("$start_time +6 days"));//本周结束时间
                $result[$weeknum] = array(
                    'weeknum'=>$weeknum,
                    'start_time'=>$start_time,
                    'end_time'=>$end_time
                );
            }
        }
    }
    if($week){
        return $result[$week];	//返回指定周开始结束
    }
    return $result;
}

//根据指定年份和周数，获取这一周的周一（开始日期）和周日（结束日期）
function getWeekDate($year,$weeknum){
    $firstdayofyear=mktime(0,0,0,1,1,$year);
    $firstweekday=date('N',$firstdayofyear);
    $firstweenum=date('W',$firstdayofyear);
    if($firstweenum==1){
        $day=(1-($firstweekday-1))+7*($weeknum-1);
        $startdate=date('Y-m-d',mktime(0,0,0,1,$day,$year));
        $enddate=date('Y-m-d',mktime(0,0,0,1,$day+6,$year));
    }else{
        $day=(9-$firstweekday)+7*($weeknum-1);
        $startdate=date('Y-m-d',mktime(0,0,0,1,$day,$year));
        $enddate=date('Y-m-d',mktime(0,0,0,1,$day+6,$year));
    }

    return array($startdate,$enddate);
}

/*  作用由起止日期算出其中的周
 *  @param start_date 开始日期
 *  @param end_date   结束日期
 *  @return 一个二维数组，其中一维为每周起止时间
 *  注意：end_date>state_date
 *	var_dump(get_all_week( "2013-12-31","2014-08-08")) ;
 */

function get_all_week($startdate,$enddate){
    //参数不能为空
    if(!empty($startdate) && !empty($enddate)){
        //先把两个日期转为时间戳
        $startdate=strtotime($startdate);
        $enddate=strtotime($enddate);
        //开始日期不能大于结束日期
        if($startdate<=$enddate){
            $end_date=strtotime("next sunday",$enddate); //next monday｜sunday 下周一(1-7)
            if(date("w",$startdate)==1){
                $start_date=$startdate;
            }else{ //last
                $start_date=strtotime("sunday",$startdate); //last monday｜sunday 上周一(1-7)
            }
            //计算时间差多少周
            $countweek=($end_date-$start_date)/(7*24*3600);
            for($i=0;$i<$countweek;$i++){
                $sd=date("Y-m-d",$start_date); //周开始
                $ed=strtotime("+ 6 days",$start_date);
                $eed=date("Y-m-d",$ed);	//周结束
                $weeknum = date('W',strtotime($eed));	//所在周数
                $arr[]=array(
                    'weeknum'=>$weeknum,
                    'start_time'=>$sd,
                    'end_time'=>$eed
                );
                $start_date=strtotime("+ 1 day",$ed);	//下一周开始
            }
            return $arr;
        }
    }
}
//echo date('W',strtotime('2014-10-6'));
//var_dump(get_all_week( "2014-1-1","2014-12-31")) ;

/*
*function：计算两个日期相隔多少年，多少月，多少天
*param string $startdate[格式如：2011-11-5]
*param string $enddate[格式如：2012-12-01]
*return array array('年','月','日');
*/
function diff_date($startdate,$enddate){
    if(strtotime($startdate)>strtotime($enddate)){
        $tmp=$enddate;
        $enddate=$startdate;
        $startdate=$tmp;
    }
    list($Y1,$m1,$d1)=explode('-',$startdate);
    list($Y2,$m2,$d2)=explode('-',$enddate);
    $Y=$Y2-$Y1;
    $m=$m2-$m1;
    $d=$d2-$d1;
    if($d<0){
        $d+=(int)date('t',strtotime("-1 month $d2"));
        $m--;
    }
    if($m<0){
        $m+=12;
        $Y--;
    }
    return array('year'=>$Y,'month'=>$m,'day'=>$d);
}

/*  作用由起止日期算出其中的周
 *	@param type 报表类型
 *  @param start_date 开始日期
 *  @param end_date   结束日期
 *  @return 一个二维数组，其中一维为每周起止时间
 */

function get_date_list($type,$startdate='',$enddate=''){
    $timestamp = time();
    $date = date("Y-m-d");  //当前日期
    $year = date('Y',$timestamp);//本年
    $week = date('W',$timestamp);//本年当前周数

    $ret['sdate'] = strtotime('-7 day');	//最近多少天
    $ret['edate'] = $timestamp;	//当前时间戳
    $days = ceil(abs($ret['edate']-$ret['sdate'])/86400);

    $is_default=false;

    if($type == 'day') {//按天
        if($startdate=='' || $enddate==''){
            $startdate = date('Y-m-d',strtotime('-7 day'));	//最近多少天-7
            $enddate = date('Y-m-d',$timestamp);	//当前时间戳
            $start  = $startdate;   //日开始时间
            $end = $enddate;		//日结束时间

            $startdate = strtotime($startdate);
            $enddate = strtotime($enddate);
            $days = ceil(abs($enddate-$startdate)/86400);
            $is_default = true;
        }else{
            $start  = $startdate;   //日开始时间
            $end = $enddate;		//日结束时间

            $startdate = strtotime($startdate);
            $enddate = strtotime($enddate);
            $days = ceil(abs($enddate-$startdate)/86400);
        }
        $date_arr['time_quantum'] = array(
            'start_time'=>$start,
            'end_time'=>$end
        );
        //取出最近几天的天数
        $index = $is_default ? 0 : 1;
        for($i=0;$i<($days+$index);$i++){
            $date_arr['date'][] = date('Y-m-d',strtotime("$start +$i days"));
        }
        //var_dump($date_arr);
//		$date_arr['date'][] = date('Y-m-d',$startdate);
//			$startdate=strtotime("+ 1 day",$startdate);
    }else if($type == 'week') {//按周
        if($startdate=='' || $enddate==''){
            $week_data = get_all_weekdate($year,$week);
            $startdate = date('Y-m-d',strtotime("-6 week",strtotime($week_data['start_time'])));
            $enddate = $week_data['end_time'];
            $is_default = true;
        }
        $result_week = get_all_week($startdate,$enddate);
        if($is_default) array_pop($result_week);	//默认上一周，去掉当前周
        //var_dump($result_week);exit;
        $start_date = current($result_week);
        $end_date = end($result_week);

        $start = $start_date['start_time'];
        $end = $end_date['end_time'];
        $date_arr['time_quantum'] = array(
            'start_time'=>$start,
            'end_time'=>$end
        );
        $date_arr['date'] = $result_week;
    }else if($type == 'month') {//按月
        if($startdate=='' || $enddate==''){
            //$startdate = date('Y-m-d',$timestamp);
            //$enddate = date('Y-m-d',strtotime("-6 month", $timestamp));
            $is_default = true;
        }
        if($is_default){
            $get_month = 6;//默认半年
        }else{
            $result_date = diff_date($startdate,$enddate);	//差多少个月
            if($result_date['month']==0){
                $def_month = 1;	//如果不足一个月，则是上个月
                $is_month = 0;
            }else{
                $is_month = 1;
            }
            $timestamp = strtotime($enddate);
            $get_month = $result_date['month']?$result_date['month']:$def_month;
        }

        $time = strtotime("-{$get_month} month", $timestamp);	//默认半年
        $last_month = strtotime('-1 month', $timestamp);
        $start = mktime(0, 0,0, date('m', $time), 1, date('Y', $time));//不包含当前月
        $end = mktime(0, 0, 0, date('m', $last_month), date('t', $last_month), date('Y', $last_month));
        $date_arr['time_quantum'] = array(
            'start_time'=>date('Y-m-d',$start),
            'end_time'=>date('Y-m-d',$end)
        );
        $index = $is_default ? 0 : $is_month;
        for($i=0;$i<($get_month+$index);$i++){
            $s_month = date('Y-m-d',strtotime("+$i month",$start));
            $e_month = date('Y-m-t',strtotime($s_month));	//取到本月最后一天
            $date_arr['date'][] = array(
                'start_time'=>$s_month,
                'end_time'=>$e_month
            );
        }
    }

    return $date_arr;
}

/**
 *  获取日期范围数组
 * @param $start 开始时间或者 week,month,year
 * @param $end 结束时间
 * @param string $format 返回的时间格式
 * @param mixed $pick 指定选取的日子，仅在多天间隔中
 * @param string $next 可以指定天间隔
 * @return array
 */
function daterang($start, $end, $format='Y-m-d', $pick=false, $next='+1 day') {
    $rang = array();
    if ($start == 'week') { //本周
        $date = new \DateTime();
        $date->modify('this week');
        $start = $date->format('Y-m-d');
        $date->modify('this week +6 days');
        $end = $date->format('Y-m-d');
    } else if ($start == 'month') { //本月
        $start = date('Y-m-01');
        $end = date('Y-m-d',strtotime('+1 month -1 day', strtotime($start)));
    } else if ($start == 'year') { //今年
        $start = date('Y-01-01');
        $end = date('Y-m-d',strtotime('+1 year -1 day', strtotime($start)));
    }

    $dt_start = strtotime($start);
    $dt_end = strtotime($end);

    while ($dt_start <= $dt_end) {
        $rang[] = date($format, $pick ? strtotime($pick, $dt_start) : $dt_start);
        $dt_start = strtotime($next,$dt_start);
    }
    return $rang;
}

//产生订单号
function get_order_sn($pre='BK'){
    list($s1,$s2)=explode(' ',microtime());
    return $pre.sprintf('%.0f',(floatval($s1)+floatval($s2))*1000).mt_rand(111111,999999);
}

/*
 * 获取季度起始时间
 * $year 指定年份
 * $season 指定季度
 * $act 上n月，下n月 ：  -1 or +1....+2
 */
function get_season($year='',$season='',$act=''){
    $year = $year ? $year :date('Y');//年份
    if($act){
        $season = ceil((date('n'))/3).$act;//上季度是第几季度
        $season=eval("return $season;");
    }else if(empty($season)){
        $season = ceil((date('n'))/3);//本季度是第几季度
    }
    $data = [];
    $data['start_date'] = date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,$year));
    $data['end_date'] = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,$year)),$year));

    return $data;
}

/*
 *  获取半年起止时间
 *  $type 1:上半年，2:下半年
 */
function get_half_year($type=1){
    if($type==1){
        $data = [
            'start_date'=>date('Y-01-01 00:00:00'),
            'end_date'=>date('Y-06-30 23:59:59')
        ];
    }else{
        $data = [
            'start_date'=>date('Y-07-01 00:00:00'),
            'end_date'=>date('Y-12-30 23:59:59')
        ];
    }
    return $data;
}

// 处理带Emoji的数据，type=0表示写入数据库前的emoji转为HTML，为1时表示HTML转为emoji码
function deal_emoji($msg, $type = 1) {
    if ($type == 0) {
        $msg = urlencode ( $msg );
        $msg = json_encode ( $msg );
    } else {

        $msg = preg_replace ( "#\\\u([0-9a-f]+)#ie", "iconv('UCS-2','UTF-8', pack('H4', '\\1'))", $msg );

        // $msg = preg_replace("#(\\\ue[0-9a-f]{3})#ie", "addslashes('\\1')",$msg);

        $msg = urldecode ( $msg );
        // $msg = json_decode ( $msg );
        // dump($msg);
        $msg = str_replace ( '"', "", $msg );
        // dump($msg);exit;
        if ($txt !== null) {
            $msg = $txt;
        }
    }

    return $msg;
}

//获取用户级别
//$param $exp_num 用户经验值
function get_user_level($exp_num,$return_exp=false){
    $level_arr = \think\Config::get('user_level');//
    array_push($level_arr,$exp_num);
    $data = array_unique($level_arr);
    sort($data);
    $level = array_search($exp_num,$data);
    $next_level = $level+1;
    if(!empty($level_arr[$next_level]) && $exp_num==$level_arr[$next_level]){
        $level +=1;
    }
    if($return_exp){//当前下级需要经验
        $level +=1;
        return $level_arr[$level];
    }else{
        return $level;
    }

}

//加锁
//发现$redis->setnx()可以提供原子操作的状态：相同的key执行setnx之后没过期或者没del，再执行会返回false。
//这就让两个以上的并发请求得到控制必须成功获取锁才能继续。
//var_dump(task_lock('abc','abc'));exit;
function task_lock($key,$taskid,$expire=2){
    $redis = new redis();
$redis->connect('127.0.0.1', 6379);
$redis->auth('zhengpinRedis');
    //$redis = new \Redis();//redis 资料对象

    //$expire = 2;
    $lock_key ='task_get_'.$key.'_'.$taskid;
    $lock = $redis->setnx($lock_key , time());//设当前时间
    if($lock){
        $redis->expire($lock_key,  $expire); //如果没执行完 2s锁失效
    }
    if(!$lock){//如果获取锁失败 检查时间
        $time = $redis->get($lock_key);
        if(time() - $time  >=  $expire){//添加时间戳判断为了避免expire执行失败导致死锁 当然可以用redis自带的事务来保证
            $redis->delete($lock_key);
        }
        $lock =  $redis->setnx($lock_key , time());
        if($lock){
            $redis->expire($lock_key,  $expire); //如果没执行完 2s锁失效
        }
    }
    return $lock;
}

//解锁
function task_unlock($key,$taskid){
    $redis = Cache::getHandler();
    $lock_key = 'task_get_'.$key.'_'.$taskid;
    $redis->delete($lock_key);
}

//解密微信数据
function decrypt_wxapp_data($sessionKey,$encryptedData,$iv){
    \think\Loader::import('org.wxBizMsgCrypt',EXTEND_PATH);
    $wxapp_config = config('wechat.wxapp');//配置
    $appid = $wxapp_config['appid'];

    $pc = new \WXBizDataCrypt($appid, $sessionKey);
    $errCode = $pc->decryptData($encryptedData, $iv, $data );
    if ($errCode == 0) {//成功
        return json_decode($data,true);
    } else {
        return false;
    }
}

//概率算法
//$prize_arr =array('1'=>10,'2'=>10,'3'=>70,'4'=>10);
function get_rand($proArr) {
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
    //概率数组循环
    foreach ($proArr as $key => $proCur){
        $randNum = mt_rand(1, $proSum);             //抽取随机数
        if($randNum <= $proCur) {
            $result = $key;                         //得出结果
            break;
        }else{
            $proSum -= $proCur;
        }
    }
    unset ($proArr);
    return $result;
}

//唯一字符串
function getRandChar($length){
   $str = null;
   $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
   $max = strlen($strPol)-1;

   for($i=0;$i<$length;$i++){
    $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
   }

   return $str;
}

//订单随机码
function makeSn() {
    return mt_rand(10,99)
        . sprintf('%010d',time() - 946656000)
        . sprintf('%03d', (float) microtime() * 1000);
}

/*
 *自动验证
 *lico
 */
function ValidateAuto($input, $request) {

    $rule = array_column($input, 1, 0);//p($rule);
    $chin = array_column($input, 2, 0);//pe($chin);//对应中文

    $msg = [];
    $validate = new Validate($rule, $msg, $chin);
    $result = $validate->check($request);

    if (!$result) {
        $error = $validate->getError();

        return $error;
    }
}

function p($param){
    echo '<pre>';
    print_r($param);
    echo '</pre>';
}

function pe($param){
    echo '<pre>';
    print_r($param);
    exit;
}

function di($param){
    echo '<pre>';
    var_dump($param);
    echo '</pre>';
}

function de($param){
    echo '<pre>';
    var_dump($param);
    exit;
}

/*
 * base64方式上传图片
 * lico
 */
function upload_base64($img,$dir=''){

    if( !$img ){
        throw new Exception("图片不存在", 1);
    }

    $dir = $dir ?: '/uploads/return_goods/'.date('Y').'/'.date('m-d');
    if (!($_exists = file_exists(UPLOAD_PATH.$dir))){//p($dir);
        $isMk = mkdirs(UPLOAD_PATH.$dir,0777);
    }

    $filename = md5(time().mt_rand(1000,9999)).'.png';//自定义图片名
    $filepath = $dir.'/'.$filename;//图片存储路径

    $img = explode(',',$img);
    file_put_contents(UPLOAD_PATH.$filepath, base64_decode($img[1]));//保存图片到自定义的路径

    return $filepath;
}


/*
 *用户等级映射
 *lico
 */
function levelMapp($addfield = '') {
    $field = 'level_id,level_name';
    if ($addfield) {
        $field .= ',' . $addfield;
    }
    $mapp = M('user_level')
    // ->cache(true)
    ->column($field);

    return $mapp;
}


function price_switch($level,$goods=[]){
    $level_discount = db('user_level')->where(['level_id'=>$level])->value('discount');//按照身份计算价格

    return $goods['shop_price']*($level_discount/100);
}

/*
 *下线列表
 *lico
 */
function childrens($user_id, $field = '*', $where = "level>=3") {

    $childrens = firsts($user_id, $field, $where);
    return $childrens;
}

/*
 * 递归查找所有下级
 */
function first_users($array_ids, $field = '*', $where, $data = []) {

    $uids = implode(',', $array_ids);

    $fids = M('users')->field($field)->where("first_leader", 'In', $uids)/*->where(function ($query) {
        $query->where('company_create_time', 0)
              ->whereOr('`company_create_time` >= '.Logic::getStartTime().' and `company_create_time` <= '.Logic::getEndTime().' and `level` = 4');
    })->where($where)->order('user_id')*/->select();
    if (!empty($fids)) {
        $array_ids = array_column($fids, 'user_id');
        // dump($array_ids);
        foreach ($fids as $key => $value) {
            $data[] = $value;
        }

        return first_users($array_ids, $field, $where, $data);
    } else {
        return $data;
    }
}

/*
 * 找所有上级
 */
function firsts($array_ids, $field,$where, $data = []) {

    $fids = M('users')->field($field)->where(['user_id' => $array_ids])->where($where)->find();

//    $data[] = $fids;
    if (!empty($fids)) {
        $data[] = $fids;
//        dump($data);
        $arr =[];
        foreach( $data as $value){
            $arr[] = $value['level'];
            if(in_array(3,$arr) && in_array(4,$arr) && in_array(5,$arr)){
                return $data;
            }
//            dump($arr);
            if(in_array(3,$arr)){
//                echo 1;
                return firsts($fids['first_leader'],$field,$where,$data);;
            }
            if(in_array(4,$arr)){
                return firsts($fids['first_leader'],$field,$where,$data);;
            }
            if(in_array(5,$arr)){
                return firsts($fids['first_leader'],$field,$where,$data);;
            }
        }
        return firsts($fids['first_leader'],$field,$where,$data);
    } else {
        return $data;
    }
}

function teamAwards($user_id){
    $sub_user_list = db('users')->where(['first_leader' => $user_id])->field('first_leader')->find();
    $data = [];
    if(!empty($sub_user_list)){
        foreach( $sub_user_list as $key => $value){
            $data = $value;
        }
        return  teamAwards($data);
    }else{
        return  $data;
    }
}

/*
 *下线列表
 *lico
 */
function childrens_max_level($array_ids, $field = 'user_id,level', $where = "user_id>0",$data = []) {

    $uids = implode(',', $array_ids);

    $fids = M('users')
        ->field($field)
        ->where("first_leader", 'In', $uids)
        ->where($where)
        ->order('user_id')
        ->select();

    if (!empty($fids)) {
        $array_ids = array_column($fids, 'user_id');

        foreach ($fids as $key => $value) {
            if( $value['level'] == 4 ){
                $data[] = $value;
            }
        }

        return childrens_max_level($array_ids, $field, $where, $data);
    } else {
        return $data;
    }

}


function checktop_parents($user_id, $where = "user_id>0", $field = 'first_leader,user_id,level',$check = false){

    $fids = [];
    if( $user_id ){
        $fids = M('users')
        ->field($field)
        ->where("user_id", $user_id)
        ->where($where)
        ->order('user_id')
        ->find();

        if( $fids && $fids['level'] == 4 ){
            $check = true;
        }

        if( $fids && $fids['first_leader'] ){
            return checktop_parents( $fids['first_leader'],$where,$field,$check );
        }else{
            return $check;
        }
    }

}

 /**
 * 更改会员的上级   Lu
 * @param int $user_id   被改用户
 * @param int $first_leader 上级用户
 * @return array
 */
 function change_distribution($user_id=0,$first_leader=0){

    $user = D('users')->where(array('user_id'=>$user_id))->find();

    if($user_id==$first_leader){
        return array('status'=>0,'msg'=>'不能把自己设为上级');
    }

    $my_distribtion = M('users')->whereOr(array('first_leader'=>$user_id))->whereOr(array('second_leader'=>$user_id))->whereOr(array('third_leader'=>$user_id))->column('user_id');
    $first_leader_users =  D('users')->where(array('user_id'=>$first_leader))->find();

    if($my_distribtion){
        if(in_array($first_leader,$my_distribtion)){
            return array('status'=>0,'msg'=>'不能把自己的下级设为上级');
        }
    }

    $new_leader['first_leader'] = $first_leader;
    $new_leader['second_leader'] = $first_leader_users['first_leader']?$first_leader_users['first_leader']:0;
    $new_leader['third_leader'] = $first_leader_users['second_leader']?$first_leader_users['second_leader']:0;

    //我的一级下级
    $my_first_distribution = M('users')->where(array('first_leader'=>$user_id))->column('user_id');
    //我的二级下级
    $my_second_distribution = M('users')->where(array('second_leader'=>$user_id))->column('user_id');
    //我的三级下级
    $my_third_distribution = M('users')->where(array('third_leader'=>$user_id))->column('user_id');

    //更改我的一级下级
    if($my_first_distribution){
        $data_first = array(
            'second_leader'=>$new_leader['first_leader'],
            'third_leader'=>$new_leader['second_leader'],
        );
        $res_first =M('users')->where(array('user_id'=>array('in',$my_first_distribution)))->save($data_first);
    }

    //更改我的二级下级
    if($my_second_distribution){
        $data_second = array(
            'third_leader'=>$new_leader['first_leader'],
        );
        $res_second =M('users')->where(array('user_id'=>array('in',$my_second_distribution)))->save($data_second);
    }

    $res1 = M('users')->where(array('user_id'=>$user_id))->update($new_leader);

    return array('status'=>1,'msg'=>'修改成功');
}
/**
 * 记录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writelog($uid,$username,$description,$status)
{
    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    Db::name('Log')->insert($data);

}
