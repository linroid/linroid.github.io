---
title: Handler removeCallbacks 安全性分析
date: 2015-01-22 17:15:27
tags:
  - Android
  - Handler
  - 消息机制
  - 踩坑
categories:
  - Android开发
  - 源码分析

---
　　`Handler` 容易引起内存泄露，这是大家都知道的，所以你应该会在适当的时候调用 `removeCallbacks()`