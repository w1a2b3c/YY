<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\index\controller;
use think\Db;
use think\facade\Env;
use think\Controller;
use app\wenjian\QrcodeServer;

class Index extends Controller
{
    public function dernout()
    {
        if(config('url')!=$_SERVER['HTTP_HOST'] || config('url_api')!=$_SERVER['HTTP_HOST'] || config('url_jk')!=$_SERVER['HTTP_HOST']){
            if(config('url')==config('url_api') || config('url')==config('url_jk') || config('url_api')==config('url') || config('url_api')==config('url_jk') || config('url_jk')==config('url') || config('url_jk')==config('url_api')){
                return response()->code(404)->allowCache(false);
            }
        }
        $data = Db::name("pay_sz")->find();
        if($data['ds_hfw']==2){
            // 模板变量赋值
            $this->assign('data',$data);
            return $this->fetch('../application/index/view/404/index.html');
        }
    }
    
    public function index()//首页
    {
        if($dernout = $this->dernout()){
            return $dernout;//域名配置
        }
        
        if(!config("ms")){
            $api = xss(input());
            if($api){
                $cls = app("\\app\pay\controller\\".'Index');
                $run = $cls->index($api);
                return $run;
            }else{
                $type = 'index';
                if(ippublic($type)){
                    $data = Db::table("pay_sz")->lock(true)->cache(true)->find(1);
                    $data['luji'] = iphtml($type);//模板路径
                    // 模板变量赋值
                    $this->assign('data',$data);
                    // 或者批量赋值
                    return $this->fetch(ippublic($type).'/index.html');
                }else{
                    $this->redirect("user/index");
                }
            }
        }else{
            $type = 'index';
            if(ippublic($type)){
                $data = Db::table("pay_sz")->lock(true)->cache(true)->find(1);
                $data['luji'] = iphtml($type);//模板路径
                // 模板变量赋值
                $this->assign('data',$data);
                // 或者批量赋值
                return $this->fetch(ippublic($type).'/index.html');
            }else{
                $this->redirect("user/index");
            }
        }
    }
    
    //生成二维码
    public function enQrcode($url)
    {
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
}