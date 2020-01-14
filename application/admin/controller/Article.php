<?php

namespace app\admin\controller;
use app\common\model\ArticleModel;
use app\common\model\ArticleCateModel;
use think\Db;

class Article extends Base
{

    function _initialize()
    {
        parent::_initialize();
        //tabs url
        $sc_urls = ['1'=>'article/add_article','2'=>'article/add_video'];

        $this->assign('sc_urls',$sc_urls);
        $this->assign('tid', 1);
    }

    /**
     * [index 活动列表]
     */
    public function index(){
        
        $map = $this->_get_params();
              
        $page = input('get.page') ? input('get.page'):1;
        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $count = Db::name('article ar')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $pagesize));
        $offset = $pagesize * ($page - 1);//起始页
        $article = new ArticleModel();
        $lists = $article->getArticleByWhere($map, $page, $pagesize);

        $this->assign('list_data', $lists);
        $this->assign('publish_info_type', config('publish_info_type'));

        $cate = new ArticleCateModel();
        $this->assign('cate',$cate->getAllCate());

        //分页的另外一种写法
        $pages = new \think\paginator\driver\Bootstrap($lists, $pagesize, $page, $count, false, ['path'=>url('index',$param)]);
        $this->assign('page', $pages->render());

        return $this->fetch();
    }

    private function _get_params(){
        $where = [];
        $start_time = input("start_time");
        $end_time = input("end_time");
        $key = input('key');
        $cate_id = input('cate_id');
        $type = input('type');

        if (!empty($start_time) && empty($end_time)) {
           $where['ar.create_time'] = ['>=',strtotime($start_time)];
        }else if (!empty($end_time) && empty($start_time)) {
           $where['ar.create_time'] = ['<=',strtotime($end_time)];
        }else if(!empty($start_time) && !empty($end_time)){
            $where["ar.create_time"][] = ['>=',strtotime($start_time)];
            $where["ar.create_time"][] = ['<=',strtotime($end_time)];
        }
        if($key&&$key!==""){
            $where['ar.title'] = ['like',"%" . $key . "%"];          
        } 
        if(!empty($cate_id)){
            $where['ar.cate_id'] = $cate_id;          
        } 
        if(!empty($type)){
            $where['ar.type'] = $type;          
        } 


        return $where;
    }


    /**
     * [add_article 添加活动]
     * @return [type] [description]
     */
    public function add_article()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $article = new ArticleModel();
            $flag = $article->insertArticle($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $cate = new ArticleCateModel();
        $this->assign('cate',$cate->getAllCate());
		
        return $this->fetch();
    }


    /**
     * [edit_article 编辑活动]
     * @return [type] [description]
     */
    public function edit_article()
    {
        $article = new ArticleModel();
        $id = input('param.id');
        if(request()->isAjax()){
			
            $param = input('post.');         
            $flag = $article->updateArticle($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
		
        $cate = new ArticleCateModel();
        $this->assign('cate',$cate->getAllCate());
        $this->assign('article',$article->getOneArticle($id));
		
        return $this->fetch();
    }



    /**
     * [del_article 删除活动]
     * @return [type] [description]
     */
    public function del_article()
    {
        $id = input('param.id');
        $cate = new ArticleModel();
        $flag = $cate->delArticle($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [article_state 活动状态]
     * @return [type] [description]
     */
    public function article_state()
    {
        $id=input('param.id');
        $status = Db::name('article')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('article')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('article')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }

   
    //*********************************************分类管理*********************************************//

    /**
     * [index_cate 分类列表]
     * @return [type] [description]
     */
    public function index_cate(){

        $cate = new ArticleCateModel();
        $list = $cate->getAllCate();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [add_cate 添加分类]
     * @return [type] [description]
     */
    public function add_cate()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $cate = new ArticleCateModel();
            $flag = $cate->insertCate($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return $this->fetch();
    }


    /**
     * [edit_cate 编辑分类]
     * @return [type] [description]
     */
    public function edit_cate()
    {
        $cate = new ArticleCateModel();

        if(request()->isAjax()){

            $param = input('post.');
            $flag = $cate->editCate($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign('cate',$cate->getOneCate($id));
        return $this->fetch();
    }


    /**
     * [del_cate 删除分类]
     * @return [type] [description]
     */
    public function del_cate()
    {
        $id = input('param.id');
        $cate = new ArticleCateModel();
        $flag = $cate->delCate($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [cate_state 分类状态]
     * @return [type] [description]
     */
    public function cate_state()
    {
        $id=input('param.id');
        $status = Db::name('article_cate')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('article_cate')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('article_cate')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }  

}