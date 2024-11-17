<?php
/**
 * User: 呆呆
 * Agreement: 禁止使用本软件（系统）用于任何违法违规业务或项目,造成的任何法律后果允由使用者（或运营者）承担
 * Date: 2021/3/3
 * Time: 14:34
 */
 
@header('Content-Type: text/html; charset=UTF-8');
$_SERVER['REQUEST_METHOD']=='GET' ? $api = $_GET : $api = $_POST;//判断：GET和POST
?>
<head>
</head>
<body style="display: none;">
    <form id='alipaysubmit' name='alipaysubmit' action='/' method='POST'>
        <?php if(!empty($api['pid']))echo"<input  name='pid' value='".$api['pid']."'/>";?>
        <?php if(!empty($api['type']))echo"<input  name='type' value='".$api['type']."'/>";?>
        <?php if(!empty($api['out_trade_no']))echo"<input  name='out_trade_no' value='".$api['out_trade_no']."'/>";?>
        <?php if(!empty($api['money']))echo"<input  name='money' value='".$api['money']."'/>";?>
        <?php if(!empty($api['return_url']))echo"<input  name='return_url' value='".$api['return_url']."'/>";?>
        <?php if(!empty($api['notify_url']))echo"<input  name='notify_url' value='".$api['notify_url']."'/>";?>
        <?php if(!empty($api['name']))echo"<input  name='name' value='".$api['name']."'/>";?>
        <?php if(!empty($api['sitename']))echo"<input  name='sitename' value='".$api['sitename']."'/>";?>
        <?php if(!empty($api['param']))echo"<input  name='param' value='".$api['param']."'/>";?>
        <?php if(!empty($api['sign']))echo"<input  name='sign' value='".$api['sign']."'/>";?>
        <?php if(!empty($api['sign_type']))echo"<input  name='sign_type' value='".$api['sign_type']."'/>";?>
        <?php if(!empty($api['mid']))echo"<input  name='mid' value='".$api['mid']."'/>";?>
        <?php if(!empty($api['json']))echo"<input  name='json' value='".$api['json']."'/>";?>
        <input type='submit' value='POST' style='display:none;'>
    </form>
    <script>document.forms['alipaysubmit'].submit();</script>
</body>