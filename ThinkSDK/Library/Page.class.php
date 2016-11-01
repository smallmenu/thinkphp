<?php

/**
 *
 */
namespace ThinkSDK\Library;

class Page
{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage = 11; // 分页栏每页显示的页数

    private $p = 'p'; //分页参数名
    private $url = ''; //当前链接URL
    private $nowPage = 1;

    // 分页显示定制
    private $config = array(
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        'prev'   => '<<',
        'next'   => '>>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    );

    /**
     * 架构函数
     *
     * @param array $totalRows 总的记录数
     * @param int $listRows 每页显示记录数
     * @param array $parameter 分页跳转的参数
     */
    public function __construct($totalRows, $listRows = 20, $parameter = array())
    {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->totalRows = $totalRows; //设置总记录数
        $this->listRows = $listRows; //设置每页显示行数
        $this->parameter = empty($parameter) ? $_GET : $parameter;
        $this->nowPage = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage = $this->nowPage > 0 ? $this->nowPage : 1;
        $this->firstRow = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 定制分页链接设置
     *
     * @param string $name 设置名称
     * @param string $value 设置值
     */
    public function setConfig($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page)
    {
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 根据 ThinkPHP 分页算法扩展，返回分页数组形式
     *
     * @param null $url
     * @return array|string
     */
    public function showPages($url = null)
    {
        if (0 == $this->totalRows) return '';

        $this->rollPage = 8;

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';

        $this->url = is_null($url) ? U(ACTION_NAME, $this->parameter) : $url;

        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        if ($this->lastSuffix) {
            $this->config['last'] = $this->totalPages;
        }

        $ret = array();

        //上一页
        $up_row = $this->nowPage - 1;
        if ($up_row > 0) {
            if ($up_row == 1) {
                $linkUrl = $this->showPagesClear();
            } else {
                $linkUrl = $this->url($up_row);
            }
            $ret['prev'] = array(
                'url' => $linkUrl,
                'p' => $up_row
            );
        }

        //下一页
        $down_row = $this->nowPage + 1;
        if ($down_row <= $this->totalPages) {
            $ret['next'] = array(
                'url' => $this->url($down_row),
                'p' => $down_row
            );
        }
        $ret['link'] = array();

        //数字连接
        if ($this->nowPage > $now_cool_page_ceil && ($this->nowPage <= $this->totalPages) && $this->totalPages > 11 && $this->nowPage != 6) {
            $ret['first'] = array(
                'url' => $this->showPagesClear(),
                'p' => 1,
            );
            if ($this->nowPage != 7) {
                $ret['link'][] = array(
                    'url' => '',
                    'p' => '...',
                );
            }
        } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages && $this->totalPages > 11) {
            $ret['first'] = array(
                'url' => $this->showPagesClear(),
                'p' => 1,
            );
        }

        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } elseif (($this->totalPages - $this->nowPage == 7 || $this->totalPages - $this->nowPage == 6)) {
                $page = $this->nowPage - $now_cool_page + $i - 1;
                if ($this->nowPage == 7) $page = $this->nowPage - $now_cool_page + $i - 1;
            } else {
                $page = $this->nowPage - $now_cool_page + $i + 1;
            }
            if ($page == 1) {
                $linkUrl = $this->showPagesClear();
            } else {
                $linkUrl = $this->url($page);
            }
            if ($page > 0 && $page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $ret['link'][] = array(
                        'url' => $linkUrl,
                        'p' => $page,
                    );
                } else {
                    break;
                }
            } else {
                if ($page > 0 && $this->totalPages != 1) {
                    $ret['link'][] = array(
                        'url'     => $linkUrl,
                        'p'     => $page,
                        'current' => true,
                    );
                }
            }
        }
        if (($this->totalPages - $this->nowPage > $now_cool_page) && $this->totalPages > 12) {
            $ret['end'] = array(
                'url' => $this->url($this->totalPages),
                'p' => $this->totalPages,
            );
            if ($this->totalPages - $this->rollPage >= 2 && $this->totalPages > 12) {
                $ret['link'][] = array(
                    'url' => '',
                    'p' => '...',
                );
            }
        }
        return $ret;
    }

    private function showPagesClear()
    {
        $url = $this->url;

        if (strpos($url, urlencode('_[PAGE]'))) {
            $url = str_replace(urlencode('_[PAGE]'), '', $url);
            return $url;
        }
        if (strpos($url, '?p='.urlencode('[PAGE]'))) {
            $url = str_replace('?p='.urlencode('[PAGE]'), '', $url);
            return $url;
        }
        if (strpos($url, '&p='.urlencode('[PAGE]'))) {
            $url = str_replace('&p='.urlencode('[PAGE]'), '', $url);
            return $url;
        }
        if (strpos($url, 'p='.urlencode('[PAGE]'))) {
            $url = str_replace('p='.urlencode('[PAGE]'), '', $url);
            return $url;
        }

        return $url;
    }
}
