<?php
namespace ThinkSDK\Controller;

use Think\Controller;

class SDKController extends Controller
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
     * @var int
     */
    protected $pagesize = 15;

    /**
     * @var null
     */
    protected $seo = null;

    /**
     * @var null
     */
    protected $seos = null;

    /**
     * initialize
     */
    public function _initialize()
    {
        $this->define = config('define');
        $this->setting = config('setting');

        $this->_init();
        $this->_assign();
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
     * 自动注入模板信息
     */
    private function _assign()
    {
        // assign APP
        if ($this->define) {
            $this->assign($this->define);
        }

        // assign SEO
        if ($this->setting && isset($this->setting['seo'])) {
            $seoConfig = $this->setting['seo'];
            $module = strtolower(MODULE_NAME);
            $controller = strtolower(CONTROLLER_NAME);
            $action = strtolower(ACTION_NAME);

            $seos = $this->seos = isset($seoConfig[$module][$controller][$action]) ? $seoConfig[$module][$controller][$action] : null;
            if ($seos && isset($seos['default'])) {
                $this->seo = $seos['default'];
                $this->assign('seo', $this->seo);
            }
        }

    }

    /**
     * 输出 HTTP 状态码
     *
     * @param $code
     */
    protected function httpCode($code)
    {
        if ($code == 404) {
            $this->_empty();
        } else {
            send_http_status($code);
        }
        exit;
    }

    /**
     * 不存在Action的时候执行
     */
    protected function _empty()
    {
        send_http_status(404);
    }

    /**
     * HTTP CacheControl缓存控制，主要用于CDN加速情况的动态脚本缓存
     *
     * @param $second
     */
    protected function httpCacheControl($second = 300)
    {
        $second = intval($second);
        if ($second > 0) {
            // HTTP_CACHE_CONTROL 在ThinkPHP框架模板渲染时输出
            config('HTTP_CACHE_CONTROL', 'max-age='.$second);
            // 兼容HTTP 1.0 的写法
            header('Expires: '. gmdate('D, d M Y H:i:s', time() + $second). ' GMT');
            header_remove('Pragma');
        }
    }

    /**
     * @param $message
     * @param $type
     */
    protected function ajaxSuccess($message = '', $type = 'json')
    {
        $ajax = array(
            'message' => $message,
            'status'  => true,
        );
        $this->ajaxReturn($ajax, $type);
    }

    /**
     * @param $message
     * @param $type
     */
    protected function ajaxError($message = '', $type = 'json')
    {
        $ajax = array(
            'message' => $message,
            'status'  => false,
        );
        $this->ajaxReturn($ajax, $type);
    }
}