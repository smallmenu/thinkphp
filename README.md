# TotoroThink

## ThinkPHP CHANGELOG

```
# 移除 ThinkPHP 以外的文件和目录
- Application/, Public/,

# 去除模板输出时 X-Powered-By 信息
* ThinkPHP/Library/Think/View.class.php

# 修改默认模板
* ThinkPHP/Tpl/*

# 修复一处目录创建 NOTICE 错误
* ThinkPHP/Library/Think/Build.class.php

# Redis 驱动增加 db 配置
* ThinkPHP/Library/Think/Cache/Driver/Redis.class.php

# 调整 SHOW_PAGE_TRACE 触发机制，不仅要开启还需要根据UA控制显示，便于线上调试
* ThinkPHP/Library/Behavior/*ShowPageTraceBehavior.class.php

# 调整 appException 自定义异常处理函数，让路由错误404，异常输出503，默认全部输出404是不对的
* ThinkPHP/Library/Think/Think.class.php
```

## ThinkSDK CHANGELOG

## 示例

### Demo/Simple 单应用单模块

单应用单模块的应用场景，比如一个独立的API服务

这种情况下实际不需要公共的Common目录，直接到最底层目录，不过需要在入口文件指定 BIND_MODULE，所以目录结构看起来是这样：

```
Simple/
├── Appdemo  # 单应用单模块应用目录，其实这级目录都应该略去，但是好像做不到啊
│   ├── Common
│   │   └── function.php
│   ├── Conf
│   │   └── config.php
│   ├── Controller
│   │   ├── IndexController.class.php
│   │   └── TestController.class.php
│   ├── Model
│   └── View
├── Public
│   ├── assets
│   │   ├── css
│   │   └── js
│   ├── index.php
│   └── robots.txt
└── Runtime
```

```
http://simple.thinkphp.loc/  (c=index&a=index)
http://simple.thinkphp.loc/index/  (c=index&a=index)
http://simple.thinkphp.loc/test/  (c=test&a=index)
http://simple.thinkphp.loc/index/test  (c=index&a=test)
http://simple.thinkphp.loc/test/test  (c=test&a=test)
```