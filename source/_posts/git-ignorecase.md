---
title: Git 在 OSX 中的提交忽略文件名称大小写
date: 2015-07-18 23:21:20
tags: 
  - git
  - OSX
categories: 遇过的坑
---
![image](/images/posts/git-ignorecase-1.png)
看到[drakeet](https://github.com/drakeet)的[妹纸](https://github.com/drakeet/Meizhi) App，想写个 iOS 版拿来练手。开始时项目命名为 `MeiZhi`，然后发现drakeet的是`Meizhi`，所以我也改为`Meizhi`来保持一致。在 GitHub 上查看时发现名称并没有改变，原因是git默认忽略了大小写。
<!--more-->
设置 git 大小写敏感:

`git config core.ignorecase false`

push后，在GitHub上查看，发现`Meizhi`和`MeiZhi`等都同时存在，而自己在本地 ls 并没有异常。OSX 的文件名大小写不敏感但大小写保留，所以并没有都显示出来。

**解决**:

![image](/images/posts/git-ignorecase-2.png)
ssh 到 vps 上，clone 代码，然后删除重复的文件，再 push 到 GitHub。
![image](/images/posts/git-ignorecase-3.png)
本地 pull 后，`Meizhi` 目录也不存在了，`git status` 查看，显示被删除，使用 `git reset ./` 来恢复

之后如果再遇到只修改字母大小时可以通过 `git mv --force FileName Filename` 来避免这个问题。
