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
 * W 函数别名
 *
 * @param $name
 * @param array $data
 * @return bool|mixed|void
 */
function widget($name, $data = array())
{
    return W($name, $data);
}

/**
 * URL安全的base64
 *
 * @param string $str
 * @return string
 */
function base64_encode_safe($str = '')
{
    $str = base64_encode($str);
    $str = str_replace(array('+', '/', '='), array('-', '_', '!'), $str);
    return $str;
}

/**
 * @param string $str
 * @return mixed|string
 */
function base64_decode_safe($str = '')
{
    $str = str_replace(array('-', '_', '!'), array('+', '/', '='), $str);
    $str = base64_decode($str);
    return $str;
}

/**
 * 为浏览器的 FirePHP / ChromePHP 扩展输出响应头数据，以便调试
 *
 * <code>
 * console($data);
 * console($data, true);
 * </code>
 *
 * @param $data
 * @param $time
 */
function console($data, $time = false)
{
    static $logger;
    static $index = 0;
    static $lasttime = APP_START_TIME;

    $thistime = microtime(true);
    $usedtime = $thistime - $lasttime;
    $lasttime = $thistime;
    $label = $time ? sprintf("%09.5fs", $usedtime) : null;

    if (is_array($data)) {
        ksort($data);
    }

    if (is_null($logger)) {
        if (strstr($_SERVER['HTTP_USER_AGENT'], ' Firefox/')) {
            $logger = new ThinkSDK\Library\Debug\Console\FirePHP();
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], ' Chrome/')) {
            $logger = ThinkSDK\Library\Debug\Console\ChromePHP::getInstance();
        } else {
            $logger = false;
        }
    }

    if ($logger) {
        if ($logger instanceof ThinkSDK\Library\Debug\Console\FirePHP) {
            $logger->info($data, $label);
        } else if ($logger instanceof ThinkSDK\Library\Debug\Console\ChromePHP) {
            if ($label) {
                $logger->info($label, $data);
            } else {
                $logger->info($data);
            }
        }
    } else {
        $name = 'Console-' . ($index++);
        if ($label) {
            $name .= '#' . $label;
        }
        header($name . ':' . json_encode($data));
    }
}

/**
 * 读取应用默认 .env 文件配置
 *
 * @param string $key
 * @param null $default
 * @return null
 * @throws Exception
 */
function env($key = '', $default = null)
{
    static $envs = null;
    $env = APP_PATH. '.env';
    $key = trim($key);

    if (is_null($envs)) {
        if (is_file($env) === false && is_readable($env) === false) {
            throw new \Exception('Env File Missing');
        }
        $envs = parse_ini_file($env, true);
        if ($envs === false) {
            throw new \Exception('Env File Parse Error');
        }
    }

    if (!empty($key)) {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $section = $keys[0];
            $index = $keys[1];

            if (isset($envs[$section][$index])) {
                return $envs[$section][$index];
            } elseif (!is_null($default)) {
                return $default;
            }
        } else {
            if (isset($envs[$key])) {
                return $envs[$key];
            } else {
                return $default;
            }
        }
    } elseif (!is_null($default)) {
        return $default;
    } else {
        return $envs;
    }

    return null;
}

/**
 * @return \ThinkSDK\Library\Crypt\Hashids\Hashids
 */
function hashid()
{
    static $hashid = null;

    $length = 4;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890';

    if (is_null($hashid)) {
        $hashkey = config('APP_HASHID_KEY') ? : '';
        $hashid = new ThinkSDK\Library\Crypt\Hashids\Hashids($hashkey, $length, $alphabet);
    }

    return $hashid;
}

/**
 * @param $id
 * @return string
 */
function eid($id)
{
    $id = intval($id);
    return hashid()->encode($id);
}

/**
 * @param $hash
 * @return int
 */
function did($hash)
{
    $decode = hashid()->decode($hash);
    return !empty($decode) ? $decode[0] : 0;
}