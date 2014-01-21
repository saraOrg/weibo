<?php
// +----------------------------------------------------------------------
// | Author: 程斌 <php_er@126.com> <http://www.diy45.com>
// +----------------------------------------------------------------------

namespace Extend\Pay;

defined('THINK_PATH') or exit();

/**
 * 支付模块抽象基类
 */
abstract class pay_abstract{
	// 订单配置信息
	public $config = array();
	// 订单信息
	public $orderinfo = array();

	// 返回参数
	public $return_parameter = array();

	// 用户from表单跳转的数组
	public $prepare_data = array();

	function __construct($cfg=''){
		if($cfg){
			$this->config = $cfg;
		}

		// 获取子类名称
		$child_class = trim(str_replace(__NAMESPACE__, '', get_class($this)), '\\');

		$fun_file_path = 'Extend.Pay.functions.';
		// 引入全局支付函数文件
		import($fun_file_path.'global', '', '.func.php');
		// 引入支付函数文件
		import($fun_file_path.$child_class, '', '.func.php');
		$this->_init();
	}

	public function _init(){

	}

	/**
	 * 设置订单信息
	 * @param [type] $orderinfo [description]
	 */
	public function set_orderinfo($orderinfo){
		$this->orderinfo = $orderinfo;
		return $this;
	}

	/**
	 * 设置配置信息
	 * @param [type] $cfg [description]
	 */
	public function set_config($cfg){
		foreach ($config as $key => $value){
			$this->config[$key] = $value;
		}
		return $this;
	}

	public function set_return_parameter($parameter){
		foreach ($parameter as $key => $value){
			$this->return_parameter[$key] = $value;
		}
		return $this;
	}

	public function set_prepare_data($prepare_data){
		foreach ($prepare_data as $key => $value){
			$this->prepare_data[$key] = $value;
		}
		return $this;
	}

	/**
	 * 根据提交数据，创建自动提交表单
	 * @param  [type] $fromdata [description]
	 * @return [type]           [description]
	 */
	public function create_auto_from($fromdata){
		 // onLoad="document.payform.submit()"
		$str = '<html><head></head><body>';
		if (strtoupper($this->config['gateway_method']) == 'POST'){
			$str .= '<form name="payform" action="' . $this->config['gateway_url'] . '" method="POST">';
		}else{
			$str .= '<form name="payform" action="' . $this->config['gateway_url'] . '" method="GET">';
		}
		$prepare_data = $this->getpreparedata();
		foreach ($prepare_data as $key => $value){
			$str .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}
		$str .= '</form>';
		$str .= '</body></html>';
		return $str;
	}

	/**
	 * 根据提交数据，创建手动提交表单 [一般用于调试]
	 * @param  [type] $fromdata [description]
	 * @return [type]           [description]
	 */
	public function create_hand_from($fromdata){
		 // onLoad="document.payform.submit()"
		$str = '<html><head></head><body>';
		if (strtoupper($this->config['gateway_method']) == 'POST'){
			$str .= '<form name="payform" action="' . $this->config['gateway_url'] . '" method="POST">';
		}else{
			$str .= '<form name="payform" action="' . $this->config['gateway_url'] . '" method="GET">';
		}
		$prepare_data = $this->getpreparedata();
		foreach ($prepare_data as $key => $value){
			$str .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}
		$str .= '<input type="submit" value="提交"/>';
		$str .= '</form>';

		$str .= '</body></html>';
		return $str;
	}

	/**
	 * get返回，响应方法
	 * @return [type] [description]
	 */
	abstract function receive();

	/**
	 * 平台通知，响应方法
	 * @return [type] [description]
	 */
	abstract function notify();

	/**
	 * 错误处理
	 * @return [type] [description]
	 */
	abstract function error_notify();

	/**
	 * 获取准备好的数据
	 * @return [type] [description]
	 */
	abstract function getpreparedata();

	/**
	 * 成功处理订单之后，处理结果通知渠道商，给出对应状态
	 * @return [type] [description]
	 */
	abstract function return_info($status);
}