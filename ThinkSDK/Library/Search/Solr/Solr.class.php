<?php
/**
 * Solr 查询抽象类提供类似ThinkPHP查询方法
 *
 * @author niuchaoqun
 */

namespace ThinkSDK\Library\Search\Solr;

abstract class Solr
{
    /**
     * 缓存连接
     *
     * @var null
     */
    protected static $clients = null;

    /**
     * 连接
     *
     * @var null
     */
    protected $client = null;

    /**
     * 查询对象
     *
     * @var null
     */
    protected $query = null;

    /**
     * Solr 配置
     *
     * @var array|mixed|null
     */
    protected $options = array();

    /**
     * Solr Core 索引，通过子类定义
     *
     * @var null
     */
    protected $core = null;

    /**
     * Solr Core 索引主键，通过子类定义
     *
     * @var null
     */
    protected $uniqueKey = null;

    /**
     * 搜索条件
     *
     * @var array
     */
    protected $querys = array();

    /**
     * Debug 开关方便调试
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * 构造函数
     *
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (!extension_loaded('solr')) {
            throw new \Exception('solr extension load failed');
        }
        $options = empty($options) ? config('solr') : $options;

        if (!$options) {
            throw new \Exception('solr config failed');
        }
        $this->options = $options;
        $this->connect();
    }

    /**
     * 获取连接
     *
     */
    protected function connect()
    {
        if (count($this->options) > 1) {
            $server = $this->options[array_rand($this->options, 1)];
        } else {
            $server = $this->options[0];
        }
        $server['path'] = $server['path'] . $this->core;

        $key = array($server['hostname'], $server['port'], $server['path']);
        $key = implode(':', $key);

        if (!isset(self::$clients[$key]) || self::$clients[$key] === null) {
            self::$clients[$key] = new \SolrClient($server);
        }
        $this->client = self::$clients[$key];
        $this->query = $this->query();
    }

    /**
     * 根据主键获取单条数据
     *
     * @param $unique
     * @param string $fields
     * @return array
     */
    public function get($unique, $fields = '*')
    {
        $this->filter(array($this->uniqueKey => $unique));
        $this->field($fields);

        return $this->sendQuery(false);
    }

    /**
     * 返回多条数据
     *
     * @return array
     */
    public function select()
    {
        return $this->sendQuery();
    }

    /**
     * 搜索suggest list
     *
     * @param string $keyword
     * @param string $dictionary
     * @return array
     */
    public function suggest($keyword, $dictionary = null) {
        $this->client->setServlet (1, 'suggest');

        // 初始化Query
        if (is_null($this->query)) {
            $this->query = $this->query();
        }

        $this->query->addParam('suggest.q', $keyword);

        if (!is_null($dictionary)) {
            $this->query->addParam('suggest.dictionary', $dictionary);
        }

        $response = $this->client->query($this->query);
        $httpStatus = $response->getHttpStatus();
        $sucess = $response->success();
        $result = array();

        // 返回数据
        if ($httpStatus == 200 && $sucess) {
            $response = $response->getResponse();
            $result = $this->format($response->suggest, true);

        }
        return $result;
    }


    /**
     * 地理位置搜索
     *
     * @param string $fields   字段列表
     * @param $locations  array('field', 'pt', 'd', 'sk', 'sv') 分别为经纬度字段，经纬度，距离，排除key， 排除value
     * @param int $start
     * @param int $rows
     * @return array
     */
    public function location($fields, $locations, $start = 0, $rows = 10)
    {
        // 初始化Query
        if (is_null($this->query)) {
            $this->query = $this->query();
        }
        $this->query->setQuery('*:*');
        $this->query->addParam('spatial', true);
        $this->query->addParam('pt', $locations['pt']);
        $this->query->addParam('sfield', $locations['field']);
        $this->query->addParam('d', $locations['d']);

        // 排除自身
        if (isset($locations['sk']) && isset($locations['sv']) && !empty($locations['sk']) && !empty($locations['sv'])) {
            $this->query->addFilterQuery('-' . $locations['sk'] . ':"'.$locations['sv']. '"');
        }

        $this->query->addFilterQuery('{!geofilt score=distance filter=false}');
        $this->query->addSortField('geodist()', \SolrQuery::ORDER_ASC);
        $this->query->addField($fields. ','. "score,distance:geodist({$locations['field']},{$locations['pt']})");

        $this->query->setStart($start);

        $this->query->setRows($rows);

        $response = $this->client->query($this->query);
        $httpStatus = $response->getHttpStatus();
        $sucess = $response->success();
        $result = array();

        // 返回数据
        if ($httpStatus == 200 && $sucess) {
            $response = $response->getResponse();
            if ($response->response->numFound) {
                $result['total'] = $response->response->numFound;
                $result['qtime'] = $response->responseHeader->QTime;
                $result['data'] = $this->format($response->response->docs);
            }
        }

        // 清空查询条件
        $this->query = null;
        $this->querys = array();
        return $result;
    }


    /**
     * 搜索关键词
     *
     * @param $keyword
     * @return $this
     */
    public function keyword($keyword, $weight = array())
    {
        $this->querys['query'] = $keyword;

        if (!empty($weight)) {
            $this->querys['weight'] = $weight;
        }

        return $this;
    }

    /**
     * 查询类型
     *
     * useDisMaxQueryParser OR useEDisMaxQueryParser
     *
     * @param string $type
     * @return $this
     */
    public function parse($type = 'dismax')
    {
        $this->querys['parse'] = $type;
        return $this;
    }

