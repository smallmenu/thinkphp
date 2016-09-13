<?php
/**
 * 公共SDK配置
 */
return array(
    /* 应用设定 */
    'ACTION_SUFFIX'        => 'Action',

    /* 默认设定 */
    'DEFAULT_MODULE'       => 'Index',
    'DEFAULT_FILTER'       => 'trim,htmlspecialchars',

    /* 错误设置 */
    'ERROR_MESSAGE'        => '系统错误',

    /* 日志设置 */
    'LOG_RECORD'           => true,
    'LOG_FILE_SIZE'        => 20971520,
    'LOG_EXCEPTION_RECORD' => true,

    /* 模板引擎设置 */
    'TMPL_ENGINE_TYPE'     => 'PHP',
    'TMPL_TEMPLATE_SUFFIX' => '.phtml',

    /* URL设置 */
    'URL_MODEL'            => 2,
    'URL_HTML_SUFFIX'      => 'html',

    /* 系统变量名称设置 */
    'CHECK_APP_DIR'        => false,
);