---
title: 利用 GitHub 的 Webhook 部署博客
date: 2015-01-21 00:01:28
tags: 
 - Github
 - Blog
categories: 瞎折腾
---
GitHub 现在比较难打开了，决定把博客放到自己的 vps 上。
当又想同步到 GitHub 上，如果每次都要手动到 vps 上执行 pull，那太麻烦了！！！
GitHub 的仓库可以设置 `Webhook`，当收到 push 后会通知到设定的 url，救星来啦～～～
<!--more-->
看了下 api 文档，用 php 写了 `git-hook.php` 文件放到博客目录下:
```php
<?php
error_reporting(7);
date_default_timezone_set('UTC');
define("WWW_ROOT", "/www/linroid.com/");
define("LOG_FILE", "/www/logs/linroid.com/git-hook.log");
$shell = sprintf("cd %s && /usr/bin/git pull 2>&1", WWW_ROOT);
$output = shell_exec($shell);
$log = sprintf("[%s] %s \n", date('Y-m-d H:i:s', time()), $output);
echo $log;
file_put_contents(LOG_FILE, $log, FILE_APPEND);
```
通过 ssh 执行 `php git-hook.php` 成功，但 url 访问时失败了。vps 上是通过 php-fpm 执行 php 的，用户为 `www-data`，shell 为`/usr/sbin/nologin`,会找不到 git 命令,需要使用 git 的绝对路径:
`$shell = sprintf("cd %s && /usr/bin/git pull", WWW_ROOT);`

出现权限问题
`error: cannot open .git/FETCH_HEAD: Permission denied`

修改目录所属：
`sudo chown  -R www-data:www-data ./linroid.com`

在仓库的webhook里添加url `http://linroid.com/git-hook.php`,然后 vps 就可以从 GitHub 自动 pull 了~