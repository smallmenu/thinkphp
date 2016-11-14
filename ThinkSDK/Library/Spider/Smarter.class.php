<?php
namespace ThinkSDK\Library\Spider;

/**
 * @version	$Id$
 */
class Smarter
{
    public $charset, $url, $html;
    public $cache, $cacheTmp;
    public $maxLevel, $minLength, $div2table;
    public $msg;
    private $p;
    static private $orig_url;

    function __construct()
    {
        $this->charset	= 'UTF-8';	// 程序使用的文件编码格式
        $this->html		= '';
        $this->url		= '';
        $this->orig_url = '';
        $this->p		= 1;		// 当前为第几页
        $this->page_split = '_';    // 自动获取分页时的分页页码前缀，默认为 _，失败时会切换 - 重新尝试
        $this->cache	= array();
        $this->maxLevel = 10;   // 内容匹配所允许的最大嵌套层次，超出部分将被省略
        $this->minLength = 200; // 内容区域的最小文本内容，小于此值将被忽略
        $this->div2table = 5;   // 内容区域DIV数量小于多少个就切换为TABLE模式
    }

    function __destruct()
    {
        $this->charset	= NULL;
        $this->html		= NULL;
        $this->url		= NULL;
        $this->orig_url	= NULL;
        $this->cache	= NULL;
    }

    /* 以字符串HTML加载内容
     * @param  string $html HTML内容
     * @return boolean
     */
    function loadString($html)
    {
        $this->html = $html;
        $this->formatHtml();
        return true;
    }

    /* 以URL地址形式加载内容
     * @param  string $url URL地址
     * @return boolean
     */
    function loadUrl($url)
    {
        if(!$this->orig_url)
        {
            $this->orig_url = $url;
        }
        if (!$url) {
            $this->msg = "URL不能为空";
            return FALSE;
        }
        if (strtolower(substr($url,0,4)) != 'http') {
            $this->msg = "URL格式不正确，必须以HTTP开头";
            return FALSE;
        }
        $this->url = $url;
        $this->getHttp();
        if(!$this->html){
            $this->msg = "内容加载失败";
            return FALSE;
        }
        $this->formatHtml();
        return true;
    }
    /* 提取标题和内容，返回数据
     * @param string $url 要提取内容的URL地址
     * @return array      返回该URL的标题和正文
     */
    function getInfo($url='', $page = false)
    {
        static $data = array();
        $datai= count($data);
        // 加载
        if(!$this->loadUrl($url)) {
            return FALSE;
        }
        // 修正相对路径
        $this->fix_path($this->html);
        // 提取标题
        if (!$title = $this->getTitle()) {
            $this->msg = '标题获取失败';
            return FALSE;
        }
        // 以标题为分隔，删除标题前的内容，重置HTML
        $tmp = $title['begin'].$title['content'].$title['end'];
        $this->html = substr($this->html, strpos($this->html, $tmp) + strlen($tmp));

        // 提取内容
        if (!$content = $this->getContent())
        {
            $this->msg = "内容提取失败";
            return FALSE;
        }
        // 释放掉title{txt}
        $title['content'] = $title['txt'];
        unset($title['txt']);
        // 对正文进行必要的过滤
        $content['content'] = $this->clearHtml(998, $content['content'], 'div,table');

        $data[$datai] = array(
            'title'=>$title['content'],
            'content'=>$content['content']
        );

        if ($page) {
            $data[$datai] = array(
                'title'=>$title['content'],
                'content'=>$content['content']
            );

            // 提取下一页地址
            $nextPage = $this->getNextPage();
            // 如果有下一页，继续采集
            if($nextPage)
            {
                $this->cache = $this->cacheTmp = NULL;
                $this->getInfo($nextPage, $page);
            }
        } else {
            $data = array(
                'title'=>$title['content'],
                'content'=>$content['content']
            );
        }

        // 返回内容
        return $data;
    }

