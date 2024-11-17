<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
 
// [ 应用入口文件 ]
namespace think;

if(version_compare(PHP_VERSION,'5.6.0','<'))  die('请在宝塔面板-网站-设置-PHP版本->最低版本：5.6 | 最高版本：自己测');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

if (!is_file(dirname(dirname(__FILE__)).'/install.lock')){// 在线安装
// 执行应用并响应
Container::get('app')->bind('install')->run()->send();
}else{
// 执行应用并响应
Container::get('app')->run()->send();
}