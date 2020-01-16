<?php

$program_root = dirname($_SERVER["DOCUMENT_ROOT"]);


return [

    // +----------------------------------------------------------------------
    // | auth配置
    // +----------------------------------------------------------------------
    'auth_config'       => [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group', // 用户组数据不带前缀表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系不带前缀表
        'auth_rule'         => 'auth_rule', // 权限规则不带前缀表
        'auth_user'         => 'admin', // 用户信息不带前缀表
    ],
// 系统数据加密设置
    'data_auth_key'     => '|Ho<F5`sCf!mzGUE-?:)Sl,u_~I}0K=XNi1qB/[(', // 默认数据加密KEY
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    'url_route_on'      => true,     //开启路由功能
    'route_config_file' => ['admin'],   // 设置路由配置文件列表
    //'url_convert' => false,  //关闭URL自动转换（支持驼峰访问控制器）

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'app_trace'         => false,      //开启应用Trace调试
    'trace'             => [
        'type' => 'html',       // 在当前Html页面显示Trace信息,显示方式console、html
    ],
    'sql_explain'       => false,     // 是否需要进行SQL性能分析
    'extra_config_list' => ['database', 'route', 'validate'],//各模块公用配置

    'app_debug'             => true,
    'default_module'        => 'api',//默认模块
    //'default_filter' => ['strip_tags', 'htmlspecialchars'],

    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl'   => APP_PATH . 'admin/view/public/error.tpl',
    //默认成功跳转对应的模板文件
    'dispatch_success_tmpl' => APP_PATH . 'admin/view/public/success.tpl',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log'                   => [
        'type'        => 'File',// 日志记录方式，内置 file socket 支持扩展
        'path'        => LOG_PATH,// 日志保存目录
        'level'       => ['error', 'notice', 'sql', 'api_log', 'send_message_log', 'game', 'jiaoyi'],// 日志记录级别 api_log第三方接口,api_base_data_log基础数据日志
        'apart_level' => ['error', 'notice', 'sql', 'api_log', 'send_message_log', 'game', 'jiaoyi'],
        //单个日志文件的大小限制，超过后会自动记录到第二个文件
        'file_size'   => 5242880,
    ],


    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache'                 => [
        //本地环境

//        'type'   => 'file',// 驱动方式
//        'path'   => CACHE_PATH,// 缓存保存目录
//        'prefix' => '',// 缓存前缀
//        'expire' => 0,// 缓存有效期 0表示永久缓存


        //测试环境
        'type'     => 'redis',// 驱动方式 支持redis memcache memcached
        'prefix'   => 'dt_',// 缓存前缀
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => 'vtoken717',
        'expire'   => 0,// 缓存有效期 0表示永久缓存

    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                      => [
        'id'             => '',
        'var_session_id' => '',// SESSION_ID的提交变量,解决flash上传跨域
        'prefix'         => 'think',// SESSION 前缀
        //'type'           => 'redis',// 驱动方式 支持redis memcache memcached
        //'host' =>'172.25.191.171',
        //'port' =>'6379',
        'expire'         => 86400,
        'auto_start'     => true,// 是否自动开启 SESSION
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                       => [
        'prefix'    => '',// cookie 名称前缀
        'expire'    => 604800,// cookie 保存时间
        'path'      => '/',// cookie 保存路径
        'domain'    => '',// cookie 有效域名
        'secure'    => false,//  cookie 启用安全传输
        'httponly'  => '',// httponly设置
        'setcookie' => true,// 是否使用 setcookie
    ],

    //分页配置
    'paginate'                     => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 10,
    ],


    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------
    'data_backup_path'             => '../data/',   //数据库备份路径必须以 / 结尾；
    'data_backup_part_size'        => '20971520',  //该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M
    'data_backup_compress'         => '1',          //压缩备份文件需要PHP环境支持gzopen,gzwrite函数        0:不压缩 1:启用压缩
    'data_backup_compress_level'   => '9',    //压缩级别   1:普通   4:一般   9:最高


    // +----------------------------------------------------------------------
    // | 极验验证,请到官网申请ID和KEY，http://www.geetest.com/
    // +----------------------------------------------------------------------
    'verify_type'                  => '1',   //验证码类型：0极验验证， 1数字验证码
    'gee_id'                       => 'ca1219b1ba907a733eaadfc3f6595fad',
    'gee_key'                      => '9977de876b194d227b2209df142c92a0',
    'auth_key'                     => 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN', //默认数据加密KEY
    'pages'                        => '10',//分页数
    'salt'                         => 'wZPb~yxvA!ir38&Z',//加密串


    // 七牛上传文件驱动配置
    'upload_qiniu_config'          => array(
        'accessKey'  => '',
        'secrectKey' => '',
        'bucket'     => '',
        'domain'     => '',
        'timeout'    => 3600
    ),

    // 百度云上传文件驱动配置
    'upload_bcs_config'            => array(
        'AccessKey' => '',
        'SecretKey' => '',
        'bucket'    => '',
        'rename'    => false
    ),
    'picture_upload_driver'        => 'Local', // Local--本地  Qiniu --七牛
    // 图片上传相关配置
    'picture_upload'               => array(
        'maxSize'  => 20097152, // 2M 上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg', // 允许上传的文件后缀
        'rootPath' => './uploads/picture/'
    ),

    // 编辑器图片上传相关配置
    'editor_upload'                => array(
        'maxSize'  => 20097152, // 2M 上传的文件大小限制 (0-不做限制)20097152
        'exts'     => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml,xls,xlsx,csv,pem,amr,mp3,mp4,rm, rmvb, wmv, avi, mpg,flv', // 允许上传的文件后缀
        'rootPath' => './uploads/editor/'
    ),
    //编辑器上传服务器
    // Local--本地  Qiniu --七牛
    'editor_picture_upload_driver' => 'Local',

    // 文件上传相关配置
    'download_upload'              => array(
        'maxSize'  => 5242880, // 5M 上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml,xls,xlsx,csv,pem,amr,mp3,mp4,rm, rmvb, wmv, avi, mpg, mpeg,apk', // 允许上传的文件后缀
        'rootPath' => './uploads/download/'
    ),

    // +----------------------------------------------------------------------
    // | 第三方接口域名
    // +----------------------------------------------------------------------
    'api_domain'                   => [
        //开发测试环境

        //生产环境

    ],

    'root_namespace' => [
        'GatewayWorker' => '../vendor/gateway-worker/src/',
    ],

    //付款方式加盐
    'salt'           => 'zhengpin',
    //Aes key   注意只支持16 24 的长度
    'aeskey'         => 'zhengpinaes12356',
    'php_root'       => '',
    'default_filter' => 'htmlentities',


    'forget_passwd_total_err' => 15,
];