<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\index\controller;
use think\Db;
use think\App;
use think\Database;
use think\Controller;
use think\facade\Env;
use think\facade\Cookie;
use app\wenjian\Template;

class Admin extends Controller
{
    
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
    
    //商户登录状态
    public function dernout()
    {
        if(config('url')!=$_SERVER['HTTP_HOST'] || config('url_api')!=$_SERVER['HTTP_HOST'] || config('url_jk')!=$_SERVER['HTTP_HOST']){
            if(config('url')==config('url_api') || config('url')==config('url_jk') || config('url_api')==config('url') || config('url_api')==config('url_jk') || config('url_jk')==config('url') || config('url_jk')==config('url_api')){
                return response()->code(404)->allowCache(false);
            }
        }
        $data = Db::table("pay_sz")->lock(true)->find(1);
        if (!Cookie::has(md5(date("m-Y")),md5(date("d-m-Y"))) and $data['user']!=Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))) and $data['ip']!=real_ip()){
            Cookie::delete(md5(date("Y-m")),md5(date("Y-m-d")));
            Cookie::clear(md5(date("Y-m-d")));
            Cookie::delete(md5(date("m-Y")),md5(date("d-m-Y")));
            Cookie::clear(md5(date("d-m-Y")));
            $this->redirect("user/login");
        }
    }
    
    //模板链接指令：后台中心
    public function workplace()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cache = tohc('ydqu');
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '商户首页';//标题
        
        $data['todayMoney_jintian'] = Db::table('pay_order') ->where("state",2)->whereTime('create_date', 'today')->lock(true)->sum("price");//今日金额
        $data['todayMoney_zuotian'] = Db::table('pay_order') ->where("state",2)->whereTime('create_date', 'yesterday')->lock(true)->sum("price");//昨日金额
        
        $data['todayOrder_jintian'] = Db::table('pay_order') ->whereTime('create_date', 'today')->lock(true)->count();//今日订单量
        $data['todayOrder_zuotian'] =  Db::table('pay_order') ->whereTime('create_date', 'yesterday')->lock(true)->count();//昨日订单量
        
        $data['todayshoxuf_jintian'] = Db::table('pay_order') ->where("state",2)->whereTime('create_date', 'today')->lock(true)->sum("sxf");//今日手续费
        $data['todayshoxuf_zuotian'] = Db::table('pay_order') ->where("state",2)->whereTime('create_date', 'yesterday')->lock(true)->sum("sxf");//昨日手续费
        
        $data['user_jintian'] = Db::table('pay_user') ->where("id>=1") ->count();//商户总数
        $data['user_zuotian'] = Db::table('pay_user') ->whereTime('zc', 'today')->lock(true)->count();//今日新增
        
        $jeyue_1 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'01'),time()))->where("create_date <=".(strtotime(date("Y-".'01'),time())+2626560))->lock(true)->sum("price");//1月成交订单金额
        $jeyue_2 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'02'),time()))->where("create_date <=".(strtotime(date("Y-".'02'),time())+2626560))->lock(true)->sum("price");//2月成交订单金额
        $jeyue_3 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'03'),time()))->where("create_date <=".(strtotime(date("Y-".'03'),time())+2626560))->lock(true)->sum("price");//3月成交订单金额
        $jeyue_4 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'04'),time()))->where("create_date <=".(strtotime(date("Y-".'04'),time())+2626560))->lock(true)->sum("price");//4月成交订单金额
        $jeyue_5 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'05'),time()))->where("create_date <=".(strtotime(date("Y-".'05'),time())+2626560))->lock(true)->sum("price");//5月成交订单金额
        $jeyue_6 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'06'),time()))->where("create_date <=".(strtotime(date("Y-".'06'),time())+2626560))->lock(true)->sum("price");//6月成交订单金额
        $jeyue_7 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'07'),time()))->where("create_date <=".(strtotime(date("Y-".'07'),time())+2626560))->lock(true)->sum("price");//7月成交订单金额
        $jeyue_8 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'08'),time()))->where("create_date <=".(strtotime(date("Y-".'08'),time())+2626560))->lock(true)->sum("price");//8月成交订单金额
        $jeyue_9 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'09'),time()))->where("create_date <=".(strtotime(date("Y-".'09'),time())+2626560))->lock(true)->sum("price");//9月成交订单金额
        $jeyue_10 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'10'),time()))->where("create_date <=".(strtotime(date("Y-".'10'),time())+2626560))->lock(true)->sum("price");//10月成交订单金额
        $jeyue_11 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'11'),time()))->where("create_date <=".(strtotime(date("Y-".'11'),time())+2626560))->lock(true)->sum("price");//11月成交订单金额
        $jeyue_12 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'12'),time()))->where("create_date <=".(strtotime(date("Y-".'12'),time())+2626560))->lock(true)->sum("price");//12月成交订单金额
        
        $ddyue_1 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'01'),time()))->where("create_date <=".(strtotime(date("Y-".'01'),time())+2626560))->lock(true)->count();//1月成交订单数
        $ddyue_2 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'02'),time()))->where("create_date <=".(strtotime(date("Y-".'02'),time())+2626560))->lock(true)->count();//2月成交订单数
        $ddyue_3 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'03'),time()))->where("create_date <=".(strtotime(date("Y-".'03'),time())+2626560))->lock(true)->count();//3月成交订单数
        $ddyue_4 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'04'),time()))->where("create_date <=".(strtotime(date("Y-".'04'),time())+2626560))->lock(true)->count();//4月成交订单数
        $ddyue_5 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'05'),time()))->where("create_date <=".(strtotime(date("Y-".'05'),time())+2626560))->lock(true)->count();//5月成交订单数
        $ddyue_6 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'06'),time()))->where("create_date <=".(strtotime(date("Y-".'06'),time())+2626560))->lock(true)->count();//6月成交订单数
        $ddyue_7 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'07'),time()))->where("create_date <=".(strtotime(date("Y-".'07'),time())+2626560))->lock(true)->count();//7月成交订单数
        $ddyue_8 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'08'),time()))->where("create_date <=".(strtotime(date("Y-".'08'),time())+2626560))->lock(true)->count();//8月成交订单数
        $ddyue_9 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'09'),time()))->where("create_date <=".(strtotime(date("Y-".'09'),time())+2626560))->lock(true)->count();//9月成交订单数
        $ddyue_10 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'10'),time()))->where("create_date <=".(strtotime(date("Y-".'10'),time())+2626560))->lock(true)->count();//10月成交订单数
        $ddyue_11 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'11'),time()))->where("create_date <=".(strtotime(date("Y-".'11'),time())+2626560))->lock(true)->count();//11月成交订单数
        $ddyue_12 = Db::name('pay_order')->where("state",2)->where("create_date >=".strtotime(date("Y-".'12'),time()))->where("create_date <=".(strtotime(date("Y-".'12'),time())+2626560))->lock(true)->count();//12月成交订单数
        
        $data['fhsjaj1'] = 'eval(['.$jeyue_1.','.$jeyue_2.','.$jeyue_3.','.$jeyue_4.','.$jeyue_5.','.$jeyue_6.','.$jeyue_7.','.$jeyue_8.','.$jeyue_9.','.$jeyue_10.','.$jeyue_11.','.$jeyue_12.'])';//成交订单金额（元）
        
        $data['fhsjaj2'] = 'eval(['.$ddyue_1.','.$ddyue_2.','.$ddyue_3.','.$ddyue_4.','.$ddyue_5.','.$ddyue_6.','.$ddyue_7.','.$ddyue_8.','.$ddyue_9.','.$ddyue_10.','.$ddyue_11.','.$ddyue_12.'])';//成交订单数（笔）
        
        $data['ver'] = config("ver");
        
        $data['sq']  = $cache->get('sq');
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    
    //模板链接指令：基本管理-基本配置
    public function config()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if(input()){
            
            $web_name = input("web_name");
            if (!$web_name || $web_name == "") {
                return json($this->getReturn(-1, "请输入站点名称"));
            }
            $web_keywords = input("web_keywords");
            if (!$web_keywords || $web_keywords == "") {
                return json($this->getReturn(-1, "请输入站点关键词"));
            }
            $web_description = input("web_description");
            if (!$web_description || $web_description == "") {
                return json($this->getReturn(-1, "请输入站点描述词"));
            }
            $web_keywords = input("web_keywords");
            if (!$web_keywords || $web_keywords == "") {
                return json($this->getReturn(-1, "请输入站点关键词"));
            }
            $data = array(
                "web_name" => input("web_name"),//站点名称
                "web_keywords" => input("web_keywords"),//站点关键词
                "web_description" => input("web_description"),//站点描述
                "web_beian" => input("web_beian"),//备案号信息
                "web_kefu" => input("web_kefu"),//客服URL
                "web_qun" => input("web_qun"),//加群URL
                "ds_money" => input("ds_money"),//默认余额
                "ds_fee" => input("ds_fee"),//默认费率
                "web_djms" => input("web_djms"),//默认模式
            );
            
            Db::name("pay_sz")->where("user",Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->update($data);
            
            return json($this->getReturn(1, "修改成功"));
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '基本管理-基本配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        return $this->fetch();
        
    }
    //模板链接指令：基本管理-其他配置
    public function config_set()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if(input()){
            
            $ds_outr = input("ds_outr");
            if (!$ds_outr || $ds_outr == "") {
                return json($this->getReturn(-1, "请输入支付网关关闭提示"));
            }
            $data = array(
                "ds_zaioc" => input("ds_zaioc"),//在线充值
                "ds_zhto" => input("ds_zhto"),//支付跳转
                "web_mail" => input("web_mail"),//邮箱通知
                "mail_smtp" => input("mail_smtp"),//SMTP地址
                "mail_port" => input("mail_port"),//邮箱端口
                "mail_name" => input("mail_name"),//邮箱账号
                "mail_pwd" => input("mail_pwd"),//邮箱密码
                "mail_email" => input("mail_email"),//邮件模板
                "ip_hfw" => input("ip_hfw"),//是否开启代理IP请求
                "ip_User" => input("ip_User"),//代理IP订单号
                "ip_Pass" => input("ip_Pass"),//代理IP密码
                "ds_hfw" => input("ds_hfw"),//网站状态
                "ds_outr" => DeleteHtml(input("ds_outr")),//网站关闭提示
                "ds_user" => input("ds_user"),//是否关闭商户注册
                "ds_txet" => DeleteHtml(input("ds_txet")),//商户注册关闭提示
            );
            
            Db::name("pay_sz")->where("user",Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->update($data);
            
            return json($this->getReturn(1, "修改成功"));
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '基本管理-其他配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //模板链接指令：基本管理-公告配置
    public function news()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '基本管理-公告配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //模板链接指令：基本管理-公告配置-表格数据
    public function news_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        $obj = Db::table('pay_gogao')->page(input("page"),input("limit"));
        if (input("title")){
            $obj = $obj->where("title",input("title"));
        }
        $array = $obj->order("state","desc")->lock(true)->select();
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    //模板链接指令：基本管理-公告配置-公告编辑|添加
    public function news_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {//添加
        
            $title = input("title");
            if (!$title || $title == "") {
                return json($this->getReturn(-1, "请输入公告标题"));
            }
            $text = input("text");
            if (!$text || $text == "") {
                return json($this->getReturn(-1, "请输入公告内容"));
            }
            
            $data = array(
            
                "title" => $title,//公告标题
                "state" => input("state"),//是否置顶
                "text" => $text,//公告内容
                "create_date" => time(),//发布时间
            
                );
        
            Db::name("pay_gogao")->insert($data);
            
            return json($this->getReturn(1, '添加公告成功'));
            
        }//编辑
        
            $title = input("title");
            if (!$title || $title == "") {
                return json($this->getReturn(-1, "请输入公告标题"));
            }
            $text = input("text");
            if (!$text || $text == "") {
                return json($this->getReturn(-1, "请输入公告内容"));
            }
            
            $data = array(
            
                "title" => $title,//公告标题
                "state" => input("state"),//是否置顶
                "text" => $text,//公告内容
                "create_date" => time(),//发布时间
            
                );
            Db::name("pay_gogao")->where("id", input("id"))->update($data);
            
            return json($this->getReturn(1, "修改公告成功"));
            
    }
    //模板链接指令：基本管理-公告配置-公告删除
    public function news_del()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：公告编号 参数错误');
        }
        if(input("id")== '1' || input("id")== '2'){
            return json($this->getReturn(-1, "删除失败"));
        }
        $tada = Db::name("pay_gogao")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：公告编号不存在');
        }else{
            Db::table('pay_gogao')->where("id", input("id"))->delete();
            return json($this->getReturn(1, "删除成功"));
        }
        
        
    }
    
    //模板链接指令：基本管理-通知配置
    public function msg()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if(input()){
            $data = array(
                "tz_denglu" => DeleteHtml(input("tz_denglu")),//登陆通知
                "tz_todao" => DeleteHtml(input("tz_todao")),//通道提示
                "tz_zhanghao" => DeleteHtml(input("tz_zhanghao")),//账号提示
                "tz_yue" => DeleteHtml(input("tz_yue")),//余额提示
            );
            
            Db::name("pay_sz")->where("user",Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->update($data);
            
            return json($this->getReturn(1, "修改成功"));
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '基本管理-通知配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    
    //模板链接指令：云端管理-云监控
    public function yd()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if(input()){
            
            if (xss(input("yd_sj")) < 10) {
                return json($this->getReturn(-1, '请设置回调时间不能少于10（分钟）'));
            }
            $data = array(
                "ds_yjkrul" => input("ds_yjkrul"),//云监控网关
                "ds_todaorul" => input("ds_todaorul"),//云端网关
                "yd_sj" => input("yd_sj"),//云监控回调时间
                "yd_jk" => input("yd_jk"),//扫码添加监控微信我的二维码解析链接
            );
            
            Db::name("pay_sz")->where("user",Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->update($data);
            
            return json($this->getReturn(1, "修改成功"));
            
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '云端管理-云监控';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    
    //模板链接指令：会员管理
    public function tablecard()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '会员管理';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    //模板链接指令：会员管理-表格数据
    public function tablecard_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_taocan')->page(input("page"),input("limit"));
        
        if (input("taocan_name")){
            $obj = $obj->where("taocan_name",input("taocan_name"));
        }
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    //模板链接指令：会员管理-会员编辑
    public function tablecard_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $id = input("id");
        if (!$id || $id == "") {
            return json($this->getReturn(-1, "请输入排序ID"));
        }
        $taocan_name = input("taocan_name");
        if (!$taocan_name || $taocan_name == "") {
            return json($this->getReturn(-1, "请输入套餐名称"));
        }
        $taocan_jiage = input("taocan_jiage");
        if ($taocan_jiage == "") {
            return json($this->getReturn(-1, "请输入套餐价格"));
        }
        $taocan_time = input("taocan_time");
        if ($taocan_time == "") {
            return json($this->getReturn(-1, "请输入套餐天数"));
        }
        $taocan_quanx1 = input("taocan_quanx1");
        if ($taocan_quanx1 == "") {
            return json($this->getReturn(-1, "请输入套餐费率"));
        }
        $taocan_quanx2 = input("taocan_quanx2");
        if ($taocan_quanx2 == "") {
            return json($this->getReturn(-1, "请选择套餐云监控权限"));
        }
        $taocan_quanx3 = input("taocan_quanx3");
        if ($taocan_quanx3 == "") {
            return json($this->getReturn(-1, "请选择套餐云端权限"));
        }
        $state = input("state");
        if ($state == "") {
            return json($this->getReturn(-1, "请选择套餐状态"));
        }
        
        $data = array(
            "taocan_name" => $taocan_name,//套餐名称
            "taocan_jiage" => $taocan_jiage,//套餐价格
            "taocan_quanx1" => $taocan_quanx1,//套餐费率权限
            "taocan_quanx2" => $taocan_quanx2,//套餐云监控权限
            "taocan_quanx3" => $taocan_quanx3,//套餐云端权限
            "taocan_time" => $taocan_time,//套餐时间
            "state" => $state,//商户状态
            );
            
        Db::name("pay_taocan")->where("id", $id)->update($data);
            
        return json($this->getReturn(1, "修改套餐成功"));
        
    }
    //模板链接指令：会员管理-会员删除
    public function del_tablecard_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：排序ID 参数错误');
        }
        $tada = Db::name("pay_taocan")->where("id", input("id"))->lock(true)->find();
        $dsad = Db::name("pay_user")->where("taocan", $tada['id'])->lock(true)->find();
        if($dsad){
            return json($this->getReturn(-1, "无法删除该套餐，因为该套餐是还有人正在使用，如需删除，请通知他切换其他套餐绑定"));
        }
        if(!$tada){
            $this->success('1002',null,'错误信息：排序ID不存在');
        }else{
            Db::table('pay_taocan')->where("id", input("id"))->delete();
            return json($this->getReturn(1, "删除成功"));
        }
    }
    //模板链接指令：会员管理-会员添加
    public function tablecard_reg()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $taocan_name = input("taocan_name");
        if (!$taocan_name || $taocan_name == "") {
            return json($this->getReturn(-1, "请输入套餐名称"));
        }
        $taocan_jiage = input("taocan_jiage");
        if ($taocan_jiage == "") {
            return json($this->getReturn(-1, "请输入套餐价格"));
        }
        $taocan_time = input("taocan_time");
        if ($taocan_time == "") {
            return json($this->getReturn(-1, "请输入套餐天数"));
        }
        $taocan_quanx1 = input("taocan_quanx1");
        if ($taocan_quanx1 == "") {
            return json($this->getReturn(-1, "请输入套餐费率"));
        }

        $data = array(
            "taocan_name" => $taocan_name,//套餐名称
            "taocan_jiage" => $taocan_jiage,//套餐价格
            "taocan_quanx1" => $taocan_quanx1,//套餐费率权限
            "taocan_time" => $taocan_time,//套餐时间

        );
        Db::table('pay_taocan')->insert($data);
        
        return json($this->getReturn(1, "添加套餐成功"));
        
    }
    
    //模板链接指令：商户管理
    public function user()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $rows = Db::name("pay_order")->where("state",2)->distinct(true)->field('pid')->cursor();
        foreach ($rows as $row){
            $jr_pay =  Db::table('pay_order')->where("pid",$row['pid'])->where("state",2)->whereTime('create_date','today')->sum("sxf");//今日消费
            $zr_pay =  Db::table('pay_order')->where("pid",$row['pid'])->where("state",2)->whereTime('create_date','yesterday')->sum("sxf");//昨日消费
            Db::name("pay_user")->where("id",$row["pid"])->update(array("jr_pay"=>$jr_pay,"zr_pay"=>$zr_pay));
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '后台管理';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    //模板链接指令：基本管理-商户管理-表格数据
    public function user_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_user')->where("id>=1")->page(input("page"),input("limit"));
        
        if (input("user")){
            $obj = $obj->where("user",input("user"));
        }
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    //模板链接指令：基本管理-商户管理-商户删除
    public function del_user_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：商户PID 参数错误');
        }
        $tada = Db::name("pay_user")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：商户PID不存在');
        }else if($tada['type']==2){
            $this->success('1003',null,'错误信息：无权删除此账号');
        }else{
            Db::table('pay_user')->where("id", input("id"))->delete();
            return json($this->getReturn(1, "删除成功"));
        }
    }
    //模板链接指令：基本管理-商户管理-商户编辑
    public function user_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $id = input("id");
        if (!$id || $id == "") {
            return json($this->getReturn(-1, "请输入商户PID"));
        }
        $user = input("user");
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入商户账号"));
        }
        $money = input("money");
        if ($money == "") {
            return json($this->getReturn(-1, "请输入商户余额"));
        }
        $fee = input("fee");
        if ($fee == "") {
            return json($this->getReturn(-1, "请输入商户费率"));
        }
        $email = input("email");
        if (!$email || $email == "") {
            return json($this->getReturn(-1, "请输入邮箱账号"));
        }
        $key = input("key");
        if (!$key || $key == "") {
            return json($this->getReturn(-1, "请输入商户KEY"));
        }
        $active = input("active");
        if ($active == "") {
            return json($this->getReturn(-1, "请选择商户状态"));
        }
        
        if(input("pass2") == ""){    
        $data = array(
            "user" => $user,//商户账号
            "money" => $money,//商户余额
            "fee" => $fee,//商户费率
            "email" => $email,//商户KEY
            "key" => $key,//商户KEY
            "active" => $active,//商户状态
            );
        }else{    
        $data = array(
            "user" => $user,//商户账号
            "pass" => password_hash(input("pass2"), PASSWORD_DEFAULT),//商户密码
            "money" => $money,//商户余额
            "fee" => $fee,//商户费率
            "email" => $email,//商户KEY
            "key" => $key,//商户KEY
            "active" => $active,//商户状态
            );
        }
            
        Db::name("pay_user")->where("id", $id)->update($data);
            
        return json($this->getReturn(1, "修改商户成功"));
        
    }
    //模板链接指令：基本管理-商户管理-商户添加
    public function userreg()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $fa = Db::name('pay_sz')->find();
        
        $user = input("username");
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        
        if (!preg_match('/^[a-zA-Z0-9]{6,18}/',$user)) {
            return json($this->getReturn(-1, "输入登录账号由6-18位数字或英文字母组成"));
        }
        
        $pass = input("password");
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        
        $email = input("email");
        if (!$email || $email == "") {
            return json($this->getReturn(-1, "请输入邮箱账号"));
        }
        
        if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",$email)) {
            return json($this->getReturn(-1, "您输入的电子邮件地址不合法"));
        }
        
        $res = Db::name('pay_user')->where('email',$email)->lock(true)->find();
        if ($res['email']) {
            return json($this->getReturn(-1, "输入邮箱账号已被注册，请重新输入邮箱账号"));
        }
        
        $res = Db::name('pay_user')->where('user',$user)->lock(true)->find();
        if ($res['user']) {
            return json($this->getReturn(-1, "输入账号已被注册，请重新输入登录账号"));
        }else{

        $data = array(
            "key" => md5($user.password_hash($pass, PASSWORD_DEFAULT).time()),
            "user" => $user,
            "pass" => password_hash($pass, PASSWORD_DEFAULT),
            "email" => $email,
            "money" => $fa['ds_money'],
            "fee" => $fa['ds_fee'],
            "duijei" => $fa['web_djms'],

        );
        Db::table('pay_user')->insert($data);
        
            return json($this->getReturn(1, "商户注册成功"));
        }
        
    }
    
    //模板链接指令：订单管理-支付订单
    public function orders()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '后台管理';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    //模板链接指令：基本管理-订单管理-支付订单-表格数据
    public function orders_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_order')->page(input("page"),input("limit"));
        
        if (input("user")){
            $data = Db::name("pay_user")->where("user", input("user"))->find();
            $obj = $obj->where("pid",$data['id']);
        }
        if (input("mid_name")){
            $obj = $obj->where("mid_name",input("mid_name"));
        }
        if (input("order_id")){
            $obj = $obj->where("order_id|pay_id|out_trade_no|record|param",input("order_id"));
        }
        if (input("mid_type")){
            $obj = $obj->where("mid_type",input("mid_type"));
        }
        if (input("state")){
            $obj = $obj->where("state",input("state"));
        }
        if (input("type")){
            $obj = $obj->where("type",input("type"));
        }
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    //模板链接指令：基本管理-订单管理-支付订单-订单删除
    public function orders_del()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：订单ID 参数错误');
        }
        $tada = Db::name("pay_order")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：订单ID不存在');
        }else{
            Db::table('pay_order')->where("id", input("id"))->delete();
            return json($this->getReturn(1, "删除成功"));
        }
        
    }
    //模板链接指令：基本管理-订单管理-支付订单-快捷订单删除
    public function orders_del_type()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("type") || input("type") == "") {
            $this->success('1001',null,'type（附加信息：删除类型 参数错误');
        }
        if(input("type")==1){//删除上周时间订单
           $del_status = Db::table('pay_order')->whereTime('create_time', 'last week')->delete();
        }else if(input("type")==2){//删除上月订单
           $del_status = Db::table('pay_order')->whereTime('create_time', 'last month')->delete();
        }else if(input("type")==3){//删除去年订单
           $del_status = Db::table('pay_order')->whereTime('create_time', 'last year')->delete();
        }else if(input("type")==4){//删除过期订单
           $where['state'] = 3;//超时的时间
           $del_status = Db::table('pay_order')->where($where)->delete();
        }else if(input("type")==5){//删除全部订单
           $del_status = Db::name('pay_order')->delete(true);
        }
        
        if($del_status){
            return json($this->getReturn(1, "删除成功"));
        }else{
            return json($this->getReturn(-1, "删除失败"));
        }
        
    }
    
    //模板链接指令：订单管理-余额订单
    public function yuedd()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '后台管理';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    //模板链接指令：基本管理-订单管理-余额订单-表格数据
    public function yuedd_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_price')->page(input("page"),input("limit"));
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    
    //模板链接指令：通道管理-通道配置
    public function game()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $rows = Db::name("pay_order")->where("state",2)->distinct(true)->field('mid_dm')->cursor();
        foreach ($rows as $row){
            $by_pay =  Db::table('pay_order') ->where("mid_dm", $row['mid_dm'])->where("state", 2)->whereTime('create_date', 'month')->sum("price");//本月总收
            $sy_pay =  Db::table('pay_order') ->where("mid_dm", $row['mid_dm'])->where("state", 2)->whereTime('create_date', 'last month')->sum("price");//上月总收
            Db::name("pay_qrcode")->where("game_dm", $row['mid_dm'])->update(array("by_pay"=>$by_pay,"sy_pay"=>$sy_pay));
        }
        
        $data['name'] = '通道管理-通道配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    
    //模板链接指令：通道管理-通道配置-表格数据
    public function game_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_qrcode')->page(input("page"),input("limit"));
        
        if (input("game_name")){
            $obj = $obj->where("game_name",input("game_name"));
        }
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //模板链接指令：通道管理-通道配置-刷新通道
    public function game_tdAll()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $Template =  new Template();
        $index= $Template->tdAll();
        return json($this->getReturn(1, "刷新成功"));
    }
    //模板链接指令：通道管理-通道配置-修改轮询状态
    public function game_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：ID 参数错误');
        }
        $tada = Db::name("pay_qrcode")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：ID不存在');
        }
        if(input("active")==1){
            Db::name("pay_qrcode")->where("id", input("id"))->update(array("state"=>0)); 
        }
        if($tada){
            Db::name("pay_qrcode")->where("id", input("id"))->update(array("state"=>input("active")));
            return json($this->getReturn(1, "操作成功"));
        }else{
            return json($this->getReturn(-1, "操作失败"));
        }
    }
    
    //模板链接指令：通道管理-账号配置
    public function land()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("game_dm")) || xss(input("game_dm")) == "") {
            $this->success('1001',null,'id（附加信息：通道标识 参数错误');
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $tada = Db::name("pay_qrcode")->where("game_dm", xss(input("game_dm")))->lock(true)->find();
        
        $data['name'] = '通道管理-账号配置';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        $this->assign('tada',$tada);
        // 或者批量赋值
        return $this->fetch();
    }
    //模板链接指令：通道管理-账号配置-表格数据
    public function land_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("game_dm")) || xss(input("game_dm")) == "") {
            $this->success('1001',null,'game_dm（附加信息：通道标识 参数错误');
        }
        
        $obj = Db::table('pay_gfg')->where("typec_dm",input("game_dm"))->page(input("page"),input("limit"));
        
        if (input("user")){
            $data = Db::name("pay_user")->where("user", input("user"))->find();
            $obj = $obj->where("pid",$data['id']);
        }
        if (input("username")){
            $obj = $obj->where("username",input("username"));
        }
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "result"=>
                    array(
                      "data"=>$array,
                      "total"=> $obj->count()
                    ),
        ));
    }
    //模板链接指令：通道管理-账号配置-修改轮询状态
    public function land_status_save1()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $tada = Db::name("pay_gfg")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        if(input("land_lx")==1){
            Db::name("pay_gfg")->where("id", input("id"))->update(array("bug_num"=>0,"bug_data"=>'')); 
        }
        $tada = Db::name("pay_gfg")->where("id", input("id"))->lock(true)->find();
        if($tada){
            Db::name("pay_gfg")->where("id", input("id"))->update(array("land_lx"=>input("land_lx")));
            return json($this->getReturn(1, "操作成功"));
        }else{
            return json($this->getReturn(-1, "操作失败"));
        }
    }
    //模板链接指令：通道管理-账号配置-修改账号状态
    public function land_status_save2()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $tada = Db::name("pay_gfg")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        if(input("ban")==0){
            Db::name("pay_gfg")->where("id", input("id"))->update(array("bug_data"=>'管理员禁止该账号使用')); 
        }
        if(input("ban")==1){
            Db::name("pay_gfg")->where("id", input("id"))->update(array("bug_data"=>'')); 
        }
        $tada = Db::name("pay_gfg")->where("id", input("id"))->lock(true)->find();
        if($tada){
            Db::name("pay_gfg")->where("id", input("id"))->update(array("state"=>input("ban")));
            return json($this->getReturn(1, "操作成功"));
        }else{
            return json($this->getReturn(-1, "操作失败"));
        }
    }
    //模板链接指令：通道管理-账号配置-账号删除
    public function land_del()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $tada = Db::name("pay_gfg")->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }else{
            Db::table('pay_gfg')->where("id", input("id"))->delete();
            return json($this->getReturn(1, "删除成功"));
        }
        
    }
    //模板链接指令：检测是否有新版本
    public function check_version()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cls = app("\\app\wenjian\\".'gengxin');
        $ret = $cls->check_version();
        return $ret;
    }
    //模板链接指令：在线更新
    public function system_update()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cls = app("\\app\wenjian\\".'gengxin');
        $ret = $cls->system_update();
        return $ret;
    }
    
    //模板链接指令：模板管理-首页模板
    public function mb_index()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '模板管理-首页模板';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //模板链接指令：模板管理-首页模板-表格数据
    public function mb_index_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $type = 'index';
        
        $obj = Db::table('pay_mb')->where("type",$type)->page(input("page"),input("limit"));
        
        $array = $obj->order("state","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //模板链接指令：模板管理-首页模板-刷新模板
    public function mb_index_updateAll()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $type = 'index';
        $Template =  new Template();
        $index= $Template->updateAll($type);
        return json($this->getReturn(1, "刷新成功"));
    }
    //模板链接指令：模板管理-首页模板-模板安装
    public function mb_index_install()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'index';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "安装失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("state" => 2));
            return json($this->getReturn(1, "安装成功"));
        }
    }
    //模板链接指令：模板管理-首页模板-模板卸载
    public function mb_index_uninstall()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'index';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "卸载失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("state" => 1,"shiyong" => 1));
            return json($this->getReturn(1, "卸载成功"));
        }
    }
    //模板链接指令：模板管理-首页模板-使用模板
    public function mb_index_shiyong()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'index';
        $tada = Db::name("pay_mb")->where("type",$type)->where("state",2)->where("shiyong",2)->lock(true)->find();
        if($tada){
            return json($this->getReturn(2, "请取消使用的模板"));
        }else{
            $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->where("state",2)->where("shiyong",1)->lock(true)->find();
            if(!$tada){
                return json($this->getReturn(-1, "使用失败"));
            }else{
                Db::name("pay_mb")->where("id", $tada['id'])->update(array("shiyong" => 2));
                return json($this->getReturn(1, "使用成功"));
            }
        }
    }
    //模板链接指令：模板管理-首页模板-取消模板
    public function mb_index_quxiao()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'index';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->where("state",2)->where("shiyong",2)->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "取消失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("shiyong" => 1));
            return json($this->getReturn(1, "取消成功"));
        }
    }
    
    //模板链接指令：模板管理-支付模板
    public function mb_pay()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->where("ip", real_ip())->lock(true)->find(1);
        $data['name'] = '模板管理-支付模板';//标题
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //模板链接指令：模板管理-首页模板-表格数据
    public function mb_pay_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $type = 'pay';
        
        $obj = Db::table('pay_mb')->where("type",$type)->page(input("page"),input("limit"));
        
        $array = $obj->order("state","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //模板链接指令：模板管理-首页模板-刷新模板
    public function mb_pay_updateAll()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $type = 'pay';
        $Template =  new Template();
        $index= $Template->updateAll($type);
        return json($this->getReturn(1, "刷新成功"));
    }
    //模板链接指令：模板管理-首页模板-模板安装
    public function mb_pay_install()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'pay';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "安装失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("state" => 2));
            return json($this->getReturn(1, "安装成功"));
        }
    }
    //模板链接指令：模板管理-首页模板-模板卸载
    public function mb_pay_uninstall()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'pay';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "卸载失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("state" => 1,"shiyong" => 1));
            return json($this->getReturn(1, "卸载成功"));
        }
    }
    //模板链接指令：模板管理-首页模板-使用模板
    public function mb_pay_shiyong()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'pay';
        $tada = Db::name("pay_mb")->where("type",$type)->where("state",2)->where("shiyong",2)->lock(true)->find();
        if($tada){
            return json($this->getReturn(2, "请取消使用的模板"));
        }else{
            $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->where("state",2)->where("shiyong",1)->lock(true)->find();
            if(!$tada){
                return json($this->getReturn(-1, "使用失败"));
            }else{
                Db::name("pay_mb")->where("id", $tada['id'])->update(array("shiyong" => 2));
                return json($this->getReturn(1, "使用成功"));
            }
        }
    }
    //模板链接指令：模板管理-首页模板-取消模板
    public function mb_pay_quxiao()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：模板ID 参数错误');
        }
        $type = 'pay';
        $tada = Db::name("pay_mb")->where("type",$type)->where("id", input("id"))->where("state",2)->where("shiyong",2)->lock(true)->find();
        if(!$tada){
            return json($this->getReturn(-1, "取消失败"));
        }else{
            Db::name("pay_mb")->where("id", $tada['id'])->update(array("shiyong" => 1));
            return json($this->getReturn(1, "取消成功"));
        }
    }
}