---
title: 使用 NameCheap 的 SSL 证书
date: 2014-12-21 01:14:35
tags:
	- Blog
categories: 瞎折腾
---
![使用NameCheap的SSL证书](/media/setting-up-ssl-with-nginx-using-a-namecheap-essentialssl/20141221121928.png)
利用之前申请了 GitHub 的学生礼包免费注册了 [alwen.me] 域名，另外一年免费的 ssl 证书也申请了.
<!--more-->
在服务器上生成用于申请证书的 CSR 和私钥
`openssl req -new -nodes -keyout alwen_me.key -out alwen_me.csr`
将 csr 交到 NameCheap，数小时后 webmaster@alwen.me 收到了来自 Comodo 的域名控制权验证邮件，完成验证后，管理员邮箱就会收到 PositiveSSL 证书了
附件中一共包含四个文件：
- Root CA Certificate - AddTrustExternalCARoot.crt
- Intermediate CA Certificate - COMODORSAAddTrustCA.crt
- Intermediate CA Certificate - COMODORSADomainValidationSecureServerCA.crt
- Your PositiveSSL Certificate - alwen_me.crt

需要将这几个密钥放到一个文件中:
```
 cat alwen_me.crt COMODORSADomainValidationSecureServerCA.crt COMODORSAAddTrustCA.crt AddTrustExternalCARoot.crt > ssl_bundle.cer
```
接下来就是设置 nginx 配置文件开启 ssl 了:
```conf
# HTTPS server
server {
	listen 443;
	server_name alwen.me;

	root /www/alwen/;
	index index.html index.htm;

	ssl on;
	ssl_certificate ssl/ssl_bundle.crt;
	ssl_certificate_key ssl/alwen_me.key;

	ssl_session_timeout 5m;

	ssl_protocols SSLv3 TLSv1 TLSv1.1 TLSv1.2;
	ssl_ciphers "HIGH:!aNULL:!MD5 or HIGH:!aNULL:!MD5:!3DES";
	ssl_prefer_server_ciphers on;

	location / {
		try_files $uri $uri/ =404;
	}
}
```
并且将 http 访问跳转至 https 连接：
```
server {
  listen 80;
  server_name alwen.me www.alwen.me;
  return 301 https://alwen.me$request_uri;
}
```
大功告成(>▽<)，现在访问 [alwen.me](https://alwen.me) 浏览器就显示出安全标志了.
必须感谢 GitHub 提供的 [学生礼包](https://education.github.com/pack) 啊，还有100美刀的 DigitalOcean 消费劵用来作梯子再好不过啦～不过现在好像国内的 .edu 邮箱被屏蔽，申请不了了 >﹏<
