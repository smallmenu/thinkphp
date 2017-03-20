<?php
namespace ThinkSDK\Library\Spider\Sogou;

class SogouWeixin
{
    /**
     *
     */
    const WEIXIN_URL = 'http://weixin.sogou.com/weixin?type=1&ie=utf8&query=';

    /**
     * @var null
     */
    private $requestUrl = null;

    /**
     * @var null
     */
    private $keyword = null;

    /**
     * @param $options
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * @param $keyword
     * @param $page
     * @param $day
     * @return bool
     */
    public function getListByKeyword($keyword, $page, $day = 3)
    {
        $lists = array();
        $this->keyword = $keyword = trim($keyword);
        $page = intval($page);

        if ($page > 10 || $page < 1) {
            return false;
        }

        if (empty($keyword)) {
            return false;
        } else {
            $keyword = urlencode($keyword);
        }

        $this->requestUrl = self::WEIXIN_URL . $keyword;
        $this->requestUrl = $this->requestUrl . '&page=' . $page;

        $listRequest = request($this->requestUrl, null, 5, false, $this->options);

        if ($listRequest['httpcode'] == 200) {

            $content = $listRequest['content'];

            if (strpos($content, '请输入验证码') === false) {
                println(array('请求列表成功'), true, false);

                preg_match_all('#<!-- a -->([\s\S]*)<!-- z -->#U', $content, $listContent);

                foreach ($listContent[1] as $key => $list) {

                    preg_match('#<p class="tit">([\s\S]*)</p>#U', $list, $weixinname);
                    preg_match('#<label name="em_weixinhao">(.*)</label>#U', $list, $weixinhao);
                    preg_match('#" d="(.*)"#U', $list, $weixinid);
                    preg_match('#<dt>功能介绍：</dt>([\s\S]*)</dd>#U', $list, $weixindesc);
                    preg_match('#认证：</dt>([\s\S]*)</dd>#U', $list, $weixinauth);
                    preg_match('#<a target="_blank" uigs="account_article(.*)href="(.*)">(.*)</a>([\s\S]*)\(timeConvert\(\'(.*)\'\)\)</script>#U', $list, $article);

                    if (!empty($weixinname[1]) && !empty($weixinhao[1]) && !empty($weixinid) && !empty($article)) {
                        $weixin = array();
                        $weixin['name'] = trim(strip_tags($weixinname[1]));
                        $weixin['openid'] = trim($weixinid[1]);
                        $weixin['uid'] = trim($weixinhao[1]);
                        $weixin['desc'] = trim(strip_tags($weixindesc[1]));
                        $weixin['auth'] = trim(strip_tags($weixinauth[1]));
                        $weixin['url'] = str_replace('&amp;', '&', trim($article[2]));
                        $weixin['title'] = trim(strip_tags($article[3]));
                        $weixin['published'] = date('Y-m-d H:i:s', trim($article[5]));

                        if ($this->_validator($weixin, $day)) {
                            $lists[] = $weixin;
                        }
                    }
                }
            } else {
                println(array('请输入验证码'), false, false);
            }
        } else {
            println(array('请求失败'), false, false);
        }

        $result['total'] = count($lists);
        $result['list'] = $lists;

        return $result;
    }

    /**
     * 数据校验
     *
     * @param $data
     * @param $day
     * @return bool
     */
    private function _validator($data, $day)
    {
        // 校验空值
        foreach ($data as $key => $d) {
            if (empty($d)) {
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