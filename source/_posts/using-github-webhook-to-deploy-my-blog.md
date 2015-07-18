title: 利用Github的Webhook部署博客
date: 2015-01-21 00:01:28
tags: Github
---
Github现在比较难打开了，决定把博客放到自己的vps上。
为了能在Github上产生点动态,不想让hexo直接push到vps上~.~如果每次都要手动到vps上执行pull，那太麻烦了！！！
Github的仓库可以设置`Webhook`，当收到push后会通知到设定的url，救星来啦～～～
看了下api文档，用php写了`git-hook.php`文件放到博客目录下:
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
通过ssh执行`php git-hook.php`成功,但url访问时失败了.vps上是通过php-fpm执行php的，用户为`www-data`，shell为`/usr/sbin/nologin`,会找不到git命令,需要使用git的绝对路径:
`$shell = sprintf("cd %s && /usr/bin/git pull", WWW_ROOT);`

出现权限问题
`error: cannot open .git/FETCH_HEAD: Permission denied`

修改目录所属：
`sudo chown  -R www-data:www-data ./linroid.com`

在仓库的webhook里添加url `http://linroid.com/git-hook.php`,然后vps就可以从Github自动pull了~