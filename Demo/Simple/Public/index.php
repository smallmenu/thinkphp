<?php
// 调试模式
define('APP_DEBUG', true);
// 关闭目录安全文件
define('BUILD_DIR_SECURE', false);
// 开始时间
define('APP_START_TIME', microtime(true));
// 默认模块
define('BIND_MODULE', 'Appdemo');
// 入口目录
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
// 应用目录
define('APP_PATH', ROOT_PATH . '../');
// 运行时目录
define('RUNTIME_PATH', ROOT_PATH . '../Runtime/');
// THINKPHP目录
define('THINK_PATH', ROOT_PATH . '../../../ThinkPHP/');
// SDK目录
define('THINKSDK_PATH', ROOT_PATH . '../../../THINKSDK/');

// 启动
require THINK_PATH . 'ThinkPHP.php';