    /* 从内容中获取出标题
     * @return string
     */
    function getTitle()
    {
        $title = array();
        if(!$this->getTagData('title')){
            $this->cache['title'] = array();
        }
        if(!$this->getTagData('h1')){
            $this->cache['h1'] = array();
        }
        if(!$this->getTagData('h2')){
            $this->cache['h2'] = array();
        }
        if(!$this->getTagData('h3')){
            $this->cache['h3'] = array();
        }
        // 如果H1有内容，取最后一个H1，和标题进行对比，如果正常则为标题，否则继续取H2
        if(empty($this->cache['title']) && empty($this->cache['h1']) && empty($this->cache['h2']) && empty($this->cache['h3']))
        {
            $this->msg = "title,h1,h2,h3标签均为空，放弃提取";
            return FALSE;
        }
        if(!empty($this->cache['title']))
        {
            $key = count($this->cache['title']);
            $title = $this->cache['title'][$key-1];
            $title['txt'] = $this->plainText($title['content']);
            // 对title得到的标题进行特别处理，增加  - _过滤
            if (strpos($title['txt'],"_")) {
                $titleTmpTxt = explode("_", $title['txt']);
                $title['txt'] = $titleTmpTxt[0];
            } elseif (strpos($title['txt']," - ")) {
                $titleTmpTxt = explode(" - ", $title['txt']);
                $title['txt'] = $titleTmpTxt[0];
            } elseif (strpos($title['txt'],"-")) {
                $titleTmpTxt = explode("-", $title['txt']);
                $title['txt'] = $titleTmpTxt[0];
            }
        }
        if(!empty($this->cache['h3']))
        {
            $key = count($this->cache['h3']);
            $titleTmpTxt = $this->plainText($this->cache['h3'][$key-1]['content']);
            if (strpos($title['txt'], $titleTmpTxt) !== false)
            {
                if (strlen($titleTmpTxt) > (strlen($title['txt']) / 2)) {
                    $title = $this->cache['h3'][$key-1];
                    $title['txt'] = $titleTmpTxt;
                    return $title;
                }
            }
        }
        if(!empty($this->cache['h2']))
        {
            $key = count($this->cache['h2']);
            $titleTmpTxt = $this->plainText($this->cache['h2'][$key-1]['content']);
            if (strpos($title['txt'], $titleTmpTxt) !== false)
            {
                if (strlen($titleTmpTxt) > (strlen($title['txt']) / 2)) {
                    $title = $this->cache['h2'][$key-1];
                    $title['txt'] = $titleTmpTxt;
                    return $title;
                }
            }
        }
        if(!empty($this->cache['h1']))
        {
            $key = count($this->cache['h1']);
            $titleTmpTxt = $this->plainText($this->cache['h1'][$key-1]['content']);
            if (strpos($title['txt'], $titleTmpTxt) !== false)
            {
                if (strlen($titleTmpTxt) > (strlen($title['txt']) / 2)) {
                    $title = $this->cache['h1'][$key-1];
                    $title['txt'] = $titleTmpTxt;
                    return $title;
                }
            }
        }
        return $title;
    }

    /* 获取内容正文
     *
     */
    public function getContent()
    {
        $tag = 'div';
        $this->clearHtml(1);
        $title = array();
        // 分析DIV或TABLE
        if(!$this->getTagData('div'))
        {
            $this->cache['div'] = array();
        }
        // DIV区域小于设定值, 就换用TABLE布局
        if(count($this->cache['div']) < $this->div2table)
        {
            if(!$this->getTagData('table'))
            {
                return false;
            }
            else
            {
                $this->cache['div'] = $this->cache['table'];
                unset($this->cache['table']);
                $tag = 'table';
            }
        }

        // 开始过滤内容区域
        $max = count($this->cache['div']);
        for ($i=0;$i<$max;$i++)
        {
            $div = $this->cache['div'][$i];

            // 过滤掉最小内容小于设置值的区域（1），这里先过滤掉一些不简单的
            if (strlen($this->plainText($div['content'],1)) < $this->minLength)
            {
                unset($this->cache['div'][$i]);
                continue;
            }

            /* 清理区域内嵌套的区域
             * 1、如果开头第一个DIV的内容小于设定值，此区应为大范围，直接过滤所有嵌套
             * 2、如果开头第一个DIV的内容大于设定值，此区可能是真正的内容，后面的嵌套中，
             *    只过滤掉小内容，因为有可能还有些数据在嵌套中。
             */
            $div['content'] = $this->clearHtml(999, $div['content'], $tag);
            $this->cache['div'][$i]['txt'] = $div['content'];

            // 过滤掉最小内容小于设置值的区域（2），这里是经过一系列处理后继续过滤
            if (strlen($this->plainText($div['content'],1)) < $this->minLength)
            {
                unset($this->cache['div'][$i]);
            }
        }

        // 取得内容最多的一个区域为content
        $arrSize = array();
        foreach ($this->cache['div'] as $k=>$div)
        {
            $arrSize[$k] = strlen($div['txt']);
            unset($this->cache['div'][$k]['txt']);
        }
        arsort($arrSize);
        $key = array_keys($arrSize);
        return $this->cache['div'][$key[0]];
    }

