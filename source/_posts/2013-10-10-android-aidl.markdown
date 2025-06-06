---
layout: post
title: "[Android 学习笔记]AIDL"
date: 2013-10-10 22:35
comments: true
tags:
  - Android
  - AIDL
  - 进程通信
  - 跨进程
categories:
  - Android开发
  - 系统组件
---

>简略地翻译一遍谷歌的教程，加深自己的理解。。。   

AIDL(Android Interface Definition Language)用于来自不同应用的客户端访问service进行进程数据交换（IPC）
<!--more-->
#创建一个AIDL接口的步骤：
1. 创建一个.aidl文件
2. 实现接口
3. 暴露接口给客户端
 * ##创建一个.aidl文件
    .aidl文件的语法使用的是java语法，但做了一些限制，每一个.aidl文件都只定义一个接口，并且只能声明接口和抽象方法   
    .aidl文件支持以下数据类型:
    * 所有的java基本类型（如int,long,byte,double,char,boolean等）
    * String
    * CharSequence
    * List  
      List中的元素数据类型必须是以下三种之一  
        * List集合支持的数据类型
        * 其他AIDL生成的接口
        * 实现了Parcelable接口
    * Map  
      Map中的元素数据类型要求同上面的List.
    
    上面未列出来的类型在使用的时候必须使用import声明导入,即使这个类和.aidl文件在同一个包下。  
    在定义一个service接口的时候需要注意的是：
      * 方法可以没有参数，返回值可有可无
      * 所有非基本数据类型的参数都需要指示出数据离开的方向(in/out/inout)
      * 所有.aidl文件中的注释都包含在生成的IBinder接口中（声明在import和package语句之前的注释除外）
      * 只能声明抽象方法，不能定义静态常量
    我创建了如下的MyAidl.aidl文件
```java
package com.diaoslin.androidlearn.service;

/**
 * Created by lin on 13-10-10.
 */
interface MyAidl {
    void showNotification();
    void cancelNotification();
}
```
 * ##实现接口
   当建立好.aidl文件后，Android SDK Tool会自动生成相应的.java文件，使用Android Studio需要在build.gradle文件中指定AIDL目录，如下：    
```
android {
    compileSdkVersion 17
    buildToolsVersion "18.1.0"

    defaultConfig {
        minSdkVersion 7
        targetSdkVersion 16
    }
    sourceSets {
        main {
            aidl.srcDirs = ['src/main/java']
        }
    }
}
```
  生成的java接口类中，包含所有在.aidl文件中声明的方法，还有一个Stub抽象子类。  
  要实现.aidl文件中声明的接口，需要继承这个Stub子类，并且实现所有.aidl中声明的方法，
```java
MyAidl.Stub mBinder = new MyAidl.Stub() {
	@Override
	public void showNotification(){
	    Log.d("mBinder", "showNotification");
	    Intent i = new Intent(AidlService.this, AidlActivity.class);
	    PendingIntent pi = PendingIntent.getActivity(AidlService.this, 0, i, 0);

	    Notification notification = new Notification.Builder(AidlService.this)
		    .setAutoCancel(true)
		    .setSmallIcon(R.drawable.ic_launcher)
		    .setWhen(System.currentTimeMillis())
		    .setContentTitle("标题")
		    .setTicker("显示通知成功")
		    .setContentIntent(pi)
		    .setContentText("消息内容")
		    .build();
	    mNotificationManager.notify(NOTIFICATION_ID, notification);
	}

	@Override
	public void cancelNotification() throws RemoteException {
	    mNotificationManager.cancel(NOTIFICATION_ID);
	}
};
```

 * ##暴露接口给客户端  
  在service的onBind()方法中返回.Stub对象，在客户端中，在ServiceConnect对象的onServiceConnected()方法中
调用MyAidl.Stub.asInterface(service)  
这个Sub子类冲还包含一个比较重要的方法：`asInterface()`
这个方法通常在ServiceConnecttion对象的onServiceConnected()方法中调用，返回一个MyAidl.Stub对象  
```java
ServiceConnection mConnection = new ServiceConnection() {
    @Override
    public void onServiceConnected(ComponentName name, IBinder service) {
        mMyAidl = MyAidl.Stub.asInterface(service);
        Log.d("mConnection", "onServiceConnected");
    }

    @Override
    public void onServiceDisconnected(ComponentName name) {
        mMyAidl = null;
        Log.d("mConnection", "onServiceDisconnected");
    }
};
```
当客户端调用bindService()绑定Service后，可以通过ServiceConnection对象的onServiceConnected()方法获得MyAidl.Stub对象
#在IPC中传递对象
  如果需要在进程之间通过IPC接口传递对象，那么这个对象的class必须实现了Parcelable接口
#参考资料:
   * [Android API Guide](http://developer.android.com/guide/components/aidl.html)
