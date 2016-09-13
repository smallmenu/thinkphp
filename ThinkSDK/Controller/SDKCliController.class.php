<?php
namespace ThinkSDK\Controller;

use Think\Controller;

class SDKCliController extends Controller
{
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