    /* 获取下一页的链接
     *
     */
    public function getNextPage()
    {
        $this->p++;
        if(!$pageURL = $this->getA_preg('下一页')){
            if(!$pageURL = $this->getNextUrl($this->p)){
                return FALSE;
            }
        }
        return $pageURL[0];	//返回匹配到的第一个
    }

    /**
     * 以URL _N 分页方式获取下一页链接
     *
     * @param int $p
     * @param string $split 分页页码前缀分隔符，默认为 _ 可尝试切换为 -
     * @return array|bool
     */
    public function getNextUrl($p=1,$split='')
    {
        if($split)
        {
            $this->page_split = $split;
        }
        $pattern = $this->orig_url;
        $pattern = str_replace(array('.shtml','.html','.htm'), '', $pattern);
        $pattern = substr($pattern,strrpos($pattern,'/')+1);
        $pattern = $pattern .$this->page_split.$p;
        if(!$pageURL = $this->getA_pregUrl($pattern)){
            // 可能页码从2开始
            if($p == 1)
            {
                $pageURL = $this->getNextUrl(2, $split);
                if($pageURL)
                {
                    return $pageURL;
                }
            }
            // 超过第二页，并且 split为 -，标示获取失败，退出
            if($p > 2 || $this->page_split == '-')
            {
                return false;
            }
            // 如果还获取不到，尝试 -
            $this->p = 1;
            $pageURL = $this->getNextUrl(1,'-');
        }
        return $pageURL;	//返回匹配到的第一个
    }

    /* 获取所有的A链接地址
     * @param string $key 链接名称中包含指定的关键字，若不指定，查找所有的A链接
     */
    private function getA($key='')
    {
        $data = array();
        $break = FALSE;
        $rootUri   = substr($this->url, 0, strpos($this->url,'/',8));
        $parentUri = substr($this->url, 0, strrpos($this->url,'/'));
        if(!$this->getTagData('a',0,TRUE)){
            $this->cacheTmp['a'] = array();
        }
        foreach($this->cacheTmp['a'] as $a)
        {
            if($key)
            {
                if($break)
                {
                    break;
                }
                if (strpos($a['content'], $key) === FALSE)
                {
                    continue;
                }
                else
                {
                    $break = TRUE;
                }
            }
            $a = strtolower($a['begin']);
            $tmp = explode('href=', $a);
            $a = trim($tmp[1]);
            $tmp = explode('>', $a);
            $a = trim($tmp[0]);
            if(strpos($a, " "))
            {
                $tmp = explode(" ", $a);
                $a = $tmp[0];
            }
            $a = str_replace(array("'",'"'),"",$a);
            if(!$a)
            {
                continue;
            }
            if($a[0] == '/')
            {
                $a = $rootUri . $a;
            }
            elseif(substr($a,0,4) == 'http')
            {
                $a = $a;
            }
            else
            {
                $a = $parentUri .$a;
            }
            $data[] = $a;
        }
        unset($this->cacheTmp['a']);
        return array_unique($data);
    }

