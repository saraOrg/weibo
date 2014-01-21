<?php
// +----------------------------------------------------------------------
// | Author: 程斌 <php_er@126.com> <http://www.diy45.com>
// +----------------------------------------------------------------------

namespace Extend\Pay;

defined('THINK_PATH') or exit();

/**
 * 支付模块工厂
 */
class pay_factory{	
	// 支付渠道对象
	public $pay_obj = null;
	/**
	 * 构造函数
	 * @param [type] $name 支付渠道名称
	 * @param [type] $cfg  支付模块配置参数
	 */
	function __construct($name, $cfg){
		$this->set_adapter($name, $cfg);
	}

	/**
	 * 实例化支付渠道
	 * @param [type] $name [description]
	 * @param [type] $cfg  [description]
	 */
	private function set_adapter($name, $cfg){
		if(!$this->pay_obj){
			$class = __NAMESPACE__.'\\'.$name;
			$this->pay_obj = new $class($cfg);
		}

		return $this->pay_obj();
	}

	function __call($method, $arguments){
		if(method_exists($this, $method)){
			return call_user_func_array(array(& $this, $method), $arguments);
		}elseif(!empty($this->pay_obj) && $this->pay_obj instanceof pay_abstract && method_exists($this->pay_obj, $method)  ){
			return call_user_func_array(array(& $this->pay_obj, $method), $arguments);
		}
	}
}