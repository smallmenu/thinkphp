<?php
/**
 *
 */
namespace ThinkSDK\Library\Util\XFormat;

class Content
{
    /**
     * @var array
     * 默认保留标签
     */
    public static $allow_tags = array(
        'p', 'a', 'img', 'span', 'br', 'b', 'strong', 'table', 'td', 'tr', 'th', 'tbody', 'theader'
    );

    /**
     * Tiny Config
     * @var array
     */
    public static $tidy_configs = array(
        'show-body-only' => true,
        'input-encoding' => 'utf8',
        'output-encoding' => 'utf8',
        'preserve-entities' => true,
    );

    /**
     * @param $content
     * @param null $allow_tags
     * @return string
     * 去除不需要的标签
     */
    public static function strip_content($content, $allow_tags = null, $tidy_configs = null)
    {
        if (null === $allow_tags)
            $allow_tags = self::$allow_tags;

        if (null === $tidy_configs)
            $tidy_configs = self::$tidy_configs;

        // 执行清理
        $tidy = new \tidy();
        $content = $tidy->repairString($content, $tidy_configs);

        $allow_tags = array_unique(array_filter(array_map('trim', $allow_tags)));
        if ($allow_tags) {
            $allow_tags = '<' . implode('><', $allow_tags) . '>';
        } else {
            $allow_tags = null;
        }
        return strip_tags($content, $allow_tags);
    }

    /**
     * @param $matches
     * @param null $allow_tags
     * @return string
     * 根据匹配情况处理p标签和换行
     */
    public static function parse_tag($matches, $allow_tags = null)
    {
        list($with_tag, $is_close, $tag) = $matches;
        $tag = strtolower($tag);

        if (null === $allow_tags)
            $allow_tags = self::$allow_tags;

        if (in_array($tag, $allow_tags)) {
            if ($is_close != '') {
                return $with_tag;
            }
            /* //这里处理分页
            if($with_tag=='<p class="page_break">'){
                return $with_tag;
            }
            */
            switch ($tag) {
                case 'p':
                    return '<p>';
                case 'br':
                    return '</p><p>';
                default:
                    return $with_tag;
            }
        }
        return $with_tag;
    }

    /**
     * @param $matches
     * @return string
     * 根据匹配情况处理开头的空格
     */
    public static function parse_space($matches)
    {
        $string = $matches[2];
        return ($string == '') ? '' : '<p>' . $string . '</p>';
    }

    /**
     * @param $content
     * @param string $url
     * @return mixed|string
     */
    public static function parse($content, $url = '')
    {
        //先把所有全角空格去除
        $content = str_replace('　', '', $content);

        //2 去除不需要的标签
        $content = self::strip_content($content);

        //对标签进行处理
        $content = preg_replace_callback('#<\s*([\/]?)\s*([\w]+)[^>]*>#im',
            function ($matches) {
                return self::parse_tag($matches);
            }
            , $content);

        // 移除所有的a标签保留文字本身
        $content = preg_replace('#<a\s[^>]*>([\s\S]*?)<\/a>#im', "$1", $content);

        //检查不是以p标签开头就加个p标签,不是以/p结尾就加个/p
        if (!preg_match('#^\<p#i', $content)) {
            $content = '<p>' . $content;
        }
        if (!preg_match('#<\/p>$#i', $content)) {
            $content = $content . '</p>';
        }

        //检查内容中紧邻开头的图片,并用p标签包裹
        $content = preg_replace('#(?:<p[^>]*>)?\s*(<img[^>]+>)\s*(?:<\/p>)?#im', "<p style='text-align:center;text-indent: 0px;'>$1</p>", $content);

        //处理空行和开头空白
        $content = preg_replace_callback('#<p>(\s|&nbsp;|　)*(.*?)<\/p>#im',
            function ($matches) {
                return self::parse_space($matches);
            }
            , $content);
        return $content;
    }
}