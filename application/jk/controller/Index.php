<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\jk\controller;
use think\Db;
use think\Controller;
use think\facade\Env;
use think\facade\Request;
use think\facade\Cache;
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
    public function index()
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
        return response()->code(404)->allowCache(false);
    }
    
    public function cehsald($user='')//监控支付回调
    {
       if($dernout = $this->dernout()){
           return $dernout;//域名配置
       }
       
       $user = xss($user);
       if (!$user || $user == "") {
            $this->success('404',null,'user（附加信息：请输入管理员账号）参数错误');
       }
       $data = Db::table("pay_user")->where("user",$user)->where("type", 2)->cache(true)->find();
       if (!$data) {
            $this->success('404',null,'user（附加信息：输入管理员账号不正确）');
       }
       $this->Heart('cehsald');//监控回调心跳检测
       $rows = Db::table("pay_order")->where("state",1)->select();
       if(!$rows){
            return '未检测到正在付款的订单';
       }
       foreach ($rows as $row){
            $cache = tohc('Heart');
            if($cache->get('pcyjk')>=date('Y-m-d H:i:s',time())){
                if($row['mid_dm']!='mq_gzfbyjk' and $row['mid_dm']!='mq_gqqyjk' and $row['mid_dm']!='mq_gvxyjk'){//云监控(支付宝)||云监控(Q Q)||云监控(微信)
                }else{
                  continue;
                }
                if($row['mid_dm']!='mq_gzfbyd' and $row['mid_dm']!='mq_gqqyd' and $row['mid_dm']!='mq_gvxyd'){//云端(支付宝)||云端(Q Q)||云端(微信)
                }else{
                  continue;
                }
                if($row['mid_dm']!='mq_gzfbjkd' and $row['mid_dm']!='mq_gqqjkd' and $row['mid_dm']!='mq_gvxjkd'){//监控端(支付宝)||监控端(Q Q)||监控端(微信)
                }else{
                  continue;
                }
            }
            $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['mid_id'])->find();
            $cls = common($ree['game_dm']);//支付通道路径
            $run = $cls->cehsald($row);//支付通道数据
            if($run['code']==1){
                $eer = Db::table("pay_gfg")->where("pid",$row['pid'])->where("mid",$row['mid'])->find();
                $data = Db::table("pay_user")->where("id",$row['pid'])->find();
                url_notify($eer,$row,$data);
            }
            echo  '通道：'.$run['json']['mid_type'].' | 订单号：'.$run['json']['order_id'].' | 状态：'.$run['data']."\r\n";
       }
       return '检测订单监听中...';
    }
    
    public function notify()//异步回调
    {
      return "success";
    }
    
    public function Heart($type)//监控回调心跳检测
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
        
        //$type = xss(input('type'));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请输入您要监控心跳的类型"));
        }
        
        if($type=='appjkd'){//APP监控端
            $user = xss(input("username"));
            if (!$user || $user == "") {
                return json($this->getReturn(-1, "请输入登录账号"));
            }
            $pass = xss(input("password"));
            if (!$pass || $pass == "") {
                return json($this->getReturn(-1, "请输入登录密码"));
            }
            $atad = Db::table("pay_user")->where("user",$user)->find();
            if ($user != $atad["user"]) {
                return json($this->getReturn(-1, "账号或密码错误"));
            }else
            if (!password_verify($pass, $atad["pass"])) {
                return json($this->getReturn(-1, "账号或密码错误"));
            }
            if($atad['active']==1 and $atad['type']!=2){
                return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
            }
            $cache = tohc('Heart');
            $cache->set('appjkd',date('Y-m-d H:i:s',time()+60*2),60*2);
            $rows = Db::table("pay_gfg")->where("pid", $atad['id'])->cursor();
            foreach ($rows as $row){
                $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['typec_id'])->cache(true)->find();
                if($ree['game_dm']=='mq_gzfbjkd'||$ree['game_dm']=='mq_gvxjkd'||$ree['game_dm']=='mq_gqqjkd'||$ree['game_dm']=='gm_gjkd'){//监控端(微信)||监控端(支付宝)||监控端(QQ)||监控端(固码)
                    Db::name("pay_gfg")->where("pid", $row['pid'])->where("typec_id",$ree['id'])->update(array("lastheart"=>time(),"jkstate"=>1));
                }else{
                    continue;
                }
            }
            return json($this->getReturn(1, "心跳正常"));
        }else if($type=='cehsald'){//宝塔链接监控回调
            $atad = Db::table("pay_sz")->find(1);
            $cache = tohc('Heart');
            if($atad['ds_todaorul']){//云端网关
                $cache->set('ds_todaorul',$atad['ds_todaorul'],60*2);
                $cache->set('cehsald',date('Y-m-d H:i:s',time()+60*2),60*2);
                $rows = Db::table("pay_gfg")->cursor();
                foreach ($rows as $row){
                    $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['typec_id'])->find();
                    if($ree['game_dm']!='mq_gzfbjkd'and$ree['game_dm']!='mq_gvxjkd'and$ree['game_dm']!='mq_gqqjkd'and$ree['game_dm']!='gm_gjkd'and$ree['game_dm']!='mq_gzfbyjk'and$ree['game_dm']!='mq_gqqyjk'){//除了监控端(微信)||监控端(支付宝)||监控端(Q Q)||监控端(固码)||云监控(支付宝)||云监控(Q Q)
                        Db::name("pay_gfg")->where("pid", $row['pid'])->where("typec_id",$ree['id'])->update(array("lastheart"=>time(),"jkstate"=>1));
                    }else{
                        continue;
                    }
                }
            }
            if($atad['ds_yjkrul']){//云监控网关
                $cache->set('ds_yjkrul',$atad['ds_yjkrul'],60*2);
                $cache->set('pcyjk',date('Y-m-d H:i:s',time()+60*2),60*2);
                $rows = Db::table("pay_gfg")->cursor();
                foreach ($rows as $row){
                    $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['typec_id'])->find();
                    if($ree['game_dm']=='mq_gvxyjk' || $ree['game_dm']=='mq_gzfbyjk' || $ree['game_dm']=='mq_gqqyjk'){//云监控(微信)||云监控(支付宝)||云监控(Q Q)
                        Db::name("pay_gfg")->where("pid", $row['pid'])->where("typec_id",$ree['id'])->update(array("lastheart"=>time(),"jkstate"=>1));
                    }else{
                        continue;
                    }
                }
            }
            closeEndOrder();//检测过期订单
        }else{
            return json($this->getReturn(-1, "心跳失败"));
        }
        
    }
    
    public function appdds()//APP监控端推送付款数据
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        $game_dm = xss(input("game_dm"));
        if (!$game_dm || $game_dm == "") {
            return json($this->getReturn(-1, "请输入通道标识"));
        }
        $type = xss(input("type"));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请输入付款类型"));
        }
        $order_id = xss(input("order_id"));
        $money = xss(input("money"));
        if (!$money || $money == "" and !$order_id || $order_id == "") {
            return json($this->getReturn(-1, "请输入付款金额或者付款订单号"));
        }
        $res = Db::table("pay_user")->where("user",$user)->find();
        if ($user != $res["user"]) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }else
        if (!password_verify($pass, $res["pass"])) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }
        if($res['active']==1 and $res['type']!=2){
            return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
        }
        $rows = Db::table("pay_jie")->where("pid",$res['id'])->where("game_dm",$game_dm)->where("state", 1)->find();
        if (!$rows){
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        if($order_id){
            $row = Db::table("pay_order")->where("pid",$res['id'])->where("mid_id",$rows['id'])->where("type",$type)->where("order_id",$order_id)->whereBetween("state",[1,3])->find();
        }else if($money){
            $row = Db::table("pay_order")->where("pid",$res['id'])->where("mid_id",$rows['id'])->where("type",$type)->where("price",$money)->whereBetween("state",[1,3])->find();
        }
        if($row){
            $eer = Db::table("pay_gfg")->where("pid", $row['pid'])->where("mid",$row['mid'])->find();
            $data = Db::table("pay_user")->where("id", $res['id'])->find();
            if(url_notify($eer,$row,$data)){
                return json($this->getReturn(1, $row['order_id'],"回调成功"));
            }else{
                return json($this->getReturn(1, $row['order_id'],"回调失败"));
            }
        }else{
           return json($this->getReturn(-1, "未检测到正在付款的订单"));
        }
    }
    
    public function shanghu()//商户登录
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        $res = Db::table('pay_user')->where('user',$user)->find();
        if ($user != $res["user"]) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }else
        if (!password_verify($pass, $res["pass"])) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }
        if($res['active']==1 and $res['type']!=2){
            return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
        }else{
            return json($this->getReturn(1, "登录成功"));
        }
    }
    
    public function appHeart()//V免签监控端心跳检测
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $sign = xss(input("sign"));
        if (!$sign || $sign == "") {
            return json($this->getReturn(-1, "请输入通信KEY"));
        }
        $cache = tohc('Heart');
        $cache->set('appjkd',date('Y-m-d H:i:s',time()+60*5),60*5);
        $rows = Db::table("pay_user")->where("active", 0)->cursor();
        foreach ($rows as $row){
            if ($sign == md5(xss(input("t")).$row["key"])) {
                $rows = Db::table("pay_gfg")->where("pid",$row['id'])->cursor();
                foreach ($rows as $row){
                    $ree = Db::table("pay_jie")->where("pid",$row['pid'])->where("id",$row['typec_id'])->cache(true)->find();
                    if($ree['game_dm']=='mq_gzfbjkd'||$ree['game_dm']=='mq_gvxjkd'||$ree['game_dm']=='mq_gqqjkd'){//监控端
                        Db::name("pay_gfg")->where("pid",$row['pid'])->where("typec_id",$ree['id'])->update(array("lastheart"=>time(),"jkstate"=>1));
                    }else{
                        continue;
                    }
                }
            }
        }
        return json($this->getReturn(1, "心跳正常"));
    }
    
    public function appPush()//V免签监控端推送付款数据接口
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $sign = xss(input("sign"));
        if (!$sign || $sign == "") {
            return json($this->getReturn(-1, "请输入商户KEY"));
        }
        $type = xss(input("type"));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请输入付款类型"));
        }
        $money = xss(input("price"));
        if (!$money || $money == "") {
            return json($this->getReturn(-1, "请输入付款金额"));
        }
        if($type=='1'){
            $type = 'weixin';
            $ros = Db::table("pay_qrcode")->where("game_dm",'mq_gvxjkd')->where("state", 1)->find();
            if (!$ros){
                return json($this->getReturn(-1, "该通道已关闭"));
            }
        }else if($type=='2'){
            $type = 'alipay';
            $ros = Db::table("pay_qrcode")->where("game_dm",'mq_gzfbjkd')->where("state", 1)->find();
            if (!$ros){
                return json($this->getReturn(-1, "该通道已关闭"));
            }
        }else if($type=='3'){
            $type = 'qq';
            $ros = Db::table("pay_qrcode")->where("game_dm",'mq_gqqjkd')->where("state", 1)->find();
            if (!$ros){
                return json($this->getReturn(-1, "该通道已关闭"));
            }
        }else{
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        $rows = Db::table("pay_user")->where("active", 0)->cursor();
        foreach ($rows as $row){
            if ($sign == md5(xss(input("type")).xss(input("price")).xss(input("t")).$row["key"])) {
                $row = Db::table("pay_order")->where("mid_dm",$ros['game_dm'])->where("type",$type)->where("price",$money)->where("state", 1)->find();
                if($row){
                    $data = Db::table("pay_user")->where("id",$row['pid'])->find();
                    $eer = Db::table("pay_gfg")->where("mid",$row['mid'])->find();
                    if(url_notify($eer,$row,$data)){
                        return json($this->getReturn(1, $row['order_id'],"回调成功"));
                    }else{
                        return json($this->getReturn(1, $row['order_id'],"回调失败"));
                    }
                }else{
                    return json($this->getReturn(-1, "未检测到正在付款的订单"));
                }
            }
        }
    }
    
    public function pcvxyjk()//PC端云监控微信推送付款数据
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $ym = xss(input("ym"));
        if (!$ym || $ym == "") {
            return json($this->getReturn(-1, "请输入主域名"));
        }
        $sq = xss(input("sq"));
        if (!$sq || $sq == "") {
            return json($this->getReturn(-1, "请输入设备码"));
        }
        $game_dm = xss(input("game_dm"));
        if (!$game_dm || $game_dm == "") {
            return json($this->getReturn(-1, "请输入通道标识"));
        }
        $type = xss(input("type"));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请输入付款类型"));
        }
        $mingcheng = xss(input("mingcheng"));
        if (!$mingcheng || $mingcheng == "") {
            return json($this->getReturn(-1, "请输入店员名称"));
        }
        $money = xss(input("money"));
        if (!$money || $money == "") {
            return json($this->getReturn(-1, "请输入付款金额"));
        }
        if ($ym != getUrlHost(config('url'))) {
            return json($this->getReturn(-1, "信息验证错误"));
        }else
        $cache = tohc('ydqu');
        if ($sq != $cache->get('sq')) {
            return json($this->getReturn(-1, "信息验证错误"));
        }
        $rows = Db::table("pay_qrcode")->where("game_dm",$game_dm)->where("state", 1)->find();
        if (!$rows){
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        $eer = Db::table("pay_gfg")->where("zhanghao",DeleteHtml($mingcheng))->where("jkstate",1)->find();
        $row = Db::table("pay_order")->where("mid_dm",$rows['game_dm'])->where("type",$type)->where("mid",$eer['mid'])->where("price",$money)->where("state", 1)->find();
        $data = Db::table("pay_user")->where("id",$row['pid'])->find();
        if($row){
            if(url_notify($eer,$row,$data)){
                return json($this->getReturn(1, $row['order_id'],"回调成功"));
            }else{
                return json($this->getReturn(1, $row['order_id'],"回调失败"));
            }
        }else{
           return json($this->getReturn(-1, "未检测到正在付款的订单"));
        }
    }
    
    public function ydvxyd()//云端微信推送付款数据
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $ym = xss(input("ym"));
        if (!$ym || $ym == "") {
            return json($this->getReturn(-1, "请输入主域名"));
        }
        $sq = xss(input("sq"));
        if (!$sq || $sq == "") {
            return json($this->getReturn(-1, "请输入设备码"));
        }
        $game_dm = xss(input("game_dm"));
        if (!$game_dm || $game_dm == "") {
            return json($this->getReturn(-1, "请输入通道标识"));
        }
        $type = xss(input("type"));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请输入付款类型"));
        }
        $wxsb = xss(input("wxsb"));
        if (!$wxsb || $wxsb == "") {
            return json($this->getReturn(-1, "请输入微信设备"));
        }
        if ($ym != getUrlHost(config('url'))) {
            return json($this->getReturn(-1, "信息验证错误"));
        }else
        $cache = tohc('ydqu');
        if ($sq != $cache->get('sq')) {
            return json($this->getReturn(-1, "信息验证错误"));
        }
        $rows = Db::table("pay_qrcode")->where("game_dm",$game_dm)->where("state", 1)->find();
        if (!$rows){
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        $api = file_get_contents("php://input");
        $json = json_decode($api,true);
        if($json['code'] == '1'){
            if($json['data']['data']['ms'] == '微信收款助手-个人收款服务-收款到账通知'){
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信收款助手-个人收款服务-收款单到账通知'){
                $pay_id = $json['data']['data']['bz'];
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信收款助手-经营收款服务-经营收款到账通知'){
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信收款助手-经营收款服务-收款单到账通知'){
                $pay_id = $json['data']['data']['bz'];
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信收款商业版-收款通知'){
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信收款商业版-微信收款单-收款通知'){
                $pay_id = $json['data']['data']['bz'];
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信支付-赞赏到账通知'){
                $pay_id = $json['data']['data']['bz'];
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信支付-收款到账通知'){
                $money = $json['data']['data']['je'];
            }else 
            if($json['data']['data']['ms'] == '微信支付-经营收款到账通知'){
                $money = $json['data']['data']['je'];
            }
        }
        $eer = Db::table("pay_gfg")->where("zhanghao",$wxsb)->where("jkstate",1)->find();
        if($eer['ck']=='无数据'){
            $row = Db::table("pay_order")->where("mid_dm",$rows['game_dm'])->where("type",$type)->where("mid",$eer['mid'])->where("pay_id",$pay_id)->where("state", 1)->find();
        }else{
            $row = Db::table("pay_order")->where("mid_dm",$rows['game_dm'])->where("type",$type)->where("mid",$eer['mid'])->where("price",$money)->where("state", 1)->find();
        }
        $data = Db::table("pay_user")->where("id",$row['pid'])->find();
        if($row){
            if(url_notify($eer,$row,$data)){
                return json($this->getReturn(1, $row['order_id'],"回调成功"));
            }else{
                return json($this->getReturn(1, $row['order_id'],"回调失败"));
            }
        }else{
           return json($this->getReturn(-1, "未检测到正在付款的订单"));
        }
    }
    
    public function enQrcode($url)//生成二维码
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $data = Db::table("pay_sz")->cache(120)->find(1);
        // 自定义二维码配置
        $config = [
            'title'         => true,
            'title_content' => $data['web_name'],
            'logo'          => false,
            'logo_url'      => (xss(input('logo'))==''? './static/user/assets/images/favicon.ico':xss(input('logo'))),
            'logo_size'     => 50,
        ];
        $qr_code = new QrcodeServer($config);
        $content = $qr_code->createServer($url);
        return response($content,250,['Content-Length'=>strlen($content)])->contentType('image/png');
    }
    
    public function shoquan()//更换云端
    {
        $sq = xss(input("sq"));
        if (!$sq || $sq == "") {
            return json($this->getReturn(-1, '请输入设备码'));
        }
        $cache = tohc('ydqu');
        if ($sq != $cache->get('sq')) {
            return json($this->getReturn(-1, '设备码错误'));
        }else{
            $ydwg = xss(input("ydwg"));
            if (!$ydwg || $ydwg == "") {
                return json($this->getReturn(-1, '请输入您要更换的云端网关'));
            }
            $data = array(
                "ds_todaorul" => $ydwg,//云端网关
            );
            $sz = Db::table("pay_sz")->find(1);
            Db::name("pay_sz")->where("id",$sz['id'])->update($data);
            return json($this->getReturn(1, '云端网关['.$ydwg.']更换成功'));
        }
    }
    
    public function sbwz()//面对面QQ红包图片文字识别
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
        
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        $game_dm = xss(input("game_dm"));
        if (!$game_dm || $game_dm == "") {
            return json($this->getReturn(-1, "请输入通道标识"));
        }
        if($game_dm != 'mq_gqqmdm'){//自定义编码
            return json($this->getReturn(-1, "自定义通道标识不错误"));
        }
        $res = Db::table("pay_user")->where("user",$user)->find();
        if ($user != $res["user"]) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }else
        if (!password_verify($pass, $res["pass"])) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }
        if($res['active']==1 and $res['type']!=2){
            return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
        }
        $rows = Db::table("pay_jie")->where("pid",$res['id'])->where("game_dm",$game_dm)->where("state", 1)->find();
        if (!$rows){
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        $base64 = input("base64");
        if (!$base64 || $base64 == "") {
            return json($this->getReturn(-1, '请输入图片base64'));
        }
        $data = array(
            'grant_type' => 'client_credentials',
            'client_id' => "xoEANHXHvuG4pCVAXISk3KO5",
            'client_secret' => "7AFFMNZKXGFsaMOSQ6eraEEZZUtFDVIi",
        );
        $url = "https://aip.baidubce.com/oauth/2.0/token";
        $ret = curl_post($url,$data);
        $json = json_decode($ret,TRUE);
        $header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        );
        $data = 'image='.urlencode($base64);
        $url = "https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=".$json['access_token'];
        $ret = curl_post($url,$data,$header);
        //$json = json_decode($ret,TRUE);
        return json($this->getReturn(1, '获取成功',$ret));
    }
    
    public function qmdhb()//面对面QQ红包获取红包
    {
        if($dernout = $this->dernout()){
           return $dernout;//域名配置
        }
       
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        $game_dm = xss(input("game_dm"));
        if (!$game_dm || $game_dm == "") {
            return json($this->getReturn(-1, "请输入通道标识"));
        }
        if($game_dm != 'mq_gqqmdm'){//自定义编码
            return json($this->getReturn(-1, "自定义通道标识不错误"));
        }
        $res = Db::table("pay_user")->where("user",$user)->find();
        if ($user != $res["user"]) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }else
        if (!password_verify($pass, $res["pass"])) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }
        if($res['active']==1 and $res['type']!=2){
            return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
        }
        $rows = Db::table("pay_jie")->where("pid",$res['id'])->where("game_dm",$game_dm)->where("state", 1)->find();
        if (!$rows){
            return json($this->getReturn(-1, "该通道已关闭"));
        }
        $row = Db::table("pay_order")->where("pid",$res['id'])->where("mid_id",$rows['id'])->where("mid_dm",$game_dm)->where("mid_url",'<>','')->where("state", 1)->cursor();
        if($row){
            foreach ($row as $row){
                 $list[] = [
                    'order_id' => $row['order_id'],
                    'money' => $row['price'],
                    'url' => $row['mid_url'],
                ];
            }
            return json($this->getReturn(1, '获取成功',$list));
        }else{
            return json($this->getReturn(-1, '获取失败'));
        }
    }
    
    public function ceshi()//测试
    {
        $post = array(
                'game_dm' => 'mq_gzfbyjk',//通道标识
                'type' => 'cehsald',//传出类型:支付类型:exec||监控类型:cehsald
                'post' => array(//传出数据
                            'ck' => '',//账号UID
                            'uid' => '',//支付宝设备
                        )
            );
        //$ret = ['order_sn'=>$order_sn,'user_id'=>$user_id,'goods_id'=>$goods_id,'sku_id'=>$sku_id,'price'=>$price,'addtime'=>time()];   
        //Cache::store('redis')->get('name');
        //Cache::store('redis')->set('name',$ret);// 将数据插入新缓存文件中
        //$cache = tohc('Heart');
        //$ret = curl_post($cache->get('ds_yjkrul'),http_build_query(ydqu($post)));
        //return $ret;
        //return ydzfbqq('mq_gzfbyd');
        //return $ret;
        
    }
    
}