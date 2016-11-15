<?php
namespace ThinkSDK\Library\Spider\Baidu;

class BaiduNews
{
    const BAIDUNEWS_URL = 'http://news.baidu.com/ns';

    private $requestUrl = null;

    private $keyword = null;



    public function __construct()
    {

    }

    /**
     * 根据关键词获取百度新闻标题搜索列表
     *
     * @param $keyword 关键词
     * @param bool $strict 严格模式，会对列表中的title进行完全匹配校验，不受分词影响，默认false
     * @param int $pagesize  最大只能是50
     * @param int $day  默认过滤出3天内的新闻
     * @return array|bool
     */
    public function getListByTitle($keyword, $strict = false, $pagesize = 50, $day = 3)
    {
        $lists = array();
        $this->keyword = $keyword = trim($keyword);
        $pagesize = intval($pagesize);
        $baiduTotal = 0;

        if ($pagesize < 10) {
            $pagesize = 10;
        } elseif ($pagesize > 50) {
            $pagesize = 50;
        }

        if (empty($keyword)) {
            return false;
        } else {
            $keyword = urlencode($keyword);
        }

        $this->requestUrl = self::BAIDUNEWS_URL . '?tn=newstitle&rn='.$pagesize. '&word='.$keyword;

        $listRequest = request($this->requestUrl);

        if ($listRequest['httpcode'] == 200) {
            $listContent = $listRequest['content'];

            preg_match('#<span class="nums">找到相关新闻(.*)篇</span>#', $listContent, $baiduTotal);

            if (isset($baiduTotal[1])) {
                $baiduTotal = intval(str_replace(array('约',','), '', $baiduTotal[1]));
            }

            preg_match_all('#<h3 class="c-title">([\s\S]*)&\#8226;&nbsp;#U', $listContent, $listHtmls);

            //print_r($listHtmls);exit;

            if (isset($listHtmls[0]) && !empty($listHtmls[0])) {
                foreach ($listHtmls[0] as $key => $listHtml) {

                    // URL
                    if (preg_match('#<h3 class="c-title"><a href="(.*)"#U', $listHtml, $urls)) {
                        $url = trim($urls[1]);
                        $url = !empty($url) ? $url : '';
                    }

                    // 标题
                    if (preg_match('#<h3 class="c-title">([\s\S]*)</h3>#U', $listHtml, $titles)) {
                        $title = trim(strip_tags($titles[1]));
                        $title = !empty($title)? $title : '';
                    }

                    // 来源
                    if (preg_match('#<div class="c-title-author">(.*)&nbsp;&nbsp;#U', $listHtml, $sources)) {
                        $source = trim($sources[1]);
                        $source = !empty($source)? $source : '';
                    }

                    // 时间
                    if (preg_match('#&nbsp;&nbsp;(.*)&nbsp;&nbsp;#U', $listHtml, $publisheds)) {
                        $published = trim($publisheds[1]);
                        if (!empty($published)) {
                            if (strpos($published, '分钟') !== false) {
                                $published = intval(str_replace('分钟前', '', $published));
                                $published = date('Y-m-d H:i:s', strtotime("- $published minutes"));
                            } else if (strpos($published, '小时') !== false) {
                                $published = intval(str_replace('小时前', '', $published));
                                $published = date('Y-m-d H:i:s', strtotime("- $published hours"));
                            } else {
                                $published = str_replace(array('年', '月'), '-', $published);
                                $published = str_replace('日', '', $published);
                                $published = date('Y-m-d H:i:s', strtotime($published));
                            }
                        }
                        $published = !empty($published)? $published : '';
                    }


                    // 数据过滤
                    $cell = array(
                        'url' => $url,
                        'title' => $title,
                        'source' => $source,
                        'published' => $published
                    );
                    if ($this->_validator($cell, $strict, $day)) {
                        $lists[] = $cell;
                    }
                }
            }
        }

        $result['keyword'] = $keyword;
        $result['baiduTotal'] = $baiduTotal;
        $result['total'] = count($lists);
        $result['list'] = $lists;

        return $result;
    }

    /**
     * 数据校验
     *
     * @param $data
     * @param $strict
     * @param $day
     * @return bool
     */
    private function _validator($data, $strict, $day)
    {
        // 校验空值
        foreach($data as $key => $d) {
            if (empty($d)) {
                return false;
            }
        }

        // 校验严格模式下的标题，确保不被分词
        if ($strict && stripos($data['title'], $this->keyword) === false) {
            return false;
        }

        // 校验日期时间，保留最近day天
        $validatorTime = strtotime('-'. $day. ' days');
        $publishedTime = strtotime($data['published']);
        if ($publishedTime < $validatorTime) {
            return false;
        }

        return true;
    }
}