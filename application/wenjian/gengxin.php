<?php

namespace app\wenjian;

use think\App;
use think\Db;
use think\facade\Env;
use think\facade\Cookie;

class gengxin {

    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }

    // 检测是否有新版本
    public function check_version()
    {
        $sz = Db::name("pay_sz")->find();
        $post = array(
                'game_dm' => 'gengxin',
                'type' => 'gengxin',
                'post' => array(
                        'type' => 'check_version',
                        'ver' => config("ver"),
                )
        );
        $ret = curls($sz['ds_todaorul'],http_build_query(ydqu($post)));
        if(!$ret){
            return $this->getReturn(-1, '连接服务器失败');
        }
        $json = json_decode($ret,TRUE);
        if($json['code']==1){
            return $this->getReturn(1, $json['msg']);
        }else{
            return $this->getReturn(-1, $json['msg'],array("sm"=>$json['data']['sm'],"ver"=>$json['data']['ver'],"qqu"=>$json['data']['qqu']));
        }
        
    }

    /**
     * 解压缩
     * @param $file 要解压的文件
     * @param $todir 要存放的目录
     * @return str 包含所有文件及目录的数组
     */
    public function deal_zip($file,$todir)
    {
        if (trim($file) == '') {
            return 406;
        }
        if (trim($todir) == '') {
            return 406;
        }
        $zip = new \ZipArchive;
        // 中文文件名要使用ANSI编码的文件格式
        if ($zip->open($file) === TRUE) {
            //提取全部文件
            $zip->extractTo($todir);
            $zip->close();
            $result = 200;
        } else {
            $result = 406;
        }
        return $result;
    }

    /**
     * 遍历当前目录不包含下级目录
     * @param $dir 要遍历的目录
     * @param $file 要过滤的文件
     * @return str 包含所有文件及目录的数组
     */
    public function scan_dir($dir,$file='')
    {
        if (trim($dir) == '') {
            return false;
        }
        $file_arr = scandir($dir);
        $new_arr = [];
        foreach($file_arr as $item){

            if($item!=".." && $item !="." && $item != $file){
                $new_arr[] = $item;
            }
        }
        return $new_arr;

    }


    /**
     * 合并目录且只覆盖不一致的文件
     * @param $source 要合并的文件夹
     * @param $target 要合并的目的地
     * @return int 处理的文件数
     */
    public function copy_merge($source, $target) {
        if (trim($source) == '') {
            return false;
        }
        if (trim($target) == '') {
            return false;
        }
        // 路径处理
        $source = preg_replace ( '#/\\\\#', DIRECTORY_SEPARATOR, $source );
        $target = preg_replace ( '#\/#', DIRECTORY_SEPARATOR, $target );
        $source = rtrim ( $source, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        $target = rtrim ( $target, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        // 记录处理了多少文件
        $count = 0;
        // 如果目标目录不存在，则创建。
        if (! is_dir ( $target )) {
            mkdir ( $target, 0777, true );
            $count ++;
        }
        // 搜索目录下的所有文件
        foreach ( glob ( $source . '*' ) as $filename ) {
            if (is_dir ( $filename )) {
                // 如果是目录，递归合并子目录下的文件。
                $count += $this->copy_merge ( $filename, $target . basename ( $filename ) );
            } elseif (is_file ( $filename )) {
                // 如果是文件，判断当前文件与目标文件是否一样，不一样则拷贝覆盖。
                // 这里使用的是文件md5进行的一致性判断，可靠但性能低。
                if (! file_exists ( $target . basename ( $filename ) ) || md5 ( file_get_contents ( $filename ) ) != md5 ( file_get_contents ( $target . basename ( $filename ) ) )) {
                    copy ( $filename, $target . basename ( $filename ) );
                    $count ++;
                }
            }
        }

        // 返回处理了多少个文件
        return $count;
    }

    /**
     * 遍历删除文件
     * @param $dir 要删除的目录
     * @return bool 成功与否
     */
    public function deldir($dir) {
        if (trim($dir) == '') {
            return false;
        }
        //先删除目录下的文件：
        $dh=opendir($dir);
            while ($file=readdir($dh)) {
                if($file!="." && $file!="..") {
                  $fullpath=$dir."/".$file;
                  if(!is_dir($fullpath)) {
                      unlink($fullpath);
                  } else {
                      $this-> deldir($fullpath);
                  }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 遍历执行sql文件
     * @param $dir 要执行的目录
     * @return bool 成功与否
     */
    public function carry_sql($dir){
        if (trim($dir) == '') {
            return false;
        }
        $sql_file_res = $this->scan_dir($dir);
        if (!empty($sql_file_res)) {
            foreach ($sql_file_res as $k => $v) {
                if (!empty(strstr($v,'.sql'))) {
                    $sql_content = file_get_contents($dir.$v);
                    $sql_arr = explode(';', $sql_content);

                    //执行sql语句
                    foreach ($sql_arr as $vv) {
                        if (!empty($vv)) {
                           $sql_res = Db::execute($vv.';');
                           if (empty($sql_res)) {
                                return false;
                           }
                        }
                    }
                }
            }
        } else {
            return false;
        }

        return true;

    }


    /**
     * 下载程序压缩包文件
     * @param $url 要下载的url
     * @param $save_dir 要存放的目录
     * @return res 成功返回下载信息 失败返回false
     */
    function down_file($url, $save_dir) {
        if (trim($url) == '') {
            return false;
        }
        if (trim($save_dir) == '') {
            return false;
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir.= '/';
        }
        $filename = basename($url);
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return false;
        }
        //开始下载
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $content = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        // 判断执行结果
        if ($status['http_code'] ==200) {
            $size = strlen($content);
            //文件大小
            $fp2 = @fopen($save_dir . $filename , 'a');
            fwrite($fp2, $content);
            fclose($fp2);
            unset($content, $url);
            $res = [
                'status' =>$status['http_code'] ,
                'file_name' => $filename,
                'save_path' => $save_dir . $filename
            ];
        } else {
            $res = false;
        }

        return $res;
    }

    /**
     * 获取文件内容
     * @param $url 要获取的url
     * @return res 成功返回内容 失败返回false
     */
    public function get_file($url){
        if (trim($url) == '') {
            return false;
        }
        $opts = array(
          'http'=>array(
            'method'=>"GET",
            'timeout'=>3,//单位秒
           )
        );
        $cnt=0;
        while($cnt<3 && ($res=@file_get_contents($url, false, stream_context_create($opts)))===FALSE) $cnt++;
        if ($res === false) {
            return false;
        } else {
            return $res;
        }
    }

    // 在线更新
    public function system_update()
    {
        $sz = Db::name("pay_sz")->find();
        $post = array(
                'game_dm' => 'gengxin',
                'type' => 'gengxin',
                'post' => array(
                        'type' => 'system_update',
                        'ver' => $sz['ver'],
                )
        );
        $ret = curls($sz['ds_todaorul'],http_build_query($post));
        $json = json_decode($ret,TRUE);
        if($json['code']==1){
            $result = [
                'code'=>1,
                'msg'=>$json['msg'],
                'data'=>''
                ];
        } else {
            // 有效期内 开始更新
            // 设定目录
            // 根目录
            $base_dir = Env::get('ROOT_PATH');
            // 本地缓存路径
            $path =  './../update';
            //return $path;
            // 没有就创建
            if(!is_dir($path)){
                mkdir(iconv("UTF-8", "GBK", $path),0777,true);
            }
            // 设定缓存目录名称
            $cache_dir = $path.'/';
            $server = $json['data']['ver'];
            if ($server === false) {
                    $result = [
                        'code'=>-1,
                        'msg'=>'服务器更新日志获取失败',
                        'data'=>''
                    ];
            }else {
                    // 循环比较是否需要下载 更新
                    foreach (explode(",", $server) as $key => $value) {
                        if ($sz['ver'] < $value) {
                            // 获取更新信息
                            // 服务器各个程序包日志存放路径
                            $up_info = $json['data']['version'];
                            // 判断是否存在
                            if ($up_info === false) {
                                $result = [
                                    'code'=>-1,
                                    'msg'=>'服务器更新包不存在',
                                    'data'=>''
                                ];
                            } else {
                                // 下载文件
                                $back = $this->down_file($up_info['download'],$cache_dir);
                                if (empty($back)) {
                                    $result = [
                                        'code'=>-1,
                                        'msg'=>'升级程序包下载失败',
                                        'data'=>''
                                    ];
                                } else {
                                    //下载成功 解压缩
                                    $zip_res = $this->deal_zip($back['save_path'] ,$cache_dir);
                                    // 判断解压是否成功
                                    if ($zip_res == 406) {
                                        $result = [
                                            'code'=>-1,
                                            'msg'=>'文件解压缩失败',
                                            'data'=>''
                                        ];
                                    } else {
                                        // 开始更新数据库和文件

                                        // sql文件
                                        //读取文件内容遍历执行sql
                                        $sql_res = $this->carry_sql($cache_dir.'mysql/');
                                        if (1 === false) {
                                            $result = [
                                                'code'=>-1,
                                                'msg'=>'sql文件写入失败',
                                                'data'=>''
                                            ];
                                        }else{
                                            // php文件合并 返回处理的文件数
                                            $file_up_res = $this->copy_merge($cache_dir.'program/',$base_dir);
                                            if (empty($file_up_res)) {
                                                $result = [
                                                    'code'=>-1,
                                                    'msg'=>'更新文件移动合并失败',
                                                    'data'=>''
                                                ];
                                                }else{
                                                    // 删除临时文件
                                                    $del_res = $this->deldir($cache_dir);
                                                    if (empty($del_res)) {
                                                        $result = [
                                                            'code'=>-1,
                                                            'msg'=>'更新缓存文件删除失败',
                                                            'data'=>''
                                                        ];
                                                    } else {
                                                      // 更新完改写网站本地版号
                                                      $write_res = Db::name("pay_sz")->where("user", Cookie::get(md5(date("m-Y")),md5(date("d-m-Y"))))->update(array("ver"=>$json['data']['ver']));
                                                      if (!$write_res) {
                                                          $result = [
                                                              'code'=>-1,
                                                              'msg'=>'系统版本改写失败',
                                                              'data'=>''
                                                          ];
                                                    }else{
                                                        $result = [
                                                            'code'=>2,
                                                            'msg'=>'在线更新已完成',
                                                            'data'=>''
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }else{
                            $result = [
                                'code'=>1,
                                'msg'=>'本地已经是最新版',
                                'data'=>''
                            ];
                        }
                    }
            }
        }
        return json($result);
    }
}
