<?php
namespace app\shop\controller;

use think\Page;
use think\Verify;
use think\Db;
use think\Session;

class Supplier extends Base
{

	/**
     * 供应商列表
     */
    public function supplier()
    {
        $supplier_count = DB::name('suppliers')->count();
        $page = new Page($supplier_count, 10);
        $show = $page->show();
        $supplier_list = DB::name('suppliers')
            ->alias('s')
            ->field('s.*,a.id admin_id,a.username user_name')
            ->join('__ADMIN__ a', 'a.suppliers_id = s.suppliers_id', 'LEFT')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('list', $supplier_list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 供应商资料
     */
    public function supplier_info()
    {
        $suppliers_id = I('get.suppliers_id/d', 0);
        if ($suppliers_id) {
            $info = DB::name('suppliers')
                ->alias('s')
                ->field('s.*,a.id admin_id,a.username user_name')
                ->join('__ADMIN__ a', 'a.suppliers_id = s.suppliers_id', 'LEFT')
                ->where(array('s.suppliers_id' => $suppliers_id))
                ->find();
            $this->assign('info', $info);
        }
        $act = empty($suppliers_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        $admin = M('admin')->field('id as admin_id,username as user_name')->select();
        $this->assign('admin', $admin);
        return $this->fetch();
    }

    /**
     * 供应商增删改
     */
    public function supplierHandle()
    {
        $data = I('post.');
        $suppliers_model = M('suppliers');
        $admin_id = isset($data['admin_id'])?$data['admin_id']:0;
        //增
        if (!empty($data['act']) && $data['act'] == 'add') {

            unset($data['suppliers_id'],$data['admin_id'],$data['act']);
            $count = $suppliers_model->where("suppliers_name", $data['suppliers_name'])->count();
            if ($count) {
                $this->error("此供应商名称已被注册，请更换", U('supplier/supplier_info'));
            } else {
                $r = $suppliers_model->insertGetId($data);
                if (!empty($admin_id)) {
                    $admin_data['suppliers_id'] = $r;
                    M('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
                    M('admin')->where(array('id' => $admin_id))->save($admin_data);
                }
            }
        }
        //改
        if (!empty($data['act']) && $data['act'] == 'edit' && $data['suppliers_id'] > 0) {
            unset($data['admin_id'],$data['act']);

            $r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->save($data);
            if (!empty($admin_id)) {
                $admin_data['suppliers_id'] = $data['suppliers_id'];
                $suppliers = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->find();
                //$admin_data['city_id'] = $suppliers['city_id'];
                //$admin_data['province_id'] = $suppliers['province_id'];
                M('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
                M('admin')->where(array('id' => $admin_id))->save($admin_data);
            }
        }
        //删
        if (!empty($data['act']) && $data['act'] == 'del' && $data['suppliers_id'] > 0) {
            $r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->delete();
            M('admin')->where(array('suppliers_id' => $data['suppliers_id']))->save(array('suppliers_id' => 0));
        }

        if ($r !== false) {
            $this->success("操作成功", U('supplier/supplier'));
        } else {
            $this->error("操作失败", U('supplier/supplier'));
        }
    }

}