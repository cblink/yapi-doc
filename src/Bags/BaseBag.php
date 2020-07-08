<?php

namespace Cblink\YApiDoc\Bags;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class BaseBag implements Arrayable
{
    protected $items = [];

    public function toArray()
    {
        return $this->items;
    }

    /**
     * 将array数据转换成yapi文档格式
     *
     * @param array $payload
     * @param array $prefix
     * @param string $method
     * @return array
     */
    protected function handlerArray(array $payload = [], array $prefix = [], $method = 'request')
    {
        // 获取payload类型，对象或数组
        $type = $this->getType($payload);

        //
        $transMethodString = sprintf('get%sTrans', ucfirst($method));
        $expectMethodString = sprintf('get%sExcept', ucfirst($method));

        $desc = empty(implode('.', $prefix)) ? "" : $this->dto->{$transMethodString}(implode('.', $prefix));

        $data = [
            'type' => $type,
            'description' => $desc,
            ($type == 'array' ? 'items' : 'properties') => [],
        ];

        if ($type === 'array') {
            // 数组返回只需要处理子集的数据
            foreach ($payload as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $data['items'] = $this->handlerArray(
                    $item,
                    array_merge($prefix, ['*']),
                    $method
                );
                break;
            }
        } else {

            // 获取前缀
            $prefixString = implode('.', $prefix);

            // 必填字段
            $data['required'] = array_keys(Arr::except($payload, $this->dto->{$expectMethodString}($prefixString)));

            foreach ($payload as $key => $item) {

                // 当前key的前缀
                $currentString = implode('.', array_merge($prefix, [$key]));

                // 当前备注
                $currentRemark = $this->dto->{$transMethodString}($currentString);

                if (is_array($item)) {
                    $data['properties'][$key] = $this->handlerArray($item, array_merge($prefix, [$key]), $method);
                    continue;
                }

                $data['properties'][$key] = [
                    'type' => $this->formatReturnType($item),
                    'description' => $currentRemark
                ];
            }
        }

        return $data;
    }

    /**
     * 格式化返回类型
     *
     * @param $val
     * @return string
     */
    public function formatReturnType($val)
    {
        if (is_int($val)) {
            return 'integer';
        }

        if (is_float($val)) {
            return 'number';
        }

        if (is_bool($val)) {
            return 'boolean';
        }

        return 'string';
    }

    /**
     * @param $payload
     * @return string
     */
    protected function getType($payload)
    {
        return array_keys($payload) === range(0, count($payload) - 1) ?
            'array' :
            'object';
    }
}
