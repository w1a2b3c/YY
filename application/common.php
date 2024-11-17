<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */

use think\App;
use app\wenjian\smtp;
use think\facade\Cookie;
use app\wenjian\Template;
use app\wenjian\Pay_Money_Api;

//检测Cookie状态||取余额
function Cookie()
{
    $cache = tohc('Cookie');
    if(!$cache->get('Cookie')){
        $cache->set('Cookie',time()+60*1);
    }else{
        if($cache->get('Cookie')){
            if($cache->get('Cookie')<=time()){
                $cache->set('Cookie',time()+60*1);
                $rows = Db::table("pay_gfg")->where("state", 1)->cursor();
                foreach ($rows as $row){
                    $ree = Db::table("pay_jie")->where("pid", $row['pid'])->where("id",$row['typec_id'])->find();
                    $cls = common($ree['game_dm']);//支付通道路径
                    $json = $cls->Cookie($row);//支付通道数据
                    if($json['code']!=1){
                        Db::name("pay_order")->where("pid",$row["pid"])->where("mid_dm",$row['typec_dm'])->where("mid_id",$row['typec_id'])->where("state", 1)->update(array("state"=>3,"create_time"=>time()));
                        Db::name("pay_gfg")->where("pid", $row["pid"])->where("id", $row['id'])->update(array("bug_data"=>$json['data'],"time"=>time(),"land_lx"=>0,"state"=>0));
                        $ds = Db::table("pay_sz")->find(1);
                        $res = Db::table('pay_user')->where('id',$row["pid"])->cache(120)->find();
                        $sub= $ds['web_name'].'-'.$row['typec_name'].'-'.$json['data'].'通知';
                        $msg = "尊敬的 " .$res['user']. " 商户<br>您正在使用的 ".$row['typec_name']." | 备注：" .$row['username']." | MID：" .$row['mid'].'<br>原因：'.$json['data']."<br>建议您尽快去".$ds['web_name']."平台更新收款账号配置。<br>以免影响您的监控收款正常使用。";
                        fmail($res['email'], $sub, $msg, $res);
                    }
                } 
            }
        }
    }
}
function param($id,$param)
{
    $json = $id;
    $json = json_decode($json,true);
    return base64_decode($json[$param]);
}
//抓取ck拼装
function getcookie($head=0)
{
error_reporting(0);    
if(empty($head)){
return false;
}
$preg = '/Set-Cookie:\ (.*?);/';//获取
preg_match_all($preg,$head,$view);
$v = $view[1];
for($i=0;$i<count($v);$i++){
$string .= $v[$i].';';
}
return $string;
}
//邮箱配置//发送账号，发送通知，发送内容
function fmail($to, $sub, $msg, $user='')
{
    if($user){
        if($user['web_mail']==1){
            if ($user['mail_smtp']and$user['mail_port']and$user['mail_name']and$user['mail_pwd']){
                return false;//邮箱账号检测空白
            }
            $From      = $user['mail_name'];
            $Host      = $user['mail_smtp'];
            $Port      = $user['mail_port'];
            $SMTPAuth  = 1;
            $Username  = $user['mail_name'];
            $Password  = $user['mail_pwd'];
            $Nickname  = $user['web_name'];
            $SSL       = $user['mail_port'] == 465 ? 1 : 0;
            $msg = msg($sub,$msg);
            $mail      = new SMTP($Host, $Port, $SMTPAuth, $Username, $Password, $SSL);
            $mail->att = array();
            if ($mail->send($to, $From, $sub, $msg, $Nickname)){
                return true;//邮件发送成功，请注意查收
            }else{
                return false;//对不起，邮件发送失败！请检查邮箱填写是否有误
            }
        }else{
            return false;//邮箱通知关闭状态，请联系客服开启
        }
    }else{
        $ds = Db::table("pay_sz")->find(1);
        if($ds['web_mail']==1){
            $From      = $ds['mail_name'];
            $Host      = $ds['mail_smtp'];
            $Port      = $ds['mail_port'];
            $SMTPAuth  = 1;
            $Username  = $ds['mail_name'];
            $Password  = $ds['mail_pwd'];
            $Nickname  = $ds['web_name'];
            $SSL       = $ds['mail_port'] == 465 ? 1 : 0;
            $msg = msg($sub,$msg);
            $mail      = new SMTP($Host, $Port, $SMTPAuth, $Username, $Password, $SSL);
            $mail->att = array();
            if ($mail->send($to, $From, $sub, $msg, $Nickname)){
                return true;//邮件发送成功，请注意查收
            }else{
                return false;//对不起，邮件发送失败！请检查邮箱填写是否有误
            }
        }else{
            return false;//邮箱通知关闭状态，请联系客服开启
        }
    }
} 
//生成随机字符串验证码 
function str_rand($codeLen)
{
 $str="abcdefghijkmnpqrstuvwxyz0123456789ABCDEFGHIGKLMNPQRSTUVWXYZ";    
 $rand="";
 for ($i = 0; $i < $codeLen; $i++) {
  //如：随机数为30 则：$str[30]
  $rand .= $str[mt_rand(0, strlen($str)-1)];
 }
 return $rand;
}
//判断客户端是微信、支付宝、QQ
function judgment(){
    //判断是不是QQ浏览器
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser') !== false){
        return false;//QQ浏览器外
    }
    //判断是不是微信
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
        return 'weixin';
    }    
    //判断是不是支付宝
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
        return 'alipay';
    }    
    //判断是不是QQ
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
        return 'qq';
    }
    //哪个都不是
    return false;
}
//检测是否|微信|支付宝|QQ|内外访问
function ds_zhto($data)
{
    if($data['type']=='weixin'){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false) {
            return true;//微信内
        } else {
            return false;//微信外
        }
    }
    if($data['type']=='qq'){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient/') !== false){
            return true;//QQ内
		}else{
			return false;//QQ外
	    }
    }
    if($data['type']=='alipay'){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/') !== false){
            return true;//支付宝外
        }else{
            return false;//支付宝内
        }
    }
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser/') !== false){
        return false;//QQ浏览器外
    }
}
 //检测自定义转跳内外访问
