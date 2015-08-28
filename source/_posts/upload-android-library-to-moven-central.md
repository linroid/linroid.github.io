
title: 提交library项目到 Maven Central
date: 2015-03-13 10:51:42
tags:
	- gradle
categories: 瞎折腾
---
  将[FilterMenu](http://github.com/linroid/FilterMenu)提交到GitHub后，在README.md的Getting Started里仅仅写上

  > Download the source to use it as library project

  这唯一使用途径，居然没有gradle/maven?作为Android Studio的忠实用户，自己写的库怎么能只提供这么麻烦的方法！！！于是决定把它提交到Maven Central中,并写下这篇。
<!--more-->
虽然android studio 的gradle 默认使用的是 jcenter仓库，但我们只需要提交到Maven Central即可，jcenter 会自动同步。  

__如果还没有账号先到Maven Central注册: [Sign up](https://issues.sonatype.org/secure/Signup!default.jspa) __
并到 [Create Issue](https://issues.sonatype.org/secure/CreateIssue.jspa?issuetype=21&pid=10134) 提交工单等待管理员的回复,填groupId时,请使用顶级groupId,比如我只需要填写`com.linroid`, 然后可以发布到任何`com.linroid.*` 下的groupId。[Why the wait?](http://central.sonatype.org/articles/2014/Feb/27/why-the-wait/)我早上提交的到晚上0:30的时候收到的管理员到回复.
> Configuration has been prepared, now you can:
Deploy snapshot artifacts into repository https://oss.sonatype.org/content/repositories/snapshots
Deploy release artifacts into the staging repository https://oss.sonatype.org/service/local/staging/deploy/maven2
Promote staged artifacts into repository 'Releases'
Download snapshot and release artifacts from group https://oss.sonatype.org/content/groups/public
Download snapshot, release and staged artifacts from staging group https://oss.sonatype.org/content/groups/staging  
please comment on this ticket when you promoted your first release, thanks

然后通过下面的方法Release,评论了那个issue,几分钟后又收到回复:
> Central sync is activated for com.linroid. After you successfully release, your component will be published to Central, typically within 10 minutes, though updates to search.maven.org can take up to two hours.

## 通过gradle-mvn-push
 [Chris Banes](https://chris.banes.me/)大神很早前写了一个插件[gradle-mvn-push](https://github.com/chrisbanes/gradle-mvn-push)(终于有机会用它了^﹏^), 让你通过一条gradle命令就可以自动构建好aar并提交到Maven Central。下面介绍这个插件的使用方法。
 - 配置用于上传的__认证信息__,配置文件默认在` ${HOME}/.gradle/gradle.properties`，如果没有则自己创建。
	```properties
	NEXUS_USERNAME=linroid
	NEXUS_PASSWORD=YOUR_MAVEN_CENTRAL_PASSWORD
	
	signing.keyId=YOUR_GPG_KEY_ID
	signing.password=YOURGPG_PASSWORD
	signing.secretKeyRingFile=${HOME}/.gnupg/secring.gpg
	```

 `NEXUS_USERNAME`和`NEXUS_PASSWORD`是你注册的用户名和密码,下面的是用于GPG校验的配置信息,关于GPG的使用可以参见阮一峰的博文:[GPG入门教程](http://www.ruanyifeng.com/blog/2013/07/gpg.html)

 - 配置__版本信息__,在你的module目录创建`gradle.properties`文件，添加配置:
	```properies
	POM_NAME=Android FilterMenu Library
	POM_ARTIFACT_ID=library
	POM_PACKAGING=aar
	VERSION_NAME=0.1.1
	VERSION_CODE=1
	GROUP=com.linroid.filtermenu
	
	POM_DESCRIPTION=Android FilterMenu Library
	POM_URL=https://github.com/linroid/FilterMenu
	POM_SCM_URL=https://github.com/linroid/FilterMenu
	POM_SCM_CONNECTION=scm:https://github.com/linroid/FilterMenu.git
	POM_SCM_DEV_CONNECTION=scm:https://github.com/linroid/FilterMenu.git
	POM_LICENCE_NAME=The Apache Software License, Version 2.0
	POM_LICENCE_URL=http://www.apache.org/licenses/LICENSE-2.0.txt
	POM_LICENCE_DIST=repo
	POM_DEVELOPER_ID=linroid
	POM_DEVELOPER_NAME=linroid
	POM_DEVELOPER_URL=http://linroid.com
	``` 
 根据你的项目修改吧('・ω・')
 - __添加gradle-mvn-push插件__

  在library module 的`build.gradle`文件中添加
	```groovy
	apply from: 'https://raw.github.com/chrisbanes/gradle-mvn-push/master/gradle-mvn-push.gradle
	```

  或者可以将[gradle-mvn-push.gradle](https://raw.githubusercontent.com/chrisbanes/gradle-mvn-push/master/gradle-mvn-push.gradle)文件下载下来，然后将上面的url该为本地路径。

 - __执行task命令__

  输入下面的命令,就可以自动构建并上传啦
	```bash
	$ gradle clean build uploadArchives
	```
  出现如下结果就说明上传成功了:）
	![执行成功](http://7u2rtn.com1.z0.glb.clouddn.com/QQ20150313-2@2x.png)

  还没结束，此时你的库并没有发布.
  
 - __最后一步__：close staging repositories 

  登陆[Sonatype Nexus Professional](https://oss.sonatype.org/) 点开左边Build Promotion的Staging Repositories,滚到最下面找到你最新上传的(可以点Content确保是你上传的)，选中之后点击上面的`Close`按钮 和`Release`按钮(多谢[@drak11t](http://weibo.com/drak11t)的提示)。
  
  Ok了，还需要等待一小段时间才能在 http://search.maven.org 搜索到你的包。

##链接
 - [OSSRH Guide](http://central.sonatype.org/pages/ossrh-guide.html) 官方指南
 - [GPG入门教程](http://www.ruanyifeng.com/blog/2013/07/gpg.html)
 - [gradle-mvn-push](https://github.com/chrisbanes/gradle-mvn-push) 
 - [Sonatype Nexus Professional](https://oss.sonatype.org/)