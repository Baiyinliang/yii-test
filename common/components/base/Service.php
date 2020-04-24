<?php


namespace common\components\base;


use yii\base\Component;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\StringHelper;

class Service extends Component
{

    public static $instance;

    /**
     * 返回 service 单例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (empty(self::$instance[get_called_class()])) {
            return self::$instance[get_called_class()] = new static();
        }
        return self::$instance[get_called_class()];
    }

    /**
     * 返回分页结果
     *
     * @param Query $query
     * @param Connection|null $db
     * @param int $page
     * @param int $limit
     * @param callable|null $handle
     * @return array
     */
    protected function pageQuery(
        Query $query,
        Connection $db = null,
        $page = 1,
        $limit = 10,
        callable $handle = null
    )
    {
        $page = intval($page);
        $page = $page > 0 ? $page : 1;
        $limit = intval($limit);
        if($limit === 0) $limit = 10;
        $countQuery = clone $query;
        $totalResults = intval($countQuery->count('*', $db));
        $_limit = $limit > 0 ? $limit : $totalResults;
        $query->offset(($page - 1) * $limit);
        $query->limit($_limit);
        $list = $query->asArray()->all($db);

        if ($list && $handle) {
            if (($result = call_user_func_array($handle, [&$list])) !== null) {
                $list = $result;
            }
        }

        $res = [
            'total' => $totalResults,
            'has_next' => $totalResults > ($page * $limit),
            'page' => $page,
            'limit' => $limit,
            'rows' => $list,
        ];
        return $res;
    }

    /**
     * 返回分页结果
     *
     * @param array $list
     * @param $totalResults
     * @param int $page
     * @param int $limit
     * @return array
     */
    protected function pageResult(array $list = [], $totalResults = 0, $page = 1, $limit = 100)
    {
        return [
            'total' => $totalResults,
            'has_next' => $totalResults > ($page * $limit),
            'page' => $page,
            'limit' => $limit,
            'list' => $list
        ];
    }

    /**
     * 字段重整，用于对 join 产生的数据重整为对象格式，map 的映射一定要明显
     *
     * [['name' => 'xx', 'xxx_name' => 'yyy']];
     *
     * fieldReorganize($a, ['xxx_' => 'xxx'])
     *
     * [['name' => 'xx', 'xxx' => ['name' => 'yyy']]]
     *
     * @param array $data
     * @param array $map
     */
    public function fieldReorganize(array &$data, array $map)
    {
        if (!$data || !$map) {
            return;
        }

        foreach ($data as $key => &$value) {
            if (is_integer($key)) {
                $this->fieldReorganize($value, $map);
                continue;
            }

            foreach ($map as $k => $v) {
                if (StringHelper::startsWith($key, $k)) {
                    if (!isset($data[$v])) {
                        $data[$v] = [];
                    }
                    $data[$v][str_replace($k, '', $key)] = $value;
                    unset($data[$key]);
                }
            }
        }
    }

}