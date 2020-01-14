<?php

namespace app\admin\controller;
use think\Config;
use think\Loader;
use app\common\logic\MonthDistLogic;

class Index extends Base
{
    public function index()
    {

        return $this->fetch('/index');
    }

    public function monthly_dist(){

        $DistributLogic = new MonthDistLogic();
        $result = $DistributLogic->monthly_dist();
        if( $result['code'] == 0 ){
            $this->error($result['msg']);
        }

        $this->success($result['msg'],url('/shop/Distribut/monthly_dist_list'));
    }


    /**
     * [indexPage 后台首页]
     * @return [type] [description]
     */
    public function indexPage()
    {
        $info = array(
            'web_server' => $_SERVER['SERVER_SOFTWARE'],
            'onload' => ini_get('upload_max_filesize'),
            'think_v' => THINK_VERSION,
            'phpversion' => phpversion(),
        );

        /*商城相关统计*/
        $today = strtotime(date('Y-m-d'));//strtotime("-1 day");
        $count['handle_order'] = db('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();//待处理订单
        $count['new_order'] = db('order')->where("add_time>$today")->count();//今天新增订单
        $count['goods'] =  db('goods')->where("1=1")->count();//商品总数
        $count['article'] =  db('article')->where("1=1")->count();//文章总数
        $count['users'] = db('users')->where("1=1")->count();//会员总数
        $count['today_login'] = db('users')->where("last_login_time>$today")->count();//今日访问
        $count['new_users'] = db('users')->where("reg_time>$today")->count();//新增会员
        $count['comment'] = db('comment')->where("is_show=0")->count();//最新评论
        $this->assign('count',$count);
        //2018-07-09 17:53:52
        //获取游戏详情

        $game_info = file_get_contents(ROOT_PATH .'/public/opengame.txt');
        $this->assign('game_info',$game_info);
        $this->assign('info',$info);
        return $this->fetch('index');
    }


    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            return json(['code' => 1, 'msg' => '清除缓存成功']);
        } else {
            return json(['code' => 0, 'msg' => '清除缓存失败']);
        }
    }

}
