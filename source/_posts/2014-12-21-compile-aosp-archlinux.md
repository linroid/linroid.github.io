---
title: Archlinux 下编译 AOSP 小记
date: 2015-01-27 09:51:28
tags:
  - Android
  - AOSP
  - Arch Linux
  - 源码编译
  - 系统开发
categories:
  - Android开发
  - 系统定制
---

我用的是 fish shell，首先要进入到 bash shell:
	`bash`
切换jdk版本到1.7
	`sudo archlinux-java set java-7-openjdk`
切换python版本到2.x
	`sudo ln -sf /usr/bin/python2.7  /usr/bin/python`
安装依赖库
	`yaourt -S libtinfo`
<!--more-->