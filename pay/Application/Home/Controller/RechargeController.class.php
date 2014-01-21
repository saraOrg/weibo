<?php
namespace Home\Controller;

/**
 * 充值控制器
 */
class RechargeController extends HomeController {

    function respond_notify($paycode){
        $return_parameter = I('post.');
        $pay_obj = $this->create_pay_api($paycode, $return_parameter);
        $pay_obj->notify();
    }

    function respond_receive($paycode){
        // 为了兼容易宝，改成 request
        $return_parameter = I('request.');
        $pay_obj = $this->create_pay_api($paycode, $return_parameter);
        $r = $pay_obj->receive();
        if($r){
            $this->success('充值成功！', U('home/recharge'));
        }else{
            $this->error('充值失败，请联系客服！', U('home/recharge'));
        }
    }

    function respond_error($paycode){
        $return_parameter = I('request.');
        $pay_obj = $this->create_pay_api($paycode, $return_parameter);
        $pay_obj->error_notify();
    }

    private function create_pay_api($paycode, $return_parameter){
        $cfgstr = 'payment.'.$paycode;
        $cfg = C($cfgstr);
        $pay_obj = new \Extend\Pay\pay_factory($paycode, $cfg);
        $pay_obj->set_return_parameter($return_parameter);
        return $pay_obj;
    }

}