function zt_zhto($type)
{
    if($type=='weixin'){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/') !== false) {
            return true;//微信外
        } else {
            return false;//微信内
        }
    }
    if($type=='qq'){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false){
            return true;//QQ内
		}else{
			return false;//QQ外
	    }
    }
    if($type=='alipay'){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient/') !== false){
            return true;//支付宝外
        }else{
            return false;//支付宝内
        }
    }
    return true;
}
//检测是否手机访问
function isMobile()
{
    $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
    $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
    $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');
    $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
    CheckSubstrs($mobile_token_list,$useragent);
    if ($found_mobile){
        return true;
    }else{
        return false;
    }
}
//MD5加密
function string2secret($str,$key)
{
 $td = mcrypt_module_open(MCRYPT_DES,'','ecb','');
 $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
 $ks = mcrypt_enc_get_key_size($td);
 $key = substr(md5($key), 0, $ks);
 mcrypt_generic_init($td, $key, $iv);
 $secret = mcrypt_generic($td, $str);
 mcrypt_generic_deinit($td);
 mcrypt_module_close($td);
 return $secret;
}
//MD5解密
function secret2string($sec,$key)
{
 $td = mcrypt_module_open(MCRYPT_DES,'','ecb','');
 $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
 $ks = mcrypt_enc_get_key_size($td);
 $key = substr(md5($key), 0, $ks);
 mcrypt_generic_init($td, $key, $iv);
 $string = mdecrypt_generic($td, $sec);
 mcrypt_generic_deinit($td);
 mcrypt_module_close($td);
 return trim($string);
}
//检测是否手机访问工具
function CheckSubstrs($substrs,$text)
{
    foreach($substrs as $substr)
        if(false!==strpos($text,$substr)){
            return true;
        }
        return false;
}
//通道缓存
function tohc($type)
{
    return cache(array('type'=>'file','expire'=>0,'prefix'=>$type));
}
function msg($title,$code){//邮箱模板发送内容//发送通知，发送内容
    $conf = Db::name("pay_sz")->find();
    if($conf['mail_email'] == "1"){
        return '<div style="background-color:#ECECEC; padding: 35px;"><table cellpadding="0" align="center"style="width: 600px; margin: 0px auto; text-align: left; position: relative; border-top-left-radius: 5px; border-top-right-radius: 5px; border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; font-size: 14px; font-family:微软雅黑, 黑体; line-height: 1.5; box-shadow: rgb(153, 153, 153) 0px 0px 5px; border-collapse: collapse; background-position: initial initial; background-repeat: initial initial;background:#fff;"><tbody><tr><th valign="middle"
                style="height: 25px; line-height: 25px; padding: 15px 35px; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #42a3d3; background-color: #49bcff; border-top-left-radius: 5px; border-top-right-radius: 5px; border-bottom-right-radius: 0px; border-bottom-left-radius: 0px;"><font face="微软雅黑" size="5" style="color: rgb(255, 255, 255); ">'.$title.' （'.$conf['web_name'].'）</font></th></tr><tr><td><div style="padding:25px 35px 40px; background-color:#fff;"><h2 style="margin: 5px 0px; "><font color="#333333" style="line-height: 20px; "><font style="line-height: 22px; " size="4">'.$code.'</h2><div style="width:700px;margin:0 auto;"><div style="padding:10px 10px 0;border-top:1px solid #ccc;color:#747474;margin-bottom:20px;line-height:1.3em;font-size:12px;">
                            <p>此为系统邮件，请勿回复<br>请保管好您的邮箱，避免账号被他人盗用</p></div></div></div></td></tr></tbody></table></div>';
    }elseif($conf['mail_email'] == "2"){
        return'<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>'.$conf['web_name'].'-邮件通知</title><meta http-equiv="content-type" content="text/html;charset=utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><div style="width:90%; margin:0 auto; background:#fafafa;margin:50px auto;padding:0px;border:0px;outline:0px;border-radius:6px;max-width:696px;overflow:hidden;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style=""><tbody><tr bgcolor="#35bdbc" height="80"><td width="580" style="line-height:10px;" align="center"><span style="font-size:24px; color:#ffffff;"></span><br><span style="color:#ffffff; font-size:36px;"><span>'.$title.'</span></span></td></tr></tbody></table>
        &nbsp;&nbsp;<table cellpadding="0" cellspacing="0" border="0" align="center" width="86%" style=""><tbody><tr height="20"></tr><tr><td style=""><img style="margin-right:5px; " src="http://www.jiankongbao.com/img/report/mark.png"><b>操作通知</b></td></tr><tr height="20"></tr></tbody></table><table cellpadding="0" cellspacing="0" border="0" align="center" width="86%" style="background-color: white;"><tbody><tr height="20"></tr><tr><td style="" align="center"><span style="font-size:24px; color:#000;">' . $code . '</span></td></tr><tr height="20"></tr></tbody></table><table width="100%" cellspacing="0" border="0" cellpadding="0" style="background-color: #fafafa;"><tbody><tr><td width="20"></td><td><table width="100%" cellspacing="0" border="0" cellpadding="0"><tbody><tr><td height="20"></td></tr><tr></tr><tr><td height="20"></td></tr></tbody></table></td><td width="20"></td></tr></tbody></table><table width="100%" cellspacing="0" border="0" cellpadding="0" style="background-color: #35bdbc;"><tbody><tr><td width="20"></td><td><table width="100%" cellspacing="0" border="0" cellpadding="0"><tbody><tr><td height="20"></td></tr><tr><td style="font-size:13px;text-align:center;">Copyright &nbsp;© <strong>'.$conf['web_name'].'</strong> 版权所有</td></tr><tr><td height="20"></td></tr></tbody></table></td><td width="20"></td></tr></tbody></table></div></body></html>';
    }elseif($conf['mail_email'] == "3"){
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html lang="en"><head><meta charset="UTF-8"><title>邮件提醒</title><meta name="viewport" content="width=device-width, initial-scale=1.0" /></head><body style="margin: 0; padding: 0;"><table align="center" border="0" cellpadding="0" cellspacing="0" width="90%" style="border-collapse: collapse;"><tr><td><div style="margin: 20px;text-align: center;margin-top: 50px"><h1>'.$title.'</h1></div></td></tr><tr><td><div style="border: #36649d 1px dashed;margin: 30px;padding: 20px"><label style="font-size: 22px;color: #36649d;font-weight: bold">'.$code.'</label></div></td></tr><tr><td><div style="margin: 40px"><p style="color:red;font-size: 14px ">（系统自动发送的邮件，请勿回复。）</p></div></td></tr><tr><td><div align="right" style="margin: 40px;border-top: solid 1px gray" id="bottomTime"><p style="margin-right: 20px"> '.$conf['web_name'].' 团队</p></div></td></tr></table></body></html>';  
    }
}
//判断还剩多少时间
function timer($date)
{
    $t = strtotime($date)-time();//单位"秒"
    $arr = [];
    $day = intval($t/86400);//天
    $hour = intval((($t/86400)-$day)*24);//小时
    $minute = intval( (((($t/86400)-$day)*24)-$hour)*60 );//分钟
    $second = intval(((((((($t/86400)-$day)*24)-$hour)*60)-$minute)*60));//秒
    return $day.'天'.$hour.'小时'.$minute.'分'.$second.'秒';
}
//取出左边文本
function gezumir($str, $leftStr)
{
    $left = explode($leftStr, $str);
 return $left[0];
}
//取出右边文本
function getbstr($str, $leftStr)
{
    $left = strpos($str, $leftStr);
 return substr($str, $left + strlen($leftStr));
}
//取中间文本
function getSubstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    $right = strpos($str, $rightStr,$left);
    if($left < 0 or $right < $left) return '';
    return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
}
//金额小数点判断   
function del0($s)
{   
    $s = trim(strval($s));   
    if (preg_match('#^-?\d+?\.0+$#', $s)) {   
        return preg_replace('#^(-?\d+?)\.0+$#','$1',$s);   
    }    
    if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {   
        return preg_replace('#^(-?\d+\.[0-9]+?)0+$#','$1',$s);   
    }   
    return $s;   
}
//检测过期订单删除
function fdhdd()
{
    Db::table('pay_order')->where("state",3)->whereTime('create_time', 'yesterday')->delete();//昨天订单
}
//检测过期订单
function closeEndOrder()
{
    fdhdd();//检测过期订单删除
    jiankong();//检测监控状态
    Cookie();//检测Cookie状态
    jkzfbqq();//云监控和云端推送监控回调
    $rows = Db::table("pay_order")->where("state",1)->cursor();
    foreach ($rows as $rew){
        $res = Db::name("pay_user")->where("id",$rew['pid'])->find();
        if($rew['create_date']<=time()-60*$res['bug_close']){
            Db::name("pay_order")->where("pid",$rew['pid'])->where("order_id",$rew['order_id'])->update(array("state"=>3,"create_time"=>time()));
            $rows = Db::table("pay_order")->where("create_time",time())->cursor();
            foreach ($rows as $row){
                Db::name("pay_tmp")->where("pid",$row['pid'])->where("order_id",$row['order_id'])->delete();
            }
        }
        $ryys = Db::table("pay_tmp")->cursor();
        foreach ($ryys as $row){
            $re = Db::name("pay_order")->where("order_id",$row['order_id'])->whereBetween("state",[2,3])->find();
            if ($re){
                Db::name("pay_tmp")->where("pid",$row['pid'])->where("order_id",$row['order_id'])->delete();
            }
        }
    }
}
//全部通道-检测开关
function qbtdjckg($user)
{
    $re = Db::name("pay_user")->where("user",$user)->find();
    $rows = Db::table("pay_jie")->where("pid",$re['id'])->cursor();
    foreach ($rows as $row){
        $ree = Db::name("pay_qrcode")->where("game_dm",$row['game_dm'])->find();
        if(!$ree){
            Db::table('pay_gfg')->where("typec_id",$row['id'])->delete();//通道账号删除
            Db::table('pay_jie')->where("id",$row['id'])->delete();//通道删除
        }else{
            continue;
        }
    }
    $rows = Db::table("pay_qrcode")->cursor();
    foreach ($rows as $row){
        $ree = Db::table("pay_jie")->where("pid",$re['id'])->where("game_dm",$row['game_dm'])->find();
        if($ree){
            if($row['state']==0){
                $ree = Db::table("pay_jie")->where("pid",$re['id'])->where("game_dm",$row['game_dm'])->where("state",1)->find();
                if($ree){
                    $data = array(
                        "state" => 0,//通道状态
                        "game_img" => $row['game_img'],//通道图标
                        "game_name" => $row['game_name'],//通道名称
                    );
                    Db::name("pay_jie")->where("pid",$re['id'])->where("id",$ree['id'])->update($data);//通道禁止
                }
                $land_count = Db::table('pay_gfg')->where("pid",$re['id'])->where("typec_id",$ree['id'])->count();//账号数量
                Db::name("pay_jie")->where("pid",$re['id'])->where("id",$ree['id'])->update(array("land_count"=>$land_count));
            }else{
                $land_count = Db::table('pay_gfg')->where("pid",$re['id'])->where("typec_id",$ree['id'])->count();//账号数量
                Db::name("pay_jie")->where("pid",$re['id'])->where("id",$ree['id'])->update(array("land_count"=>$land_count));
                $ree = Db::table("pay_jie")->where("pid",$re['id'])->where("game_dm",$row['game_dm'])->where("state",0)->find();
                if($ree){
                    $data = array(
                        "state" => 1,//通道状态
                        "game_img" => $row['game_img'],//通道图标
                        "game_name" => $row['game_name'],//通道名称
                    );
                    Db::name("pay_jie")->where("pid",$re['id'])->where("id",$ree['id'])->update($data);//通道开启
                }
            }
        }else{
            $data = array(
                "pid" => $re['id'],//商户PID
                "game_dm" => $row['game_dm'],//通道标识
                "game_img" => $row['game_img'],//通道图标
                "game_name" => $row['game_name'],//通道名称
            );
            Db::name("pay_jie")->insert($data);//通道添加
        }
    }
}
//检测余额充值
function yecz()
{
    $price = Db::table("pay_price")->where("state", 1)->cursor();
    if($price){//余额充值
        foreach ($price as $row){
            $re = Db::name("pay_user")->where("user",$row['user'])->find();
            if($re){
                $money = $re['money']+$row['money'];
                Db::name("pay_user")->where("id", $re['id'])->update(array("money"=>$money));//充值余额
                Db::name("pay_price")->where("id", $row["id"])->update(array("state"=>2));//充值到账
            }else{    
                Db::name("pay_price")->where("id", $row["id"])->update(array("state"=>3));//充值不到账：商户不存在
            }
        }
    } 
}
//通道账号金额统计
function tdzhtj($tada)
{
    $z_pay =  Db::table('pay_order')->where("pid",$tada['pid'])->where("mid",$tada["mid"])->where("state", 2)->sum("price");//总充值
    $jr_pay =  Db::table('pay_order') ->where("pid",$tada['pid'])->where("mid",$tada["mid"])->where("state", 2)->whereTime('create_date', 'today')->sum("price");//今日充值
    $zr_pay =  Db::table('pay_order') ->where("pid",$tada['pid'])->where("mid",$tada["mid"])->where("state", 2)->whereTime('create_date', 'yesterday')->sum("price");//昨日充值
    Db::name("pay_gfg")->where("pid",$tada["pid"])->where("mid",$tada["mid"])->update(array("z_pay"=>$z_pay,"jr_pay"=>$jr_pay,"zr_pay"=>$zr_pay));
    
    $land_count = Db::table('pay_gfg')->where("pid",$tada["pid"])->where("typec_id",$tada['mid_id'])->count();//账号数量
    $jr = Db::table('pay_gfg')->where("pid",$tada["pid"])->where("typec_id",$tada['mid_id'])->sum("jr_pay");//今日收入
    $zr = Db::table('pay_gfg')->where("pid",$tada["pid"])->where("typec_id",$tada['mid_id'])->sum("zr_pay");//昨日收入
    Db::name("pay_jie")->where("pid",$tada["pid"])->where("id",$tada['mid_id'])->update(array("land_count"=>$land_count,"jr_pay"=>$jr,"zr_pay"=>$zr));
}
//检测监控状态
function jiankong()
{
     $rows = Db::table("pay_gfg")->where("jkstate",1)->where("state",1)->cursor();
     foreach ($rows as $row){
         if ((time()-$row['lastheart'])>120){//2分钟
             Db::name("pay_gfg")->where("pid",$row['pid'])->update(array("jkstate"=>0));
         }
     }
     $rows = Db::table("pay_order")->where("state",3)->cursor();
     foreach ($rows as $row){
         if($row['create_date'] > strtotime(date("Y-m-d H:i:s",strtotime("+1 day")))){//保留今天的过期订单
             Db::table('pay_order')->where("id",$row['id'])->where("state",3)->delete();
         }
     }
}
//获取当前SSL
function http_ssl()
{
    $atad = Db::name("pay_sz")->find();
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $url =  $http_type;
    return $url;
}
//获取当前域名
function http_type()
{
    $url =  $_SERVER['HTTP_HOST']."/";
    return $url;
}
//获取支付域名
function http_pay()
{
    $atad = Db::name("pay_sz")->find();
    $url =  (config('url_api')=='' ? '请设置支付域名'."/":config('url_api'));//支付网关
    return $url;
}
//获取当前完整域名
function http_url()
{
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $url = $http_type.$_SERVER['HTTP_HOST'];
    return $url;
}
//获取回调结果success
function success($str)
{
    $words = ['ok','OK','success','SUCCESS','yes','YES','成功'];//判断是否包含这些字符
    foreach ($words as $word) {
        if (mb_strpos($str, $word) !== false) {
            return true;
        }else{
            continue;
        }
    }
}
//获取链接域名
function getUrlHost($url)
{
    // 判断请求类型
    $httpType = 'http://';
    if(strpos($url,'https://') !== false){
        $httpType = 'https://';
    }
    //去掉互联网协议请求类型
    $urlStr = str_replace($httpType,"",$url);  
    //以斜杠拆分字符串成数组
    $urlArr = explode("/",$urlStr);
    //取第一个斜杠前的字符
    return $urlArr[0];
}
//判断域名模式
function HQWuf($url,$url_api,$url_jk)
{
    if($url==$url_api || $url==$url_jk || $url_api==$url || $url_api==$url_jk || $url_jk==$url || $url_jk==$url_api){
        return false;
    }else{
        return true;
    }
}
function do_notify($url){
	$ch=curl_init($url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1');
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$return=curl_exec($ch);
	curl_close($ch);
	return $return;
}
//手续费算法
function money($money,$data)
{
if($data['type']==1){
    $taocan = Db::table("pay_taocan")->where("id", $data['taocan'])->find();
    if($taocan){
        if($data['taocan_time']>=time()||$data['taocan_time']==0){
            $fee = $taocan['taocan_quanx1'];
        }else{
            Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
            $fee = $data['fee'];
        }
    }else{
        Db::name("pay_user")->where("id", $data['id'])->update(array("type"=>0,"taocan"=>0,"taocan_time"=>0));
        $fee = $data['fee'];
    }
}else if($data['type']==2){
    $fee = '0';
}else{
    $fee = $data['fee'];
}  
//$money = 100; // 总金额
//$fee = 5; // 扣除手续费百分之5
$newtotal = ($fee / 100) * $money;
$money = $money - $newtotal;
return $newtotal;
}
//云端-请求json
function ydqu($data)
{
    $cache = tohc('ydqu');
    $post = array(
        'ym' => getUrlHost(config('url')),
        'sq' => $cache->get('sq'),
        'data' => $data,//请求功能
    );
    return $post;
}
//云端和云监控回调
function cehsald()
{
    $cache = tohc('cehsald');
    $sz = Db::table("pay_sz")->find(1);
    
    $m = $sz['yd_sj'];
    if(!$cache->get('mq_gzfbyjk')){//云监控(支付宝)
        $cache->set('mq_gzfbyjk',time()+$m);
    }else{
        if($cache->get('mq_gzfbyjk')){
            if($cache->get('mq_gzfbyjk')<=time()){
                $cache->set('mq_gzfbyjk',time()+$m);
                yjkzfbqq('mq_gzfbyjk');
            }
        }
    }
    
    $m = $sz['yd_sj'];
    if(!$cache->get('mq_gqqyjk')){//云监控(Q Q)
        $cache->set('mq_gqqyjk',time()+$m);
    }else{
        if($cache->get('mq_gqqyjk')){
            if($cache->get('mq_gqqyjk')<=time()){
                $cache->set('mq_gqqyjk',time()+$m);
                yjkzfbqq('mq_gqqyjk');
            }
        }
    }
    
    $m = $sz['yd_sj'];
    if(!$cache->get('mq_gzfbyd')){//云端(支付宝)
        $cache->set('mq_gzfbyd',time()+$m);
    }else{
        if($cache->get('mq_gzfbyd')){
            if($cache->get('mq_gzfbyd')<=time()){
                $cache->set('mq_gzfbyd',time()+$m);
                ydzfbqq('mq_gzfbyd');
            }
        }
    }
    
    $m = $sz['yd_sj'];
    if(!$cache->get('mq_gqqyd')){//云端(Q Q)
        $cache->set('mq_gqqyd',time()+$m);
    }else{
        if($cache->get('mq_gqqyd')){
            if($cache->get('mq_gqqyd')<=time()){
                $cache->set('mq_gqqyd',time()+$m);
                ydzfbqq('mq_gqqyd');
            }
        }
    }
    
    $m = $sz['yd_sj'];
    if(!$cache->get('mq_gvxyd')){//云端(微信)
        $cache->set('mq_gvxyd',time()+$m);
    }else{
        if($cache->get('mq_gvxyd')){
            if($cache->get('mq_gvxyd')<=time()){
                $cache->set('mq_gvxyd',time()+$m);
                ydzfbqq('mq_gvxyd');
            }
        }
    }
}
//云监控账单
function yjkzfbqq($game_dm)
{
    $sz = Db::table("pay_sz")->find(1);
    $cache = tohc('Heart');
    if($cache->get('cehsald')>=date('Y-m-d H:i:s',time())){
        $rows = Db::table("pay_order")->where("mid_dm",$game_dm)->where("state",1)->distinct(true)->field('pid,mid_dm,mid')->cursor();
        foreach ($rows as $row){
            $rows = Db::table("pay_gfg")->where("pid",$row['pid'])->where("typec_dm",$row['mid_dm'])->where("mid",$row['mid'])->where("land_lx",1)->where("state",1)->cursor();
            foreach ($rows as $row){
                $ros = Db::table("pay_jie")->where("id",$row['typec_id'])->find();
                if($ros['game_dm']!=$game_dm){
                    continue;
                }
                $post = array(
                    'game_dm' => $ros['game_dm'],//通道标识
                    'type' => 'cehsald',//传出类型:支付类型:exec||监控类型:cehsald
                    'post' => array(//传出数据
                                'uid' => $row['zhanghao'],//账号UID
                                'zfbsb' => $row['ck'],//支付宝设备
                                'ck' => $row['ck'],//账号Cookie
                                'wxsb' => $row['zhanghao'],//微信设备
                                'qq' => $row['zhanghao'],//QQ账号
                                'qqsb' => $row['ck'],//QQ设备
                                'zhanghao' => $row['zhanghao'],//通道账号
                                'ck' => $row['ck'],//通道CK数据
                                'bf' => $row['bf'],//通道备份数据
                                'bfbf' => $row['bfbf'],//通道备份数据
                                'bfbfbf' => $row['bfbfbf'],//通道备份数据
                            )
                );
                $cache = tohc('Heart');
                $ret = curls($cache->get('ds_yjkrul'),http_build_query(ydqu($post)));
                $json = json_decode($ret,TRUE);
                if($ret){
                    if($json['code']=="1"){
                        $cache = tohc($ros['game_dm']);//通道缓存
                        if($cache->get($json['data']['uid'])==$ret){
                            continue;
                        }else{
                            $jsou = json_decode($cache->get($json['data']['uid']),TRUE);
                            if($jsou){
                                if($jsou['data']['data']){
                                    foreach ($json['data']['data'] as $item){
                                        if(Db::table("pay_order")->where('pay_id','in',[$item['order_id'],$item['pay_id']])->where("state",2)->find()){
                                            continue;
                                        }
                                        $list[] = $item;
                                    }
                                    $ret = array("code"=>$json['code'],"msg"=>$json['msg'],"data"=>array("uid"=>$json['data']['uid'],"data"=>$list));
                                    $cache->set($json['data']['uid'],json_encode($ret,JSON_UNESCAPED_UNICODE ));// 将数据插入新缓存文件中
                                }else{
                                    $cache->set($json['data']['uid'],$ret);// 将数据插入新缓存文件中
                                }
                            }else{
                                $cache->set($json['data']['uid'],$ret);// 将数据插入新缓存文件中
                            }
                        }
                    }else{
                        if($json['msg']){
                            Db::name("pay_order")->where("pid",$row["pid"])->where("mid_dm",$row['typec_dm'])->where("mid_id",$row['typec_id'])->where("state", 1)->update(array("state"=>3,"create_time"=>time()));
                            Db::name("pay_gfg")->where("pid",$row["pid"])->where("id",$row['id'])->update(array("land_lx"=>0));
                            $res = Db::table('pay_user')->where('id',$row["pid"])->find();
                            $sub = $sz['web_name'].'-'.$row['typec_name'].'-'.$json['msg'].'通知';
                            $msg = "尊敬的 " .$res['user']. " 商户<br>您正在使用的 ".$row['typec_name']." | 备注：" .$row['username']." | MID：" .$row['mid'].'<br>原因：'.$json['msg']."<br>该账号目前在线状态".$sz['web_name']."平台系统自动关闭使用，如需使用手动开启轮询。<br>".$sz['web_name']."平台系统自动将未支付的订单设置为超时状态避免客户付款。<br>以免影响您的客户正在付款导致不正常监控回调。<br>假如已有客户已付款，订单没有正常回调，请及时补单。";
                            fmail($res['email'], $sub, $msg, $res);
                        }else{
                            continue;
                        }
                    }
                }else{
                    continue;
                }
            }
        }
    }
}
//云端账单
function ydzfbqq($game_dm)
{
    $sz = Db::table("pay_sz")->find(1);
    $cache = tohc('Heart');
    if($cache->get('cehsald')>=date('Y-m-d H:i:s',time())){
        $rows = Db::table("pay_order")->where("mid_dm",$game_dm)->where("state",1)->distinct(true)->field('pid,mid_dm,mid')->cursor();
        foreach ($rows as $row){
            $rows = Db::table("pay_gfg")->where("pid",$row['pid'])->where("typec_dm",$row['mid_dm'])->where("mid",$row['mid'])->where("land_lx",1)->where("state",1)->cursor();
            foreach ($rows as $row){
                $ros = Db::table("pay_jie")->where("id",$row['typec_id'])->find();
                if($ros['game_dm']!=$game_dm){
                    continue;
                }
                $post = array(
                    'game_dm' => $ros['game_dm'],//通道标识
                    'type' => 'cehsald',//传出类型:支付类型:exec||监控类型:cehsald
                    'post' => array(//传出数据
                                'uid' => $row['zhanghao'],//账号UID
                                'zfbsb' => $row['ck'],//支付宝设备
                                'ck' => $row['ck'],//账号Cookie
                                'wxsb' => $row['zhanghao'],//微信设备
                                'qq' => $row['zhanghao'],//QQ账号
                                'qqsb' => $row['ck'],//QQ设备
                                'zhanghao' => $row['zhanghao'],//通道账号
                                'ck' => $row['ck'],//通道CK数据
                                'bf' => $row['bf'],//通道备份数据
                                'bfbf' => $row['bfbf'],//通道备份数据
                                'bfbfbf' => $row['bfbfbf'],//通道备份数据
                            )
                );
                $cache = tohc('Heart');
                $ret = curls($cache->get('ds_todaorul'),http_build_query(ydqu($post)));
                $ret = strtr($ret,['\n' => '']);
                $json = json_decode($ret,TRUE);
                if($ret){
                    if($json['code']=="1"){
                        $cache = tohc($ros['game_dm']);//通道缓存
                        if($cache->get($json['data']['uid'])==$ret){
                            continue;
                        }else{
                            $jsou = json_decode($cache->get($json['data']['uid']),TRUE);
                            if($jsou){
                                if($jsou['data']['data']){
                                    foreach ($json['data']['data'] as $item){
                                        if(Db::table("pay_order")->where('pay_id','in',[$item['order_id'],$item['pay_id']])->where("state",2)->find()){
                                            continue;
                                        }
                                        $list[] = $item;
                                    }
                                    $ret = array("code"=>$json['code'],"msg"=>$json['msg'],"data"=>array("uid"=>$json['data']['uid'],"data"=>$list));
                                    $cache->set($json['data']['uid'],json_encode($ret,JSON_UNESCAPED_UNICODE ));// 将数据插入新缓存文件中
                                }else{
                                    $cache->set($json['data']['uid'],$ret);// 将数据插入新缓存文件中
                                }
                            }else{
                                $cache->set($json['data']['uid'],$ret);// 将数据插入新缓存文件中
                            }
                        }
                    }else{
                        if($json['msg']){
                            Db::name("pay_order")->where("pid",$row["pid"])->where("mid_dm",$row['typec_dm'])->where("mid_id",$row['typec_id'])->where("state", 1)->update(array("state"=>3,"create_time"=>time()));
                            Db::name("pay_gfg")->where("pid",$row["pid"])->where("id",$row['id'])->update(array("land_lx"=>0));
                            $res = Db::table('pay_user')->where('id',$row["pid"])->find();
                            $sub = $sz['web_name'].'-'.$row['typec_name'].'-'.$json['msg'].'通知';
                            $msg = "尊敬的 " .$res['user']. " 商户<br>您正在使用的 ".$row['typec_name']." | 备注：" .$row['username']." | MID：" .$row['mid'].'<br>原因：'.$json['msg']."<br>该账号目前在线状态".$sz['web_name']."平台系统自动关闭使用，如需使用手动开启轮询。<br>".$sz['web_name']."平台系统自动将未支付的订单设置为超时状态避免客户付款。<br>以免影响您的客户正在付款导致不正常监控回调。<br>假如已有客户已付款，订单没有正常回调，请及时补单。";
                            fmail($res['email'], $sub, $msg, $res);
                        }else{
                            continue;
                        }
                    }
                }else{
                    continue;
                }
            }
        }
    }
}
//云监控和云端推送监控回调
function jkzfbqq()
{
    cehsald();//云端和云监控回调
    $rows = Db::table("pay_order")->where("state",1)->select();
    foreach ($rows as $row){
        $ree = Db::table("pay_jie")->where("pid",$row['pid'])->where("id",$row['mid_id'])->find();
        if($ree['game_dm']!='mq_gzfbyjk' and $ree['game_dm']!='mq_gqqyjk' and $ree['game_dm']!='mq_gqqyd' and $ree['game_dm']!='mq_gzfbyd' and $ree['game_dm']!='mq_gvxyd'){//云监控(支付宝)||云监控(Q Q)||云端(Q Q)||云端(支付宝)||云端(微信)
            continue;
        }
        $cls = common($ree['game_dm']);//支付通道路径
        $run = $cls->cehsald($row);//支付通道数据
        if($run['code']==1){
            $eer = Db::table("pay_gfg")->where("pid", $row['pid'])->where("mid",$row['mid'])->find();
            $data = Db::table("pay_user")->where("id", $row['pid'])->find();
            url_notify($eer,$row,$data);
        }
    }
}
//云端和云监控过期1分钟内补单回调
function bdcehsald($game_dm)
{ 
    $sz = Db::name("pay_sz")->find();
    $rows = Db::table("pay_order")->where("mid_dm",$game_dm)->whereTime('create_time','<=',time())->whereTime('create_time','>=',time()-60*1)->where("state",3)->cursor();
    foreach ($rows as $row){
        $ree = Db::name("pay_jie")->where("pid", $row['pid'])->where("id",$row['mid_id'])->find();
        if($ree['game_dm']!='mq_gzfbyjk' and $ree['game_dm']!='mq_gqqyjk' and $ree['game_dm']!='mq_gqqyd' and $ree['game_dm']!='mq_gzfbyd' and $ree['game_dm']!='mq_gvxyd'){//云监控(支付宝)||云监控(Q Q)||云端(Q Q)||云端(支付宝)||云端(微信)
            continue;
        }
        $cls = common($ree['game_dm']);//支付通道路径
        $run = $cls->cehsald($row);//支付通道数据
        if($run['code']==1){
            $eer = Db::name("pay_gfg")->where("pid", $row['pid'])->where("mid",$row['mid'])->find();
            $data = Db::name("pay_user")->where("id", $row['pid'])->find();
            url_notify($eer,$row,$data);
        }
    } 
}
//获取IP
function real_ip($type = 0)
{
     //$type       =  $type ? 1 : 0;
     //static $ip  =   NULL;
     //if ($ip !== NULL) return $ip[$type];
     //if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
         //$ip=$_SERVER['HTTP_X_REAL_IP'];     
     //}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
         //$ip     =   $_SERVER['HTTP_CLIENT_IP'];
     //}elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
         //$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
         //$pos    =   array_search('unknown',$arr);
         //if(false !== $pos) unset($arr[$pos]);
         //$ip     =   trim($arr[0]);
     //}elseif (isset($_SERVER['REMOTE_ADDR'])) {
         //$ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
     //}else{
         //$ip=$_SERVER['REMOTE_ADDR'];
     //}
     // IP地址合法验证
     //$long = sprintf("%u",ip2long($ip));
     //$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
     //return $ip[$type];
     return request()->ip();
}
//服务器地址和响应速度
function checkServerSatatus($ip)
{
	$str = null;
	$ip = str_replace("http://","",$ip);
	$ip = str_replace("/","",$ip);
	$get_ip_city = get_ip_city($ip);
	list($usec, $sec) = explode(' ', microtime());  
    $t1 = ((float)$usec + (float)$sec); 
	$fp = @fsockopen($ip,80,$errno,$errstr,10);
	if (!$fp) {
		return array('code'=>0,'msg'=>'不在线[异常]','IP'=>$get_ip_city['Result']['IP'],'Country'=>$get_ip_city['Result']['Country']);
	} else {
		fclose($fp);
		list($usec, $sec) = explode(' ', microtime());  
        $t2 = ((float)$usec + (float)$sec); 
		$num= $t2-$t1;
		$mis=$num;
		$mis = round((round(($mis * 1000)/4,3) * 10)/2,3);
		$Ping = $mis;
		//if($mis<100)//如果大于800则可能影响速度,所以颜色需要改变
			//$Ping = '<font color=green>'.$mis.'</font>'; //得到连接耗时
		//elseif($mis<150)//如果大于800则可能影响速度,所以颜色需要改变
			//$Ping = '<font color=#ff3399>'.$mis.'</font>'; //得到连接耗时
		//else
			//$Ping = '<font color=red>'.$mis.'</font>'; //得到连接耗时
		return array('code'=>1,'msg'=>'在线[正常]','IP'=>$get_ip_city['Result']['IP'],'Country'=>$get_ip_city['Result']['Country'],'Ping'=>$Ping);
	}
}
//过滤空格换行 
function DeleteHtml($str)
{
    $str = trim($str); //清除字符串两边的空格
    $str = preg_replace("/\t/","",$str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
    $str = preg_replace("/\r\n/","",$str); 
    $str = preg_replace("/\r/","",$str); 
    $str = preg_replace("/\n/","",$str); 
    $str = preg_replace("/ /","",$str);
    $str = preg_replace("/  /","",$str);  //匹配html中的空格
    return trim($str); //返回字符串
}
//IP地址
function get_ip_city($ip='')
{
    if($ip == ''){
        $ip = real_ip();
    }
    $ip = gezumir($ip,":");
    $ip = str_replace("/","",$ip); 
    $ip = str_replace("https:","",$ip); 
    $ip = str_replace("http:","",$ip);
	$url = "http://whois.pconline.com.cn/ipJson.jsp?ip=".$ip."&json=true";
    $json = json_decode(mb_convert_encoding(curl($url),'utf-8','GBK,UTF-8,ASCII'),TRUE);
	$IP_IP = $json['ip'];
	$Country = $json['addr'];
	if($IP_IP!=$ip){
	    $Result=array('IP'=>$IP_IP,'Country'=>$Country);
	}else{
	    $Result=array('IP'=>'无法读取','Country'=>'无法读取');
	}
	$return=array('code'=>1,'msg'=>'成功','Result'=>$Result);
	return $return;
}
//xss攻击过滤     
function xss($val)
{
    $val = preg_replace('/([\\x00-\\x08,\\x0b-\\x0c,\\x0e-\\x19])/', '', $val);
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '12     34567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
        //
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
        //
    }
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);
    $found = true;
    //
    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
         $pattern .= '/i';
         $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2);
            //
         $val = preg_replace($pattern, $replacement, $val);
            //
         if ($val_before == $val) {
                $found = false;
         }
         }
         }
         return $val;
}
//生成推广海报图片（二维码）
function createPoster($url,$jh_ewm,$filename=""){
    switch($jh_ewm){
		case 1:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'190',
                      	'top'=>'360',
                      	'width'=>'520',
                      	'height'=>'520',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 2:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'250',
                      	'top'=>'322',
                      	'width'=>'400',
                      	'height'=>'400',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 3:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'190',
                      	'top'=>'375',
                      	'width'=>'520',
                      	'height'=>'520',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 4:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'175',
                      	'top'=>'605',
                      	'width'=>'550',
                      	'height'=>'550',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 5:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'273',
                      	'top'=>'355',
                      	'width'=>'350',
                      	'height'=>'350',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 6:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'225',
                      	'top'=>'225',
                      	'width'=>'450',
                      	'height'=>'450',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 7:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'233',
                      	'top'=>'205',
                      	'width'=>'450',
                      	'height'=>'450',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 8:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'236',
                      	'top'=>'305',
                      	'width'=>'440',
                      	'height'=>'400',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 9:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'173',
                      	'top'=>'290',
                      	'width'=>'550',
                      	'height'=>'550',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 10:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'173',
                      	'top'=>'125',
                      	'width'=>'550',
                      	'height'=>'500',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 11:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'173',
                      	'top'=>'125',
                      	'width'=>'500',
                      	'height'=>'500',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 12:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'220',
                      	'top'=>'425',
                      	'width'=>'450',
                      	'height'=>'450',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 13:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'400',
                      	'top'=>'595',
                      	'width'=>'420',
                      	'height'=>'420',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 14:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'173',
                      	'top'=>'125',
                      	'width'=>'550',
                      	'height'=>'500',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 15:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'235',
                      	'top'=>'225',
                      	'width'=>'250',
                      	'height'=>'250',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 16:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'220',
                      	'top'=>'190',
                      	'width'=>'450',
                      	'height'=>'390',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 17:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'210',
                      	'top'=>'225',
                      	'width'=>'480',
                      	'height'=>'580',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 18:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'210',
                      	'top'=>'245',
                      	'width'=>'480',
                      	'height'=>'480',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 19:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'253',
                      	'top'=>'410',
                      	'width'=>'494',
                      	'height'=>'470',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 20:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'245',
                      	'top'=>'440',
                      	'width'=>'430',
                      	'height'=>'410',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 21:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'220',
                      	'top'=>'330',
                      	'width'=>'480',
                      	'height'=>'510',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
		case 22:
			$config = array(
          		'image'=>array(
                    array(
                      	'url'=>$url,//二维码
                      	'left'=>'160',
                      	'top'=>'275',
                      	'width'=>'605',
                      	'height'=>'560',
                    )
          		),
          	'background'=>Env::get('ROOT_PATH').'/public/static/user/assets/images/img/'.$jh_ewm.'.png'//背景图
        	);
		break;
	}
    //如果要看报什么错，可以先注释调这个header 
	if(empty($filename)) header("content-type: image/png");
    $imageDefault = array(
        'left'=>0,
        'top'=>0,
        'right'=>0,
        'bottom'=>0,
        'width'=>100,
        'height'=>100,
        'opacity'=>100
    );
    $textDefault =  array(
        'text'=>'',
        'left'=>0,
        'top'=>0,
        'fontSize'=>32,             //字号
        'fontColor'=>'255,255,255', //字体颜色
        'angle'=>0,
    );
    $background = $config['background'];//海报最底层得背景  
    //背景方法
    $backgroundInfo = getimagesize($background);
    $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
    $background = $backgroundFun($background);
    $backgroundWidth = imagesx($background);    //背景宽度
    $backgroundHeight = imagesy($background);   //背景高度
    $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight); 
    $color = imagecolorallocate($imageRes, 0, 0, 0);  
    imagefill($imageRes, 0, 0, $color);  
    // imageColorTransparent($imageRes, $color);    //颜色透明
    imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));  
    //处理了图片
    if(!empty($config['image'])){
        foreach ($config['image'] as $key => $val) {
            $val = array_merge($imageDefault,$val);
 
            $info = getimagesize($val['url']);
            $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
			if($val['stream']){		//如果传的是字符串图像流
				$info = getimagesizefromstring($val['url']);
				$function = 'imagecreatefromstring';
			}
            $res = $function($val['url']);
            $resWidth = $info[0];
            $resHeight = $info[1];
            //建立画板 ，缩放图片至指定尺寸
            $canvas=imagecreatetruecolor($val['width'], $val['height']); 
            imagefill($canvas, 0, 0, $color);
            //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h） 
            imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight); 
            $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
            $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
            //放置图像
            imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度 
        }
    }
    //处理文字
    if(!empty($config['text'])){
        foreach ($config['text'] as $key => $val) {
            $val = array_merge($textDefault,$val);
            list($R,$G,$B) = explode(',', $val['fontColor']);
            $fontColor = imagecolorallocate($imageRes, $R, $G, $B); 
            $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
            $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
            imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']); 
        }
    }
    //生成图片  
    if(!empty($filename)){
        $res = imagejpeg ($imageRes,$filename,90); //保存到本地
		imagedestroy($imageRes);
		if(!$res) return false;
		return $filename;
    }else{
        imagejpeg ($imageRes);			//在浏览器上显示
		imagedestroy($imageRes);
    }  
    exit;
}
//CURL请求
function curls($url,$post_data="",$cookie="",$header="")
{
    $ch = curl_init();
    if($header==""){
        $header = [
            'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
        ];
    }
    $atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // 我们在POST数据哦！
    curl_setopt($ch, CURLOPT_POST, 1);
    // 把post的变量加上
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
// RC4加密和解密  action 1加密 2解密
function encode($data,$action=1)
{
    if ($action == 1){
        return base64_encode(RC4('f5624bac9df1db7b9d6c8fabdb77706d', $data));
    }
    if ($action == 2){
        return iconv("UTF-8", "GB2312//IGNORE", RC4('f5624bac9df1db7b9d6c8fabdb77706d', base64_decode($data)));
    }
}
//RC4加密
function RC4 ($pwd, $data)
{
    $key[] ="";
    $box[] ="";
    $cipher = "";
    $pwd_length = strlen($pwd);
    $data_length = strlen($data);
                
    for ($i = 0; $i < 256; $i++)
    {
        $key[$i] = ord($pwd[$i % $pwd_length]);
        $box[$i] = $i;
    }
                
    for ($j = $i = 0; $i < 256; $i++)
    {
        $j = ($j + $box[$i] + $key[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
                
    for ($a = $j = $i = 0; $i < $data_length; $i++)
    {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
                    
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
                    
        $k = $box[(($box[$a] + $box[$j]) % 256)];
        $cipher .= chr(ord($data[$i]) ^ $k);
    }
                
    return $cipher;
}
//随机IP
function randIP()
{
    $ip_long = array(array("607649792", "608174079"), array("1038614528", "1039007743"), array("1783627776", "1784676351"), array("2035023872", "2035154943"), array("2078801920", "2079064063"), array("        -1950089216", "-1948778497"), array("-1425539072", "-1425014785"), array("-1236271104", "-1235419137"), array("-770113536", "-768606209"), array("-569376768", "-564133889"));
    $rand_key = mt_rand(0, 9);
    $ip = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    return $ip;
}
//CURL-POST代理IP请求
function curl_post($url, $data = array(), $header = array(),$cookie="")
{
    $atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if(!empty($header)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    if($cookie!=""){
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
    curl_setopt($ch, CURLOPT_POST, 1);
    // 把post的变量加上
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
//CURL-GET代理IP请求
function curl_get($url,$header=array(),$cookie="")
{
    $atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //设置选项，包括URL
    if(!empty($header)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    if($cookie!=""){
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
        
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    //打印获得的数据
    return $output;
}
//curl获取协议头
function get_curl_header($url,$post=0,$cookie=0,$header=0,$ua=0,$referer=0,$nobaody=0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $httpheader[] = "Accept:*/*";
    $httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
    $httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
    $httpheader[] = "Connection:close";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if($post){
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if($header){
    	//curl_setopt($ch, CURLOPT_HEADER, TRUE);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    if($cookie)curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    $atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
    if($referer){
    	if($referer==1) curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
    	else curl_setopt($ch, CURLOPT_REFERER, $referer);
    		
    }
    if($ua) curl_setopt($ch, CURLOPT_USERAGENT,$ua);
    else curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1');
    if($nobaody) curl_setopt($ch, CURLOPT_NOBODY,1);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $data=curl_exec($ch);
    curl_setopt($ch, CURLOPT_HEADER,1); 
    $header = curl_exec($ch);
    //$headers = curl_getinfo($ch);
    //print_r($headers);
    $ret['data']=$data;
    $ret['header']=$header;
    curl_close($ch);
    return $ret; 
}
function get_curl2($url,$post=0,$referer=0,$cookie=0,$header=0,$ua=0,$nobaody=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept:*/*";
	$httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
	$httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
	$httpheader[] = "Connection:close";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if($post){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if($header){
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
	}
	if($cookie){
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	$atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
	if($referer){
		if($referer==1){
			curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
		}else{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
	}
	if($ua){
		curl_setopt($ch, CURLOPT_USERAGENT,$ua);
	}else{
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1');
	}
	if($nobaody){
		curl_setopt($ch, CURLOPT_NOBODY,1);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
function get_curl($url,$post=0,$referer=0,$cookie=0,$header=0,$ua=0,$nobaody=0,$ip=0,$split=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$httpheader[] = "Accept:*/*";
	$httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
	$httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
	$httpheader[] = "Connection:close";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);

	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if($post){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}

	if($header){
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
	}
	if($cookie){
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if($referer){
		if($referer==1){
			curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
		}else{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
	}
    if($ip){
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$ip, 'CLIENT-IP:'.$ip));
    }
	if($ua){
		curl_setopt($ch, CURLOPT_USERAGENT,$ua);
	}else{
		curl_setopt($ch, CURLOPT_USERAGENT, 'Stream/1.0.3 (iPhone; iOS 12.4; Scale/2.00)');
	}
	if($nobaody){
		curl_setopt($ch, CURLOPT_NOBODY,1);
	}
	$atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$ret = curl_exec($ch);
    if ($split) {
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($ret, 0, $headerSize);
		$body = substr($ret, $headerSize);
		$ret=array();
		$ret['header']=$header;
		$ret['body']=$body;
	} 
    curl_close($ch);
    return $ret; 
}
function curl($url,$post='',$cookies='',$header='',$referer='') 
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
            
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
    $atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
    if($post!=''){
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36");
    curl_setopt($curl, CURLOPT_REFERER,$referer);
		      curl_setopt($curl, CURLOPT_COOKIE, $cookies);
	if($header){
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
    }
            
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    return $data;
    curl_close($curl);
}
function doCurl($url, $data=array(), $header, $timeout=30,$bankId = "",$POST = true)
{ 
	$ch = curl_init();
	if(substr($url,0,5)=='https'){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
	}
	$atad = Db::name("pay_sz")->find();
    if($atad['ip_hfw']==2){
        $use_agent=false;
    }else{
        $use_agent=true;
    }
    if($use_agent){
        // 代理服务器
        $proxyServer = "http://dyn.horocn.com:50000";
        // 隧道身份信息
        $proxyUser = $atad['ip_User'];//订单号
        $proxyPass = $atad['ip_Pass'];//密码
        // 设置代理服务器
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        
        // 设置隧道验证信息
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxyUser}:{$proxyPass}");
    }
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_POST, $POST);
	curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	//curl_setopt($ch, CURLOPT_AUTOREFERER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);	 
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
		
	if($error=curl_error($ch)){
		//die($error);
	}
		//print_R($info);
	curl_close($ch);
	return $bankId  == "alipayDirectPay" ? $info['redirect_url'] : $response;
}
function sq()
{
    $sq = md5(php_uname().PHP_VERSION.DEFAULT_INCLUDE_PATH.$_SERVER['PROCESSOR_IDENTIFIER'].$_SERVER['SystemRoot'].$_SERVER['SERVER_PORT'].Env::get('root_path').config("url"));
    $cache = tohc('ydqu');
    if(!$cache->get('sq')){
        $cache->set('sq',$sq);
    }
}
/**
 * zip解压方法
 * @param string $filePath 压缩包所在地址 【绝对文件地址】d:/test/123.zip
 * @param string $path 解压路径 【绝对文件目录路径】d:/test
 * @return bool
 */
function unzip($filePath, $path)
{
    if (empty($path) || empty($filePath)) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        $zip->extractTo($path);
        $zip->close();
        return true;
    } else {
        return false;
    }
}
//删除指定文件夹全部内容
function deldir($path)
{
    $handle = opendir($path); //目录资源
    if($handle){
        while( $item = readdir($handle)){
            if($item !="." && $item !=".."){
                if(is_dir("$path/$item")){
                    deldir("$path/$item");
                }else{
                    unlink("$path/$item");
                }
            }
        }
        closedir($handle);
        // 删除文件夹
        if(rmdir($path)){
            return true;
        }else{
            return false;
        }
    }else{
        if(file_exists($path)){
            return unlink($path);
        }else{
            return false;
        }
    }
}
//模板路径显示
function ippublic($type)
{
    $tada = Db::name("pay_mb")->where("type",$type)->where("state",2)->where("shiyong",2)->find();
    if($tada){
        return config("__public__").$type.'/'.$tada['name'];
    }else{
        return false;
    }
}
//渲染路径显示
function iphtml($type)
{
    $tada = Db::name("pay_mb")->where("type",$type)->where("state",2)->where("shiyong",2)->find();
    if($tada){
        if($type=='index'){
            return config("__index_html__").$tada['name'];
        }else if($type=='pay'){
            return config("__pay_html__").$tada['name'];
        }
    }else{
        return false;
    }
}
//支付通道
function common($game_dm)
{
    $cls = app("\\app\common\\".$game_dm."\\controller\\".'Index');
    return $cls;
}
//支付接口
function pay($pay)
{
    $cls = app("\\app\wenjian\pay\\".$pay);
    return $cls;
}
//支付接口-功能判断
function sign($api='',$data1='',$data2='',$type)
{
    if($type=='sign'){
        if($data2['duijei']==1){
            $cls = pay('mr');//默认接口
            return $cls->sign($api,$data1);
        }else if($data2['duijei']==2){
            $cls = pay('yzf');//易支付接口
            return $cls->sign($api,$data1);
        }
    }else if($type=='ybhd'){
        if($data2['duijei']==1){
            $cls = pay('mr');//默认接口
            return $cls->ybhd($api,$data1,$data2['key']);
        }else if($data2['duijei']==2){
            $cls = pay('yzf');//易支付接口
            return $cls->ybhd($api,$data1,$data2['key']);
        }
    }
}
//推送异步回调数据
function huidiao($eer,$row,$data)
{
    $header = array(
        'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
    );
    $url = sign($eer,$row,$data,'ybhd');
    if($eer['method']=='post'){
        $hq = curl_post($row['notify'],$url['notify'],$header);
    }else{
        $hq = curl_get($row['notify'].$url['notify'],$header);
    }
    return $hq;
}

//订单回调判断操作数据
function url_notify($gfg,$order,$user,$type='')
{
    if($type == 1){//手动补单回调模式
        $hq = huidiao($gfg,$order,$user);//推送异步回调数据
        $money = $user['money']-$order['sxf'];
        Db::name("pay_user")->where("id", $user['id'])->update(array("money"=>$money));
        if(success($hq)){
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>2,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_budan"=>1));
            tdzhtj($order);//通道账号金额统计
            return true;//回调成功
        }else{
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>3,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_jieguo"=>$hq,"api_budan"=>1));
            tdzhtj($order);//通道账号金额统计
            return false;//回调失败
        }
    }else if($type == 2){//自动补单回调模式
        $hq = huidiao($gfg,$order,$user);//推送异步回调数据
        if(success($hq)){
            //$money = $user['money']-$order['sxf'];
            //Db::name("pay_user")->where("id", $user['id'])->update(array("money"=>$money));
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>2,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_budan"=>2));
            tdzhtj($order);//通道账号金额统计
            return true;//回调成功
        }else{
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>3,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_jieguo"=>$hq,"api_budan"=>2));
            tdzhtj($order);//通道账号金额统计
            return false;//回调失败
        }
    }else{//自动正常回调模式
        $hq = huidiao($gfg,$order,$user);//推送异步回调数据
        $money = $user['money']-$order['sxf'];
        Db::name("pay_user")->where("id", $user['id'])->update(array("money"=>$money));
        if(success($hq)){
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>2,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_budan"=>0));
            tdzhtj($order);//通道账号金额统计
            return true;//回调成功
        }else{
            Db::name("pay_tmp")->where("pid", $gfg["pid"])->where("order_id",$order['order_id'])->delete();
            Db::name("pay_order")->where("pid", $gfg["pid"])->where("id", $order['id'])->update(array("state"=>2,"api_state"=>3,"pay_date"=>time(),"pay_time"=>time(),"api_jieguo"=>xss($hq),"api_budan"=>0));
            tdzhtj($order);//通道账号金额统计
            return false;//回调失败
        }
    }
}