<?php
namespace app\api\controller;


use My\DataReturn;
use think\Db;

class Help extends Base {

    public function __construct(){
        parent::__construct();
    }

    //帮助中心 列表
    public function articleList() {
        $list = Db::name('article')->where('cate_id', 1)->order('is_tui', 'desc')->field('id,title')->page(1, 25)
            ->select();
        DataReturn::returnJson(200,'返回成功', $list);
    }

    //帮助中心 详情
    public function articleDetail() {
        $post = input('post.');
        $article_id = $post['data']['article_id'];
        if ($article_id) {
            $detail = Db::name('article')->where(['id'=>$article_id])->field('title,content')->find();
            if ($detail) {
                 DataReturn::returnJson(200,'ok', $detail);
            }
        }
        DataReturn::returnJson(404,'请求失败');
    }

    //系统消息  活动通知
    public function system_message() {
        $system_message = Db::name('article')->where('cate_id', 2)->where('status',1)->order('create_time', 'desc')->page(1, 25)
            ->select();
        $system_list   = [];
        foreach ($system_message as $key => $value) {
                $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
                $value['content'] =  html_entity_decode($value['content']);
                $system_list[] = $value;
        }
        $activity = Db::name('article')->where('cate_id', 3)->where('status',1)->order('create_time', 'desc')->page(1, 25)
        ->select();
        $activity_list = [];
        foreach ($activity as $key => $value) {

                $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
                $value['content'] =  html_entity_decode($value['content']);

                $activity_list[] = $value;
        }

        $person_message = db('send_mail')->where(['user_id'=>$this->user_id])->order('create_time', 'desc')->select();
        $person_list = [];
        foreach ($person_message as $key => $value) {
                $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
                $person_list[] = $value;
        }
        $all_list  = array_merge($activity_list,$system_list,$person_list);
        $create_time = array_column($all_list,'create_time');
        array_multisort($create_time,SORT_DESC,$all_list);
        // var_dump($all_list);die();
        $data = [];
        $data=[
            'activity_list'   =>$activity_list,
            'system_list'     =>$system_list,
            'all_list'        =>$all_list
        ];
        DataReturn::returnBase64Json(200,'返回成功', $data);
    }

}