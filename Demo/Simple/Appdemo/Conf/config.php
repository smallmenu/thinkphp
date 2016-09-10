<?php
/**
 * 单应用单模块重载 ThinkSDK 默认配置
 */
if (is_file(THINKSDK_PATH . 'Conf/config.php')) $THINKSDK_CONFIG = include THINKSDK_PATH . 'Conf/config.php';

return array_merge($THINKSDK_CONFIG,
    array(
        // 加载THINKSDK 命名空间
        'AUTOLOAD_NAMESPACE' => array(
            'ThinkSDK' => THINKSDK_PATH,
        ),

        // 单应用单模块配置覆盖
        'DEFAULT_FILTER'     => 'htmlspecialchars',

        // 单应用单模块配置
        'simple'             => 'test',
    )
);