<?php
// +----------------------------------------------------------------------
// | Author: 程斌 <php_er@126.com> <http://www.diy45.com>
// +----------------------------------------------------------------------

namespace Extend\Pay;

defined('THINK_PATH') or exit();

class yeepay extends pay_abstract{
	const CHARSET = 'utf-8';
	/**
	 * 初始化方法
	 * @return [type] [description]
	 */
	public function _init(){
		$this->_init_config();
	}

	/**
	 * 初始化配置
	 * @return [type] [description]
	 */
	private function _init_config(){
		//易宝支付平台统一使用GBK/GB2312编码方式,参数如用到中文，请注意转码
		$this->config['charset'] = self::CHARSET;
		//发送请求的时候ssl验证文件
		$this->config['gateway_method'] = 'POST';// 提交地址
		// https://www.yeepay.com/app-merchant-proxy/node
		// http://tech.yeepay.com:8080/robot/debug.action
		$this->config['gateway_url'] = 'https://www.yeepay.com/app-merchant-proxy/node';
		// 易宝页面跳转和平台通信都是同一个地址
		if(!isset($this->config['return_url']) || empty($this->config['return_url'])){
			$this->config['return_url'] = return_url($this->config['paycode']);
		}

		if(!isset($this->config['error_notify_url']) || empty($this->config['error_notify_url'])){
			$this->config['error_notify_url'] = return_error_url($this->config['paycode']);
		}

		$this->config['Cur'] = 'CNY';
		
	}

	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 * @return [type] [description]
	 */
	public function receive(){
		// 1.先验证
		$return_parameter = $this->return_parameter;
		$receive_hmac = $return_parameter['hmac'];
		/*过滤掉多余的参数*/
		$receive_data = $return_parameter;
		//$this->filterParameter($return_parameter);
		// $receive_data = arg_sort($receive_data);
		if($receive_data){
			$hmac = build_myhmac($receive_data, $this->config['key']);
			// var_dump($receive_data);
			// exit;
			/*验证签名*/
			if($hmac == $receive_hmac){
				$return_data['trade_sn'] = $receive_data['r6_Order'];
				switch ($receive_data['r1_Code']){				
					case 1:
						$return_data['order_status'] = true;
						break;
					default:
						 $return_data['order_status'] = false;						
				}
				$res = proce_order($return_data);
				// 如果是平台返回，就 echo su... 标识
				if($res && ($r9_BType =='2')){
					$status = 0;
					$this->return_info($status);
				}

				return $res;
			}
		}

		// write_log($return_parameter);
		return false;
	}

	/**
	 * POST接收数据，易宝向同一个地址发送
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消6交易发生错误）
	 * @return [type] [description]
	 */
	public function notify(){
	}

	/**
	 * 错误处理
	 * @return [type] [description]
	 */
	public function error_notify(){
		// 错误通信地址，可以做日志处理
	}

	/**
	 * 装载订单记录
	 * @return [type] [description]
	 */
	public function getpreparedata() {
		$prepare_data =	array(
			'p0_Cmd'=>'Buy',
			'p1_MerId'=>$this->config['account'],
			'p2_Order'=>$this->orderinfo['trade_sn'],
			'p3_Amt'=>$this->orderinfo['money'],
			'p4_Cur'=>$this->config['Cur'],
			'p5_Pid'=>$this->orderinfo['money'], //$this->to_gbk($this->orderinfo['usernote']),
			'p6_Pcat'=>'',
			'p7_Pdesc'=>$this->orderinfo['money'],//$this->to_gbk($this->orderinfo['usernote']),
			'p8_Url'=>$this->config['return_url'],
			'p9_SAF'=>'0',
			'pa_MP'=>'',
			'pd_FrpId'=>$this->prepare_data['bank'],
			'pr_NeedResponse'=>"1",
		);

		// 数字签名
		$prepare_data['hmac'] = getReqHmacString($prepare_data, $this->config['key']);
		return $prepare_data;
	}

	/**
	 * 成功处理订单之后，处理结果通知渠道商，给出对应状态
	 * @return [type] [description]
	 */
	public function return_info($status){
		// 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
		if($status === 0){
			$res_msg = 'success';
			echo $res_msg;
		}
	}

	// 由于易宝平台用的gbk编码，所以中文要转换成gbk编码
	private function to_gbk($str){
		// return mb_convert_encoding($str, 'GBK');
		return $str;
	}
}