    /* 获取所有的A链接地址(正则版)
     * @param string $key 链接名称中包含指定的关键字，若不指定，查找所有的A链接
     */
    private function getA_preg($key='')
    {
        $data = array();
        $rootUri   = substr($this->url, 0, strpos($this->url,'/',8));
        $parentUri = substr($this->url, 0, strrpos($this->url,'/'));
        if($key)
        {
            preg_match_all('#<a[^>]*>' .$key .'</a>#is', $this->html ,$match);
        }
        else
        {
            preg_match_all('#<a[^>]*>[^<]*</a>#is', $this->html ,$match);
        }
        if(!$match){
            return FALSE;
        }
        foreach($match[0] as $a)
        {
            $a = str_ireplace('HREF=','href=',$a);
            $a = str_ireplace('HTTP:','http:',$a);
            $tmp = explode('href=', $a);
            $a = trim($tmp[1]);
            $tmp = explode('>', $a);
            $a = trim($tmp[0]);
            if(strpos($a, " "))
            {
                $tmp = explode(" ", $a);
                $a = $tmp[0];
            }
            $a = str_replace(array("'",'"'),"",$a);
            if(!$a)
            {
                continue;
            }
            if($a[0] == '/')
            {
                $a = $rootUri . $a;
            }
            elseif(substr($a,0,4) == 'http')
            {
                $a = $a;
            }
            else
            {
                $a = $parentUri .$a;
            }
            $data[] = $a;
        }
        return array_unique($data);
    }

    /* 获取所有的A链接地址(正则版)
     * @param string $key 链接地址中包含指定的关键字，若不指定，返回FALSE
     */
    private function getA_pregUrl($key='')
    {
        if(!$key)
        {
            return FALSE;
        }
        $data = array();
        $rootUri   = substr($this->url, 0, strpos($this->url,'/',8));
        $parentUri = substr($this->url, 0, strrpos($this->url,'/'));
        preg_match_all('#<a[^>]*'.$key.'[^>]*>[^<]*</a>#is', $this->html ,$match);
        if(!$match){
            return FALSE;
        }
        foreach($match[0] as $a)
        {
            $a = str_ireplace('HREF=','href=',$a);
            $a = str_ireplace('HTTP:','http:',$a);
            $tmp = explode('href=', $a);
            $a = trim($tmp[1]);
            $tmp = explode('>', $a);
            $a = trim($tmp[0]);
            if(strpos($a, " "))
            {
                $tmp = explode(" ", $a);
                $a = $tmp[0];
            }
            $a = str_replace(array("'",'"'),"",$a);
            if(!$a)
            {
                continue;
            }
            if($a[0] == '/')
            {
                $a = $rootUri . $a;
            }
            elseif(substr($a,0,4) == 'http')
            {
                $a = $a;
            }
            else
            {
                $a = $parentUri .$a;
            }
            $data[] = $a;
        }
        return array_unique($data);
    }

    /* 生成干净的纯文本内容
     * @param string $str 原字符
     * @return string
     */
    public function plainText($str, $useFilter=FALSE, $tags='')
    {
        if ($useFilter) {
            if (!$tags) {
                $tags = 'ul,ol,li';
            }
            $str = $this->clearHtml(101, $str, $tags);
        }
        $str = strip_tags($str);
        $str = str_replace(array("\r\n","\n","\t"), "", $str);
        return trim($str);
    }

    /* 格式化HTML内容，包括进行编码转换，进行不必要的字符串清理
     * @return NULL
     */
    private function formatHtml()
    {
        // 探测字符编码
        $charset = strtoupper($this->getCharset());

        // 获取真实编码再比较
        $mb_charset = mb_detect_encoding($this->html, 'UTF-8', true);
        if ($charset != $mb_charset) {
            $charset = $mb_charset;
        }

        if ($charset != $this->charset){
            if ($charset == 'GB2312') $charset = 'gbk';
            if (function_exists('mb_convert_encoding'))
            {
                $this->html = @mb_convert_encoding($this->html, $this->charset, $charset);
            } elseif (function_exists('iconv'))
            {
                $this->html = @iconv($charset, $this->charset, $this->html);
            }
            $this->html = str_ireplace("charset=" .$charset, "charset=" .$this->charset, $this->html);
        }else{
            //为什么是UTF-8还要进行替换？
            //--原因是像 SosoVersion 2.0 系统由http header输出编码信息，内容中还有原站的编码有可能是GB2312
            $this->html = str_ireplace(array("chraset=gbk","charset=gb2312"), "charset=" .$this->charset, $this->html);
        }
        // 增加一个标准头部
        $this->html = '<meta http-equiv="Content-Type" content="text/html;charset=' .$this->charset .'">'.$this->html;
        // 非常奇怪的问题 SosoVersion 2.0中如果有 &nbsp; 会被转换成奇怪的空白字符，无法去除
        $this->html = str_ireplace("&nbsp;","", $this->html);
        //进行大小写格式处理及垃圾代码清理
        $this->clearHtml(0);
    }

