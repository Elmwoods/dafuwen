<?php

namespace app\admin\controller;
use app\admin\model\UserModel;
use app\admin\model\UserType;
use think\Db;

class User extends Base
{

    /**
     * [index 用户列表]
     * @return [type] [description]
     */
    public function index(){
        $pagesize = config('paginate')['list_rows'];//每页数量
        $param=request()->param(); //获取url参数
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['username'] = ['like',"%" . $key . "%"];          
        }       
        $count = Db::name('admin')->where($map)->count();//计算总页面
        $lists = db('admin')->alias('adm')->field('adm.*,title')->join('auth_group ag', 'adm.groupid = ag.id')
            ->where($map)->order('id desc')->paginate($pagesize,false,array('query' => array_splice($param,1)));
        $admin_id = session('admin_id');
        $this->assign('admin_id', $admin_id);
        $this->assign('count', $count); //当前页
        $this->assign('lists', $lists);
        $this->assign("page", $lists->render());
        $this->assign('val', $key);
        return $this->fetch();
    }

    /**
     * [userAdd 添加用户]
     * @return [type] [description]
     */
    public function userAdd()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['password'] = md5(md5($param['password']) . config('auth_key'));
            $user = new UserModel();
            $flag = $user->insertUser($param);
            $accdata = array(
                'uid'=> $user['id'],
                'group_id'=> $param['groupid'],
            );
            $group_access = Db::name('auth_group_access')->insert($accdata);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $role = new UserType();
        $this->assign('role',$role->getRole());
        return $this->fetch();
    }

    /**
     * [userEdit 编辑用户]
     * @return [type] [description]
     */
    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isAjax()){
            $param = input('post.');
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5(md5($param['password']) . config('auth_key'));
            }
            if($param['id'] == 1 && session('uid')!=1){
                $this->error('抱歉，您没有操作权限');
            }
            $flag = $user->editUser($param);
            $group_access = Db::name('auth_group_access')->where('uid', $user['id'])->update(['group_id' => $param['groupid']]);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $role = new UserType();
        $this->assign([
            'user' => $user->getOneUser($id),
            'role' => $role->getRole()
        ]);
        return $this->fetch();
    }

    /**
     * [UserDel 删除用户]
     * @return [type] [description]
     */
    public function UserDel()
    {
        $id = input('param.id');
        $role = new UserModel();
        $flag = $role->delUser($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [user_state 用户状态]
     * @return [type] [description]
     */
    public function user_state()
    {
        $id = input('param.id');
        $status = Db::name('admin')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('admin')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('admin')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }

}