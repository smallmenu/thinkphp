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
        send_http_status($code);
        exit;
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