    /* 清理常见的垃圾内容和干扰内容
     * @param intval $level 清理级别，不同的级别清理不同的内容，0为初始化时的清理，1为获取标题以后的清理，999为内容小区域清理
     *                        规定，级别小于100的为全局处理，大于100的为小区域处理
     * @param string $html  要清理的内容，默认为$this->html，如果为小区域清理时，需指定要清理的内容
     * @param string $tags  小区域清理时，要特别清理的标签
     * @param string $html  当为小区域清理时，返回清理过的内容
     */
    private function clearHtml($level=0, $html="", $tags ="")
    {
        $htmlTmp = "";
        if($html)
        {
            $htmlTmp = $this->html;
            $this->html = $html;
        }
        switch ($level)
        {
            case 0:
                //常规字符进行统一格式化为小写，去除注释及CSS和JS代码
                $this->html = str_ireplace(
                    array('title','meta','script','style','h1','h2','div','dl','ul','ol','li','<a ','</a>','table','tr','td'),
                    array('title','meta','script','style','h1','h2','div','dl','ul','ol','li','<a ','</a>','table','tr','td'),
                    $this->html
                );
                $this->html = preg_replace("'<!--(.*?)-->'s",'',$this->html);
                $this->filterTags('script,style', $level);
                break;
            case 1:
                //去除小单位字符串，因为这些短单位不可能是内容
                $this->filterTags('dl,dt,dd', $level);
                $this->html = strip_tags($this->html, '<table><tr><td><div><ul><ol><li><span><p><strong><b><pre><em><img><a><br><font>');
                break;
            case 101:
                //纯文本过滤时使用
                $this->filterTags($tags, $level);
                break;
            case 998:
                // 内容获取后做过滤再输出
                $this->filterTags($tags, $level);
                //$this->html = preg_replace("'<p.*?(?=summary)\">(.*?)</p>'is", "", $this->html);
                break;
            case 999:
                // 内容过滤专用，使用临时缓存
                $this->filterTags($tags, $level);
                $this->html = preg_replace("'<a[^>]*>(.*?)</a>'is", "", $this->html);
                break;
            default:
                break;
        }
        $this->html = str_replace(array("\r\n\r\n","\n\n","\t\t","  ","\t"), array("\n","\n","\t","",""), $this->html);
        if($html)
        {
            $html = $this->html;
            $this->html = $htmlTmp;
            return $html;
        }
        return true;
    }

    /* 根据指定的TAG进行内容清理
     * @param string $tag 要进行清理的TAG,如果有多个以英文逗号分开
     * @param intval $level 操作级别，不同的级别过滤方式和使用的CACHE不一样，默认全清除并使用$this->cache
     *                        规定，级别小于100的为全局处理，使用主缓存，大于100的为小区域处理，使用临时缓存
     */
    private function filterTags($tag, $level=0)
    {
        $tags = explode(',', $tag);
        foreach($tags as $tag){
            if ($this->getTagData($tag, 0, intval($level) > 100 ? TRUE : FALSE))
            {
                switch ($level)
                {
                    case 0:
                        foreach($this->cache[$tag] as $v)
                        {
                            $this->html = str_replace(implode($v), '', $this->html);
                        }
                        unset($this->cache[$tag]);
                        break;
                    case 1:
                        foreach($this->cache[$tag] as $v)
                        {
                            $this->html = str_replace(implode($v), '', $this->html);
                        }
                        unset($this->cache[$tag]);
                        break;
                    case 101:
                        foreach($this->cacheTmp[$tag] as $v)
                        {
                            $this->html = str_replace(implode($v), '', $this->html);
                        }
                        unset($this->cacheTmp[$tag]);
                        break;
                    case 998:
                        foreach($this->cacheTmp[$tag] as $v)
                        {
                            if (strlen($this->plainText($v['content'],1)) < $this->minLength) {
                                $this->html = str_replace(implode($v), '', $this->html);
                            }
                        }
                        unset($this->cacheTmp[$tag]);
                        break;
                    case 999:
                        foreach($this->cacheTmp[$tag] as $v)
                        {
                            $this->html = str_replace(implode($v), '', $this->html);
                        }
                        unset($this->cacheTmp[$tag]);
                        break;
                    default:
                        break;
                }
            }
        }
        return true;
    }

