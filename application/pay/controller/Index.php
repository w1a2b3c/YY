<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\pay\controller;
use think\Db;
use think\App;
use think\Controller;
use think\facade\Env;
use app\wenjian\QrcodeServer;

class Index extends Controller
{
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
    public function dernout()
    {
        if(config('url')!=$_SERVER['HTTP_HOST'] || config('url_api')!=$_SERVER['HTTP_HOST'] || config('url_jk')!=$_SERVER['HTTP_HOST']){
            if(config('url')==config('url_api') || config('url')==config('url_jk') || config('url_api')==config('url') || config('url_api')==config('url_jk') || config('url_jk')==config('url') || config('url_jk')==config('url_api')){
                return response()->code(404)->allowCache(false);
            }
        }
    }
    
    public function order()//API查询单个订单
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $api = xss(input());
        
        $pid = $api['pid'];
        if (!$pid || $pid == "") {
            $this->success('1001',null,'pid（附加信息：商户PID（商户后台获取）参数错误');
        }
        $key = $api['key'];
        if (!$key || $key == "") {
            $this->success('1002',null,'key（附加信息：商户KEY（商户后台获取）参数错误');
        }
        if ($api['order_id']=='' && $api['pay_id']=='' && $api['out_trade_no']=='' && $api['record']=='') {
            $this->success('1003',null,'order_id | pay_id | out_trade_no | record（附加信息：订单号（商户后台获取）参数错误');
        }
        $dsa = Db::table("pay_user")->where("id",$pid)->where("key",$key)->find();
        if(!$dsa){
            $this->success('1004',null,'查询失败（错误信息：商户PID或商户KEY 不正确)');
        }
        if($api['order_id']){
            $data = Db::table("pay_order")->where("pid", $pid)->where("order_id", $api['order_id'])->find();
        }else
        if($api['pay_id']){
            $data = Db::table("pay_order")->where("pid", $pid)->where("pay_id", $api['pay_id'])->find();
        }else
        if($api['out_trade_no']){
            $data = Db::table("pay_order")->where("pid", $pid)->where("out_trade_no", $api['out_trade_no'])->find();
        }else
        if($api['record']){
            $data = Db::table("pay_order")->where("pid", $pid)->where("record", $api['record'])->find();
        }
        if($data){
            $data = array(
                "pid" => $data['pid'],//商户PID
                "order_id" => $data['order_id'],//平台订单号
                "pay_id" => $data['pay_id'],//通道订单号
                "record" => $data['record'],//附加参数
                "type" => $data['type'],//支付方式
                "money" => $data['money'],//金额
                "price" => $data['price'],//实际金额
                "mid" => $data['mid'],//收款账号MID
                "mid_id" => $data['mid_id'],//通道ID
                "mid_name" => $data['mid_name'],//收款账号
                "api_state" => $data['api_state'],//回调状态
                "api_budan" => $data['api_budan'],//补单状态
                "state" => $data['state'],//订单状态
            );
            return json($this->getReturn(1, "查询成功", $data));
        }else{
            return json($this->getReturn(-1, "查询失败（错误信息：订单号 不存在)"));
        }
    }
    
    public function index()//发起创建订单
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
       
        $data = Db::table("pay_sz")->find();
        if($data['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$data);
            return $this->fetch('../application/index/view/404/index.html');
        }else{
        error_reporting(0);
        $api = xss(input());
        if(!$api){
            if(!config("ms")){
                $this->redirect('/index/user/jieko');
            }else{
                return response()->code(404)->allowCache(false);
            }
        }
        closeEndOrder();//检测过期订单
        $pid = $api['pid'];
        if (!$pid || $pid == "") {
            $this->success('1001',null,'pid（附加信息：商户PID（商户后台获取）参数错误');
        }
        $dsa = Db::table("pay_user")->where("id",$pid)->find();
        $orderId = date("YmdHms").rand(1, 9).rand(1, 9).rand(1, 9).rand(1, 9);//平台订单号
        if($dsa['duijei']==1){
            $orderId = $this->mrpay($api,$orderId);//默认叁数
        }else if($dsa['duijei']==2){
            $orderId = $this->yzfpay($api,$orderId);//易支付叁数
        }else{
            $this->success('1001',null,'附加信息：您使用的商户PID已被禁用或者不存在，请联系客服咨询原因');
        }
            if(!$api['json'] and iphtml('pay')!=false){
                if(!config("ms")){
                    return redirect(url('/pay/index/pay','','html',true).'?orderid='.$orderId);
                }else{
                    return redirect(url('pay','','html',true).'?orderid='.$orderId);
                }
            }else{
                $data = Db::table("pay_order")->where("order_id",$orderId)->cache(true)->find();
                if(!config("ms")){
                    $state = url('pay/index/checkOrder','','html',true).'?orderId='.$data['order_id'];
                }else{
                    $state = url('checkOrder','','html',true).'?orderId='.$data['order_id'];
                }
                if(!config("ms")){
                    $pay = url('pay/index/pay','','html',true).'?orderid='.$data['order_id'];
                }else{
                    $pay = url('pay','','html',true).'?orderid='.$data['order_id'];
                }
                $data = array(
                    "pid" => $data['pid'],//商户PID
                    "order_id" => $data['order_id'],//平台订单号
                    "type" => $data['type'],//支付方式
                    "money" => $data['money'],//金额
                    "price" => $data['price'],//实际金额
                    "mid" => $data['mid'],//收款账号MID
                    "url" => $data['mid_url'],//通道内容
                    "src" => (!config("ms")?url('pay/index/enQrcode','','html',true):url('enQrcode','','html',true)).'?url='.$data['mid_url'],//二维码
                    "pay" => $pay,//支付页面链接
                    "state" => $state,//回调链接
                );
                return json($this->getReturn(1,"获取成功",$data));
            }
        }
    }
    
    public function yzfpay($api,$orderId)//易支付叁数
    {
        $pid = $api['pid'];
        if (!$pid || $pid == "") {
            $this->success('1001',null,'pid（附加信息：商户PID（商户后台获取）参数错误');
        }
        $dsa = Db::table("pay_user")->where("id",$pid)->find();
        if(!$dsa || $dsa['active']==1 and $dsa['type']!=2){
            $this->success('1001',null,'pid（附加信息：您使用的商户PID已被禁用或者不存在，请联系客服咨询原因）');
        }
        $type = $api['type'];
        if (!$type || $type == "") {
            $this->success('1002',null,'type（附加信息：支付方式（商户后台获取）参数错误');
        }
        $out_trade_no = $api['out_trade_no'];
        if (!$out_trade_no || $out_trade_no == "") {
            $this->success('1003',null,'out_trade_no（附加信息：网站的订单号或用户名）参数错误');
        }
        $money = $api['money'];
        if (!$money || $money == "") {
            $this->success('1004',null,'money（金额）参数错误');
        }
        if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money)) {
            $this->success('1007',null,'money（附加信息：金额不合法）');
        }
        $return_url = $api['return_url'];
        if (!$return_url || $return_url == "") {
            $this->success('1005',null,'return_url（附加信息：当支付成功或支付超时后将自动跳转到指定网址）参数错误');
        }
        $notify_url = $api['notify_url'];
        if (!$notify_url || $notify_url == "") {
            $this->success('1006',null,'notify_url（附加信息：当支付成功回调数据逻辑来编写您的业务）参数错误');
        }
        $name = $api['name'];
        if (!$name || $name == "") {
            $this->success('1007',null,'name（附加信息：商品名称）参数错误');
        }
        $sign = $api['sign'];
        if (!$sign || $sign == "") {
            $this->success('1008',null,'sign（附加信息：数据签名（签名方法商户后台获取）参数错误');
        }
        $key = sign(input(),$dsa['key'],$dsa,'sign');//数据签名算法
        if($key!==$sign){
             $this->success('1009',null,'sign（附加信息：数据签名（签名方法商户后台获取）签名错误');
        }
        $fti = Db::table("pay_order")->where("pid",$pid)->where("out_trade_no",$out_trade_no)->where("state",1)->find();//判断商户订单号已存在
        if($fti){
          $this->success('1010',null,'out_trade_no（附加信息：商户订单号已存在）请重新唤起支付');
        }
        if($type=='alipay'){
            $type = 'alipay';
        }elseif($type=='wxpay'){
            $type = 'weixin';
        }elseif($type=='qqpay'){
            $type = 'qq';
        }
        $sxf = money($money,$dsa);//手续费算法
        if($sxf>$dsa['money']){
          $this->success('1010',null,'当前商户余额不足，无法完成支付，请商户登录用户中心充值余额');
        }
        $mid = $api['mid'];
        if (!$mid || $mid == "") {
            $data = $this->land($pid,$type);//随机账号
            $price = $this->mqf($pid,$data,$money,$type,$orderId);//获取金额判断
            $run = $this->tongtao($api,$data,$price,$type);//获取通道支付信息
        }else{
            $data = Db::table("pay_gfg")->where("pid",$pid)->where("mid",$mid)->where("land_lx", 1)->where("state", 1)->find();//指定账号
            if(!$data){
                $this->success('1015',null,'暂无账户可用,请稍后再试');
            }
            $data = $this->rzjr($data);//账号限额配置
            $data = $this->ds($data);//账号限量配置
            $jie = Db::table("pay_jie")->where("pid",$data['pid'])->where("id",$data['typec_id'])->where("state", 1)->find();//获取通道
            if(!$jie){
                $this->bug($data,'该通道已关闭');//账号异常配置
                $this->success('1013',null,'订单创建失败（错误信息：该通道已关闭)');
            }
            $price = $this->mqf($pid,$data,$money,$type,$orderId);//获取金额判断
            $cls = common($jie['game_dm']);//支付通道路径
            $run = $cls->exec($api,$data,$price,$type);//支付通道数据
            if($run['code']==-1){
                $this->bug($data,$run['data']);//账号异常配置
                $this->success('1014',null,'订单创建失败（错误信息：'.$run['data'].')');
            }
        }
        $jie = Db::table("pay_jie")->where("pid", $data['pid'])->where("id", $data['typec_id'])->where("state", 1)->find();//获取通道
        if($data['jkstate']==0){
            $this->success('1017',null,'监控状态异常，请检查是否挂监控回调');
        }
        $data = array(
            "pid" => $pid,//商户PID
            "order_id" => $orderId,//平台订单号
            "pay_id" => $run['data']['pay_id'],//通道订单号
            "out_trade_no" => $out_trade_no,//商户订单号
            "type" => $type,//支付方式
            "name" => $name,//商品名称
            "sitename" => $sitename = ($api['sitename']==''? '':$api['sitename']),//网站名称
            "param" => $param = ($api['param']==''? '':$api['param']),//扩展参数
            "refer" => $return_url,//同步回调
            "notify" => $notify_url,//异步回调
            "money" => $money,//金额
            "price" => $price,//实际金额
            "sxf" => $sxf,//手续费
            "mid" => $data['mid'],//收款账号MID
            "mid_id" => $data['typec_id'],//通道ID
            "mid_name" => $data['username'],//收款账号
            "mid_dm" => $jie['game_dm'],//通道标识
            "mid_type" => $data['typec_name'],//所属通道
            "mid_url" => $run['data']['mid_url'],//通道二维码
            "mid_json" => ($run['json']==''? '无数据':$run['json']),//通道数据
            "ip" => real_ip(),//交易IP
            "create_date" => time(),//创建时间戳（10位）
        );
        Db::name("pay_order")->insert($data);
        
        return $orderId;
        
    }
    
    public function mrpay($api,$orderId)//默认叁数
    {
        $pid = $api['pid'];
        if (!$pid || $pid == "") {
            $this->success('1001',null,'pid（附加信息：商户PID（商户后台获取）参数错误');
        }
        $dsa = Db::table("pay_user")->where("id",$pid)->find();
        if(!$dsa || $dsa['active']==1 and $dsa['type']!=2){
            $this->success('1001',null,'pid（附加信息：您使用的商户PID已被禁用或者不存在，请联系客服咨询原因）');
        }
        $type = $api['type'];
        if (!$type || $type == "") {
            $this->success('1002',null,'type（附加信息：支付方式（商户后台获取）参数错误');
        }
        $record = $api['record'];
        if (!$record || $record == "") {
            $this->success('1003',null,'record（附加信息：网站的订单号或用户名）参数错误');
        }
        $money = $api['money'];
        if (!$money || $money == "") {
            $this->success('1004',null,'money（金额）参数错误');
        }
        if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money)) {
            $this->success('1007',null,'money（附加信息：金额不合法）');
        }
        $refer = $api['refer'];
        if (!$refer || $refer == "") {
            $this->success('1005',null,'refer（附加信息：当支付成功或支付超时后将自动跳转到指定网址）参数错误');
        }
        $notify = $api['notify'];
        if (!$notify || $notify == "") {
            $this->success('1006',null,'notify（附加信息：当支付成功回调数据逻辑来编写您的业务）参数错误');
        }
        $sign = $api['sign'];
        if (!$sign || $sign == "") {
            $this->success('1008',null,'sign（附加信息：数据签名（签名方法商户后台获取）参数错误');
        }
        $key = sign(input(),$dsa['key'],$dsa,'sign');//数据签名算法
        if($sign!==$key){
             $this->success('1009',null,'sign（附加信息：数据签名（签名方法商户后台获取）签名错误');
        } 
        $sxf = money($money,$dsa);//手续费算法
        if($sxf>$dsa['money']){
          $this->success('1010',null,'当前商户余额不足，无法完成支付，请商户登录用户中心充值余额');
        }
        $mid = $api['mid'];
        if (!$mid || $mid == "") {
            $data = $this->land($pid,$type);//随机账号
            $price = $this->mqf($pid,$data,$money,$type,$orderId);//获取金额判断
            $run = $this->tongtao($api,$data,$price,$type);//获取通道支付信息
        }else{
            $data = Db::table("pay_gfg")->where("pid",$pid)->where("mid",$mid)->where("land_lx", 1)->where("state", 1)->find();//指定账号
            if(!$data){
                $this->success('1015',null,'暂无账户可用,请稍后再试');
            }
            $data = $this->rzjr($data);//账号限额配置
            $data = $this->ds($data);//账号限量配置
            $jie = Db::table("pay_jie")->where("pid",$data['pid'])->where("id",$data['typec_id'])->where("state", 1)->find();//获取通道
            if(!$jie){
                $this->bug($data,'该通道已关闭');//账号异常配置
                $this->success('1013',null,'订单创建失败（错误信息：该通道已关闭)');
            }
            $price = $this->mqf($pid,$data,$money,$type,$orderId);//获取金额判断
            $cls = common($jie['game_dm']);//支付通道路径
            $run = $cls->exec($api,$data,$price,$type);//支付通道数据
            if($run['code']==-1){
                $this->bug($data,$run['data']);//账号异常配置
                $this->success('1014',null,'订单创建失败（错误信息：'.$run['data'].')');
            }
        }
        $jie = Db::table("pay_jie")->where("pid", $data['pid'])->where("id", $data['typec_id'])->where("state", 1)->find();//获取通道
        if($data['jkstate']==0){
            $this->success('1017',null,'监控状态异常，请检查是否挂监控回调');
        }
        $data = array(
            "pid" => $pid,//商户PID
            "order_id" => $orderId,//平台订单号
            "pay_id" => $run['data']['pay_id'],//通道订单号
            "record" => $record,//附加参数
            "type" => $type,//支付方式
            "name" => $record,//商品名称
            "money" => $money,//金额
            "price" => $price,//实际金额
            "sxf" => $sxf,//手续费
            "refer" => $refer,//同步回调
            "notify" => $notify,//异步回调
            "mid" => $data['mid'],//收款账号MID
            "mid_id" => $data['typec_id'],//通道ID
            "mid_name" => $data['username'],//收款账号
            "mid_dm" => $jie['game_dm'],//通道标识
            "mid_type" => $data['typec_name'],//所属通道
            "mid_url" => $run['data']['mid_url'],//通道二维码
            "mid_json" => ($run['json']==''? '无数据':$run['json']),//通道数据
            "ip" => real_ip(),//交易IP
            "create_date" => time(),//创建时间戳（10位）
        );
        Db::name("pay_order")->insert($data);
        
        return $orderId;
        
    }
    
    public function land($pid,$type)//随机账号轮询
    {
        if($type=='alipay'){
            $type = '1';
        }elseif($type=='weixin'){
            $type = '0';
        }elseif($type=='qq'){
            $type = '2';
        }
        $account = Db::table('pay_gfg')->where("pid",$pid)->where("type",$type)->where("jkstate",1)->where("state",1)->where("land_lx",1)->where("ds_status",0)->orderRand()->limit(1)->find();
        if($account==false){
            $this->success('1009',null,'暂无账户可用,请稍后再试');
        }
        return $account;
    }
    public function rzjr($data)//账号限额配置
    {
        if($data['r_money']!=0){//日限额开关
            if($data['r_money'] <= $data['jr_pay']){
                $this->success('1011',null,'暂无账户可用,请稍后再试');
            }
        }
        if($data['z_money']!=0){//总限额开关
            if($data['z_money'] <= $data['z_pay']){
                $this->success('1012',null,'暂无账户可用,请稍后再试');
            }
        }
        return $data;
    }
    public function ds($data)//账号限量配置
    {
        if($data['ds_time']!=0){//设定时间
            if($data['ds_type']==0){//限量模式
               $count = Db::table('pay_order')
               ->where("pid", $data["pid"])
               ->where("mid", $data['mid'])
               ->where("state", 2)
               ->whereTime('pay_date', '<=', time())
               ->whereTime('pay_date', '>=', time()-(60*$data['ds_time']))
               ->count();//已支付订单数量
            }else{
               $count = Db::table('pay_order')
               ->where("pid", $data["pid"])
               ->where("mid", $data['mid'])
               ->where("state", 1)
               ->whereTime('create_date', '<=', time())
               ->whereTime('create_date', '>=', time()-(60*$data['ds_time']))
               ->count();//新创建订单数量
            }
            if($data['ds_num'] <= $count){//限量数量
                Db::name("pay_gfg")->where("pid", $data['pid'])->where("id", $data['id'])->update(array("ds_status"=>1));
                $this->success('1010',null,'暂无账户可用,请稍后再试');
            }
        }
        return $data;
    }
    public function bug($data,$msg)//账号异常配置
    {
        if($msg=='该通道已关闭'){
            $count = Db::name('pay_gfg')->where("pid", $data["pid"])->where("id", $data['id'])->sum("bug_num");//异常次数
            Db::name("pay_gfg")->where("pid", $data['pid'])->where("id", $data['id'])->update(array("state"=>0,"land_lx"=>0,"bug_num"=>$count,"bug_data"=>$msg,"time"=>time()));
        }
        $res = Db::table("pay_user")->where("id",$data['pid'])->find();
        if($res['bug_num']<=$data['bug_num']){
            if($res['bug_type']==1){
                $count = Db::name('pay_gfg')->where("pid", $data["pid"])->where("id", $data['id'])->sum("bug_num");//异常次数
                Db::name("pay_gfg")->where("pid", $data['pid'])->where("id", $data['id'])->update(array("state"=>0,"land_lx"=>0,"bug_num"=>$count,"bug_data"=>$msg,"time"=>time()));
                $ds = Db::table("pay_sz")->find();
                $res = Db::table('pay_user')->where('id',$data["pid"])->find();
                $sub= $ds['web_name'].'-'.$data['typec_name'].'-通道账号异常掉线通知';
                $msg = "尊敬的" .$res['user']. "商户<br>  您正在使用的".$data['typec_name']."|MID：" .$data['mid']."|账号备注：" .$data['username']. " 通道账号已异常！建议您尽快去".$ds['web_name']."平台[账号管理-异常账号]检测原因，以免影响您的监控收款正常使用。";
                fmail($res['email'], $sub, $msg);
            }else{
                Db::table('pay_gfg')->where("pid", $data['pid'])->where("id", $data['id'])->delete();
                $land_count = Db::name('pay_gfg')->where("pid", $data["pid"])->where("typec_id", $data['typec_id'])->count();//账号数量
                $jr = Db::table('pay_order') ->where("pid", $data["pid"])->where("mid_id",$data['typec_id'])->where("state",2)->whereTime('create_date', 'today')->sum("money");//今日收入
                $zr = Db::table('pay_order') ->where("pid", $data["pid"])->where("mid_id",$data['typec_id'])->where("state",2)->whereTime('create_date', 'yesterday')->sum("money");//昨日收入
                Db::name("pay_jie")->where("pid", $data['pid'])->where("id", $data['typec_id'])->update(array("land_count"=>$land_count,"jr_pay"=>$jr,"zr_pay"=>$zr));
            }
        }else{
            $count = Db::name('pay_gfg')->where("pid", $data["pid"])->where("id", $data['id'])->sum("bug_num")+1;//异常次数
            Db::name("pay_gfg")->where("pid", $data['pid'])->where("id", $data['id'])->update(array("bug_num"=>$count));
        }
        return $data;
    }
    public function tongtao($api,$data,$money,$type)//获取通道支付信息
    {
        $jie = Db::table("pay_jie")->where("pid",$data['pid'])->where("id",$data['typec_id'])->where("state", 1)->find();//获取通道
        if(!$jie){
            $this->bug($data,'该通道已关闭');//账号异常配置
            $this->success('1013',null,'订单创建失败（错误信息：该通道已关闭)');
        }
        $cls = common($jie['game_dm']);//支付通道路径
        $run = $cls->exec($api,$data,$money,$type);//支付通道数据
        if($run['code']==-1){
            $this->bug($data,$run['data']);//账号异常配置
            $data = $this->land($data['pid'],$type);//随机账号
            $run = $this->tongtao($api,$data,$money,$type);//获取通道支付信息
        }
        return $run;
    }
    public function mqf($pid,$data,$money,$type,$orderId)//获取金额判断
    {
        $jie = Db::table("pay_jie")->where("pid",$pid)->where("id",$data['typec_id'])->where("state", 1)->find();
        if($jie['game_dm']=='mq_gzfbjkd'||$jie['game_dm']=='mq_gvxjkd'||$jie['game_dm']=='mq_gqqjkd'||$jie['game_dm']=='mq_gvxyjk'||$jie['game_dm']=='mq_gqqyjk'||$jie['game_dm']=='mq_gzfbyjk'||$jie['game_dm']=='vx_xsd'||$jie['game_dm']=='usdt'){//监控端(微信)||监控端(支付宝)||监控端(Q Q)||云监控(微信)||云监控(Q Q)||云监控(支付宝)||微信小商店(CK)||usdt
            $reallyPrice = bcmul($money ,100);
            for ($i = 0; $i < 999; $i++) {
                $payQf = 1;
                $tmpPrice = $reallyPrice . "-" . $type. "-" . $jie['game_dm']. "-" . $data['pid'];
                $row = Db::execute("INSERT IGNORE INTO pay_tmp (pid,price,order_id) VALUES ('" . $pid . "','" . $tmpPrice . "','" . $orderId . "')");
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            $money = bcdiv($reallyPrice, 100,2);
            if (!$ok) {
                $this->success('1016',null,'订单创建失败（错误信息：订单创建超出负荷，请稍后重试)');
            }
        }else
        if($jie['game_dm']=='mq_gqqyd' and $data['bf']==1){//云端(Q Q)
            $reallyPrice = bcmul($money ,100);
            for ($i = 0; $i < 999; $i++) {
                $payQf = 1;
                $tmpPrice = $reallyPrice . "-" . $type. "-" . $jie['game_dm']. "-" . $data['pid'];
                $row = Db::execute("INSERT IGNORE INTO pay_tmp (pid,price,order_id) VALUES ('" . $pid . "','" . $tmpPrice . "','" . $orderId . "')");
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            $money = bcdiv($reallyPrice, 100,2);
            if (!$ok) {
                $this->success('1016',null,'订单创建失败（错误信息：订单创建超出负荷，请稍后重试)');
            }
        }else
        if($jie['game_dm']=='mq_gvxyd' and $data['ck']!='无数据'){//云端(微信)
            $reallyPrice = bcmul($money ,100);
            for ($i = 0; $i < 999; $i++) {
                $payQf = 1;
                $tmpPrice = $reallyPrice . "-" . $type. "-" . $jie['game_dm']. "-" . $data['pid'];
                $row = Db::execute("INSERT IGNORE INTO pay_tmp (pid,price,order_id) VALUES ('" . $pid . "','" . $tmpPrice . "','" . $orderId . "')");
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            $money = bcdiv($reallyPrice, 100,2);
            if (!$ok) {
                $this->success('1016',null,'订单创建失败（错误信息：订单创建超出负荷，请稍后重试)');
            }
        }else
        if($jie['game_dm']=='mq_gzfbyd' and $data['bf']!='无数据'){//云端(支付宝)
            $reallyPrice = bcmul($money ,100);
            for ($i = 0; $i < 999; $i++) {
                $payQf = 1;
                $tmpPrice = $reallyPrice . "-" . $type. "-" . $jie['game_dm']. "-" . $data['pid'];
                $row = Db::execute("INSERT IGNORE INTO pay_tmp (pid,price,order_id) VALUES ('" . $pid . "','" . $tmpPrice . "','" . $orderId . "')");
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            $money = bcdiv($reallyPrice, 100,2);
            if (!$ok) {
                $this->success('1016',null,'订单创建失败（错误信息：订单创建超出负荷，请稍后重试)');
            }
        }else
        if($jie['game_dm']=='mq_gzfbmck' and $data['bf']!='无数据'){//云端(支付宝-免CK)
            $reallyPrice = bcmul($money ,100);
            for ($i = 0; $i < 999; $i++) {
                $payQf = 1;
                $tmpPrice = $reallyPrice . "-" . $type. "-" . $jie['game_dm']. "-" . $data['pid'];
                $row = Db::execute("INSERT IGNORE INTO pay_tmp (pid,price,order_id) VALUES ('" . $pid . "','" . $tmpPrice . "','" . $orderId . "')");
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            $money = bcdiv($reallyPrice, 100,2);
            if (!$ok) {
                $this->success('1016',null,'订单创建失败（错误信息：订单创建超出负荷，请稍后重试)');
            }
        }
        
        return $money;
    }
    public function pay()//转跳支付页面
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $orderid = xss(input('orderid'));
        if (!$orderid || $orderid == ""){
            return response()->code(404)->allowCache(false);
            $this->success('1001',null,'order_id（附加信息：请输入订单号（平台订单号）参数错误');
        }
        $data = Db::table("pay_order")->where("order_id",$orderid)->whereOr('pay_id',$orderid)->find();
        if(!$data){
            return response()->code(404)->allowCache(false);
            $this->success('1002',null,'order_id（附加信息：订单号错误（不存在）');
        }
        $zt = xss(input('zt'));//浏览器转跳页面
        if($zt){
            if(zt_zhto($zt)){
                $data['url'] = request()->url(true);//跳转的地址
                $this->assign('data',$data);
                return  $this->fetch(config("__pay__").'/vxqqzfb.html');//支付页面||微信内
            }
        }else{
            $ta = Db::table("pay_sz")->find();
            if($ta['ds_zhto']==1){
                if(ds_zhto($data)){
                    $data['url'] = request()->url(true);//跳转的地址
                    $this->assign('data',$data);
                    return  $this->fetch(config("__pay__").'/vxqqzfb.html');//支付页面||微信内
                }
            }
        }
        $type = xss(input('type'));//自定义响应输出页面
        if($type){
            return $type($data['mid_url']);
        }
        $pay_type = xss(input('pay_type'));//自定义通道功能
        if($pay_type){
            $cls = common($data['mid_dm']);//支付通道路径
            $run = $cls->$pay_type($data,input());//监控回调
            return $run;
        }
        $app = xss(input('app'));//APP支付页面
        if($app){
            if($data['type']=='alipay'){
                $atad= Db::table("pay_gfg")->where("pid",$data['pid'])->where("mid",$data['mid'])->find();
                $this->assign('data',$data);
                $this->assign('atad',$atad);
                return $this->fetch(config("__pay__").'/Ali_PayH5.html');
            }else if($data['type']=='weixin'){
                $atad= Db::table("pay_gfg")->where("pid",$data['pid'])->where("mid",$data['mid'])->find();
                $this->assign('data',$data);
                $this->assign('atad',$atad);
                return $this->fetch(config("__pay__").'/Wx_PayH5.html');
            }
        }
        if(!ippublic('pay')){
            $this->success('1003',null,'未启动支付模板，请联系客服！'); 
        }
        $cls = common($data['mid_dm']);//支付通道路径
        $run = $cls->fukyem($data);//支付通道数据
        return $run;
    }
    public function checkOrder()//支付页面监控
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
       
        $orderId = xss(input('orderId'));
        if (!$orderId || $orderId == "") {
            return json($this->getReturn(-1, 'orderId（附加信息：请输入订单号（订单号）参数错误'));
        }
        $data = Db::table("pay_order")->where("order_id",$orderId)->find();
        $atad= Db::table("pay_user")->where("id",$data['pid'])->find();
        $eer = Db::table("pay_gfg")->where("pid",$data['pid'])->where("mid",$data['mid'])->find();
        closeEndOrder($data['pid']);//检测过期订单
        if(!$data){//订单被销毁
            return json($this->getReturn(-1, '该订单已不存在'));
        }
        $url = sign($atad,$data,$atad,'ybhd');
        $hq = $data['refer'].$url['return'];
        if($data['state']==2){//订单支付成功
            return json($this->getReturn(1, '订单已支付',$hq));
        }else if($data['state']==3){//订单已经超时
            return json($this->getReturn(-1, '订单已过期'));
        }else{
            return json($this->getReturn(0, '订单未支付'));
        }
    }
    public function enQrcode()//生成二维码
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $url = xss(input('url'));
        if (!$url || $url == ""){
            return response()->code(404)->allowCache(false);
        }
        $data = Db::table("pay_sz")->cache(120)->find();
        // 自定义二维码配置
        $config = [
            'title'         => false,
            'title_content' => $data['web_name'],
            'logo'          => false,
            'logo_url'      => (xss(input('logo'))==''? './static/user/assets/images/favicon.ico':xss(input('logo'))),
            'logo_size'     => 50,
            'size'          => 250,
        ];
        $qr_code = new QrcodeServer($config);
        $content = $qr_code->createServer($url);
        return response($content,250,['Content-Length'=>strlen($content)])->contentType('image/png');
    }
    public function sjztbcss()//手机版保存图片下载
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        $orderid = xss(input('orderid'));
        if (!$orderid || $orderid == ""){
            return response()->code(404)->allowCache(false);
        }
        $data = Db::table("pay_order")->where("order_id",$orderid)->where("state",1)->find();
        if(!$data){
               $this->success('1001',null,'order_id（附加信息：订单号错误（不存在）'); 
        }
        if($data['type']=='qq'||$data['type']=='alipay'||$data['type']=='weixin'){//QQ||支付宝||微信
            $file = curl(url('enqrcode','','html',true).'?url='.$data['mid_url']);
            $path = Env::get('ROOT_PATH').'/public/static/user/assets/images/sjztbcss.png';
            @unlink($path);//删除目录图片
            $resource = fopen($path, 'a');
            fwrite($resource, $file);
            fclose($resource);
            return download($path,'付款码.png');
        }
    }
    public function sytpay()//收银台
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
       
        $pid = xss(input("pid"));
        if (!$pid || $pid == "") {
            return response()->code(404)->allowCache(false);
            //return json($this->getReturn(-1, "请输入商户PID"));
        }
        $key = base64_decode(xss(input("key")));
        if (!$key || $key == "") {
            return response()->code(404)->allowCache(false);
            //return json($this->getReturn(-1, "请输入商户KEY"));
        }
        $data = Db::table("pay_user")->where("id",$pid)->where("key",$key)->find();
        if (!$data) {
            return response()->code(404)->allowCache(false);
            //return json($this->getReturn(-1, "商户PID或商户KEY错误"));
        }
        if($data['active']==1 and $data['type']!=2){
            return response()->code(404)->allowCache(false);
            //return json($this->getReturn(-1, "商户PID或商户KEY已被禁用，请联系客服咨询原因"));
        }
        if(!xss(input("sytpay"))){//模式切换
            if(trim($data['jh_je'])=="" or $data['jh_je']==null){
                $data['preselection_switch'] = false;
            }else{
                $data['jh_je'] = array_filter(explode("|",$data['jh_je']));
                if(count($data['jh_je'])<=0){
                    $data['preselection_switch'] = false;
                }else{
                    $data['preselection_switch'] = true;
                }
            }
            $type = judgment();
            if(isMobile()){
                $data['type'] = $type;
                if(!ds_zhto($data)){
                    $data['pay_type'] = $type;
                    $data['name'] = '收银台';//标题
                    $this->assign('data',$data);
                    return $this->fetch(config("__pay__").'/appsyt.html');
                }
            }else{
                $data['pay_type'] = $type;
                $data['name'] = '收银台';//标题
                $this->assign('data',$data);
                return $this->fetch(config("__pay__").'/pcsyt.html');
            }
        
        }else{
            
            if($data['duijei']==1){//对接模式
		    $api = array(
			    "pid" => $data['id'],//商户ID
			    "type" => xss(input("type")),//支付方式
			    "record"=> (xss(input("remarks"))==""?'收银台':xss(input("remarks"))),//附加参数
			    "money"	=> xss(input("money")),//付款金额
			    "refer" =>  url('refer','','html',true),//同步回调
			    "notify" => url('notify','','html',true),//异步通知
		    );
            }else{
                if($type=='alipay'){
                    $type = 'alipay';
                }elseif($type=='weixin'){
                    $type = 'wxpay';
                }elseif($type=='qq'){
                    $type = 'qqpay';
                }
            $api = array(
			    "pid" => $data['id'],//商户ID
			    "type" => xss(input("type")),//支付方式
			    "out_trade_no"=> time(),//附加参数
			    "money"	=> xss(input("money")),//付款金额
			    "name" => (xss(input("remarks"))==""?'收银台':xss(input("remarks"))),//商品名称
			    "return_url" => url('refer','','html',true),//同步回调
			    "notify_url" => url('notify','','html',true),//异步通知
		    );
            }
            //建立请求
            $sign = sign($api,$key,$data,'sign');
            $api['sign'] = $sign;
            $url = url('/','','',config("url_api")).'?'.http_build_query($api);//拉起支付
            if($url){
                return redirect($url);
            }else{
                $this->success('1000',null,'请求支付失败，请联系客服');
            }
        }
    }
    public function refer()//收银台-同步回调
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
       
        error_reporting(0);  
        $ata = xss(input());
        if(!$ata){
            return $this->fetch('../application/index/view/pay/fail.html');
        }
        if($ata['order']){
            $order = Db::table("pay_order")->where("order_id",$ata['order'])->find();
        }else if($ata['trade_no']){
            $order = Db::table("pay_order")->where("order_id",$ata['trade_no'])->find();
        }
        // 模板变量赋值
        $this->assign('data',$order);
        if($order['state']==2){
            return $this->fetch('../application/index/view/pay/success.html');
        }else{
            return $this->fetch('../application/index/view/pay/fail.html');
        }
    }
    //模板链接指令：收银台-异步回调
    public function notify()
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $ata = xss(input());
        
        if($ata['order']){
            $data = Db::table("pay_order")->where("order_id",$ata['order'])->find();
            $bqdf = Db::table("pay_user")->where("id",$data['pid'])->find();
            $sign = sign($ata,$bqdf['key'],$bqdf,'sign');
            if($sign===$ata['sign']){
                return 'success';
            }else{
                return 'fail';
            }
        }else if($ata['trade_no']){
            $data = Db::table("pay_order")->where("order_id",$ata['trade_no'])->find();
            $bqdf = Db::table("pay_user")->where("id",$data['pid'])->find();
            $sign = sign($ata,$bqdf['key'],$bqdf,'sign');
            if($sign===$ata['sign']){
                return 'success';
            }else{
                return 'fail';
            }
        }else{
            return 'fail';
        }
    }
    
    public function api_refer()//其他通道同步回调
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $api = xss(input());
        if($api['order']){//默认
            $data = Db::table("pay_order")->where("pay_id",$api['order'])->find();
        }else if($api['out_trade_no']){//易支付
            $data = Db::table("pay_order")->where("pay_id",$api['out_trade_no'])->find();
        }else{
            return 'fail';
        }
        $rows = Db::table("pay_jie")->where("pid",$data['pid'])->where("game_dm",$data['mid_dm'])->where("state", 1)->find();
        if(!$rows){
            return 'fail';
        }
        $hfhd = Db::table("pay_user")->where("id",$data['pid'])->find();
        $eer = Db::table("pay_gfg")->where("pid",$data['pid'])->where("mid",$data['mid'])->find();
        if($data){
            $cls = common($data['mid_dm']);//支付通道路径
            $run = $cls->cehsald($data,$api);//监控回调
            if($run['code']==1){
                $url = sign($eer,$data,$hfhd,'ybhd');
                $hq = $data['refer'].$url['return'];
                return redirect($hq);
            }else{
                return 'fail';
            }
        }else{
            return 'fail';
        }
    }
    
    public function api_notify()//其他通道异步回调
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        $api = xss(input());
        if($api['order']){//默认
            $data = Db::table("pay_order")->where("pay_id",$api['order'])->find();
        }else if($api['out_trade_no']){//易支付
            $data = Db::table("pay_order")->where("pay_id",$api['out_trade_no'])->find();
        }else{
            return 'fail';
        }
        $rows = Db::table("pay_jie")->where("pid",$data['pid'])->where("game_dm",$data['mid_dm'])->where("state", 1)->find();
        if(!$rows){
            return 'fail';
        }
        $hfhd = Db::table("pay_user")->where("id",$data['pid'])->find();
        $eer = Db::table("pay_gfg")->where("pid", $data['pid'])->where("mid",$data['mid'])->find();
        if($data){
            $cls = common($data['mid_dm']);//支付通道路径
            $run = $cls->cehsald($data,$api);//监控回调
            if($run['code']==1){
                url_notify($eer,$data,$hfhd);
                return 'success';
            }else{
                return 'fail';
            }
        }else{
            return 'fail';
        }
    }
    
}