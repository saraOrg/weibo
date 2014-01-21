<?php

/**
 * 支付宝平台是发起ajax请求，默认返回浏览器跳转处理地址
 * @param  integer $is_ajax [description]
 * @return [type]           [description]
 */
function return_url($paycode, $is_ajax = 0){
    $param = array('paycode'=>$paycode);
    if($is_ajax){
        $url  = U('Home/Recharge/respond_notify', $param, true, true);
    }else{
        $url  = U('Home/Recharge/respond_receive', $param, true, true);
    }

    return $url;
}

function return_error_url($paycode){
    $param = array('paycode'=>$paycode);
    return U('Home/Recharge/respond_error', $param, true, true);
}

function write_log($return_parameter){

}

/**
 * 根据结果数据处理订单
 * @param  [type] $return_data [description]
 * @return [type]              [description]
 */
function proce_order($return_data){
	$r = check_return_data_frm($return_data);
	if(!$r){
		// write_log('结果数组验证失败！');
		return false;
	}

	if(!$return_data['order_status']){
		// write_log('支付渠道返回未支付成功');
		return false;
	}

	//如果支付宝订单状态是成功，根据订单号查询订单信息
	$trade_sn = $return_data['trade_sn'];
	$ordermodel = M('payaccount');
	$order_info = $ordermodel->where(array('trade_sn'=>$trade_sn))->find();
	// 根据订单状态，来处理业务逻辑
	$r = order_is_proce($order_info['status']);

	if(!$r){
		// write_log('该比订单处于不需要业务逻辑处理的状态');
		return false;
	}

	$r = update_member_balance($order_info['userid']);
	if(!$r){
		// write_log('需要处理的状态，但是更新用户余额失败！');
		return false;
	}

	$r = update_order_status_to_succ($trade_sn);
	if(!$r){
		// write_log('账户余额更新成功，但是修改订单状态失败！');
		return false;
	}
	
	// write_log('订单成功处理！');
	return true;
}

/**
 * 验证订单结果数组格式
 * @param  [type] $return_data [description]
 * @return [type]              [description]
 */
function check_return_data_frm($return_data){
	$keys = array('trade_sn', 'order_status');
	foreach ($keys as $key => $value) {
		if(!array_key_exists($value, $return_data)){
			return false;
		}
	}

	return true;
}

/**
 * 处理业务逻辑订单
 * @param  [type] $userid [description]
 * @return [type]         [description]
 */
function update_member_balance($userid){
	return true;
}

/**
 * 把订单状态更新为成功状态
 * @return [type] [description]
 */
function update_order_status_to_succ($trade_sn){
	$ordermodel = M('payaccount');
	$where = array('trade_sn'=>$trade_sn);
	$data = array('status'=>'succ');
	return $ordermodel->where($where)->save($data);
}

/**
 * 判断指定状态的订单是否需要处理
 * @param  [type] $status [description]
 * @return [type]         [description]
 */
function order_is_proce($status){
	$status = strtolower($status);
	// 不需要处理的状态数组
	$no_proce_status = array('succ', 'cancel');
	return !in_array($status, $no_proce_status);
}