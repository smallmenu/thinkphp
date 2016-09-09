<?php
/**
 * 域名部署，公共模块配置重载
 */
if (is_file(THINKSDK_PATH . 'Conf/config.php')) $THINKSDK_CONFIG = include THINKSDK_PATH . 'Conf/config.php';

return array_merge($THINKSDK_CONFIG,
    array(
        // 加载THINKSDK 命名空间
        'AUTOLOAD_NAMESPACE'    => array(
            'ThinkSDK' => THINKSDK_PATH,
        ),

        // 子域名部署
        'APP_SUB_DOMAIN_DEPLOY' => 1,
        'APP_DOMAIN_SUFFIX'     => 'com',
        'APP_SUB_DOMAIN_RULES'  => array(
            'www' => 'Www',
            'm'   => 'Mobile',
        )
    )
);