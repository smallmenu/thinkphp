<?php
/**
 * C 函数别名
 *
 * @param null $name
 * @param null $value
 * @param null $default
 * @return mixed
 */
function config($name = null, $value = null, $default = null)
{
    return C($name, $value, $default);
}

/**
 * I 函数别名
 *
 * @param $name
 * @param string $default
 * @param null $filter
 * @param null $datas
 * @return mixed
 */
function filter($name, $default = '', $filter = null, $datas = null)
{
    /**
     * 此处Hack了一下ThinkPHP框架的I函数，为了支持URL中文不同的编码
     * 当使用/c修正符时，会判断，$GET是否为utf-8，否则则使用GBK转换
     */
    $origin = $name;
    if (strpos($name, '/')) {
        list($name, $type) = explode('/', $name, 2);
    } else {
        $type = '';
    }
    if (strpos($name,'.')) { // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else {
        $method = 'get';
    }
    if (strtolower($method) == 'get' && $type == 'c') {
        if (isset($_GET[$name]) && mb_detect_encoding($_GET[$name], 'UTF-8', true) != 'UTF-8') {
            $_GET[$name] = iconv('GBK', 'UTF-8', $_GET[$name]);
        }
    }

    return I($origin, $default, $filter, $datas);
}

/**
 * U 函数别名
 *
 * @param string $url
 * @param string $vars
 * @param bool $suffix
 * @param bool $domain
 * @return string
 */
function url($url = '', $vars = '', $suffix = true, $domain = false)
{
    return U($url, $vars, $suffix, $domain);
}

/**
 * M 函数别名
 *
 * @param string $name
 * @param string $tablePrefix
 * @param string $connection
 * @return Model|\Think\Model
 */
function model($name = '', $tablePrefix = '', $connection = '')
{
    return M($name, $tablePrefix, $connection);
}

/**
 * D 函数别名
 *
 * @param string $name
 * @param string $layer
 * @return Model|\Think\Model
 */
function data($name = '', $layer = '')
{
    return D($name, $layer);
}

/**
 * T 函数别名
 *
 * @param string $template
 * @param string $layer
 * @return string
 */
function template($template = '', $layer = '')
{
    return T($template, $layer);
}

/**
 * S 函数别名
 *
 * @param $name
 * @param string $value
 * @param null $options
 * @return mixed
 */
function cache($name, $value = '', $options = null)
{
    return S($name, $value, $options);
}

/**
 * @param $name
 * @param array $data
 */
function widget($name, $data = array())
{
    return W($name, $data);
}