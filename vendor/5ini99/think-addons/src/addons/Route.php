<?php
// +----------------------------------------------------------------------
// | thinkphp5 Addons [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zzstudio.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Byron Sampson <xiaobo.sun@qq.com>
// +----------------------------------------------------------------------
namespace think\addons;

use think\Hook;
use think\Request;

/**
 * 插件执行默认控制器
 * Class AddonsController
 * @package think\addons
 */
class Route extends Controller
{
    /**
     * 插件执行
     */
    public function execute()
    {

        if (!empty($this->addon) && !empty($this->controller) && !empty($this->action)) {
            // 获取类的命名空间
            $class = get_addon_class($this->addon, 'controller', $this->controller);
            if (class_exists($class)) {
                //定义插件常量 2017-04-02 22:04:25
                define ( 'ADDON_BASE_PATH',  Url('/addons/' . $this->addon) );
                define ( 'ADDON_PUBLIC_PATH', ROOT_PATH . '/addons/' . $this->addon . '/view/public' );
                defined ( '_ADDONS' ) or define ( '_ADDONS', $this->addon );
                defined ( '_CONTROLLER' ) or define ( '_CONTROLLER', $this->controller );
                defined ( '_ACTION' ) or define ( '_ACTION', $this->action );
                //

                $model = new $class();
                if ($model === false) {
                    abort(500, lang('addon init fail'));
                }
                // 调用操作
                if (!method_exists($model, $this->action)) {
                    abort(500, lang('Controller Class Method Not Exists'));
                } 
                // 监听addons_init
                Hook::listen('addons_init', $this);
                return call_user_func_array([$model, $this->action], [Request::instance()]);
            } else {
                abort(500, lang('Controller Class Not Exists'));
            }
        }
        abort(500, lang('addon cannot name or action'));
    }
}
