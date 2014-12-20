---
layout: post
title: "[Android学习笔记]Service 学习"
date: 2013-10-06 13:04
comments: true
categories: Android学习
---
#Service是andoird四大组件之一  
##继承Service类要重写以下方法：
* `onBind()`: 当在其他组件中调用`bindService()`方法启动Service时会回调该方法
* `onStartCommand()`:当在其他组件中调用`startService()`方法启动Service时会回调该方法
* `onUnBind()`:当与Service绑定的组件结束时会回调该方法
* `onDestory()`:当系统由于内存低等原因杀掉Service时会回调该方法。  
重写这些方法的时候不必像Activity中那样调用父类中该方法。
<!--more-->
#Service由"Started"和"Bound"两种组成:
* Started: 当在其他组件中调用 `startService()`启动service，这个service是"Started"的;
```java
Intent intent = new Intent(this, SimpleService.class);
startService(intent);
```
此时，该Service可能会无限地执行下去，除非系统由于内存低、释放资源等终止它（会调用该service的`onDestory()`方法）。当调用`startService()`方法时，系统会回调这个Service的`onStartCommand()`方法,这个方法的返回值为int类型,用来告诉系统当系统kill掉这个service时，系统如何继续这个service，有三个值可供选择:
  * `START_NOT_STICKY`，系统不会重新创建这个service，除非有pending intent要传递
  * `START_STICKY`,系统会重新创建这个Service，但是不会重新传递最后一个intent也就是说，系统在回调onStartCommand()方法时，参数intent会为null，除非有pending intent去启动service，系统才会传递intent,
  * `START_REDELIVER_INTENT`系统会重新创建这个Service，并且会传递最后一个intent
"Started"的Service当onStartCommand()方法返回后，系统不会结束这个Service。 调用`stopSelf()`或`stopService()`方法可以停止该Service

* Bound:在其他组件中调用`bindService()`方法启动，此时Service与启动它的组件生命周期绑定在一起，系统会回调service的`onBind()`方法返回一个IBinder对象，通过IBinder接口，Service可以与其他进程进行数据交互 (interprocess communication(IPC)).当和它绑定的所有组件都结束时，系统会回调它的`onUnbind()`方法。一个Service可以和多个组件绑定，但只有第一个组件和它绑定时系统才会调用它的onBind()方法.
##创建"Started"Service时有两个类可以作为父类：
* Service 可以同时处理多个intent，如果要处理耗时和阻塞的任务，需要在子线程中进行，否则会出现ANR，因为Service使用的是应用的主线程，并没有在独立的线程中进行。
* IntentService Service 的子类，只需实现`onHandleIntent（）方法，如果service不需要同时处理多个intent，这将是最好的选择。会创建新的线程，当任务执行完毕后会自动结束Service

#Service的生命周期

* started Service : 其他组件调用startService()开始到调用自身调用stopSelf()或其他组件调用stopService()结束，即整个生命周期发生在系统开始回调Service的onCreate()方法到回调onDestory方法的结束。
* bound Service :  其他组件调用bindService()开始到调用自身stopSelf或与它绑定的所有组件都调用了unbindService()结束  
![Service的生命周期](http://developer.android.com/images/service_lifecycle.png)  
两种方式创建的Service并不是完全分开的可以绑定一个已经started 的Service，两种方式创建的Service，系统都会调用onCreate()和onDestory方法
##参考资料:
* [Android Guides](http://developer.android.com/guide/components/services.html)

