
ALTER TABLE `pay_sz` ADD `ip_hfw` varchar(11) DEFAULT '2' COMMENT '是否开启代理IP请求' AFTER `mail_email`;
ALTER TABLE `pay_sz` ADD `ip_User` mediumtext COMMENT '代理IP订单号' AFTER `mail_email`;
ALTER TABLE `pay_sz` ADD `ip_Pass` mediumtext COMMENT '代理IP密码' AFTER `mail_email`;
ALTER TABLE `pay_sz` ADD `yd_sj` varchar(32) DEFAULT '15' COMMENT '云监控回调时间' AFTER `yd_jk`;
ALTER TABLE `pay_user` ADD `mail_smtp` mediumtext COMMENT 'SMTP地址' AFTER `jh_je`;
ALTER TABLE `pay_user` ADD `mail_port` mediumtext COMMENT 'SMTP端口' AFTER `jh_je`;
ALTER TABLE `pay_user` ADD `mail_name` mediumtext COMMENT '邮箱账号' AFTER `jh_je`;
ALTER TABLE `pay_user` ADD `mail_pwd` mediumtext COMMENT '邮箱密码' AFTER `jh_je`;
ALTER TABLE `pay_user` ADD `mail_email` int(11) DEFAULT '1' COMMENT '邮件模板' AFTER `jh_je`;
ALTER TABLE `pay_user` ADD `web_mail` varchar(30) DEFAULT '2' COMMENT '邮箱通知开关' AFTER `jh_je`;