    /**
     * 搜索字段列表
     *
     * @param string $field
     * @return $this
     */
    public function field($field = '*')
    {
        $this->querys['field'] = $field;

        return $this;
    }

    /**
     * @param $start
     * @param null $rows
     * @return $this
     */
    public function limit($start, $rows = null)
    {
        if (is_null($rows)) {
            $this->querys['start'] = 0;
            $this->querys['rows'] = intval($start);
        } else {
            $this->querys['start'] = intval($start);
            $this->querys['rows'] = intval($rows);
        }
        return $this;
    }

    /**
     * 搜索过滤条件
     *
     * @param $filter
     * @return $this
     */
    public function filter($filter)
    {
        $this->querys['filter'][] = $filter;

        return $this;
    }

    /**
     * 搜索排序规则
     *
     * @param $fields
     * @return $this
     */
    public function sort($fields)
    {
        if (is_array($fields)) {
            $this->querys['sort'] = array();
            foreach ($fields as $field => $value) {
                $sort['field'] = $field;
                $sort['order'] = 0;

                if (strtolower($value) == 'desc') {
                    $sort['order'] = 1;
                } elseif (is_numeric($value) && $value == 1) {
                    $sort['order'] = 1;
                }
                array_push($this->querys['sort'], $sort);
            }
        }
        return $this;
    }

    /**
     * 执行查询
     *
     * @param bool $multiple
     * @return array
     */
    protected function sendQuery($multiple = true)
    {
        // 初始化Query
        if (is_null($this->query)) {
            $this->query = $this->query();
        }

        // 主查询
        if (isset($this->querys['query'])) {
            $this->query->setQuery($this->querys['query']);
            if (isset($this->querys['weight'])) {
                foreach ($this->querys['weight'] as $key => $w) {
                    $this->query->addQueryField($key, $w);
                }
            }
        } else {
            $this->query->setQuery('*:*');
        }

        // 查询解析器类型
        if (isset($this->querys['parse'])) {
            if ($this->querys['parse'] == 'dismax') {
                $this->query->useDisMaxQueryParser();
            } else {
                $this->query->useEDisMaxQueryParser();
            }
        }

        // 分页
        if (isset($this->querys['start'])) {
            $this->query->setStart($this->querys['start']);
        }
        if (isset($this->querys['rows'])) {
            $this->query->setRows($this->querys['rows']);
        }

        // 字段列表
        if (isset($this->querys['field'])) {
            $this->query->addField($this->querys['field']);
        }

        // 查询条件
        if (isset($this->querys['filter'])) {
            foreach ($this->querys['filter'] as $key => $filter) {
                $filterStr = '';
                if (is_array($filter)) {
                    $strs = array();
                    foreach ($filter as $k => $v) {
                        if (is_array($v)) {
                            $strs[] = $k . ':' . '(' . implode(' OR ', $v) . ')';
                        } elseif (strpos($v, '*') !== false) {
                            $strs[] = $k . ':' . $v;
                        } else {
                            $strs[] = $k . ':"' . $v . '"';
                        }
                    }
                    $filterStr = implode(' AND ', $strs);
                } else {
                    $filterStr = $filter;
                }
                $this->query->addFilterQuery($filterStr);
            }
        }

        // 排序
        if (isset($this->querys['sort']) && !empty($this->querys['sort'])) {
            foreach ($this->querys['sort'] as $k => $v) {
                $this->query->addSortField($v['field'], $v['order']);
            }
        }

        if ($this->debug) {
            console($this->querys);
            console($this->query->toString());
        }

        $response = $this->client->query($this->query);
        $httpStatus = $response->getHttpStatus();
        $sucess = $response->success();
        $result = array();

        // 返回数据
        if ($httpStatus == 200 && $sucess) {
            $response = $response->getResponse();

            if ($response->response->numFound) {
                if ($multiple) {
                    $result['total'] = $response->response->numFound;
                    $result['qtime'] = $response->responseHeader->QTime;
                    $result['data'] = $this->format($response->response->docs);

                } else {
                    $result = $this->format($response->response->docs[0], false);
                }
            }
        }

        // 清空查询条件
        $this->query = null;
        $this->querys = array();
        return $result;
    }

    /**
     * SolrObject to Array
     *
     * @param $data
     * @return array
     */
    protected function toArray($data)
    {
        $results = array();
        foreach ($data as $key => $value) {
            // Solr 对象多值属性如果为空也会返回array[0]
            if (is_array($value) && count($value) == 1 && empty($value[0])) {
                $results[$key] = array();
            } else {
                $results[$key] = $value;
            }
        }
        return $results;
    }

    /**
     * 格式化数据输出
     *
     * @param $datas
     * @param bool $multiple
     * @return array
     */
    protected function format($datas, $multiple = true)
    {
        if ($multiple) {
            $results = array();
            foreach ($datas as $key => $data) {
                $results[$key] = $this->toArray($data);
            }
        } else {
            $results = $this->toArray($datas);
        }

        return $results;
    }

    /**
     * 返回当前连接
     *
     * @return null
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * 返回当前Query
     *
     * @return null
     */
    public function query()
    {
        $this->query = new \SolrDisMaxQuery();
        return $this->query;
    }

    /**
     * Debug 开关
     *
     * @return $this
     */
    public function debug()
    {
        $this->debug = true;
        return $this;
    }
}