    /* 根据指定的TAG获取内容
     * @param string $tag 要获取内容的HTML TAG
     * @param intval $pos 查找内容开始的标识
     * @param boolean $tmp 是否操作临时缓存，默认为$this->cache
     * @return array
     */
    private function getTagData($tag, $pos=0, $tmp=FALSE)
    {
        if($tmp)
        {
            if (isset($this->cacheTmp[$tag]))
            {
                return true;
            }
        }
        else
        {
            if (isset($this->cache[$tag]))
            {
                return true;
            }
        }
        static $data = array();
        $datai	= count($data);
        $_begin = '<' .$tag;
        $_end   = '</' .$tag .'>';
        $pos1   = $pos2 = $tag_pos = $level = 0;
        $tag_html = $pos_html = '';

        if ($pos == 0) {
            $data = array();
            $datai = 0;
        }

        if (($pos1 = strpos($this->html, $_begin, $pos)) === false)
        {
            return false;
        }
        $pos = $pos1 = $pos1 + strlen($_begin);
        if (($pos2 = strpos($this->html, $_end, $pos)) === false)
        {
            $pos2 = strlen($this->html) - strlen($_end);
        }
        $pos_html = substr($this->html, $pos1, $pos2 - $pos1);
        $level = 1;
        if ($tag_count = substr_count($pos_html, $_begin))
        {
            for($i=0;$i<$tag_count;$i++)
            {
                if($level > $this->maxLevel){
                    // 为了节省资源超过设定嵌套层次，后面舍弃
                    break;
                }
                $tag_pos  = strpos($this->html, $_end, $pos2 + strlen($_end));
                if (!$tag_pos) {
                    $tag_pos = strlen($this->html) - strlen($_end);;
                }
                $tag_html = substr($this->html, $pos2, $tag_pos - $pos2);
                if ($tag_count2 = substr_count($tag_html, $_begin))
                {
                    $i = $i - $tag_count2;
                }
                $pos2 = $tag_pos;
                $level++;
                if ($tag_pos == (strlen($this->html) - strlen($_end))) {
                    break;
                }
            }
            $pos_html = substr($this->html, $pos1, $pos2 - $pos1);
            // 超过设定嵌套层次，后面被舍弃，导致TAG不能匹配，故增加一个TAG进行匹配
            if($level > $this->maxLevel)
            {
                $pos_html .= $_end;
            }
        }
        $_begin_full = $_begin . substr($pos_html, 0, strpos($pos_html, '>') + 1);
        $pos_html = substr($pos_html, strpos($pos_html, '>') + 1);
        // 有一种情况，两个标签可以匹配，但是是空标签，这里要过滤掉，所以有内容时才存储
        if (trim($pos_html) || $tmp)
        {
            $data[$datai] = array(
                'begin' => $_begin_full,
                'content' => $pos_html,
                'end' => $_end
            );
        }
        // 如果剩下的内容中还有这个标签，继续查找
        if (strpos($this->html, $_begin, $pos) === false)
        {
            if($tmp)
            {
                $this->cacheTmp[$tag] = $data;
            }
            else
            {
                $this->cache[$tag] = $data;
            }
            return true;
        } else {
            $this->getTagData($tag, $pos, $tmp);
        }
        return true;
    }

    private function fix_path(&$content)
    {
        // 查找所有IMG,A链接（非HTTP开头的）并替换
        $content = preg_replace_callback('#(<img\s+[^>]*src\s*=\s*(["\'])?)([^"\']+)(\2.*[/]?>)#Uis',array($this,'_fix_imgpath'), $content);
        $content = preg_replace_callback('#(<a\s+[^>]*href\s*=\s*(["\'])?)([^"\']+)(\2.*>)#Uis',array($this,'_fix_apath'), $content);
    }
    private function _fix_imgpath($match)
    {
        if(count($match) < 3) return '';
        $url = $match[3];
        if(strpos($url,'://') === false)
        {
            $url = $this->full_url($url);
        }
        $url = '<img src="'.$url.'" />';
        return $url;
    }
    private function _fix_apath($match)
    {
        if(count($match) < 3) return '';
        $url = $match[3];
        if(strpos($url,'://') === false)
        {
            $url = $this->full_url($url);
        }
        $url = '<a href="'.$url.'">';
        return $url;
    }

