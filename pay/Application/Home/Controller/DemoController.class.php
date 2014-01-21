<?php

namespace Home\Controller;

/**
 * 功能测试页面
 */
class DemoController{
	
    /**
     * 充值处理页面
     * @return [type] [description]
     */
	function reg_demo(){
        if(!IS_POST){
            $this->error('没有表单提交数据！');
        }
		// 表单信息提交，页面保存方式 payinfo[username];
		$payinfo = I('post.payinfo');
        // 检测数据输入的正确性，直接弹出错误，终止运行
        $this->checkdate($payinfo);
        // 写入订单信息，并且判断用户的存在性
        $userModel = D('Member');
        $username = $payinfo['username'];
        $userinfo = $userModel->getUsersInfoByUsername($username);
        if(!$userinfo){
            $this->error('不存在该用户，请确认后重新输入！');
        }

        // from 表单提交需要用到的数据
        $prepare_data = array();

        $prepare_data['bank'] = $payinfo['bank'];

        // 需要把页面选择的充值方式，前端提交过来
        // 为了安全起见，可以写个数组判断下
        $paycode_methods = array('yeepay', 'alipay');
        $paycode = $payinfo['paycode'];
        if(!in_array($paycode, $paycode_methods)){
        	$this->error('sorry，你选择的支付模式占不支持！');
        }
        $cfgstr = 'payment.'.$paycode;
        $paymentInfo = C($cfgstr);
        // 折扣
        $discount = $paymentInfo['rebate'];
        //$payinfo['price']
        //测试金额 0.01 rmb
        $payinfo['price'] = 0.01;
        $orderinfo = array(
            'trade_sn'=>create_sn(),
            'userid'=>$userinfo['uid'],
            'username'=>$userinfo['username'],
            'contactname'=>'',
            'email'=>$userinfo['email'],
            'telephone'=>$userinfo['mobile'],
            'discount'=>$discount,
            'money'=>$payinfo['price'],
            'quantity'=>1,
            'addtime'=>NOW_TIME,
            'paytime'=>0,
            'usernote'=>$username.'充值'.$payinfo['price'].'RMB',
            'paycode'=>$paycode,
            'payment'=>$paymentInfo['name'],
            'ip'=>getip(),
            'status'=>'unpay',
            'adminnote'=>'',
            'zoneid'=>$payinfo['zone']
        );

        $payaccountmodel = M('Payaccount');
        // 写入订单
        if(!$order_id = $payaccountmodel->data($orderinfo)->filter('strip_tags')->add()){
            $this->error('订单信息写入失败！');
        }

        $orderinfo['id'] = $order_id;
        $class_name = strtolower($paymentInfo['paycode']);
        //支付模式，配置数组
        $cfg = $paymentInfo;
        $pay_obj = new \Extend\Pay\pay_factory($class_name, $cfg);
        $from_html = $pay_obj->set_orderinfo($orderinfo)->set_prepare_data($prepare_data)->create_hand_from();
        $pageInfo = array('from_html'=>$from_html);
        // 充值跳转页面
        $this->assign($pageInfo);
        $this->display('auto_from');
	}

    public function checkdate($payinfo){
        $verify_session_key = get_sessionkey();
        if(session($verify_session_key)){
            if(!isset($payinfo['code']) || !$payinfo['code'] || !check_verify($payinfo['code'])){
                $this->error('验证码输入错误！');
            }
        }

        if(!isset($payinfo['username']) || !$payinfo['username']){
            $this->error('用户输入错误！');
        }
        $username = $payinfo['username'];

        if(!isset($payinfo['verifyusername']) || !$payinfo['verifyusername'] || ($payinfo['verifyusername']!=$username)){
            $this->error('两次输入用户名不一致！');
        }

        if(!isset($payinfo['price']) || !$payinfo['price']){
            $this->error('充值金额输入错误！');
        }

        if(!isset($payinfo['payid']) || !$payinfo['payid']){
            $this->error('支持模式错误！');
        }

        if(!isset($payinfo['zone']) || !$payinfo['zone']){
            $this->error('游戏大区错误！');
        }

        $payid = $payinfo['payid'];
        // 1，代表网银支付
        if($payid == 1){
            if(!isset($payinfo['bank']) || !$payinfo['bank']){
                $this->error('选择银行错误！');
            }
        }

    }

	function testreg(){
		$cfg = array();
		$reg = new \Extend\Pay\pay_factory('alipay', $cfg);
	}
}
