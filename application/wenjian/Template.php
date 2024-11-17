<?php

namespace app\wenjian;
use think\Db;
use think\facade\Env;

class Template {
	
	static public function getList($dir){
		$dirArray[] = NULL;
		if (false != ($handle = opendir($dir))) {
			$i = 0;
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && strpos($file, ".")===false) {
					$dirArray[$i] = $file;
					$i++;
				}
			}
			closedir($handle);
		}
		return $dirArray;
	}
	
	static public function getConfig($name1,$name2){
		$filename = Env::get('APP_PATH').'public/'.$name1.'/'.$name2.'/config.ini';
		if(file_exists($filename)){
			return parse_ini_file($filename);
		}else{
			return false;
		}
	}
	
	static public function getConimg($name1,$name2){
		$filename = Env::get('APP_PATH').'public/'.$name1.'/'.$name2.'/index.png';
		if(file_exists($filename)){
			return 'data:image/png;base64,'.base64_encode(file_get_contents($filename));
		}else{
			return false;
		}
	}
	
	static public function updateAll($name){
		$list1 = self::getList(Env::get('APP_PATH').'public/');
		$list2 = Db::name("pay_mb")->where("type",$name)->lock(true)->select();
		
		foreach($list1 as $name1){
		    if($name1!=$name)continue;
		    foreach($list2 as $name2){
		        $list3 = Env::get('APP_PATH').'public/'.$name1.'/'.$name2['name'].'/';
		        if(is_dir($list3))continue;
		        Db::table('pay_mb')->where("type",$name1)->where("name",$name2['name'])->delete();
		    }
		}
		foreach($list1 as $name1){
		    if($name1!=$name)continue;
		    $list2 = self::getList(Env::get('APP_PATH').'public/'.$name1.'/');
		    foreach($list2 as $name2){
		        if (preg_match("/([\x81-\xfe][\x40-\xfe])/",$name2))continue;
		        $config = self::getConfig($name1,$name2);
		        $img = self::getConimg($name1,$name2);
		        if($config){
		            $data = Db::table("pay_mb")->where("type",$name1)->where("name",xss($name2))->lock(true)->find();
		            $dat = array(
                        "img" => $img,//模板样图
                        "title" => xss($config['title']),//模板名称
                        "author" => xss($config['author']),//模板作者
                        "description" => xss($config['description']),//模板介绍
                        "website" => xss($config['website']),//模板演示
                        "version" => xss($config['version']),//模板版本
                    );
                    Db::name("pay_mb")->where("type",$name1)->where("name",xss($name2))->update($dat);
		            if($data)continue;
		            $data = array(
                        "type" => xss($name1),//模板类型
                        "name" => xss($name2),//模板标识
                        "img" => $img,//模板样图
                        "title" => xss($config['title']),//模板名称
                        "author" => xss($config['author']),//模板作者
                        "description" => xss($config['description']),//模板介绍
                        "website" => xss($config['website']),//模板演示
                        "version" => xss($config['version']),//模板版本
                    );
                    Db::name("pay_mb")->insert($data);
		        }
		    }
		}
	}
	
	static public function tdConfig($name){
		$filename = Env::get('APP_PATH').'common/'.$name.'/config.ini';
		if(file_exists($filename)){
			return parse_ini_file($filename);
		}else{
			return false;
		}
	}
	
	static public function tdConimg($name){
		$filename = Env::get('APP_PATH').'common/'.$name.'/logo.png';
		if(file_exists($filename)){
			return 'data:image/png;base64,'.base64_encode(file_get_contents($filename));
		}else{
			return false;
		}
	}
	
	static public function tdAll(){
		$list1 = self::getList(Env::get('APP_PATH').'common/');
		$list2 = Db::table("pay_qrcode")->lock(true)->select();
		
		foreach($list2 as $name2){
		        $config = self::tdConfig($name2['game_dm']);
		        if($config)continue;
		        Db::table('pay_qrcode')->where("game_dm",$name2['game_dm'])->delete();
		}
		foreach($list1 as $name1){
		    $config = self::tdConfig($name1);
		    if($config['game_dm']==$name1)continue;
		    foreach($list2 as $name2){
		        if($name2['game_dm']!=$name1)continue;
		        Db::table('pay_qrcode')->where("game_dm",$name2['game_dm'])->delete();
		    }
		}
		foreach($list1 as $name1){
		    if (preg_match("/([\x81-\xfe][\x40-\xfe])/",$name1))continue;
		    $config = self::tdConfig($name1);
		    if($config['game_dm']!=$name1)continue;
		    $img = self::tdConimg($name1);
		    $data = Db::table("pay_qrcode")->where("game_dm",$name1)->lock(true)->find();
		    if($data){
		        $data = array(
                    "game_img" => $img,//通道图标
                    "game_name" => xss($config['game_name']),//通道名称
                );
                Db::name("pay_qrcode")->where("game_dm",$name1)->update($data);
		    }else{
		        $data = array(
                    "game_dm" => xss($config['game_dm']),//通道标识
                    "game_img" => $img,//通道图标
                    "game_name" => xss($config['game_name']),//通道名称
                );
                Db::name("pay_qrcode")->insert($data);
		    }
		}
	}
	
}