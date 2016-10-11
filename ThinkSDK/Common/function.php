<?php
/**
 * C 函数别名
 *
 * @param null $name
 * @param null $value
 * @param null $default
 * @return mixed
 */
function config($name = null, $value = null, $default = null)
{
    return C($name, $value, $default);
}

/**
 * I 函数别名
 *
 * @param $name
 * @param string $default
 * @param null $filter
 * @param null $datas
 * @return mixed
 */
function filter($name, $default = '', $filter = null, $datas = null)
{
    /**
     * 此处Hack了一下ThinkPHP框架的I函数，为了支持URL中文不同的编码
     * 当使用/c修正符时，会判断，$GET是否为utf-8，否则则使用GBK转换
     */
    $origin = $name;
    if (strpos($name, '/')) {
        list($name, $type) = explode('/', $name, 2);
    } else {
        $type = '';
    }
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else {
        $method = 'get';
    }
    if (strtolower($method) == 'get' && $type == 'c') {
        if (isset($_GET[$name]) && mb_detect_encoding($_GET[$name], 'UTF-8', true) != 'UTF-8') {
            $_GET[$name] = iconv('GBK', 'UTF-8', $_GET[$name]);
        }
    }

    return I($origin, $default, $filter, $datas);
}

/**
 * U 函数别名
 *
 * @param string $url
 * @param string $vars
 * @param bool $suffix
 * @param bool $domain
 * @return string
 */
function url($url = '', $vars = '', $suffix = true, $domain = false)
{
    return U($url, $vars, $suffix, $domain);
}

/**
 * M 函数别名
 *
 * @param string $name
 * @param string $tablePrefix
 * @param string $connection
 * @return Model|\Think\Model
 */
function model($name = '', $tablePrefix = '', $connection = '')
{
    return M($name, $tablePrefix, $connection);
}

/**
 * D 函数别名
 *
 * @param string $name
 * @param string $layer
 * @return Model|\Think\Model
 */
function data($name = '', $layer = '')
{
    return D($name, $layer);
}

/**
 * T 函数别名
 *
 * @param string $template
 * @param string $layer
 * @return string
 */
function template($template = '', $layer = '')
{
    return T($template, $layer);
}

/**
 * S 函数别名
 *
 * @param $name
 * @param string $value
 * @param null $options
 * @return mixed
 */
function cache($name, $value = '', $options = null)
{
    return S($name, $value, $options);
}

/**
 * W 函数别名
 *
 * @param $name
 * @param array $data
 * @return bool|mixed|void
 */
function widget($name, $data = array())
{
    return W($name, $data);
}

/**
 * URL安全的base64
 *
 * @param string $str
 * @return string
 */
function base64_encode_safe($str = '')
{
    $str = base64_encode($str);
    $str = str_replace(array('+', '/', '='), array('-', '_', '!'), $str);
    return $str;
}

/**
 * @param string $str
 * @return mixed|string
 */
function base64_decode_safe($str = '')
{
    $str = str_replace(array('-', '_', '!'), array('+', '/', '='), $str);
    $str = base64_decode($str);
    return $str;
}

/**
 * 为浏览器的 FirePHP / ChromePHP 扩展输出响应头数据，以便调试
 *
 * <code>
 * console($data);
 * console($data, true);
 * </code>
 *
 * @param $data
 * @param $time
 */
function console($data, $time = false)
{
    static $logger;
    static $index = 0;
    static $lasttime = APP_START_TIME;

    $thistime = microtime(true);
    $usedtime = $thistime - $lasttime;
    $lasttime = $thistime;
    $label = $time ? sprintf("%09.5fs", $usedtime) : null;

    if (is_array($data)) {
        ksort($data);
    }

    if (is_null($logger)) {
        if (strstr($_SERVER['HTTP_USER_AGENT'], ' Firefox/')) {
            $logger = new ThinkSDK\Library\Debug\Console\FirePHP();
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], ' Chrome/')) {
            $logger = ThinkSDK\Library\Debug\Console\ChromePHP::getInstance();
        } else {
            $logger = false;
        }
    }

    if ($logger) {
        if ($logger instanceof ThinkSDK\Library\Debug\Console\FirePHP) {
            $logger->info($data, $label);
        } else if ($logger instanceof ThinkSDK\Library\Debug\Console\ChromePHP) {
            if ($label) {
                $logger->info($label, $data);
            } else {
                $logger->info($data);
            }
        }
    } else {
        $name = 'Console-' . ($index++);
        if ($label) {
            $name .= '#' . $label;
        }
        header($name . ':' . json_encode($data));
    }
}

/**
 * 读取应用默认 .env 文件配置
 *
 * @param string $key
 * @param null $default
 * @return null
 * @throws Exception
 */
