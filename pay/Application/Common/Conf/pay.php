<?php
return array(
	//担心 alipay 这样的配置节点冲突，所以配置成二维数组
	'payment'=>array(
		'yeepay'=>array(
			// 支付渠道名称
			'name'=>'易宝',
			// 该paycode，确定扩展中的类名，扩展支付渠道的函数类名
			'paycode'=>'yeepay',
			// 比例，默认为1；
			'rebate'=>'1',
			// 可以自定义返回处理地址，默认有处理地址：http://www.domain.com/index.php?s=/home/recharge/respond_receive
			'return_url'=>'',
			'error_notify_url'=>'',
			// 支付渠道帐号（11位数字）如：20140113111，开通易宝账户后给予
			'account'=>'',
			// 加密key，开通易宝账户后给予
			'key'=>'',
			// 简介
			'description'=>'易宝（YeePay.com）是中国行业支付的领导者。2003年8月成立。',
		),

		'alipay'=>array(
			'name'=>'支付宝',
			// 该paycode，确定扩展中的类名，扩展支付渠道的函数类名
			'paycode'=>'alipay',
			'rebate'=>'1',
			// 支付宝同步通知地址（浏览器跳转），可以自定义返回处理地址，默认有处理地址：http://www.domain.com/index.php?s=/home/recharge/respond_receive
			'return_url'=>'',
			// 支付宝异步通知地址，可以自定义返回处理地址，默认有处理地址：http://www.domain.com/index.php?s=/home/recharge/respond_notify
			'notify_url'=>'',
			'error_notify_url'=>'',
			// 支付渠道帐号，开通支付宝账户后给予
			'account'=>'',
			// 加密key，开通支付宝账户后给予
			'key'=>'',
			// 合作者ID，支付宝有该配置，开通易宝账户后给予
			'partner'=>'',
			// 简介
			'description'=>'支付宝是国内领先的独立第三方支付平台，由阿里巴巴集团创办。',
		),
	)
	
);