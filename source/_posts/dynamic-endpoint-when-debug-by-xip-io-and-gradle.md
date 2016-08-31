title: xip.io + gradle 在调试时动态设置服务端地址
date: 2015-07-24 19:25:55
tags:
 - gradle
 - Android Studio
categories: 瞎折腾
---

> 日常开发中，如果服务端在本地，通常可通过改hosts、写死IP、动态域名等方式来设置服务端地址，但总觉很麻烦，不灵活；比如更换网络导致IP变化，就得重新设置。
> 今天突然想到，利用xip.io 和 gradle来自动设置服务端地址

<!--more-->
 [xip.io](http://xip.io) 是一个直接使用域名来指定 IP 的域名服务，无需手动设置 DNS，同时也不需要任何注册。这解决了使用 IP 无法使用多个 Virtual Host 而使用域名又得很麻烦改地DNS的问题。

 xip.io 支持 `{custom_prefix}.{host_ip}.xip.io` 的域名格式，解析出来的 ip 就是 {host_id}。如:

 `exmple.com.127.0.0.1.xip.ip` 会解析为`127.0.0.1`

 下面进行 gradle 配置:

 ```
 buildTypes {
  debug {
    //...
    def hostIp = InetAddress.getLocalHost().getHostAddress()
    buildConfigField "String", "ENDPOINT", "\"http://example.com.${hostIp}.xip.io/api\""
  }
  release {
	//...
    buildConfigField "String", "ENDPOINT", "\"http://example.com/api\""
  }
}

 ```

 在 debug 下，gradle 会获取当前电脑的 IP，然后写入到 BuildConfig 类中的 `ENDPOINT` 属性。gradle sync 一下后，`BuildConfig.ENDPOINT` 就被赋值为 `http://example.com.${hostIp}.xip.io/api`

 当然，还可以放到 xml 文件中:)

 ```
 resValue "string", "host_url", "http://example.com.${hostIp}.xip.io";
 ```


 最后记得在 apache/nginx 等配置 Virtual Host 时使用宽域名，如 Nginx 中:

 ```
 server_name example.com.*
 ```