function env($key = '', $default = null)
{
    static $envs = null;
    $env = APP_PATH . '.env';
    $key = trim($key);

    if (is_null($envs)) {
        if (is_file($env) === false && is_readable($env) === false) {
            throw new \Exception('Env File Missing');
        }
        $envs = parse_ini_file($env, true);
        if ($envs === false) {
            throw new \Exception('Env File Parse Error');
        }
    }

    if (!empty($key)) {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $section = $keys[0];
            $index = $keys[1];

            if (isset($envs[$section][$index])) {
                return $envs[$section][$index];
            } elseif (!is_null($default)) {
                return $default;
            }
        } else {
            if (isset($envs[$key])) {
                return $envs[$key];
            } else {
                return $default;
            }
        }
    } elseif (!is_null($default)) {
        return $default;
    } else {
        return $envs;
    }

    return null;
}

/**
 * 返回Hashids实例
 *
 * @return \ThinkSDK\Library\Crypt\Hashids\Hashids
 */
function hashid()
{
    static $hashid = null;

    $length = config('APP_HASHID_LENGTH');
    $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890';

    if (is_null($hashid)) {
        $hashkey = config('APP_HASHID_KEY') ?: '';
        $hashid = new ThinkSDK\Library\Crypt\Hashids\Hashids($hashkey, $length, $alphabet);
    }

    return $hashid;
}

/**
 * Hashid encode
 *
 * @param $id
 * @return string
 */
function eid($id)
{
    $id = intval($id);
    return hashid()->encode($id);
}

/**
 * Hashid dencode
 *
 * @param $hash
 * @return int
 */
function did($hash)
{
    $decode = hashid()->decode($hash);
    return !empty($decode) ? $decode[0] : 0;
}

/**
 * 检查字符串是否是UTF8编码
 *
 * @param string $string 字符串
 * @return Boolean
 */
function is_utf8($string)
{
    return preg_match('%^(?:
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);
}

/**
 * 字符串截取，支持中文和其他编码
 *
 * @param  string $str 需要转换的字符串
 * @param  int $start 开始位置
 * @param  int $length 截取长度，每个字符为一个长度
 * @param  string $charset 编码格式
 * @param  string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $length, $suffix = '..', $charset = 'utf-8', $start = 0)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $slice == $str ? $slice : $slice . $suffix;
}

/**
 * CmsTop 截取字符串
 *
 * @param string $string 原始字符串
 * @param int $length 截取长度
 * @param string $dot 省略符
 * @param string $charset 字符集
 * @return string
 */
function str_cut($string, $length, $dot = '...', $charset = 'utf-8')
{
    $strlen = strlen($string);
    if ($strlen <= $length) return $string;
    $specialchars = array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;');
    $entities = array('&', '"', "'", '<', '>');
    $string = str_replace($specialchars, $entities, $string);
    $strcut = '';
    if (strtolower($charset) == 'utf-8') {
        $n = $tn = $noc = 0;
        while ($n < $strlen) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } elseif (bin2hex($string[$n]) >= 65281 || bin2hex($string[$n]) <= 65374) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) break;
        }
        if ($noc > $length) $n -= $tn;
        $strcut = substr($string, 0, $n);
    } else {
        $dotlen = strlen($dot);
        $maxi = $length - $dotlen - 1;
        for ($i = 0; $i < $maxi; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
        }
    }
    if (strlen($strcut) == $strlen)
        return $string;
    else
        return $strcut . $dot;
}

/**
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 *
 * @param int $len 长度
 * @param string $type 字串类型默认字母与数字 0 字母 1 数字  2 大写字母  3 小写字母 4 中文
 * @param string $addChars 额外字符
 * @return string
 */
