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
     * @param int $pagesize 最大只能是50
     * @param int $day 默认过滤出3天内的新闻
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
            $keyword = urlencode('title:' . $keyword);
        }

        $this->requestUrl = self::BAIDUNEWS_URL . '?tn=json&rn=' . $pagesize . '&word=' . $keyword;

        $listRequest = request($this->requestUrl);

        if ($listRequest['httpcode'] == 200) {
            $listContent = $listRequest['content'];
            $listContent = json_decode($listContent, true);

            if (isset($listContent['feed']) && $listContent['feed']['all'] > 0) {
                $baiduTotal = intval($listContent['feed']['all']);

                $listHtmls = $listContent['feed']['entry'];

                foreach ($listHtmls as $key => $listHtml) {
                    // URL
                    $url = trim($listHtml['url']);
                    $url = !empty($url) ? $url : '';

                    // 标题
                    $title = trim(strip_tags($listHtml['title']));
                    $title = !empty($title) ? $title : '';

                    // 来源
                    $source = trim($listHtml['source']);
                    $source = !empty($source) ? $source : '';

                    // 时间
                    $published = !empty($listHtml['time']) ? date('Y-m-d H:i:s', $listHtml['time']) : '';

                    // 数据过滤
                    $cell = array(
                        'url'       => $url,
                        'title'     => $title,
                        'source'    => $source,
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
        foreach ($data as $key => $d) {
            if (empty($d)) {
                return false;
            }
        }

        // 校验严格模式下的标题，确保不被分词
        if ($strict !== false) {
            if ($strict === true && stripos($data['title'], $this->keyword) === false) {
                return false;
            } else if (stripos($data['title'], $strict) === false) {
                return false;
            }
        }

        // 校验日期时间，保留最近day天
        $validatorTime = strtotime('-' . $day . ' days');
        $publishedTime = strtotime($data['published']);
        if ($publishedTime < $validatorTime) {
            return false;
        }

        return true;
    }
}