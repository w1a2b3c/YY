<?php

namespace app\wenjian\pay;
use think\Db;

class mr {
    
	//数据签名算法
    static public function sign($api,$yek)
    {
        ksort($api); //排序GET和POST参数
        reset($api); //内部指针指向数组中的第一个元素
        $sign = '';//初始化
        foreach ($api AS $key => $val) { //遍历附加参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $sign .= "$key=$val"; //拼接为url参数形式
        }
        return md5($sign.$yek);
    }
    
    //异步回调数据
    static public function ybhd($eer,$data,$yek)
    {
        error_reporting(0);
        $dat = array(
		    "type"	=> $data['type'],//支付类型
		    "money"	=> $data['money'],//付款金额
		    "order" => $data['order_id'],//云端订单号
		    "record"=> $data['record'],//附加参数
        );
        //建立请求
        $urls = http_build_query($dat);
        $sign = self::sign($dat,$yek);
        if(strpos($data['notify'],'?'))
	        $url['notify']='&'.$urls.'&sign='.$sign;
        else
            if($eer['method']=='post'){
                $url['notify']= $urls.'&sign='.$sign;
            }else{
                $url['notify']='?'.$urls.'&sign='.$sign;
            }
        if(strpos($data['refer'],'?'))
	        $url['return']='&'.$urls.'&sign='.$sign;
        else
            $url['return']='?'.$urls.'&sign='.$sign;
		
        return $url;
    }
    
}