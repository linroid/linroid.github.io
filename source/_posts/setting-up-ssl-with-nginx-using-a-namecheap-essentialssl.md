title: 使用NameCheap的SSL证书
date: 2014-12-21 01:14:35
tags: 
	- Nginx
	- 域名
---
![使用NameCheap的SSL证书](/media/setting-up-ssl-with-nginx-using-a-namecheap-essentialssl/20141221121928.png)
[@笛子_初心莫负](http://weibo.com/u/1669184023)说学编程不知道干嘛了，于是打算教她搭建一个自己的博客.
<!--more-->
利用之前申请了Github的学生礼包免费注册了[alwen.me]域名，当然一年免费的ssl证书也申请了.
在服务器上生成用于申请证书的CSR和私钥
`openssl req -new -nodes -keyout alwen_me.key -out alwen_me.csr`
将csr交到NameCheap，数小时后webmaster@alwen.me收到了来自Comodo的域名控制权验证邮件，完成验证后，管理员邮箱就会收到PositiveSSL证书了
附件中一共包含四个文件：
- Root CA Certificate - AddTrustExternalCARoot.crt
- Intermediate CA Certificate - COMODORSAAddTrustCA.crt
- Intermediate CA Certificate - COMODORSADomainValidationSecureServerCA.crt
- Your PositiveSSL Certificate - alwen_me.crt

需要将这几个密钥放到一个文件中:
`cat alwen_me.crt COMODORSADomainValidationSecureServerCA.crt COMODORSAAddTrustCA.crt AddTrustExternalCARoot.crt > ssl_bundle.cer`
接下来就是设置nginx配置文件开启ssl了:
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
并且将http访问跳转至https连接：
```
server {
  listen 80;
  server_name alwen.me www.alwen.me;
  return 301 https://alwen.me$request_uri;
}
```
大功告成(>▽<)，现在访问[alwen.me](https://alwen.me)浏览器就显示出安全标志了.
必须感谢Github提供的[学生礼包](https://education.github.com/pack)啊，还有100美刀的DigitalOcean消费劵,用来作梯子再好不过啦～不过现在好像国内的.edu邮箱被屏蔽，申请不了了 >﹏<