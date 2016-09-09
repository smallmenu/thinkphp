# TotoroThink

## ThinkPHP CHANGELOG


## ThinkSDK CHANGELOG

## 示例

### Demo/Simple 单应用单模块

单应用单模块的应用场景，比如一个独立的API服务

这种情况下实际不需要公共的Common目录，直接到最底层目录，不过需要在入口文件指定 BIND_MODULE，所以目录结构看起来是这样：

```
Simple/
├── Application  # 默认应用目录，其实这级目录都应该略去，但是好像做不到
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
└── Runtime  # Linux需要可写
```

```
http://simple.thinkphp.loc/  (c=index&a=index)
http://simple.thinkphp.loc/index/  (c=index&a=index)
http://simple.thinkphp.loc/test/  (c=test&a=index)
http://simple.thinkphp.loc/index/test  (c=index&a=test)
http://simple.thinkphp.loc/test/test  (c=test&a=test)
```