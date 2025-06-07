---
title: 提升 Android Studio 编译速度的实用技巧
date: 2013-10-02 17:15:27
tags:
  - Android
  - Android Studio
  - 编译优化
  - 开发效率
  - Gradle
categories:
  - Android开发
  - 工具优化
---
Android Studio 用了这么久了，亮点就不说了，唯一蛋疼的就是编译很慢，而且在未更改任何代码的时候，点运行任然重新编译。  
昨天 Google 了一下，找到以下两个讨论：  
<!--more-->
* http://www.reddit.com/r/androiddev/comments/1k3nb3/gradle_and_android_studio_way_slower_to_build/  
* https://plus.google.com/u/0/110774282522099816721/posts/e9PG6vSN5w3  

打开 `setting` -> `compiler` -> `gradle` -> `Compile independent modules in parallel`   
试了以下，的确让编译速度提升了  

![Screenshot of compile more quickly By Android Studio](http://ww3.sinaimg.cn/bmiddle/7a69d277jw1e7sevg3xf7j20i802r0t2.jpg)  
和在命令行下用 gradle 编译所用时间差不多  
但是在未更改任何代码的时候，点运行任然重新编译。  
我之前都是在 Android Studio 中先新建 Project 之后，再重新导入，发现这样之后编译非常快，而且未改代码的时候 Run 不会重新编译
不过后来发现在 Event Log 中提示这种方法已经不赞成使用，其实在设置中关闭 "external build" 和这个一样的效果。
好吧。
也没继续深究，不应该在IDE上浪费太多时间。还是专心敲好代码吧。
