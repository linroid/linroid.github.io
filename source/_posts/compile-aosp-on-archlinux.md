title: Archlinux 下编译AOSP小记
date: 2015-01-27 09:51:28
tags: 
 - linux
 - AOSP
categories: 瞎折腾
---

我用的是fish shell，首先要进入到bash shell:
	`bash`
切换jdk版本到1.7
	`sudo archlinux-java set java-7-openjdk`
切换python版本到2.x
	`sudo ln -sf /usr/bin/python2.7  /usr/bin/python`
安装依赖库
	`yaourt -S libtinfo`
