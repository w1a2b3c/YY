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
use think\Request;
use think\facade\Env;
use think\Controller;
use think\facade\Cookie;
use app\wenjian\QrcodeServer;

class User extends Controller
{
    
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
    
    //生成二维码
    public function enQrcode($url)
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        $data = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        // 自定义二维码配置
        $config = [
            'title'         => false,
            'title_content' => (xss(input('title'))==''? $data['web_name']:xss(input('title'))),
            'logo'          => false,
            'logo_url'      => (xss(input('logo'))==''? './static/user/assets/images/favicon.ico':xss(input('logo'))),
            'logo_size'     => 50,
            'size'          => 500,
            
        ];
        $qr_code = new QrcodeServer($config);
        $content = $qr_code->createServer($url);
        return response($content,250,['Content-Length'=>strlen($content)])->contentType('image/png');
    }
    
    //商户登录状态
    public function dernout()
    {
        if(config('url')!=$_SERVER['HTTP_HOST'] || config('url_api')!=$_SERVER['HTTP_HOST'] || config('url_jk')!=$_SERVER['HTTP_HOST']){
            if(config('url')==config('url_api') || config('url')==config('url_jk') || config('url_api')==config('url') || config('url_api')==config('url_jk') || config('url_jk')==config('url') || config('url_jk')==config('url_api')){
                return response()->code(404)->allowCache(false);
            }
        }
        
        if (!Cookie::has(md5(date("Y-m")),md5(date("Y-m-d")))){
            $this->redirect("user/login");
        }
        $dsa = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->where("ip",real_ip())->cache(true)->find();
        $data = Db::name("pay_sz")->find();
        if($data['ds_hfw']==2 and $dsa['type']<2){
            // 模板变量赋值
            $this->assign('data',$data);
            return $this->fetch('../application/index/view/404/index.html');
        }else if(!$dsa || $dsa['active'] == 1 || $dsa['ip'] != real_ip() and $dsa['type']<2){
            Db::table("pay_user")->where("user",Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->update(array("scsj"=>time()));
            Cookie::delete(md5(date("Y-m")),md5(date("Y-m-d")));
            Cookie::clear(md5(date("Y-m-d")));
            Cookie::delete(md5(date("m-Y")),md5(date("d-m-Y")));
            Cookie::clear(md5(date("d-m-Y")));
            $this->redirect("user/login");
        }else{
            sq();
            yecz();//检测余额充值
        }
    }
    
    //系统管理菜单
    public function getMenu()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        $data = Db::name("pay_sz")->find();
        
        $menu = array(
            array(//默认加载主页
                "url" => "../user/workplace",//主页路径
                "i" => "layui-icon-home",//图标
            ),array(//商户管理
                "name" => "arrow1",
                "type" => "nav",
                "url" => "xt1",
                "node" => array(//下级菜单
                    array(//单按钮
                        "name" => "商户中心",//标题
                        "i" => "layui-icon-home",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/workplace",//页面路径
                    ),array(//多按钮
                        "name" => "账号管理",//标题
                        "i" => "layui-icon-user",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "我的账号",//标题
                                "url" => "../user/user",//链接
                            ),array(
                                "name" => "异常账号",//标题
                                "url" => "../user/bug_list",//链接
                            ),
                        )
                    ),array(//多按钮
                        "name" => "通道管理",//标题
                        "i" => "layui-icon-app",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "常用通道",//标题
                                "url" => "../user/changyon",//链接
                            ),array(
                                "name" => "全部通道",//标题
                                "url" => "../user/quanbtodao",//链接
                            ),
                        )
                    ),array(//单按钮
                        "name" => "订单管理",//标题
                        "i" => "layui-icon-cart",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/listt",//链接
                    ),array(//单按钮
                        "name" => "会员套餐",//标题
                        "i" => "layui-icon-diamond",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/tablecard",//链接
                    ),array(//单按钮
                        "name" => "余额充值",//标题
                        "i" => "layui-icon-rmb",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/chozhi",//链接
                    ),array(//单按钮
                        "name" => "接口信息",//标题
                        "i" => "layui-icon-about",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/jieko",//链接
                    ),array(//单按钮
                        "name" => "支付监控",//标题
                        "i" => "layui-icon-console",//图标
                        "type" => "url",//单窗类型
                        "url" => "../user/jiankong",//链接
                    ),
                )
                
            ),array(//后台管理
                "name" => "layui-hide",
                "type" => "nav",
                "url" => "xt2",
                "node" => array(//下级菜单
                    array(//单按钮
                        "name" => "后台中心",//标题
                        "i" => "layui-icon-home",//图标
                        "type" => "url",//首页类型
                        "url" => "../admin/workplace",//链接
                    ),array(//多按钮
                        "name" => "基本管理",//标题
                        "i" => "layui-icon-set",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "基本配置",//标题
                                "url" => "../admin/config",//链接
                            ),array(
                                "name" => "其他配置",//标题
                                "url" => "../admin/config_set",//链接
                            ),array(
                                "name" => "公告配置",//标题
                                "url" => "../admin/news",//链接
                            ),array(
                                "name" => "通知配置",//标题
                                "url" => "../admin/msg",//链接
                            ),
                        )
                    ),array(//多按钮
                        "name" => "云端管理",//标题
                        "i" => "layui-icon-website",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "软件配置",//标题
                                "url" => "../admin/yd",//链接
                            ),array(
                                "name" => "呆呆博客",//标题
                                "url" => "//shoquan.xyz/",//链接
                            ),
                        )
                    ),array(//多按钮
                        "name" => "模板管理",//标题
                        "i" => "layui-icon-template-1",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "首页模板",//标题
                                "url" => "../admin/mb_index",//链接
                            ),array(
                                "name" => "支付模板",//标题
                                "url" => "../admin/mb_pay",//链接
                            ),
                        )
                    ),array(//单按钮
                        "name" => "通道管理",//标题
                        "i" => "layui-icon-app",//图标
                        "type" => "url",//单窗类型
                        "url" => "../admin/game",//链接
                    ),array(//单按钮
                        "name" => "会员管理",//标题
                        "i" => "layui-icon-diamond",//图标
                        "type" => "url",//单窗类型
                        "url" => "../admin/tablecard",//链接
                    ),array(//单按钮
                        "name" => "商户管理",//标题
                        "i" => "layui-icon-user",//图标
                        "type" => "url",//单窗类型
                        "url" => "../admin/user",//链接
                    ),array(//多按钮
                        "name" => "订单管理",//标题
                        "i" => "layui-icon-cart",//图标
                        "type" => "menu",//多窗类型
                        "node" => array(//下级菜单
                            array(
                                "name" => "支付订单",//标题
                                "url" => "../admin/orders",//链接
                            ),array(
                                "name" => "余额订单",//标题
                                "url" => "../admin/yuedd",//链接
                            ),
                        )
                    ),
                )
            )
            
        );
        return json($menu);
    }
    
    //模板链接指令：系统管理
    public function index()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $dat = Db::name("pay_sz")->find();
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $data['name'] = $dat['web_name'];//标题
        $data['web_name'] = $dat['web_name'];
        $data['keywords'] = $dat['web_keywords'];
        $data['description'] = $dat['web_description'];
        $data['web_beian'] = $dat['web_beian'];
        $data['wuqhhdd'] = ($dat['tz_denglu']==''?'0':'1');
        $data['tz_denglu'] = $dat['tz_denglu'];
        $data['web_kefu'] = $dat['web_kefu'];
        $data['web_qun'] = $dat['web_qun'];
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch();
    }
    //系统管理-商户登录退出
    public function logout()
    {
        Db::name("pay_user")->where("user",Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->update(array("scsj"=>time()));
        Cookie::delete(md5(date("Y-m")),md5(date("Y-m-d")));
        Cookie::clear(md5(date("Y-m-d")));
        Cookie::delete(md5(date("m-Y")),md5(date("d-m-Y")));
        Cookie::clear(md5(date("d-m-Y")));
        $this->redirect("user/login");
    }
    //系统管理-商户修改密码
    public function info_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $oldPsw = xss(input("oldPsw"));
        if (!$oldPsw || $oldPsw == "") {
            return json($this->getReturn(-1, "请输入原始密码"));
        }
        $newPsw = xss(input("newPsw"));
        if (!$newPsw || $newPsw == "") {
            return json($this->getReturn(-1, "请输入新密码"));
        }
        $rePsw = xss(input("rePsw"));
        if (!$rePsw || $rePsw == "") {
            return json($this->getReturn(-1, "请再次输入新密码"));
        }
        if (!$newPsw===$rePsw) {
            return json($this->getReturn(-1, "两次输入不一致"));
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        if (!password_verify($oldPsw, $data["pass"])) {
            return json($this->getReturn(-1, "原始密码错误"));
        }else{
            Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->update(array("pass"=>password_hash($newPsw, PASSWORD_DEFAULT)));
            Cookie::delete(md5(date("Y-m")),md5(date("Y-m-d")));
            Cookie::clear(md5(date("Y-m-d")));
            return json($this->getReturn(1, "修改密码成功"));
        }
    }
    
    //模板链接指令：商户中心
    public function workplace()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->lock(true)->find();
        $data['name'] = '商户中心';//标题
        if($data['type'] == '1'){
        $taocan = Db::name("pay_taocan")->where("id", $data['taocan'])->lock(true)->find();
        $data['fee'] = $taocan['taocan_quanx1'];//商户费率
        $data['type'] = '会员身份';//商户类型
        }else if($data['type'] == '2'){
            $data['fee'] = '0';//商户费率
            $data['type'] = '管理员';//商户类型
        }else{
            $data['type'] = '普通身份';//商户类型
        }
        switch($data['duijei']){
				case 1:
					$data['jdfds'] = '默认（系统）';
				break;
				case 2:
					$data['jdfds'] = '易支付';
 				break;
			}
        ;//商户类型
        $data['todayOrder_jintian'] = Db::table('pay_order') ->where("pid", $data["id"])->whereTime('create_date', 'today')->lock(true)->count();//今日订单
        $data['todayOrder_zuotian'] =  Db::table('pay_order') ->where("pid", $data["id"])->whereTime('create_date', 'yesterday')->lock(true)->count();//昨日订单
        $data['todayMoney_jintian'] = Db::table('pay_order') ->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'today')->lock(true)->sum("price");//今日收入
        $data['todayMoney_zuotian'] = Db::table('pay_order') ->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'yesterday')->lock(true)->sum("price");//昨日收入
        
        $jeyue_1 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'01'),time()))->where("create_date <=".(strtotime(date("Y-".'01'),time())+2626560))->lock(true)->sum("price");//1月成交订单金额
        $jeyue_2 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'02'),time()))->where("create_date <=".(strtotime(date("Y-".'02'),time())+2626560))->lock(true)->sum("price");//2月成交订单金额
        $jeyue_3 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'03'),time()))->where("create_date <=".(strtotime(date("Y-".'03'),time())+2626560))->lock(true)->sum("price");//3月成交订单金额
        $jeyue_4 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'04'),time()))->where("create_date <=".(strtotime(date("Y-".'04'),time())+2626560))->lock(true)->sum("price");//4月成交订单金额
        $jeyue_5 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'05'),time()))->where("create_date <=".(strtotime(date("Y-".'05'),time())+2626560))->lock(true)->sum("price");//5月成交订单金额
        $jeyue_6 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'06'),time()))->where("create_date <=".(strtotime(date("Y-".'06'),time())+2626560))->lock(true)->sum("price");//6月成交订单金额
        $jeyue_7 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'07'),time()))->where("create_date <=".(strtotime(date("Y-".'07'),time())+2626560))->lock(true)->sum("price");//7月成交订单金额
        $jeyue_8 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'08'),time()))->where("create_date <=".(strtotime(date("Y-".'08'),time())+2626560))->lock(true)->sum("price");//8月成交订单金额
        $jeyue_9 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'09'),time()))->where("create_date <=".(strtotime(date("Y-".'09'),time())+2626560))->lock(true)->sum("price");//9月成交订单金额
        $jeyue_10 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'10'),time()))->where("create_date <=".(strtotime(date("Y-".'10'),time())+2626560))->lock(true)->sum("price");//10月成交订单金额
        $jeyue_11 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'11'),time()))->where("create_date <=".(strtotime(date("Y-".'11'),time())+2626560))->lock(true)->sum("price");//11月成交订单金额
        $jeyue_12 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'12'),time()))->where("create_date <=".(strtotime(date("Y-".'12'),time())+2626560))->lock(true)->sum("price");//12月成交订单金额
        
        $ddyue_1 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'01'),time()))->where("create_date <=".(strtotime(date("Y-".'01'),time())+2626560))->lock(true)->count();//1月成交订单数
        $ddyue_2 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'02'),time()))->where("create_date <=".(strtotime(date("Y-".'02'),time())+2626560))->lock(true)->count();//2月成交订单数
        $ddyue_3 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'03'),time()))->where("create_date <=".(strtotime(date("Y-".'03'),time())+2626560))->lock(true)->count();//3月成交订单数
        $ddyue_4 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'04'),time()))->where("create_date <=".(strtotime(date("Y-".'04'),time())+2626560))->lock(true)->count();//4月成交订单数
        $ddyue_5 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'05'),time()))->where("create_date <=".(strtotime(date("Y-".'05'),time())+2626560))->lock(true)->count();//5月成交订单数
        $ddyue_6 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'06'),time()))->where("create_date <=".(strtotime(date("Y-".'06'),time())+2626560))->lock(true)->count();//6月成交订单数
        $ddyue_7 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'07'),time()))->where("create_date <=".(strtotime(date("Y-".'07'),time())+2626560))->lock(true)->count();//7月成交订单数
        $ddyue_8 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'08'),time()))->where("create_date <=".(strtotime(date("Y-".'08'),time())+2626560))->lock(true)->count();//8月成交订单数
        $ddyue_9 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'09'),time()))->where("create_date <=".(strtotime(date("Y-".'09'),time())+2626560))->lock(true)->count();//9月成交订单数
        $ddyue_10 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'10'),time()))->where("create_date <=".(strtotime(date("Y-".'10'),time())+2626560))->lock(true)->count();//10月成交订单数
        $ddyue_11 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'11'),time()))->where("create_date <=".(strtotime(date("Y-".'11'),time())+2626560))->lock(true)->count();//11月成交订单数
        $ddyue_12 = Db::name('pay_order')->where("pid", $data["id"])->where("state",2)->where("create_date >=".strtotime(date("Y-".'12'),time()))->where("create_date <=".(strtotime(date("Y-".'12'),time())+2626560))->lock(true)->count();//12月成交订单数
        
        $data['fhsjaj1'] = 'eval(['.$jeyue_1.','.$jeyue_2.','.$jeyue_3.','.$jeyue_4.','.$jeyue_5.','.$jeyue_6.','.$jeyue_7.','.$jeyue_8.','.$jeyue_9.','.$jeyue_10.','.$jeyue_11.','.$jeyue_12.'])';//成交订单金额（元）
        $data['fhsjaj2'] = 'eval(['.$ddyue_1.','.$ddyue_2.','.$ddyue_3.','.$ddyue_4.','.$ddyue_5.','.$ddyue_6.','.$ddyue_7.','.$ddyue_8.','.$ddyue_9.','.$ddyue_10.','.$ddyue_11.','.$ddyue_12.'])';//成交订单数（笔）
        
        $news_data =  Db::name('pay_gogao')->order('state desc')->order('id desc')->limit(8)->lock(true)->select();
        
        // 模板变量赋值
        $this->assign('data',$data);
        $this->assign('news_data',$news_data);
        return $this->fetch();
    }
    //模板链接指令：商户中心-公告页面
    public function news($id){
        
        $data = Db::name("pay_gogao")->where("id", $id)->lock(true)->find();
        if(!$data){
            
            $this->success('1001',null,'公告不存在');
            
        }
        // 模板变量赋值
        $this->assign('news',$data);
        return $this->fetch();
        
    }
    //商户中心-基本配置
    public function workplace_set()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        
        if (xss(input())) {
            
            if (xss(input("bug_close")) == "") {
                $this->success('1001',null,'bug_close（附加信息：订单过期时间 参数错误');
            }
            if (xss(input("mail_email")) == "") {
                $this->success('1002',null,'mail_email（附加信息：邮件模板 参数错误');
            }
            if (xss(input("web_mail")) == "") {
                $this->success('1003',null,'web_mail（附加信息：邮箱通知 参数错误');
            }
            if (xss(input("bug_close")) < 1 || xss(input("bug_close")) > 5) {
                return json($this->getReturn(-1, '请设置过期时间：1-5（分钟）'));
            }
            if($data['bug_close']==xss(input("bug_close"))and$data['mail_smtp']==xss(input("mail_smtp"))and$data['mail_port']==xss(input("mail_port"))and$data['mail_name']==xss(input("mail_name"))and$data['mail_pwd']==xss(input("mail_pwd"))and$data['mail_email']==xss(input("mail_email"))and$data['web_mail']==xss(input("web_mail"))){
                return json($this->getReturn(-1, '设置失败'));
            }else{
                Db::name("pay_user")->where("id", $data['id'])->update(array("bug_close"=>xss(input("bug_close")),"mail_smtp"=>xss(input("mail_smtp")),"mail_port"=>xss(input("mail_port")),"mail_name"=>xss(input("mail_name")),"mail_pwd"=>xss(input("mail_pwd")),"mail_email"=>xss(input("mail_email")),"web_mail"=>xss(input("web_mail"))));
                return json($this->getReturn(1, '设置成功'));
            }
        }
        return json(array(
            "bug_close"=>$data['bug_close'],//订单有效期
            "mail_smtp"=>$data['mail_smtp'],//SMTP
            "mail_port"=>$data['mail_port'],//邮箱端口
            "mail_name"=>$data['mail_name'],//邮箱账号
            "mail_pwd"=>$data['mail_pwd'],//邮箱密码
            "mail_email"=>$data['mail_email'],//邮件模板
            "web_mail"=>$data['web_mail'],//邮箱通知
            ));
    }
    
    //模板链接指令：会员套餐
    public function tablecard()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        $dawd = Db::name("pay_taocan")->where("id", $data['taocan'])->find();
        if($data['type']=='2'){
        $data['taocan_name'] = '管理员权限';//套餐名称
        $data['create_time'] = '永久';//过期时间戳
        $data['taocan_quanx1'] = '0';//套餐费率权限
        $data['taocan_quanx2'] = '1';//套餐云监控权限
        $data['taocan_quanx3'] = '1';//套餐云端权限
        }else if($dawd and $data['type']=='1'){
        $data['taocan_name'] = $dawd['taocan_name'];//套餐名称
        if($data['taocan_time']>=time()||$data['taocan_time']==0){
            $data['create_time'] = ($data['taocan_time'] !='0'?timer(date("Y-m-d H:i:s",$data['taocan_time'])):'永久');
        }else{
            Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
            $data['create_time'] = ($data['taocan_time'] !='0'?timer(date("Y-m-d H:i:s",$data['taocan_time'])):'永久');
        }
        $data['taocan_quanx1'] = $dawd['taocan_quanx1'];//套餐费率权限
        $data['taocan_quanx2'] = $dawd['taocan_quanx2'];//套餐云监控权限
        $data['taocan_quanx3'] = $dawd['taocan_quanx3'];//套餐云端权限
        }else{
        $data['taocan_name'] = '未开通';//套餐名称
        $data['create_time'] = '未开通';//过期时间戳
        $data['taocan_quanx1'] = '-1';//套餐费率权限
        $data['taocan_quanx2'] = '0';//套餐云监控权限
        $data['taocan_quanx3'] = '0';//套餐云端权限
            
        }
        
        $data['name'] = '会员套餐';//标题
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //会员套餐-会员套餐表格数据
    public function tablecard_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $obj = Db::table('pay_taocan')->where("state", 1)->page(xss(input("page")),xss(input("limit")));
        
        $array = $obj->order("id","desc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //会员套餐-购买套餐
    public function tablecard_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $name = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        if (!$name) {
            return json($this->getReturn(-1, "请输入充值商户"));
        }
        $taocan = Db::name("pay_taocan")->where("id", xss(input("id")))->find();
        if (!$taocan) {
            return json($this->getReturn(-1, "请输入正确购买套餐ID"));
        }
        $type = xss(input("type"));
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请选择充值方式"));
        }
        if ($name['type'] == 2) {
            return json($this->getReturn(-1, "管理员身份，无需开通或者续费"));
        }
        if ($name['money'] < $taocan['taocan_jiage']) {
            return json($this->getReturn(-1, "余额不足，请到余额充值页面充值"));
        }
        if($name['taocan']==$taocan['id']){
            if($name['taocan_time']==0){
                return json($this->getReturn(-1, '无需续费'));
            }
            $money = $name['money']-$taocan['taocan_jiage'];
            $taocan_time = $name['taocan_time']+$taocan['taocan_time']*86400;
            Db::name("pay_user")->where("id", $name['id'])->update(array("money"=>$money,"type"=>1,"taocan_time"=>$taocan_time));
            return json($this->getReturn(1, '续费成功'));
        }else{
            $money = $name['money']-$taocan['taocan_jiage'];
            $taocan_time = ($taocan['taocan_time'] !='0'?(time()+$taocan['taocan_time']*86400):'0');
            Db::name("pay_user")->where("id", $name['id'])->update(array("taocan"=>$taocan['id'],"money"=>$money,"type"=>1,"taocan_time"=>$taocan_time));
            return json($this->getReturn(1, '开通成功'));
        }
        
    }
    
    //模板链接指令：账号管理-我的账号
    public function user()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
                
        $atad = Db::name("pay_sz")->find();
        $data['name'] = '账号管理-我的账号';//标题
        $data['gogao'] = $atad['tz_zhanghao'];//公告
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //账号管理-我的账号-账号表格数据
    public function user_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_gfg')->where("pid", $data['id'])->where("state", 1)->page(xss(input("page")),xss(input("limit")));
        
        if (xss(input("username"))){
            $obj = $obj->where("state", 1)->where("username",xss(input("username")));
        }
        
        $array = $obj->order("jkstate","desc")->lock(true)->select();
        
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
    //账号管理-我的账号-发起测试
    public function pay_demo()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("mid")) || xss(input("mid")) == "") {
            $this->success('1001',null,'mid（附加信息：收款账号MID 参数错误');
        }
        if (!xss(input("money")) || xss(input("money")) == "") {
            $this->success('1001',null,'money（附加信息：测试金额 参数错误');
        }
        $atad = Db::name("pay_sz")->find();
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("mid", xss(input("mid")))->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：收款账号MID不存在');
        }
        if($data['duijei']==1){//对接模式
        if($tada['type']=='1'){
            $type = 'alipay';
        }elseif($tada['type']=='0'){
            $type = 'weixin';
        }elseif($tada['type']=='2'){
            $type = 'qq';
        } 
		$api = array(
			"pid" => $tada['pid'],//商户ID
			"type" => $type,//支付方式
			"record"=> '测试发起',//附加参数
			"money"	=> xss(input("money")),//付款金额
			"refer" =>  url('refer','','html',true),//同步回调
			"notify" => url('csnotify','','html',true),//异步通知
		    "mid" => xss(input("mid")),//收款账号MID
		    "json" => 1,//JSON格式输出
		);
        }else if($data['duijei']==2){
        if($tada['type']=='1'){
            $type = 'alipay';
        }elseif($tada['type']=='0'){
            $type = 'wxpay';
        }elseif($tada['type']=='2'){
            $type = 'qqpay';
        }    
		$api = array(
			"pid" => $tada['pid'],//商户ID
			"type" => $type,//支付方式
			"out_trade_no"=> time(),//商户订单号
			"money"	=> input("money"),//付款金额
			"name" => '发起测试',//商品名称
			"sitename" => $atad['web_name'],//网站名称
			"return_url" => url('refer','','html',true),//同步回调
			"notify_url" => url('csnotify','','html',true),//异步通知
		    "mid" => xss(input("mid")),//收款账号MID
		    "json" => 1,//JSON格式输出
		);
        }
        $sign = sign($api,$data['key'],$data,'sign');
		$api['sign'] = $sign;
        $url = http_ssl().http_pay().'?'.http_build_query($api);//拉起支付
        $header = array(
            'CLIENT-IP: '.real_ip(),
            'FORWARDED: '.real_ip(),
        );
        $ret = curl_get($url,$header);
        $json = json_decode($ret,true);
        if($data and $json){
            return json($this->getReturn(1, "调用成功",$json));
        }else{
            return json($this->getReturn(-1, "调用失败",$url));
        }
    }
    //账号管理-我的账号-异步回调
    public function csnotify()
    {
      return "success";
    }
    //支付页面监控
    public function checkOrder()
    {
        $this->dernout();//域名配置
       
        $orderId = xss(input('orderId'));
        if (!$orderId || $orderId == "") {
            return json($this->getReturn(-1, 'orderId（附加信息：请输入订单号（订单号）参数错误'));
        }
        $data = Db::name("pay_order")->where("order_id",$orderId)->find();
        $atad= Db::name("pay_user")->where("id", $data['pid'])->find();
        $eer = Db::name("pay_gfg")->where("pid", $data['pid'])->where("mid",$data['mid'])->find();
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
    //账号管理-我的账号-修改限量配置
    public function ds_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        if(xss(input("ds_time"))==0 and xss(input("ds_num"))!=0){
            return json($this->getReturn(-1, "请设定时间（分钟）"));
        }
        if(xss(input("ds_num"))==0 and xss(input("ds_time"))!=0){
            return json($this->getReturn(-1, "请输入限量数量"));
        }
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if($tada){
            Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->update(array("ds_time"=>input("ds_time"),"ds_type"=>input("ds_type"),"ds_num"=>xss(input("ds_num")),"ds_status"=>0));
            return json($this->getReturn(1, "操作成功"));
        }else{
            return json($this->getReturn(-1, "操作失败"));
        }
    }
    //账号管理-我的账号-修改异常配置
    public function bug_set()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        
        if (xss(input())) {
            
            if (xss(input("bug_num")) == "") {
                $this->success('1002',null,'bug_num（附加信息：异常次数 参数错误');
            }
            if (!xss(input("bug_type")) || xss(input("bug_type")) == "") {
                $this->success('1003',null,'bug_type（附加信息：触发操作 参数错误');
            }
            if(xss(input("bug_num"))==0){
                return json($this->getReturn(-1, "请设置账号异常次数"));
            }
            if($data['bug_num']==xss(input("bug_num")) and $data['bug_type']==xss(input("bug_type"))){
                return json($this->getReturn(-1, '设置失败'));
            }else{
                Db::name("pay_user")->where("id", $data['id'])->update(array("bug_num"=>xss(input("bug_num")),"bug_type"=>xss(input("bug_type"))));
                return json($this->getReturn(1, '设置成功'));
            }
        }
        return json(array(
            "bug_num"=>$data['bug_num'],//异常次数
            "bug_type"=>$data['bug_type'],//触发操作
            ));
    }
    
    //模板链接指令：账号管理-异常账号
    public function bug_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $atad = Db::name("pay_sz")->find();
        $data['name'] = '账号管理-异常账号';//标题
        $data['gogao'] = $atad['tz_zhanghao'];//公告
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //账号管理-异常账号-账号表格数据
    public function bug_land_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_gfg')->where("pid", $data['id'])->where("state", 0)->page(xss(input("page")),xss(input("limit")));
        
        if (xss(input("username"))){
            $obj = $obj->where("state", 0)->where("username",xss(input("username")));
        }
        if (xss(input("typec_name"))){
            $obj = $obj->where("state", 0)->where("typec_name",xss(input("typec_name")));
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
    
    //账号管理-修改轮询状态
    public function status_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        if(xss(input("land_lx"))==1){
            Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->update(array("state"=>1,"bug_num"=>0,"bug_data"=>'')); 
        }
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if($tada){
            Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->update(array("land_lx"=>xss(input("land_lx"))));
            return json($this->getReturn(1, "操作成功"));
        }else{
            return json($this->getReturn(-1, "操作失败"));
        }
    }
    //账号管理-删除账号
    public function del_save()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!input("id") || input("id") == "") {
            $this->success('1001',null,'id（附加信息：账号ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if($tada){
            Db::table('pay_gfg')->where("pid", $data['id'])->where("id", xss(input("id")))->delete();
            return json($this->getReturn(1, "删除成功"));
        }else{
            return json($this->getReturn(-1, "删除失败"));
        }
    }
    //模板链接指令：账号管理-修改账号
    public function land_edit()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            
            if (!xss(input("username")) || xss(input("username")) == "") {
                return json($this->getReturn(-1, "请输入账户备注"));
            }
            if (xss(input("r_money")) == "") {
                return json($this->getReturn(-1, "请输入日限额"));
            }
            if (xss(input("z_money")) == "") {
                return json($this->getReturn(-1, "请输入总限额"));
            }
            if (!xss(input("game_id")) || xss(input("game_id")) == "") {
                return json($this->getReturn(-1, "请输入通道ID"));
            }
            $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
            $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("game_id")))->find();
            $jkgj = Db::name("pay_jie")->where("pid", $data['id'])->where("id", $tada['typec_id'])->find();
                error_reporting(0);
                if($jkgj['game_dm']=='mq_gvxyjk'){
                    if($tada['zhanghao']!=xss(input("zhanghao"))){
                        $fjhd = Db::name("pay_gfg")->where("zhanghao",xss(input("zhanghao")))->find();
                        if($fjhd){
                            return json($this->getReturn(-1, "您修改的微信店员(唯一)名称已存在，请重新修改！"));
                        }
                        //$zh_pid = gezumir(DeleteHtml(xss(input("zhanghao"))),'_');//商户PID
                        //$zh_wxmz = getbstr(DeleteHtml(xss(input("zhanghao"))),'_');//微信店长名称
                        //if($data['id']==$zh_pid){
                            //$fjhd = Db::name("pay_gfg")->where("pid", $data['id'])->where("zhanghao",xss(input("zhanghao")))->find();
                            //if($fjhd){
                                //return json($this->getReturn(-1, "您修改的微信店员(唯一)名称已存在，请重新修改！"));
                            //}
                        //}else{
                            //return json($this->getReturn(-1, '您修改的正确格式：'.$data['id'].'_'.'自定义名称'));
                        //}
                    }
                }
                $data = array(
                    "typec_dm" => $jkgj['game_dm'],//通道标识
                    "username" => xss(input("username")),//账号备注
                    "zhanghao" => (xss(input("zhanghao"))==''?'无数据':xss(input("zhanghao"))),//通道账号
                    "ck" => (xss(input("ck"))==''?'无数据':xss(input("ck"))),//通道CK数据
                    "bf" => (xss(input("bf"))==''?'无数据':xss(input("bf"))),//通道备份数据
                    "bfbf" => (xss(input("bfbf"))==''?'无数据':xss(input("bfbf"))),//通道备份数据
                    "bfbfbf" => (xss(input("bfbfbf"))==''?'无数据':xss(input("bfbfbf"))),//通道备份数据
                    "type" => xss(input("type")),//支付方式
                    "ms" => xss(input("ms")),//支付模式
                    "method" => xss(input("method")),//回调方式
                    "r_money" => xss(input("r_money")),//日限额
                    "z_money" => xss(input("z_money")),//总限额
                    "create_date" => time(),//创建时间戳（10位）
                );
            if($tada['username']==xss(input("username")) and $tada['zhanghao']==(xss(input("zhanghao"))==''?'无数据':xss(input("zhanghao"))) and $tada['ck']==(xss(input("ck"))==''?'无数据':xss(input("ck"))) and $tada['bf']==(xss(input("bf"))==''?'无数据':xss(input("bf"))) and $tada['bfbf']==(xss(input("bfbf"))==''?'无数据':xss(input("bfbf"))) and $tada['bfbfbf']==(xss(input("bfbfbf"))==''?'无数据':xss(input("bfbfbf"))) and $tada['type']==xss(input("type")) and $tada['ms']==xss(input("ms")) and $tada['method']==xss(input("method")) and $tada['r_money']==xss(input("r_money")) and $tada['z_money']==xss(input("z_money"))){
                return json($this->getReturn(-1, '修改账号失败'));
            }else{
                Db::name("pay_gfg")->where("id", $tada['id'])->update($data);
                return json($this->getReturn(1, '修改账号成功'));
            }
        }else{
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_gfg")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        $fdsf = Db::name("pay_jie")->where("pid", $data['id'])->where("id", $tada['typec_id'])->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        $tad = Db::name("pay_jie")->where("pid", $data['id'])->where("id", $tada['typec_id'])->find();
        $tada['game_name'] = $fdsf['game_name'];//通道名称
        
        if($fdsf['game_dm']=='mq_gvxyjk'){
            //$tada['zhanghao'] = $data['id'].'_'.'自定义名称';//微信店长名称
            $tada['zhanghao'] = '自定义微信店长[名字或者门店]名称';//微信店长名称
            $data = Db::name("pay_sz")->find();
        }    
        // 模板变量赋值
        $this->assign('data',$tada);
        $this->assign('sz',$data);
        return $this->fetch('../application/common/'.$tad['game_dm'].'/view/index.html');
        }
    }
    
    //模板链接指令：通道管理-全部通道
    public function quanbtodao()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        qbtdjckg(Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))));//全部通道-检测开关
        
        $atad = Db::name("pay_sz")->find();
        $data['name'] = '全部通道';//标题
        $data['gogao'] = $atad['tz_todao'];//公告
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //通道管理-全部通道-通道表格数据
    public function game_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::name("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_jie')->where("pid", $data['id'])->where("state", 1)->page(xss(input("page")),xss(input("limit")));
        
        if (xss(input("game_name"))){
            $obj = $obj->where("state", 1)->where("game_name",xss(input("game_name")));
        }
        
        $array = $obj->order("game_my","asc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //模板链接指令：通道管理-常用通道
    public function changyon()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        qbtdjckg(Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))));//全部通道-检测开关
        $atad = Db::name("pay_sz")->find();
        $data['name'] = '常用通道';//标题
        $data['gogao'] = $atad['tz_todao'];//公告
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //通道管理-常用通道-通道表格数据
    public function chang_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_jie')->where("pid", $data['id'])->where("game_my", 0)->where("state", 1)->page(input("page"),input("limit"));
        
        if (xss(input("game_name"))){
            $obj = $obj->where("game_my", 0)->where("state", 1)->where("game_name",xss(input("game_name")));
        }
        
        $array = $obj->order("id","asc")->lock(true)->select();
        
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "count"=>$obj->count(),
            "data"=>$array,
        ));
    }
    //通道管理-通道常用设置
    public function cy_set()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：订单ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if(!$tada||$tada['state']==0){
            $this->success('1002',null,'错误信息：订单ID不存在');
        }
        $tada = Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->where("game_my",1)->find();
        if($tada){
            Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->update(array("game_my"=>0));
            return json($this->getReturn(1, "设置常用成功"));
        }else{
            Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->update(array("game_my"=>1));
            return json($this->getReturn(-1, "取消常用成功"));
        }
        
    }
    //模板链接指令：通道管理-添加账号
    public function land_add()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            
            if (!xss(input("username")) || xss(input("username")) == "") {
                return json($this->getReturn(-1, "请输入账户备注"));
            }
            if (xss(input("r_money")) == "") {
                return json($this->getReturn(-1, "请输入日限额"));
            }
            if (xss(input("z_money")) == "") {
                return json($this->getReturn(-1, "请输入总限额"));
            }
            if (!xss(input("game_id")) || xss(input("game_id")) == "") {
                return json($this->getReturn(-1, "请输入通道ID"));
            }
            $data = Db::table("pay_user")->where("user",Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
            $tada = Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("game_id")))->find();
            $mid =  Db::table('pay_gfg') ->count()+1;
            if($tada['game_dm']=='mq_gvxyjk'||$tada['game_dm']=='mq_gzfbyjk'||$tada['game_dm']=='mq_gqqyjk'){
                if($data['type']==1){
                    $taocan = Db::name("pay_taocan")->where("id", $data['taocan'])->find();
                    if($taocan){
                        if($data['taocan_time']>=time()||$data['taocan_time']==0){
                            if($taocan['taocan_quanx2']==0){
                                return json($this->getReturn(-1, "您的会员套餐里未开通云监控权限"));
                            }
                        }else{
                            Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
                            return json($this->getReturn(-1, "套餐云监控权限已过期"));
                        }
                    }else{
                        Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
                        return json($this->getReturn(-1, "未开通会员套餐云监控权限"));
                    }
                }else if($data['type']==2){
                }else{
                    return json($this->getReturn(-1, "您的会员套餐里未开通云监控权限"));
                }
                error_reporting(0);
                if($tada['game_dm']=='mq_gvxyjk'){
                    $fjhd = Db::name("pay_gfg")->where("zhanghao",xss(input("zhanghao")))->find();
                    if($fjhd){
                        return json($this->getReturn(-1, "您输入的微信店员(唯一)名称已存在，请重新输入！"));
                    }
                    //$zh_pid = gezumir(DeleteHtml(xss(input("zhanghao"))),'_');//商户PID
                    //$zh_wxmz = getbstr(DeleteHtml(xss(input("zhanghao"))),'_');//微信店长名称
                    //if($data['id']==$zh_pid){
                        //$fjhd = Db::name("pay_gfg")->where("pid", $data['id'])->where("zhanghao",xss(input("zhanghao")))->find();
                        //if($fjhd){
                            //return json($this->getReturn(-1, "您输入的微信店员名称已存在，请重新输入！"));
                        //}
                    //}else{
                        //return json($this->getReturn(-1, '您输入的正确格式：'.$data['id'].'_'.'自定义名称'));
                    //}
                }
            }
            if($tada['game_dm']=='mq_gvxyd'||$tada['game_dm']=='mq_gzfbyd'||$tada['game_dm']=='mq_gqqyd'){
                if($data['type']==1){
                    $taocan = Db::name("pay_taocan")->where("id", $data['taocan'])->find();
                    if($taocan){
                        if($data['taocan_time']>=time()||$data['taocan_time']==0){
                            if($taocan['taocan_quanx3']==0){
                                return json($this->getReturn(-1, "您的会员套餐里未开通云端权限"));
                            }
                        }else{
                            Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
                            return json($this->getReturn(-1, "套餐云端权限已过期"));
                        }
                    }else{
                        Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
                        return json($this->getReturn(-1, "未开通会员套餐云端权限"));
                    }
                }else if($data['type']==2){
                }else{
                    return json($this->getReturn(-1, "您的会员套餐里未开通云端权限"));
                }
            }
                error_reporting(0);
                $data = array(
                    "pid" => $data['id'],//商户PID
                    "mid" => $mid.rand(100,6666),//收款账号MID
                    "typec_id" => xss(input("game_id")),//通道ID
                    "username" => xss(input("username")),//账号备注
                    "typec_id" => $tada['id'],//通道ID
                    "typec_dm" => $tada['game_dm'],//通道标识
                    "typec_name" => $tada['game_name'],//所属通道
                    "zhanghao" => (xss(input("zhanghao"))==''?'无数据':xss(input("zhanghao"))),//通道账号
                    "ck" => (xss(input("ck"))==''?'无数据':xss(input("ck"))),//通道CK数据
                    "bf" => (xss(input("bf"))==''?'无数据':xss(input("bf"))),//通道备份数据
                    "bfbf" => (xss(input("bfbf"))==''?'无数据':xss(input("bfbf"))),//通道备份数据
                    "bfbfbf" => (xss(input("bfbfbf"))==''?'无数据':xss(input("bfbfbf"))),//通道备份数据
                    "type" => xss(input("type")),//支付方式
                    "ms" => xss(input("ms")),//支付模式
                    "method" => xss(input("method")),//回调方式
                    "r_money" => xss(input("r_money")),//日限额
                    "z_money" => xss(input("z_money")),//总限额
                    "create_date" => time(),//创建时间戳（10位）
                );
            Db::name("pay_gfg")->insert($data);
            return json($this->getReturn(1, '添加账号成功'));
        }else{
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：账号ID不存在');
        }
        
        if($tada['game_dm']=='mq_gvxyjk'){
            //$tada['zhanghao'] = $data['id'].'_'.'自定义名称';//微信店长名称
            $tada['zhanghao'] = '自定义微信店长[名字或者门店]名称';//微信店长名称
            $data = Db::name("pay_sz")->find();
        }
        // 模板变量赋值
        $this->assign('data',$tada);
        $this->assign('sz',$data);
        return $this->fetch('../application/common/'.$tada['game_dm'].'/view/index.html');
        //return view('../application/common/'.$tada['game_dm'].'/view/index.html');
        }
    }
    //模板链接指令：通道管理-账号管理
    public function land_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：通道ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $tada = Db::name("pay_jie")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        
        $tada['name'] = '通道管理-账号管理';//标题
        // 模板变量赋值
        $this->assign('data',$tada);
        
        return $this->fetch();
    }
    //通道管理-账号管理-账号表格数据
    public function land_ajax_list()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：通道ID 参数错误');
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_gfg')->where("pid", $data['id'])->where("typec_id", xss(input("id")))->page(xss(input("page")),xss(input("limit")));
        
        if (xss(input("username"))){
            $obj = $obj->where("typec_id", input("id"))->where("username",xss(input("username")));
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
    
    
    //模板链接指令：订单管理
    public function listt()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $data['name'] = '订单管理';//标题
        $data['todayMoney_jintian'] = Db::table('pay_order') ->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'today')->sum("price");//今日收入
        $data['todayMoney_zuotian'] = Db::table('pay_order') ->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'yesterday')->sum("price");//昨日收入
        $data['todayMoney_benzho'] = Db::table('pay_order')->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'week')->sum("price");//本周收入
        $data['todayMoney_benyue'] = Db::table('pay_order')->where("pid", $data["id"])->where("state",2)->whereTime('create_date', 'month')->sum("price");//本月收入
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //订单管理-订单表格数据
    public function getOrders()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $obj = Db::table('pay_order')->where("pid", $data['id'])->page(xss(input("page")),xss(input("limit")));
        
        if (xss(input("order_id"))){
            $obj = $obj->where("order_id|pay_id|out_trade_no|record|param",input("order_id"));
        }
        if (xss(input("state"))){
            $obj = $obj->where("state",xss(input("state")));
        }
        if (xss(input("startDate")).xss(input("endDate"))){
            $obj = $obj->where("create_date",'>=',strtotime(input("startDate")))->where("create_date",'<=',strtotime(xss(input("endDate"))));
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
    //订单管理-手动补单
    public function reback()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (!xss(input("id")) || xss(input("id")) == "") {
            $this->success('1001',null,'id（附加信息：订单ID 参数错误');
        }
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        $tada = Db::table("pay_order")->where("pid", $data['id'])->where("id", xss(input("id")))->find();
        $eer = Db::table("pay_gfg")->where("pid", $data['id'])->where("mid",$tada['mid'])->find();
        if(!$tada){
            $this->success('1002',null,'错误信息：订单ID不存在');
        }
        if($tada['state']==2 and $tada['api_state']==2){
            $this->success('1003',null,'提示信息：该订单ID不需要补单');
        }
        if($tada['sxf']>$data['money']){
            return json($this->getReturn(-1, "商户余额不足，请及时充值"));
        }
        if(url_notify($eer,$tada,$data,1)){
            return json($this->getReturn(1, "补单成功"));
        }else{
            return json($this->getReturn(-1, "补单失败"));
        }
    }
    
    //模板链接指令：接口信息
    public function jieko()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $atad = Db::name("pay_sz")->find();
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        switch($data['duijei']){
				case 1:
					$data['jdfds'] = '默认（系统）';
					$text = '1';
				break;
				case 2:
					$data['jdfds'] = '易支付';
					$text = '2';
 				break;
			}
        $dhhd = Db::name("pay_gogao")->where("id",$text)->find();
        $data['fsdrf'] = urlencode(http_ssl().http_pay().'/sytpay?pid='.$data['id'].'&key='.base64_encode($data['key']));
        $data['name'] = '接口信息';//标题
        $data['text'] =  $dhhd['text'];//接口文档
        if(file_get_contents(Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png')){
            $data['ewm'] =  base64_encode(file_get_contents(Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png'));//收银台图片
        }else{
            $data['ewm'] =  base64_encode(file_get_contents(Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$data['jh_ewm'].'.png'));//收银台模板
        }
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //接口信息-重新生成KEY
    public function generatingkey()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        
        $key = md5($data['user'].$data['email'].password_hash($data['pass'], PASSWORD_DEFAULT).time());
        
        Db::name("pay_user")->where("user", $data['user'])->update(array("key"=>$key));
        
        return json($this->getReturn(1, "已成功重新生成", $key));
        
    }
    //接口信息-对接模式切换
    public function duijei()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        switch($data['duijei']){
				case 1:
					$eiq = '默认（系统）';
				break;
				case 2:
					$eiq = '易支付';
 				break;
			}
        if($data['duijei']<2){
            $duijei = $data['duijei']+1;
            Db::name("pay_user")->where("user", $data['user'])->update(array("duijei"=>$duijei));
        }else{
            $duijei = '1';
            Db::name("pay_user")->where("user", $data['user'])->update(array("duijei"=>$duijei));
        }
        
        return json($this->getReturn(1, "切换成功", $eiq));
        
    }
    
    //模板链接指令：余额充值
    public function chozhi()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        if (xss(input())) {
            
            $name = xss(input("name"));
            if (!$name || $name == "") {
                return json($this->getReturn(-1, "请输入充值商户"));
            }
            $money = xss(input("money"));
            if (!$money || $money == "") {
                return json($this->getReturn(-1, "请输入充值金额"));
            }
            $type = xss(input("type"));
            if (!$type || $type == "") {
                return json($this->getReturn(-1, "请选择充值方式"));
            }
            
            $atad = Db::name("pay_sz")->find();
            $hfhd = Db::name("pay_user")->where("type", 2)->find();
            $data = Db::name("pay_user")->where("user", $name)->find();
            if($atad['ds_zaioc']==2){
                return json($this->getReturn(-1, "余额充值已关闭，请联系客服充值"));
            }
            if(!$data){
                return json($this->getReturn(-1, "错误信息：充值商户不存在"));
            }
            
            if($hfhd['duijei']==1){//对接模式
		    $api = array(
			    "pid" => $hfhd['id'],//商户ID
			    "type" => $type,//支付方式
			    "record"=> $name,//附加参数
			    "money"	=> $money,//付款金额
			    "refer" => url('refer','','html',true),//同步回调
			    "notify" => url('notify','','html',true),//异步通知
		        "json" => 1,//JSON格式输出
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
			    "pid" => $hfhd['id'],//商户ID
			    "type" => $type,//支付方式
			    "out_trade_no"=> time(),//附加参数
			    "money"	=> $money,//付款金额
			    "name" => '余额充值',//商品名称
			    "param" => $name,//业务扩展叁数
			    "sitename" => $atad['web_name'],//网站名称
			    "return_url" => url('refer','','html',true),//同步回调
			    "notify_url" => url('notify','','html',true),//异步通知
		        "json" => 1,//JSON格式输出
		    );
            }
            $sign = sign($api,$hfhd['key'],$hfhd,'sign');
		    $api['sign'] = $sign;
            $url = url('/','','',config('url_api')).'?'.http_build_query($api);//拉起支付
            $header = array(
                'CLIENT-IP: '.real_ip(),
                'FORWARDED: '.real_ip(),
            );
            $ret = curl_get($url,$header);
            $json = json_decode($ret,true);
            if($atad['ds_zaioc']==1 and $json){
                return json($this->getReturn(1, "发起支付成功",$json));
            }else{
                return json($this->getReturn(2, "发起支付失败，请联系客服"));
            }
            
        }
        $atad = Db::name("pay_sz")->find();
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        $data['name'] = '余额充值';//标题
        $data['gogao'] = $atad['tz_yue'];//公告
        $data['web_kefu'] = $atad['web_kefu'];//客服URL
        
        // 模板变量赋值
        $this->assign('data',$data);
        $this->assign('atad',$atad);
        
        return $this->fetch();
    }
    //模板链接指令：余额充值-异步回调
    public function notify()
    {
        $api = xss(input());
        
        if($api['order']){//默认
            $data = Db::name("pay_order")->where("order_id",$api['order'])->find();
            $user = Db::name("pay_user")->where("user", $api['record'])->find();
            $hfhd = Db::name("pay_user")->where("type", 2)->find();
            $sign = sign($api,$hfhd['key'],$hfhd,'sign');
            if($sign==$api['sign']){
                $rrw = array(
                    "pid" => $data['pid'],//商户PID
                    "user" => $user['user'],//商户账号
                    "money" => $data['money'],//金额
                    "order" => $data['order_id'],//平台订单号
                    "create_date" => time(),//创建时间戳（10位）
                );
                $mnb = Db::name("pay_price")->where("pid", $rrw['pid'])->where("user", $rrw['user'])->where("money", $rrw['money'])->where("order", $rrw['order'])->find();
                if(!$mnb){
                Db::name("pay_price")->insert($rrw);
                }
                return 'success';
            }else{
                return 'fail';
            }
        }else if($api['trade_no']){//易支付
            $data = Db::name("pay_order")->where("order_id",$api['trade_no'])->find();
            $user = Db::name("pay_user")->where("user", $api['param'])->find();
            $hfhd = Db::name("pay_user")->where("type", 2)->find();
            $sign = sign($api,$hfhd['key'],$hfhd,'sign');
            if($sign==$api['sign']){
                $rrw = array(
                    "pid" => $data['pid'],//商户PID
                    "user" => $user['user'],//商户账号
                    "money" => $data['money'],//金额
                    "order" => $data['order_id'],//平台订单号
                    "create_date" => time(),//创建时间戳（10位）
                );
                $mnb = Db::name("pay_price")->where("pid", $rrw['pid'])->where("user", $rrw['user'])->where("money", $rrw['money'])->where("order", $rrw['order'])->find();
                if(!$mnb){
                Db::name("pay_price")->insert($rrw);
                }
                return 'success';
            }else{
                return 'fail';
            }
        }else{
          return 'fail';
        }
    }
    //模板链接指令：余额充值-同步回调
    public function refer()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        error_reporting(0);  
        $ata = xss(input());
        if(!$ata){
            return $this->fetch('pay/fail');
        }
        if($ata['order']){
            $order = Db::name("pay_order")->where("order_id", $ata['order'])->find();
        }else if($ata['trade_no']){
            $order = Db::name("pay_order")->where("order_id", $ata['trade_no'])->find();
        }
        // 模板变量赋值
        $this->assign('data',$order);
        if($order['state']==2){
            return $this->fetch('pay/success');
        }else{
            return $this->fetch('pay/fail');
        }
        
    }
    
    //模板链接指令：商户登录
    public function login()
    {
        Cookie::delete(md5(date("Y-m")),md5(date("Y-m-d")));
        Cookie::clear(md5(date("Y-m-d")));
        Cookie::delete(md5(date("m-Y")),md5(date("d-m-Y")));
        Cookie::clear(md5(date("d-m-Y")));
        $data = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        $data['name'] = '商户登录';//标题
        // 模板变量赋值
        if(Cookie::has('password')) {
        $this->assign('password',Cookie::get('password'));
        }
        $this->assign('usermember',Cookie::get('usermember'));
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //商户登录-商户登录
    public function userlogin()
    {
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        //保存登录账号
        Cookie::forever('usermember',$user);
            
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        } 
        $code = xss(input("code"));
        if (!$code || $code == "") {
            return json($this->getReturn(-1, "请输入验证码"));
        }
        if(!captcha_check($code)){
            return json($this->getReturn(-1, "验证码错误"));
        }
        $res = Db::name('pay_user')->where('user',$user)->lock(true)->find();
        if ($user != $res["user"]) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }else
        if (!password_verify($pass, $res["pass"])) {
            return json($this->getReturn(-1, "账号或密码错误"));
        }
        //是否记住密码input("type")
        if(!empty(xss((input("remember"))) and xss(input("remember"))) == 1) {
            //检查当前有没有记住的密码
            if(Cookie::has('password')) {
                    Cookie::delete('password');
            }
            //保存新的
            Cookie::forever('password',$pass);
        } else {
            //未选择记住账号，或属于取消操作
            if(Cookie::has('password')) {
                Cookie::delete('password');
            }
        }
        $atad = Db::name("pay_user")->where("user",$user)->lock(true)->find();
        if($atad['active']==1 and $atad['type']!=2){
            return json($this->getReturn(-1, "您登录的账号已被禁用，请联系客服咨询原因"));
        }
        if($atad['type']==2){
            Cookie::set(md5(date("m-Y")),$res['user'],['prefix'=>md5(date("d-m-Y")),'expire'=>0]);
            $data = array(
                "user" => $res['user'],//后台账号
                "ip" => real_ip(),//登录IP
            );
            Db::name("pay_sz")->where("id",1)->update($data);
        }
        Cookie::set(md5(date("Y-m")),$res['user'],['prefix'=>md5(date("Y-m-d")),'expire'=>0]);
        Db::table("pay_user")->where("user",Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->update(array("scsj"=>time(),"ip" => real_ip()));
        if($atad['ip']!=real_ip()){
            $data = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
            $get_ip_city = get_ip_city(real_ip());
            $sub = $data['web_name'].'-'.'商户登录'.'-登录通知';
            $msg = "嗨！尊敬的" .$res['user']. "商户<br>  您好！<br>刚刚您账号在商户登录操作登录成功，若不是你本人登录请尽快做好安全措施！<br>登录IP：" .real_ip(). "<br>IP地址：".$get_ip_city['Result']['Country'];
            fmail($res['email'], $sub, $msg, $res);
        }
        return json($this->getReturn(1, "登录成功"));
    }
    //模板链接指令：商户注册
    public function reg()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        if ($fa['ds_user'] == "2") {
            $this->redirect("user/login");
        }
        $data['web_mail'] = $fa['web_mail'];//邮箱通知开关
        $data['name'] = '商户注册';//标题
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //商户注册-商户注册
    public function userreg()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        if ($fa['ds_user'] == "2") {
            return json($this->getReturn(2, $fa['ds_txet']));
        }
        $user = xss(input("username"));
        if (!$user || $user == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入登录密码"));
        }
        $pass2 = xss(input("password2"));
        if (!$pass2 || $pass2 == "") {
            return json($this->getReturn(-1, "请再次输入登录密码"));
        }
        if (!$pass===$pass2) {
            return json($this->getReturn(-1, "输入登录密码不一致"));
        }
        $email = xss(input("email"));
        if (!$email || $email == "") {
            return json($this->getReturn(-1, "请输入邮箱账号"));
        }
        if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",$email)) {
            return json($this->getReturn(-1, "您输入的电子邮件地址不合法"));
        }
        $code = xss(input("code"));
        if (!$code || $code == "") {
            return json($this->getReturn(-1, "请输入验证码"));
        }
        if($fa['web_mail']==1){
            $email_code = xss(input("email_code"));
            if (!$email_code || $email_code == "") {
                return json($this->getReturn(-1, "请输入邮箱验证码"));
            }
            $cache = tohc($email);
            if ($cache->get($user)!=$email_code) {
                return json($this->getReturn(-1, "邮箱验证码错误"));
            }
        }
        if(!captcha_check($code)){
            return json($this->getReturn(-1, "验证码错误"));
        }
        $res = Db::name('pay_user')->where('user',$user)->lock(true)->find();
        if ($res['user']) {
            return json($this->getReturn(-1, "输入账号已被注册，请重新输入登录账号"));
        }else{
        $data = array(
            "key" => md5($user.$email.password_hash($pass, PASSWORD_DEFAULT).time()),
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
    //商户注册-获取邮箱验证码
    public function djksadhsaji()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        if ($fa['ds_user'] == "2") {
            return json($this->getReturn(2, $fa['ds_txet']));
        }
        
        $username = xss(input("username"));
        if (!$username || $username == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $email = xss(input("email"));
        if (!$email || $email == "") {
            return json($this->getReturn(-1, "请输入邮箱账号"));
        }
        $code = xss(input("code"));
        if (!$code || $code == "") {
            return json($this->getReturn(-1, "请输入验证码"));
        }
        if(!captcha_check($code)){
            return json($this->getReturn(-1, "验证码错误"));
        }
        $res = Db::name('pay_user')->where('user',$username)->lock(true)->find();
        if ($res['user']) {
            return json($this->getReturn(-1, "输入账号已被注册，请重新输入登录账号"));
        }
        if(Cookie::has('yanzhengma')){
            return json($this->getReturn(-1, '请求验证码频率过于频繁，请60秒后操作'));
        }else{
            if($fa['web_mail']==1){
                $code = str_rand(5);//获取的验证码
                $sub= $fa['web_name'].'-注册账号-验证码通知';
                $msg = "【注册账号】您正在使用".$fa['web_name']."注册账号，验证码：" .$code. "，60秒内有效，如有疑虑请详询（客服）";
                if(fmail($email, $sub, $msg)==true){
                    $cache = tohc($email);
                    $cache->set($username,$code,60);
                    return json($this->getReturn(1, "验证码发送到您的邮件里，请注意查收"));
                }else{
                    return json($this->getReturn(-1, "对不起，邮件发送失败！如有疑虑请详询（客服）"));
                }
            }else{
                return json($this->getReturn(-1, "对不起，邮箱通知已关闭！如有疑虑请详询（客服）"));
            }
        }
    }
    
    //模板链接指令：重置密码
    public function forget()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        $data['web_mail'] = $fa['web_mail'];//邮箱通知开关
        $data['name'] = '重置密码';//标题
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    //重置密码-修改商户密码
    public function userforget()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        $username = xss(input("username"));
        if (!$username || $username == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $res = Db::table('pay_user')->where('user',$username)->lock(true)->find();
        if (!$res['user']) {
            return json($this->getReturn(-1, "输入登录账号不存在，请重新输入登录账号"));
        }
        $pass = xss(input("password"));
        if (!$pass || $pass == "") {
            return json($this->getReturn(-1, "请输入修改密码"));
        }
        $pass2 = xss(input("password2"));
        if (!$pass2 || $pass2 == "") {
            return json($this->getReturn(-1, "请再次输入修改密码"));
        }
        if (!$pass===$pass2) {
            return json($this->getReturn(-1, "输入修改密码不一致"));
        }
        $code = xss(input("code"));
        if (!$code || $code == "") {
            return json($this->getReturn(-1, "请输入验证码"));
        }
        if(!captcha_check($code)){
            return json($this->getReturn(-1, "验证码错误"));
        }
        $cache = tohc($username);
        if ($cache->get($username)==$code) {
            Db::name("pay_user")->where("user", $username)->update(array("pass"=>password_hash($pass, PASSWORD_DEFAULT)));
            return json($this->getReturn(1, "修改密码成功"));
        }else{
            return json($this->getReturn(-1, "输入验证码错误"));
        }  
    }
    //重置密码-获取验证码
    public function sendEmailVer()
    {
        $fa = Db::table("pay_sz")->lock(true)->cache(120)->find(1);
        if($fa['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$fa);
            return $this->fetch('../application/index/view/404/index.html');
        }
        
        $username = xss(input("username"));
        if (!$username || $username == "") {
            return json($this->getReturn(-1, "请输入登录账号"));
        }
        $code = xss(input("code"));
        if (!$code || $code == "") {
            return json($this->getReturn(-1, "请输入验证码"));
        }
        if(!captcha_check($code)){
            return json($this->getReturn(-1, "验证码错误"));
        }
        $res = Db::table('pay_user')->where('user',$username)->lock(true)->find();
        if (!$res['user']) {
            return json($this->getReturn(-1, "输入登录账号不存在，请重新输入登录账号"));
        }
        if(Cookie::has('yanzhengma')){
            return json($this->getReturn(-1, '请求验证码频率过于频繁，请60秒后操作'));
        }else{
            if($fa['web_mail']==1){
                $code = str_rand(5);//获取的验证码
                $sub= $fa['web_name'].'-重置密码-验证码通知';
                $msg = "【重置密码】您正在使用".$fa['web_name']."重置密码，验证码：" .$code. "，60秒内有效，如有疑虑请详询（客服）";
                if(fmail($res['email'], $sub, $msg)==true){
                    $cache = tohc($username);
                    $cache->set($username,$code,60);
                    return json($this->getReturn(1, "验证码发送到您的邮件里，请注意查收"));
                }else{
                    return json($this->getReturn(-1, "对不起，邮件发送失败！如有疑虑请详询（客服）"));
                }
            }else{
                return json($this->getReturn(-1, "对不起，邮箱通知已关闭！如有疑虑请详询（客服）"));
            }
        } 
    }
    //接口调用
    public function api()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cache = tohc('Heart');
        error_reporting(0);
        $post = input();//判断：GET和POST
        if($cache->get('pcyjk')>=date('Y-m-d H:i:s',time())){
            $ret = curl_get($cache->get('ds_yjkrul')."/?".http_build_query($post));
        }else{
            $ret = curl_post($cache->get('ds_todaorul'),http_build_query(ydqu($post)));
        }
        echo $ret;
    }
    //云端接口
    public function ydapi()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cache = tohc('Heart');
        error_reporting(0);
        $post = input();//判断：GET和POST
        $ret = curl_post($cache->get('ds_todaorul'),http_build_query(ydqu($post)));
        echo $ret;
    }
    //云监控接口
    public function yjkapi()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $cache = tohc('Heart');
        error_reporting(0);
        $post = input();//判断：GET和POST
        $ret = curl_post($cache->get('ds_yjkrul'),http_build_query(ydqu($post)));
        echo $ret;
    }
    //二维码解析
    public function upload()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
    // 获取表单上传文件 例如上传了001.jpg
    //$file = request()->file('file');
    // 移动到框架应用根目录/public/static/user/assets/erweima/ 目录下
    //$info = $file->validate(['ext'=>'jpg,png,gif'])->move('../public/static/user/assets/erweima/',true,false);
        //if($info){
            //$ret = curls('https://api.uomg.com/api/qr.encode?url='.http_url().'/static/user/assets/erweima/'.$info->getSaveName());
            //$json = json_decode($ret,TRUE);
            //@unlink(Env::get('ROOT_PATH').'/public/static/user/assets/erweima/'.$info->getSaveName());//删除目录图片
            deldir(Env::get('ROOT_PATH').'/public/static/user/assets/erweima/');//删除文件夹
            //if($json['code'] == 1){
                //return json($this->getReturn(1, $json["qrurl"]));
            //}else{
                //return json($this->getReturn(-1, $json['msg']));
            //}
        //}else{
            return json($this->getReturn(-1, '上传失败[可点收款码解析地址]'));
        //}
    }
    //最新订单实时提醒
    public function dsadsa()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $roo = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->cache(true)->find();
        $todr1 = Db::table("pay_order")//等待支付
            ->where("pid", $roo["id"])
            ->where("state",1)
            ->whereTime('create_date', '<=', time())
            ->whereTime('create_date', '>=', time()-10)
            ->lock(true)->count();
        $todr2 = Db::name("pay_order")//补单成功
            ->where("pid", $roo["id"])
            ->where("state",2)
            ->where("api_budan",1)
            ->whereTime('pay_time', '<=', time())
            ->whereTime('pay_time', '>=', time()-10)
            ->lock(true)->count();
        $todr3 = Db::name("pay_order")//支付完成但通知失败
            ->where("pid", $roo["id"])
            ->where("state",2)
            ->where("api_state",3)
            ->whereTime('pay_date', '<=', time())
            ->whereTime('pay_date', '>=', time()-10)
            ->lock(true)->count();
        $todr4 = Db::name("pay_order")//支付成功
            ->where("pid", $roo["id"])
            ->where("state",2)
            ->where("api_budan",0)
            ->whereTime('pay_date', '<=', time())
            ->whereTime('pay_date', '>=', time()-10)
            ->lock(true)->count(); 
        if($todr1){
            return json($this->getReturn(1, "等待支付",$todr1));
        }else if($todr2){
            return json($this->getReturn(2, "补单成功",$todr2));
        }else if($todr3){
            return json($this->getReturn(3, "支付完成但通知失败",$todr3));
        }else if($todr4){
            return json($this->getReturn(4, "支付成功",$todr4));
        }else{
            return json($this->getReturn(0, "ok"));
        }
    }
    //取余额
    public function money()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $id = xss(input("id"));
        if (!$id || $id == "") {
            return json($this->getReturn(-1, "查询信息失败"));
        }
        $res = Db::table('pay_user')->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->lock(true)->cache(true)->find();
        $ree = Db::table("pay_gfg")->where("pid", $res['id'])->where("id",$id)->lock(true)->find();
        $ret = Db::table("pay_jie")->where("pid", $res['id'])->where("id",$ree['typec_id'])->lock(true)->find();
        $cls = common($ret['game_dm']);//支付通道路径
        $json = $cls->Cookie($ree);//支付通道数据
        if($json['code']==1){
            return json($this->getReturn(1, $json['data']));
        }else{
            return json($this->getReturn(-1, $json['data']));
        }
    }
    
    //模板链接指令：支付监控
    public function jiankong()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->lock(true)->cache(true)->find();
        $cache = tohc('Heart');
        $data['name'] = '支付监控';//标题
        $atad = Db::table("pay_sz")->lock(true)->find(1);
        if($atad['ds_yjkrul']){//PC云监控
            if($cache->get('pcyjk')>=date('Y-m-d H:i:s',time())){
                $data['pcyjk'] = '1';
                $ip = checkServerSatatus($cache->get('ds_yjkrul'));
                if($ip['code']==0){
                    $data['pcyjk1'] =  $ip['Country'];//服务器地址
                    $data['pcyjk2'] =  '响应异常（端口堵塞）';//服务器速度
                    $data['pcyjk3'] =  '在线[正常]';//服务器状态
                }else{
                    $data['pcyjk1'] =  $ip['Country'];//服务器地址
                    $data['pcyjk2'] =  'ms:'.$ip['Ping'];//服务器速度
                    $data['pcyjk3'] =  $ip['msg'];//服务器状态
                }
            }else{
                $data['pcyjk'] = '0';
            }
        }else{
            $data['pcyjk'] = '0';
        }
        if($cache->get('appjkd')>=date('Y-m-d H:i:s',time())){//监控端
            $data['appjkd'] =  '在线[正常]';//监控状态
        }else{
            $data['appjkd'] =  '不在线[异常]';//监控状态
        }
        
        if($atad['ds_todaorul']){
            if($cache->get('cehsald')>=date('Y-m-d H:i:s',time())){//通道监控（计划任务）
                $data['cehsald'] =  '在线[正常]';//监控状态
                $data['cehsald2'] = '1';
            }else{
                $data['cehsald'] =  '不在线[异常]';//监控状态
                $data['cehsald2'] = '0';
            }
        }else{
            $data['cehsald'] =  '不在线[异常]';//监控状态
            $data['cehsald2'] = '0';
        }
        
        // 模板变量赋值
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    
    //收银台
    public function jhsyt()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->lock(true)->find();
        
        $jh_ewm = xss(input("jh_ewm"));
        if (!$jh_ewm || $jh_ewm == "") {
            return json($this->getReturn(-1, "请现在生成二维码模板"));
        } 
        $jh_je = xss(input("jh_je"));
        if (!$jh_je || $jh_je == "") {
            $jh_je = null;
        }else{
            $arr = explode("|",$jh_je);
            $arr = array_filter($arr);
            if(count($arr)>6){
                return json($this->getReturn(-1, "预选金额最多设置6个哦！"));
            }
            foreach ($arr as $k=>$v){
                $money = number_format(round($v, 2), 2, ".", "");
                if ($money <= 0 || !is_numeric($v) || !preg_match('/^[0-9.]+$/', $v)) {
                    return json_encode(['code' => 1, 'msg' => "预选金额不合法！"]);
            }
                $arr[$k] = $money;
            }
            $jh_je = implode("|",$arr);
        }
            
        $dsad = array(
            "jh_ewm" => $jh_ewm,//二维码模板
            "jh_mdtx" => xss(input("jh_mdtx")),//门店头像
            "jh_mdcm" => xss(input("jh_mdcm")),//门店名称
            "jh_je" => $jh_je,//预选金额
        );
        $dsad = Db::name("pay_user")->where("id",$data['id'])->update($dsad);
        if($dsad){
            @unlink(Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png');//删除目录图片
            return json($this->getReturn(1, "保存成功"));
        }else{
            return json($this->getReturn(-1, "保存失败"));
        }
    }
    //收银台-下载收银台图片
    public function createPoster()
    {
        if($dernout = $this->dernout()){
            return $dernout;//判断登录状态
        }
        
        $data = Db::table("pay_user")->where("user", Cookie::get(md5(date("Y-m")),md5(date("Y-m-d"))))->find();
        
        $url = 'https://qun.qq.com/qrcode/index?data='.urlencode(http_ssl().http_pay().'/sytpay?pid='.$data['id'].'&key='.base64_encode($data['key']));
        @unlink(Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png');//删除目录图片
        $dsadsa = createPoster($url,$data['jh_ewm'],Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png');//生成二维码收银台
        if($dsadsa){
            return download(Env::get('ROOT_PATH').'/public/static/user/assets/images/png/'.$data['id'].'.png', '收银台.png');
        }else{
            return json($this->getReturn(-1, "保存失败"));
        }
    }
}