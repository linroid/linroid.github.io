---
layout: post
title: "集成极光推送后无法无法在genymotion模拟器上运行"
date: 2013-08-08 11:01
comments: true
tas: Genymotion
categories:
  - Android开发
  - 第三方服务
---

#Log信息： 
```java
08-06 14:30:59.167      316-333/system_process E/NativeLibraryHelper: Comparing ABIs x86 and unknown and unknown versus armeabi/libsys.so
08-06 14:30:59.167      316-333/system_process E/NativeLibraryHelper: abi didn't match anything: armeabi/libsys.so (end at 7)
08-06 14:30:59.171      316-333/system_process I/PackageManager: Running dexopt on: com.xtuers.news
08-06 14:30:59.207      316-327/system_process W/InputMethodManagerService: Window already focused, ignoring focus gain of: com.android.internal.view.IInputMethodClient$Stub$Proxy@a6f4b860 attribute=null
08-06 14:30:59.307    8495-8495/? D/dalvikvm: Unable to stat classpath element '/system/framework/filterfw.jar'
08-06 14:30:59.587    8495-8495/? D/dalvikvm: DexOpt: load 71ms, verify+opt 164ms, 1618708 bytes
```
这是因为极光推送有一个用 C 写的 .so 包，需要单独为 CPU 编译。

而之前我下载的 SDK 包中的 libsys.so 只支持 CPU 为 ARM 架构的。Genymotion 的模拟器是运行在 VirtualBox 上的，CPU 是 X86 架构，还好极光推送提供 [X86 CPU的SDK](https://www.jpush.cn/sdk/android)

 下载后解压，将 x86 目录复制到项目的 libs 下，成功在 Genymotion 模拟器上运行

<!--more-->