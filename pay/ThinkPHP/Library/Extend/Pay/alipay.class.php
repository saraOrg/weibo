<?php
// +----------------------------------------------------------------------
// | Author: 程斌 <php_er@126.com> <http://www.diy45.com>
// +----------------------------------------------------------------------

namespace Extend\Pay;

defined('THINK_PATH') or exit();

class alipay extends pay_abstract{
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
		$this->config['service'] = 'create_direct_pay_by_user';
		$this->config['charset'] = self::CHARSET;
		// 提交地址
		$this->config['gateway_url'] = 'https://www.alipay.com/cooperate/gateway.do?_input_charset='.self::CHARSET;
		//查询订单url，验证(notify_id)一次失效
		$this->config['queryord_url'] = 'https://mapi.alipay.com/gateway.do?_input_charset='.self::CHARSET;
		//发送请求的时候ssl验证文件
		$this->config['cacert'] = __DIR__.'\\cacert.pem';
		$this->config['gateway_method'] = 'POST';
		if(!isset($this->config['return_url']) || empty($this->config['return_url'])){
			$this->config['return_url'] = return_url($this->config['paycode']);
		}

		if(!isset($this->config['notify_url']) || empty($this->config['notify_url'])){
			$this->config['notify_url'] = return_url($this->config['paycode'], 1);
		}

		if(!isset($this->config['error_notify_url']) || empty($this->config['error_notify_url'])){
			$this->config['error_notify_url'] = return_error_url($this->config['paycode']);
		}
		
	}

	/**
	 * GET接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付5交易取消6交易发生错误）
	 * @return [type] [description]
	 */
	public function receive(){
		// 1.先验证
		$return_parameter = $this->return_parameter;
		$receive_sign = $return_parameter['sign'];
		/*过滤掉多余的参数*/
		$receive_data = $this->filterParameter($return_parameter);
		$receive_data = arg_sort($receive_data);
		if($receive_data){
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?partner='.$this->config['partner'].'&notify_id='.$receive_data['notify_id']);
			if(true || preg_match('/true$/i', $verify_result)){
				$sign = build_mysign($receive_data,$this->config['key'], 'MD5');
				/*验证签名*/
				if($sign == $receive_sign){
					$return_data['trade_sn'] = $receive_data['out_trade_no'];
					switch ($receive_data['trade_status']){				
						case 'TRADE_FINISHED':
						case 'TRADE_SUCCESS':
							$return_data['order_status'] = true;
							break;
						default:
							 $return_data['order_status'] = false;						
					}
					return proce_order($return_data);
				}
			}
		}

		// write_log($return_parameter);
		return false;
	}

	/**
	 * POST接收数据
	 * 状态码说明  （0 交易完成 1 交易失败 2 交易超时 3 交易处理中 4 交易未支付 5交易取消6交易发生错误）
	 * @return [type] [description]
	 */
	public function notify(){
		$status = 1;
		// 1.先验证
		$return_parameter = $this->return_parameter;
		$receive_sign = $return_parameter['sign'];
		/*过滤掉多余的参数*/
		$receive_data = $this->filterParameter($return_parameter);
		$receive_data = arg_sort($receive_data);
		if($receive_data){
			$verify_result = $this->get_verify('http://notify.alipay.com/trade/notify_query.do?partner='.$this->config['partner'].'&notify_id=' . $receive_data['notify_id']);
			if(preg_match('/true$/i', $verify_result)){
				$sign = build_mysign($receive_data,$this->config['key'], 'MD5');
				/*验证签名*/
				if($sign == $receive_sign){
					$return_data['trade_sn'] = $receive_data['out_trade_no'];
					switch ($receive_data['trade_status']){				
						case 'TRADE_FINISHED':
						case 'TRADE_SUCCESS':
							$return_data['order_status'] = true;
							break;
						default:
							 $return_data['order_status'] = false;						
					}
					
					$r = proce_order($return_data);
					if($r){
						$status = 0;
					}
					$this->return_info($status);
					return $r;

				}
			}
		}		
		// 平台通知方式，需要调用方法通知渠道商
		$this->return_info($status);

		// write_log($return_parameter);
		return false;
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
		// 基本参数
		$prepare_data['service'] = $this->config['service'];
		$prepare_data['partner'] = $this->config['partner'];
		$prepare_data['_input_charset'] = $this->config['charset'];
		$prepare_data['notify_url'] = $this->config['notify_url'];
		$prepare_data['return_url'] = $this->config['return_url'];
		$prepare_data['error_notify_url'] = $this->config['error_notify_url'];

		// 业务参数，商品信息
		$prepare_data['out_trade_no'] = $this->orderinfo['trade_sn'];
		$prepare_data['subject'] = $this->orderinfo['usernote'];
		$prepare_data['payment_type'] = '1';
		$prepare_data['seller_email'] = $this->config['account'];		
		$prepare_data['price'] = $this->orderinfo['money'];
		$prepare_data['quantity'] = $this->orderinfo['quantity'];
		$prepare_data['body'] = $this->orderinfo['usernote'];
		
		$prepare_data = arg_sort($prepare_data);
		// 数字签名
		$prepare_data['sign'] = build_mysign($prepare_data, $this->config['key'], 'MD5');
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
		}else{
			$res_msg = 'fail';
		}

		echo $res_msg;
	}

	protected function get_verify($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO, $this->config['cacert']);//证书地址
		$responseText = curl_exec($curl);
		// var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);
		
		return $responseText;
    }

	/**
     * 返回字符过滤
     * @param $parameter
     */
	public function filterParameter($parameter){
		$para = array();
		foreach ($parameter as $key => $value)
		{
			if ('sign' == $key || 'sign_type' == $key || 'paycode' == $key ) continue;
			else $para[$key] = $value;
		}
		return $para;
	}
}