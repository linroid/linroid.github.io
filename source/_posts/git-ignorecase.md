title: Git在OSX中的提交忽略文件名称大小写
date: 2015-07-18 23:21:20
tags: 
  - git
  - OSX
categories: 遇过的坑
---
![image](http://7u2rtn.com1.z0.glb.clouddn.com/Snip20150718_8.png)
看到[drakeet](https://github.com/drakeet)的[妹纸](https://github.com/drakeet/Meizhi)App，想写个iOS版拿来练手。开始时项目命名为`MeiZhi`，然后发现drakeet的是`Meizhi`，所以我也改为`Meizhi`来保持一致。在GitHub上查看时发现名称并没有改变，原因是git默认忽略了大小写。
<!--more-->
设置git大小写敏感:

`git config core.ignorecase false`

push后，在GitHub上查看，发现`Meizhi`和`MeiZhi`等都同时存在，而自己在本地ls并没有异常。OSX的文件名大小写不敏感但大小写保留，所以并没有都显示出来。

**解决**:

![image](http://7u2rtn.com1.z0.glb.clouddn.com/Snip20150718_5.png)
ssh到vps上，clone代码，然后删除重复的文件，再push到HitHub。
![image](http://7u2rtn.com1.z0.glb.clouddn.com/Snip20150718_7.png)
本地pull后，`Meizhi`目录也不存在了，`git status`查看，显示被删除，使用`git reset ./`来恢复

之后如果再遇到只修改字母大小时可以通过`git mv --force FileName Filename`来避免这个问题。
