---
title: 提交 library 项目到 Maven Central
date: 2015-03-13 10:51:42
tags:
	- gradle
categories: 瞎折腾
---
  将 [FilterMenu](http://github.com/linroid/FilterMenu) 提交到 GitHub 后，在 README.md 的 Getting Started 里仅仅写上

  > Download the source to use it as library project

  这唯一使用途径，居然没有 gradle/maven ?作为 Android Studio 的忠实用户，自己写的库怎么能只提供这么麻烦的方法！！！于是决定把它提交到 Maven Central 中。
<!--more-->

虽然 AS 的 gradle 默认使用的是 jcenter 仓库，但我们只需要提交到 Maven Central 即可，jcenter 会自动同步。  

__如果还没有账号先去 Maven Central 注册: [Sign up](https://issues.sonatype.org/secure/Signup!default.jspa) __

并到 [Create Issue](https://issues.sonatype.org/secure/CreateIssue.jspa?issuetype=21&pid=10134) 提交工单等待管理员的回复，填 groupId 时，请使用顶级 groupId，比如我只需要填写 `com.linroid`，就可以发布到任何 `com.linroid.*` 下的groupId。

我是早上提交的，到晚上0:30的时候收到回复。[Why the wait?](http://central.sonatype.org/articles/2014/Feb/27/why-the-wait/)

> Configuration has been prepared, now you can:
Deploy snapshot artifacts into repository https://oss.sonatype.org/content/repositories/snapshots
Deploy release artifacts into the staging repository https://oss.sonatype.org/service/local/staging/deploy/maven2
Promote staged artifacts into repository 'Releases'
Download snapshot and release artifacts from group https://oss.sonatype.org/content/groups/public
Download snapshot, release and staged artifacts from staging group https://oss.sonatype.org/content/groups/staging  
please comment on this ticket when you promoted your first release, thanks

然后通过下面的方法 Release，再次回复那个 issue，几分钟后又收到回复:

> Central sync is activated for com.linroid. After you successfully release, your component will be published to Central, typically within 10 minutes, though updates to search.maven.org can take up to two hours.

## 通过 gradle-mvn-push

 [Chris Banes](https://chris.banes.me/)大神很早前写了一个插件 [gradle-mvn-push](https://github.com/chrisbanes/gradle-mvn-push)(终于有机会用它了^﹏^)，让你通过一条gradle命令就可以自动构建好aar并提交到 Maven Central。下面介绍这个插件的使用方法。

 - 配置用于上传的__认证信息__
  配置文件默认在` ${HOME}/.gradle/gradle.properties`，如果没有则自己创建。

	```properties
	NEXUS_USERNAME=linroid
	NEXUS_PASSWORD=YOUR_MAVEN_CENTRAL_PASSWORD

	signing.keyId=YOUR_GPG_KEY_ID
	signing.password=YOUR_GPG_PASSWORD
	signing.secretKeyRingFile=${HOME}/.gnupg/secring.gpg
	```
 `NEXUS_USERNAME` 和 `NEXUS_PASSWORD` 是你注册的用户名和密码，下面的是用于 GPG 校验的配置信息，关于 GPG 的使用可以参见阮一峰的博文:[GPG 入门教程](http://www.ruanyifeng.com/blog/2013/07/gpg.html)

 - 配置__版本信息__
   在你的module目录创建`gradle.properties`文件，添加配置:
	```
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
  在 library module 的`build.gradle`文件中添加
	```groovy
	apply from: 'https://raw.github.com/chrisbanes/gradle-mvn-push/master/gradle-mvn-push.gradle
	```
  或者可以将 [gradle-mvn-push.gradle](https://raw.githubusercontent.com/chrisbanes/gradle-mvn-push/master/gradle-mvn-push.gradle) 文件下载下来，然后将上面的url该为本地路径。

 - __执行gradle task__
  输入下面的命令，就可以自动构建并上传啦
	```bash
	$ gradle clean build uploadArchives
	```
  出现如下结果就说明上传成功了:）
	![执行成功](/images/posts/maven-central-success.png)
  还没结束，此时你的库并没有发布.
  
 - __最后一步__：close staging repositories 
  登陆 [Sonatype Nexus Professional](https://oss.sonatype.org/) 点开左边 Build Promotion 的 Staging Repositories ，滚到最下面找到你最新上传的(可以点Content确保是你上传的)，选中之后点击上面的 `Close` 按钮 和 `Release` 按钮(多谢[@drak11t](http://weibo.com/drak11t)的提示)。
  
  Ok 了，还需要等待一小段时间才能在 http://search.maven.org 搜索到你的包。

##链接
 - [OSSRH Guide](http://central.sonatype.org/pages/ossrh-guide.html) 官方指南
 - [GPG 入门教程](http://www.ruanyifeng.com/blog/2013/07/gpg.html)
 - [gradle-mvn-push](https://github.com/chrisbanes/gradle-mvn-push) 
 - [Sonatype Nexus Professional](https://oss.sonatype.org/)