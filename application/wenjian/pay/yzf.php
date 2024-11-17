<?php

namespace app\wenjian\pay;
use think\Db;

class yzf {

	//数据签名算法
    static public function sign($api,$yek)
    {
        $prestr=self::createLinkstring(self::argSort(self::paraFilter($api)));
        $sign = self::md5Sign($prestr, $yek);
        return $sign;
    }
    
    //异步回调数据
    static public function ybhd($eer,$data,$yek)
    {
        error_reporting(0);
	    if($data['type']=='alipay'){
    	    $data['type']= 'alipay';
	    }elseif($data['type']=='weixin'){
    	    $data['type']= 'wxpay';
	    }elseif($data['type']=='qq'){
    	    $data['type']= 'qqpay';
	    }
	    $array=array('pid'=>$data['pid'],'trade_no'=>$data['order_id'],'out_trade_no'=>$data['out_trade_no'],'type'=>$data['type'],'name'=>$data['name'],'money'=>(float)$data['money'],'trade_status'=>'TRADE_SUCCESS');
	    if(!empty($data['param']))$array['param']=$data['param'];
	    $arg=self::argSort(self::paraFilter($array));
	    $prestr=self::createLinkstring($arg);
	    $urlstr=self::createLinkstringUrlencode($arg);
	    $sign=self::md5Sign($prestr, $yek);
	    if(strpos($data['notify'],'?'))
	        $url['notify']='&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        else
            if($eer['method']=='post'){
                $url['notify']= $urlstr.'&sign='.$sign.'&sign_type=MD5';
            }else{
                $url['notify']='?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
            }
        if(strpos($data['refer'],'?'))
	        $url['return']='&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        else
            $url['return']='?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
		
        return $url;
    }
    
    static public function createLinkstring($para) {
		$arg  = "";
		foreach ($para as $key=>$val) {
			$arg.=$key."=".$val."&";
		}
		$arg = substr($arg,0,-1);

		return $arg;
	}
	static public function createLinkstringUrlencode($para) {
		$arg  = "";
		foreach ($para as $key=>$val) {
			$arg.=$key."=".urlencode($val)."&";
		}
		$arg = substr($arg,0,-1);

		return $arg;
	}
	static public function paraFilter($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == "sign" || $key == "sign_type" || $val == "")continue;
			else $para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}
	static public function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}
	static public function md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}
	static public function md5Verify($prestr, $sign, $key) {
		$prestr = $prestr . $key;
		$mysgin = md5($prestr);

		if($mysgin == $sign) {
			return true;
		}
		else {
			return false;
		}
	}
    
}