    /**
     * 格式化为完整的URL
     *
     * @param string $url 待格式化的URL
     * @param string $baseurl 母本URL
     * @return string
     */
    public function full_url($url, $baseurl='')
    {
        if (preg_match('#^(?:http|https|ftp|mms|rtsp|thunder|emule|ed2k)://#', $url))
        {
            return $url;
        }

        if(!$baseurl)
        {
            $baseurl = $this->url;
        }
        $base = $this->_init_urlinfo($baseurl);

        $url = trim($url);
        if ($url == '')
        {
            return '';
        }
        $pos = strpos($url, "#");
        if ($pos > 0)
        {
            $url = substr($url, 0, $pos);
        }
        if ($url[0] == '/')
        {
            $url = $base['hosturi'] . $url;
        }
        else
        {
            $url = $base['baseuri'] .($url[0] == '?' ? '' : '/'). $url;
        }
        $parts = explode('/', $url);
        $okparts = array();
        while (($part = array_shift($parts)) !== NULL)
        {
            $part = trim($part);
            if ($part == '.' || $part === '')
            {
                continue;
            }
            if ($part == '..')
            {
                if (count($okparts) > 1)
                {
                    array_pop($okparts);
                }
                continue;
            }
            $okparts[] = $part;
        }
        return $base['scheme'] .'://'. implode('/', $okparts);
    }

    /**
     * 加载URL的信息数组并缓存
     *
     * @param $url
     * @return bool
     */
    private function _init_urlinfo($url)
    {
        $url = parse_url($url);
        $url['pass'] = empty($url['pass']) ? '' : ":{$url['pass']}";
        $url['auth'] = empty($url['user']) ? '' : "{$url['user']}{$url['pass']}@";
        $url['port'] = empty($url['port']) ? '' : ":{$url['port']}";
        $path = explode('/', $url['path']);
        array_pop($path);
        $url['path'] = implode('/', $path);
        $url['hosturi'] = $url['auth'].$url['host'].$url['port'];
        $url['baseuri'] = rtrim($url['hosturi'].$url['path'], '/');
        $url = array(
            'scheme'=>$url['scheme'],
            'hosturi'=>$url['hosturi'],
            'baseuri'=>$url['baseuri']
        );
        return $url;
    }

    /**
     * 获取HTML内容的数据编码
     *
     */
    private function getCharset()
    {
        // 如果有URL，则从HTTP头抓取编码（HTML中给出的编码都太不准了，HTTP中是显示的标准）
        if($this->url){
            $header = substr($this->html,0,500);
            if(preg_match("/Content-Type:.*charset=(utf-8|gbk|gb2312)+/Ui", $header, $match)){
                $charset = $match[1];
                return strtolower($charset);
            }
        }
        // 如果是SOSO抓取系统，直接返回UTF-8
        if(strpos($this->html,'SosoVersion 2.0'))
        {
            return 'utf-8';
        }

        // 最后通用方式，查找头部的meta
        if(preg_match("/\<meta .*charset=\"(utf-8|gbk|gb2312)+\"/Ui", $this->html, $match)) {
            $charset = $match[1];
            return $charset;
        }
        if(preg_match("/\<meta .*charset=(utf-8|gbk|gb2312)+\"/Ui", $this->html, $match)) {
            $charset = $match[1];
            return $charset;
        }

        return 'utf-8';
    }

    /**
     * 获取HTTP的HTML内容
     *
     */
    private function getHttp()
    {
        $userAgent = 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)';
        $referer = $this->url;
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(array(
                    'http' => array(
                        'timeout' => 10 //设置一个超时时间，单位为秒
                    )
                )
            );
            $this->html = @file_get_contents($this->url, 0, $ctx);
            return true;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);			//设置访问的url地址
        curl_setopt($ch, CURLOPT_HEADER, 1);				//设置返回头部，用于内容编码判断
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);				//设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);	//用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER, $referer);		//设置 referer
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');			//设置客户端是否支持 gzip压缩
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);		//跟踪301,已关闭
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		//返回结果
        if(stripos($this->url,'https:') !== false){				//加载SSL公共证书，请求HTTPS访问
            # Below two option will enable the HTTPS option.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        $this->html = curl_exec($ch);
        curl_close($ch);
        return true;
    }

}