function rand_string($len = 6, $type = '', $addChars = '')
{
    $str = '';
    switch ($type) {
        case 0:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1:
            $chars = str_repeat('0123456789', 3);
            break;
        case 2:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3:
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 4:
            $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借" . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
            break;
    }
    //位数过长重复字符串一定次数
    if ($len > 10) {
        $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
    }
    if ($type != 4) {
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
    } else {
        // 中文随机字
        for ($i = 0; $i < $len; $i++) {
            $str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
        }
    }
    return $str;
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 *
 * @param $size
 * @param int $dec
 * @return string
 */
function byte2string($size, $dec = 2)
{
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . " " . $a[$pos];
}

/**
 * 计算指定存储单位字符串的数值
 *
 * @param $size_string 存储单位字符串，如 128KB,1MB,1T,1GB 等
 * @return int 以 byte 计算的容量数值
 */
function string2byte($size_string)
{
    if (is_int($size_string)) {
        return $size_string;
    }
    $units = array('K', 'M', 'G', 'T', 'P', 'E');
    if (!preg_match('/^(\d+)([' . implode('', $units) . '])?(B)?$/i', $size_string, $matches)) {
        return intval($size_string);
    }
    $value = intval($matches[1]);
    if (isset($matches[2]) && $matches[2]) {
        $index = array_search(strtoupper($matches[2]), $units);
        if ($index !== false) {
            return $value * pow(1024, $index + 1);
        }
    }
    return $value;
}

/**
 * 利用curl模拟浏览器发送请求
 *
 * @param string $url 请求的URL
 * @param array|string $post post数据
 * @param int $timeout 执行超时时间
 * @param boolean $sendcookie 是否发送当前cookie
 * @param array $options 可选的CURL参数
 * @return array
 */
function request($url, $post = null, $timeout = 40, $sendcookie = true, $options = array())
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : 'cmstopinternalloginuseragent');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout ? $timeout : 40);
    if ($sendcookie) {
        $cookie = '';
        foreach ($_COOKIE as $key => $val) {
            $cookie .= rawurlencode($key) . '=' . rawurlencode($val) . ';';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }

    if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    foreach ($options as $key => $value) {
        curl_setopt($ch, $key, $value);
    }

    $ret = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    if (!$content_length) $content_length = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return array(
        'httpcode'       => $httpcode,
        'content_length' => $content_length,
        'content_type'   => $content_type,
        'content'        => $ret
    );
}

/**
 * 把返回的数据集转换成Tree
 *
 * @param $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param int $root
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 有缓存机制的便捷获取表数据或表主键数据
 *
 * @param $table  表名
 * @param null $id  主键值
 * @param null $field  要获取的记录字段
 * @param bool $force  是否强制获取，强制获取依然会用到static缓存，若忽略所有缓存请使用Model
 * @return null
 */
function table($table, $id = null, $field = null, $force = false)
{
    $table_cache = 'table_cache:';
    static $_staticCache;

    if (isset($_staticCache[$table]) && !$force) {
        return table_return($_staticCache[$table], $id, $field);
    } else {
        if ($force) {
            if (is_null($id)) {
                static $multiRow;
                if (!isset($multiRow[$table])) {
                    $datas = table_select($table);
                    $multiRow[$table] = $datas;
                }
                return $multiRow[$table];

            } else {
                static $row;
                $index = $table . '_' . $id;
                if (!isset($row[$index])) {
                    $row[$index] = model($table)->find($id);
                }
                return is_null($field) && !isset($row[$index][$field]) ? $row[$index] : $row[$index][$field];
            }
        } else {
            if ($_cache = cache($table_cache . $table)) {
                $_staticCache[$table] = $_cache;
                return table_return($_staticCache[$table], $id, $field);
            } else {
                $datas = table_select($table);

                if ($datas) {
                    $_staticCache[$table] = $datas;
                    cache($table_cache . $table, $_staticCache[$table]);
                    return table_return($_staticCache[$table], $id, $field);
                }
            }
        }
    }
}

/**
 * 为table()函数返回结果
 *
 * @param $datas
 * @param null $id
 * @param null $field
 * @return null
 */
function table_return($datas, $id = null, $field = null)
{
    if (is_null($id)) {
        return $datas;
    } else {
        if (is_null($field)) {
            return $datas[$id];
        } else {
            return isset($datas[$id][$field]) ? $datas[$id][$field] : null;
        }
    }
}

/**
 * 获取table的所有数据，并格式化索引为表主键
 *
 * @param $table
 * @return mixed
 */
function table_select($table)
{
    $datas = array();
    $result = model($table)->select();
    $pk = model($table)->getPk();
    if ($result) {
        foreach ($result as $key => $res) {
            if (!isset($res[$pk])) {
                break;
            }
            $datas[$res[$pk]] = $res;
        }
    }

    return $datas;
}

/**
 * 简单的构造资源
 *
 * @example <link rel="stylesheet" href="<?=assets('www/v1/css/index.css', true)?>">
 *
 * @param $src
 * @param bool $version
 * @return string
 */
function assets($src, $version = false)
{
    $assets = $src = trim($src);
    $assets_url = config('define.ASSETS_URL');

    if (!empty($src)) {
        if (!$version) {
            $assets = $assets_url. $src;
        } else {
            $srcPath = ASSETS_PATH . $src;

            $mtime = filemtime($srcPath);
            $mtime = date('YmdHi', $mtime);
            $concat = strpos($src, '?') === false ? '?v=' : '&v=';
            $assets = $assets_url. $src . $concat . $mtime;
        }
    }
    return $assets;
}
