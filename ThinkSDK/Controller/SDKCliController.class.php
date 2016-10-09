<?php
namespace ThinkSDK\Controller;

use Think\Controller;

class SDKCliController extends Controller
{
    /**
     * @var null
     */
    protected $define = null;

    /**
     * @var null
     */
    protected $setting = null;

    /**
     * @var \Think\Cache\Driver\Redis
     */
    protected $cache = null;

    /**
     * initialize
     */
    public function _initialize()
    {
        if (PHP_SAPI !== 'cli') {
            send_http_status(404); exit;
        }
        set_time_limit(0);
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');

        $this->define = config('define');
        $this->setting = config('setting');

        $this->_init();
    }

    /**
     *
     */
    private function _init()
    {
        /**
         * 初始化 cache 通过domain, prefix, module避免污染，实现应用模块私有缓存
         */
        $domain = $this->define['DOMAIN'];
        if ($cache = config('cache')) {
            $module = strtolower(MODULE_NAME);
            $cache['prefix'] = $domain. ':'. $cache['prefix']. $module . ':';
            config('cache.prefix', $cache['prefix']);
            $this->cache = cache($cache);
        }
    }

    /**
     * 友好的控制台打印
     *
     * @param $datas
     * @param bool $exit
     * @param bool $result
     */
    protected function println($datas, $result = true, $exit = true)
    {
        static $lasttime = APP_START_TIME;

        $thistime = microtime(true);
        $usedtime = $thistime - $lasttime;
        $lasttime = $thistime;
        $usedtime = sprintf("% 7d ms] ", $usedtime * 1000);

        $memory = memory_get_usage() / 1000000;
        $memory = sprintf("% 6.1f MB ", $memory);

        $message = date('[m-d H:i:s ');
        $message .= $memory . $usedtime;

        if (is_array($datas) && !empty($datas)) {
            $message .= '[';
            $message .= implode('||', $datas);
            $message .= '] ';
        } else {
            $message .= $datas;
        }

        $message .= $result ? '[SUCCESS]' : '[FAILED]';

        echo $message;
        echo PHP_EOL;
        if ($exit) {
            exit;
        }
    }
}