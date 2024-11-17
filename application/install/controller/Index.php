<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
namespace app\install\controller;
use think\facade\Env;
use think\Controller;

class Index extends Controller
{
    
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
    
    public function index()//在线安装
    {
        if (is_file(Env::get('ROOT_PATH').'install.lock')){
            return response('您已经安装过程序了，无需重复安装！<br/>如需重装系统，请删除根目录 <span style="color:#f00;"> install.lock </span> 的文件');
        }
        $data['PHP_VERSION'] = PHP_VERSION;
        // 模板变量赋值
        $this->assign('data',$data);
        // 或者批量赋值
        return $this->fetch('index');
    }
    
    public function mysql()//数据库核心
    {
        if (is_file(Env::get('ROOT_PATH').'install.lock')) {
            return json($this->getReturn(-1, "你已经安装过该程序，如需重新安装需要先删除 install.lock 文件"));
        }
        $data = input();
        $db_str = <<<php
<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */

// +----------------------------------------------------------------------
// | 数据库设置
// +----------------------------------------------------------------------

// 数据库类型
return [
    // 数据库类型
    'type'            => '{$data['type']}',
    // 服务器地址
    'hostname'        => '{$data['hostname']}',
    // 数据库名
    'database'        => '{$data['database']}',
    // 用户名
    'username'        => '{$data['username']}',
    // 密码
    'password'        => '{$data['password']}',
    // 端口
    'hostport'        => '{$data['hostport']}',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => '',
    // 数据库调试模式
    'debug'           => false,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => true,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
    // Query类
    'query'           => '\\think\\db\\Query',
];
php;
        $link = mysqli_connect($data['hostname'],$data['username'],$data['password'],$data['database'],$data['hostport']);
        if(!$link){
            return json($this->getReturn(-1,"数据库连接失败：".mysqli_connect_error()));
        }else{
            $mysql = file_put_contents(Env::get('ROOT_PATH').'config/database.php',urldecode($db_str));
            if($mysql){
                error_reporting(0);//关闭报错
                // 获取数据库文件路径
                $sql = Env::get('app_path').'install/install.sql';
                if (!is_file($sql)) {
                    return json($this->getReturn(-1, "恭喜你，数据库sql文件不存在"));
                }
                // 设置字符集
                $link->query("SET NAMES 'utf8'");
                // 创建数据库并选中
                $link->select_db($data['database']);
                // 修改表前缀
                $sql_array = preg_split("/;[\r\n]+/", str_replace('pay_','pay_',file_get_contents($sql)));
                // 循环导入数据库
                foreach ($sql_array as $k => $v) {
                    if (!empty($v)) {
                        $link->query($v);
                    }
                }
                $link->close();
                return json($this->getReturn(1, "恭喜你，数据库导入成功"));
            }else{
                return json($this->getReturn(-1, "恭喜你，数据库导入失败"));
            }
        }
    }
    
    public function config()//配置信息
    {
        if (is_file(Env::get('ROOT_PATH').'install.lock')){
            return json($this->getReturn(-1, "你已经安装过该程序，如需重新安装需要先删除 install.lock 文件"));
        }
        $data = input();
        $db_str = <<<php
<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

%24config['url']	    = '{$data['url']}';//主域名

%24config['url_api']	= '{$data['url_api']}';//支付域名

%24config['url_jk']	= '{$data['url_jk']}';//监控域名

%24config['app_url']	= '#';//APP监控端下载链接

%24config['pc_url']	= '#';//PC监控端下载链接

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

?>
php;
        $config = Env::get('ROOT_PATH').'config.php';
        if (!is_file($config)) {
            touch(Env::get('ROOT_PATH').'config.php');
        }
        $mysql = file_put_contents($config, urldecode($db_str));
        if($mysql){
            sq();
            touch(Env::get('ROOT_PATH').'install.lock');
            return json($this->getReturn(1, "安装成功", $data['url']));
        }else{
            return json($this->getReturn(-1, "安装失败"));
        }
    }
}