---
layout: post
title: "自定义 Android Studio Locat 的输出颜色"
date: 2014-03-04 11:32
comments: true
tags: Android Studio
categories: 瞎折腾
---
AndroidStudio 默认的日志输出颜色只有灰色和红色两种，不易区分不同级别的日志。  
<!--more-->
###自定义日志输出颜色：  
* 打开`setting`>`editor`>`Color & Fontd`>`Android Logcat`.
* 点击不同的日志级别然后设置相应颜色即可，自定义颜色之前要取消勾选Inherit Attributes From...  

**如下图：**  
![截图01](/media/2014-03-04-make-logcat-of-android-stidio-colorful/screenshot_01.png)  
**我这里使用的颜色是 [Android Design](http://developer.android.com/design/style/color.html) 中的颜色模板.  
设置成功后，Logcat 的输出就变成彩色的了:**
![截图02](/media/2014-03-04-make-logcat-of-android-stidio-colorful/screenshot_02.png)
