<?php
error_reporting(7);
date_default_timezone_set('UTC');
define("WWW_ROOT", "/www/linroid.com/");
define("LOG_FILE", "/www/logs/linroid.com/git-hook.log");
if(isset($_REQUEST['payload'])){
    $shell = sprintf("cd %s && /usr/bin/git pull 2>&1", WWW_ROOT);
    $output = shell_exec($shell);
    $log = sprintf("[%s] %s \n", date('Y-m-d H:i:s', time()), $output);
    echo $log;
    file_put_contents(LOG_FILE, $log, FILE_APPEND);
}
