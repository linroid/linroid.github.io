title: 四季电台
date: 2015-02-11 13:12:23
tags: 我的作品
---
躲在某一时间,想念一段时间的掌纹;躲在某一地点,聆听四季的声音.
![四季电台主播列表截图](http://7u2rtn.com1.z0.glb.clouddn.com/device-2015-01-22-224820.png)
[下载地址](http://fir.im/sky31radio)
<!--more-->
写完[http://radio.sky31.com](http://radio.sky31.com)的后台后,想把app也写出来.正好人机交互和Java也要交课程设计,就开始写了-.-
这次偷懒,数据缓存直接用DiskLruCache来管理没有使用数据库,也没有使用MVP模式.
播放器使用SDK中的[MediaPlayer](http://developer.android.com/reference/android/media/MediaPlayer.html)实现,暂时还没实现播放缓存.
使用了如下的开源库:
- [ButterKnife](http://jakewharton.github.io/butterknife/)
- [Dagger](http://square.github.io/dagger/)
- [Retrofit](http://square.github.io/retrofit/)
- [OkHttp](http://square.github.io/okhttp/)
- [Gson](http://code.google.com/p/google-gson/)
- [RxAndroid](https://github.com/ReactiveX/RxAndroid)
- [SystemBarTint](https://github.com/jgilfelt/SystemBarTint)
- [Timber](http://jakewharton.github.io/timber/)
- [DiscreteSeekBar](https://github.com/AnderWeb/discreteSeekBar)
把源码放到Github上了,有兴趣的就看看把:):[Github地址](http://github.com/linroid/Sky31Radio)
