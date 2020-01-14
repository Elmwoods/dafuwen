<?php

namespace app\common\model;

use think\Model;

class OrderInfo extends Model
{
    protected $name = 'order_info';

    //
    public function find($where){
        return db($this->name)->where($where)->find();
    }

	//修改订单状态
	public function update_data($where,$data){
        return OrderInfo::where($where)->update($data);
    }

    /*
     *增加基础订单
     */
    public function store_base_order($store_id,$user_id,$amount,$order_type,$mark){

    	$db_order_info = db('order_info');

    	$data['store_id'] = $store_id;
		$data['user_id'] = $user_id;
        $data['order_sn'] = get_order_sn();
		$data['order_status'] = 0;
		$data['pay_status'] = 0;
		$data['order_amount'] = $amount;
		$data['add_time'] = time();
		$data['pay_valid_time'] = strtotime(date('Ym').config('pay_valid_time'));
        $data['late_fee_scale'] = config('late_fee_scale');
		$data['order_type'] = $order_type;
		$data['mark'] = $mark;
		// p($data);
		return $db_order_info->insert($data);
    } 


    /*
     *生成本月订单
     */
    public function set_order_by_thisMonth($user_id){

    	$db_order_info = db('order_info');
    	$db_store = db('store');

    	$store = $db_store->where('user_id',$user_id)->find();
    	if(!$store){
    		throw new Exception("店铺不存在", 1);	
    	}

        $store_id = 0;//付给平台

    	//本月第一天
    	$month_oneday = strtotime(date("Y-m"));//p($month_oneday);

    	$row1 = $row2 = 1;
    	$base1 = $db_order_info
    	->where(['order_type'=>1,'user_id'=>$user_id])
    	->where('add_time','>',$month_oneday)
    	->select();
    	if( !$base1 ){
    		$row1 = $this->store_base_order($store_id,$user_id,$store['base_rent'],1,'基础租金');
    	}

    	$base2 = $db_order_info
    	->where(['order_type'=>2,'user_id'=>$user_id])
    	->where('add_time','>',$month_oneday)
    	->select();
    	if( !$base2 ){
    		$row2 = $this->store_base_order($store_id,$user_id,$store['manage_fee'],2,'管理费');
    	}

    	return $row1+$row2;
    }


    /*
     *滞纳金订单
     *定时任务
     */
    public function set_fine_order(){

        $db_order_info = db('order_info');
        $late_fee_scale = config('late_fee_scale');
        $pay_valid_time =  config('pay_valid_time');//p($pay_valid_time);

        //本月第一天
        $month_oneday = strtotime(date("Y-m"));
        $all = $db_order_info
        ->field('order_id,store_id,user_id,user_name,order_type,order_sn,order_amount,add_time,pay_valid_time,late_fee_scale')
        ->where('order_type','<>',5)                //排除扫码
        ->where('order_status','=',0)               //未支付
        ->where('pay_valid_time','<',time())        //逾期
        ->select();
        // p($all);

        $store = [];
        if( $all ){

            //根据商铺分组
            foreach ($all as $key => $value) {
                //逾期天数
                $overday = floor( ( time() - $value['pay_valid_time'])/86400 );//p($overday);
                
                // $all[$key]['overday'] = $overday;

                $all[$key]['overmoney'] = $value['order_amount'] * $value['late_fee_scale'] * $overday;

                $ym = date('Ym',$value['add_time']);
                // $all[$key]['monthday'] = $ym;

                $k = $ym.'-'.$value['store_id'].'-'.$value['user_id'];
                if( !isset($store[$k]) ){
                    $store[$k] = [];
                }
                $store[$k][] = $all[$key];

            }
            // p($all);
            // p($store);

            $update = $add = [];
            //生成滞纳金订单
            foreach ($store as $key => $value) {

                $types = array_column($value, 'order_type');
               
                    
                    foreach ($value as $k => $v) {

                        if( in_array(3, $types) ){
                        //存在更新
                            if( $v['order_type'] == 3 ){
                                $update[$key]['order_id'] = $v['order_id'];
                            }else{
                                !isset($update[$key]) && $update[$key] = [];
                                !isset($update[$key]['overmoney']) && $update[$key]['overmoney'] = 0;
                                $update[$key]['overmoney'] += $v['overmoney']; 
                                $update[$key]['order_amount'] = sprintf( '%.2f',$update[$key]['overmoney'] ); 
                            }

                        }else{
                        //不存在添加
                            !isset($add[$key]) && $add[$key] = [] ;
                            !isset($add[$key]['overmoney']) && $add[$key]['overmoney'] = 0;
                            $add[$key]['overmoney'] += $v['overmoney']; 
                            $add[$key]['order_amount'] = sprintf( '%.2f',$add[$key]['overmoney'] );
                        }
                    }

                    //去除中间介质
                    if( isset($update[$key]['overmoney']) ) {
                        unset( $update[$key]['overmoney'] );
                    }
                    if( isset($add[$key]['overmoney']) ) {
                        unset( $add[$key]['overmoney'] );
                    }

                    if( isset($add[$key]) ){

                        list($mtime,$store_id,$user_id) = explode('-',$key);
                        $add[$key]['store_id'] = 0;
                        $add[$key]['user_id'] = $user_id;
                        $add[$key]['order_sn'] = get_order_sn();
                        $add[$key]['order_status'] = 0;
                        $add[$key]['pay_status'] = 0;
                        $add[$key]['add_time'] = strtotime($mtime.$pay_valid_time);
                        $add[$key]['pay_valid_time'] = strtotime($mtime.$pay_valid_time);
                        $add[$key]['late_fee_scale'] = $late_fee_scale;
                        $add[$key]['order_type'] = 3;
                        $add[$key]['mark'] = '滞纳金';

                    }
                    
                
            }
            // p($update);
            // pe($add);

            if($add){
                $db_order_info->insertAll($add);
            }

            if($update){
                $this->saveAll($update,true);
            }



        }

        return true;
    }


    

}