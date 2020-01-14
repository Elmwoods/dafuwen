<?php

namespace app\shop\controller;
use app\shop\logic\GoodsLogic;
use app\shop\model\SmsLog;
use think\Db;
use think\Cache;
class Systems extends Base
{

   /**
    * 自定义导航
    */
    public function navigationList(){
           $model = M("Navigation");
           $navigationList = $model->order("id desc")->select();
           $this->assign('navigationList',$navigationList);
           return $this->fetch('navigationList');
     }

    /**
     * 添加修改编辑 前台导航
     */
    public function addEditNav()
    {
        $model = D("Navigation");
        if (IS_POST) {
            $data = input('post.');
            unset($data['system_nav']);
            if (input('id')){
                $model->update($data);
            }else{
                $model->add($data);
            }
            $this->success("操作成功!!!", url('Systems/navigationList'));
            exit;
        }
        // 点击过来编辑时
        $id = I('id',0);
        $navigation = DB::name('navigation')->where('id',$id)->find();
        // 系统菜单
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $select_option = array();
        if(!empty($cat_list))
        {
            foreach ($cat_list AS $key => $value) {
                $strpad_count = $value['level'] * 4;
                $select_val = url("/Home/Goods/goodsList", array('id' => $key));
                $select_option[$select_val] = str_pad('', $strpad_count, "-", STR_PAD_LEFT) . $value['name'];
            }
        }
        $system_nav = array(
          
        );
        $system_nav = array_merge($system_nav, $select_option);
        $this->assign('system_nav', $system_nav);

        $this->assign('navigation', $navigation);
        return $this->fetch('_navigation');
    }

    /**
     * 删除前台 自定义 导航
     */
    public function delNav()
    {
        // 删除导航
        M('Navigation')->where("id",I('id'))->delete();
        $this->success("操作成功!!!",url('Systems/navigationList'));
    }

    /**
     * 商品静态页面缓存清理
     */
      public function ClearGoodsThumb(){
            $goods_id = I('goods_id');
            delFile(UPLOAD_PATH."goods/thumb/".$goods_id); // 删除缩略图
            $json_arr = array('status'=>1,'msg'=>'清除成功,请清除对应的静态页面','result'=>'');
            $json_str = json_encode($json_arr);
            exit($json_str);
      }
    
    /**
     *  管理员登录后 处理相关操作
     */
     public function login_task()
     {

  //       /*** 随机清空购物车的垃圾数据*/
  //       $time = time() - 3600; // 删除购物车数据  1小时以前的
  //       M("Cart")->where("user_id = 0 and  add_time < $time")->delete();
  //       $today_time = time();

		// // 删除 cart表垃圾数据 删除一个月以前的
		// $time = time() - 2592000;
  //       M("cart")->where("add_time < $time")->delete();
		// // 删除 tp_sms_log表垃圾数据 删除一个月以前的短信
  //       M("sms_log")->where("add_time < $time")->delete();

  //       // 发货后满多少天自动收货确认
  //       $auto_confirm_date = tpCache('shopping.auto_confirm_date');
  //       $auto_confirm_date = $auto_confirm_date * (60 * 60 * 24); // 7天的时间戳
		// $time = time() - $auto_confirm_date; // 比如7天以前的可用自动确认收货
  //       $order_id_arr = M('order')->where("order_status = 1 and shipping_status = 1 and shipping_time < $time")->getField('order_id',true);
  //       foreach($order_id_arr as $k => $v)
  //       {
  //           confirm_order($v);
  //       }

  //       // 多少天后自动分销记录自动分成
  //        $switch = tpCache('distribut.switch');
  //        if($switch == 1 && file_exists(APP_PATH.'common/logic/DistributLogic.php')){
  //           $distributLogic = new \app\common\logic\DistributLogic();
  //           $distributLogic->auto_confirm(); // 自动确认分成
  //        }
     }

	//清除所有活动数据
	public function clearProm()
	{
		Db::name('flash_sale')->where('1=1')->delete();
		Db::name('group_buy')->where('1=1')->delete();
		Db::name('prom_goods')->where('1=1')->delete();
		Db::name('prom_order')->where('1=1')->delete();
		Db::name('coupon')->where('1=1')->delete();
		Db::name('coupon_list')->where('1=1')->delete();
		Db::name('goods_coupon')->where('1=1')->delete();
		Db::name('goods')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('spec_goods_price')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('cart')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('order_goods')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		$this->success('清除活动数据成功');
	}

	//清楚拼团活动数据
	public function clearTeam(){
		Db::name('team_activity')->where('1=1')->delete();
		Db::name('team_follow')->where('1=1')->delete();
		Db::name('team_found')->where('1=1')->delete();
		Db::name('team_lottery')->where('1=1')->delete();
		Db::name('goods')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('spec_goods_price')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('order')->where('order_prom_type',6)->update(['order_prom_type' => 0, 'order_prom_id' => 0]);
		Db::name('order_goods')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		$this->success('清除拼团活动数据成功');
	}

    
    public function clearDemo(){
        $file = dirname(APP_PATH).'/data/data.lock';
        if(!file_exists($file)){
            file_put_contents($file, '1');
            Db::name('account_log')->where('1=1')->delete();
            Db::name('admin_log')->where('1=1')->delete();
//            Db::name('bank_card')->where('1=1')->delete();
//            Db::name('brand')->where('1=1')->delete();
            Db::name('file')->where('1=1')->delete();
            Db::name('pig_appeal')->where('1=1')->delete();
            Db::name('pig_award_log')->where('1=1')->delete();
            Db::name('pig_doge_money')->where('1=1')->delete();
            Db::name('pig_failure_redis')->where('1=1')->delete();
            Db::name('pig_order')->where('1=1')->delete();
            Db::name('pig_price_log')->where('1=1')->delete();
            Db::name('pig_reservation')->where('1=1')->delete();
            Db::name('recharge')->where('1=1')->delete();
            Db::name('user_address')->where('1=1')->delete();
            Db::name('user_apply_log')->where('1=1')->delete();
//            Db::name('user_distribution')->where('1=1')->delete();
            Db::name('user_exclusive_pig')->where('1=1')->delete();
            Db::name('user_exclusive_pig_del')->where('1=1')->delete();
            Db::name('user_exclusive_pig_delete')->where('1=1')->delete();
            Db::name('user_identity')->where('1=1')->delete();
            Db::name('user_lock_log')->where('1=1')->delete();
            Db::name('user_message')->where('1=1')->delete();
            Db::name('user_payment')->where('1=1')->delete();
            Db::name('user_payment_err')->where('1=1')->delete();
            Db::name('users')->where('1=1')->delete();
            Db::name('user_payment')->where('1=1')->delete();
            Db::name('send_mail')->where('1=1')->delete();

        }
    }
    /**
     * 短信发送日志
     * @author lhk
     */
    public function send_sms(){

        $keyword = input('keyword');
        $smsList = SmsLog::smsList($keyword);

        return $this->fetch('send_sms',[
            'keyword'   =>  $keyword,//搜索关键词
            'smsList'   =>  $smsList,//短信列表
        ]);
    }


}