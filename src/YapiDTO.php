<?php

namespace Cblink\YApiDoc;

use Cblink\DTO\DTO;
use Illuminate\Support\Arr;

/**
 * Class YapiDTO
 * @package Cblink\YApiDoc
 * @property-read string $project
 * @property-read string $name
 * @property-read string $desc
 * @property-read string $category
 * @property-read array $params
 * @property-read array $request
 * @property-read array $response
 */
class YapiDTO extends DTO
{
    public function rules(): array
    {
        return [
            // 项目名
            'project' => ['nullable', 'array'],
            // 接口名
            'name' => ['required', 'string'],
            // 路由参数
            'params' => ['nullable', 'array'],
            // 接口分类
            'category' => ['nullable', 'string'],
            // 接口简介
            'desc' => ['nullable', 'string'],
            // 请求参数
            'request' => ['nullable', 'array'],
            // 请求参数说明
            'request.trans' => ['nullable' , 'array'],
            // 请求参数排除必填
            'request.except' => ['nullable', 'array'],
            // 返回参数
            'response' => ['nullable', 'array'],
            // 返回参数说明
            'response.trans' => ['nullable' , 'array'],
            // 返回参数排除必填
            'response.except' => ['nullable', 'array'],
        ];
    }

    public function getCategoryName($uri)
    {
        $moduleName = $this->category;

        // 如果没有指定名称，则尝试去配置中获取
        if (empty($this->category)) {
            foreach (config('yapi.public.modules', []) as $prefixUri => $val) {
                if ($prefixUri == substr($uri, 0, mb_strlen($prefixUri))) {
                    return $val;
                    break;
                }
            }
        }

        return $moduleName;
    }

    public function hasRequestTrans($key)
    {
        return  Arr::has($this->payload, sprintf('request.trans.%s', $key));
    }

    public function hasRequestExcept($key)
    {
        return  Arr::has($this->payload, sprintf('request.except.%s', $key));
    }

    public function hasResponseTrans($key)
    {
        return  Arr::has($this->payload, sprintf('response.trans.%s', $key));
    }

    public function hasResponseExcept($key)
    {
        return  Arr::has($this->payload, sprintf('response.except.%s', $key));
    }

    public function getRequestTrans($key, $default = null)
    {
        return $this->getResult('request.trans', $key, $default);
    }

    public function getRequestExcept($key, $default = null)
    {
        return $this->getResult('request.except', $key, $default);
    }

    public function getResponseTrans($key, $default = null)
    {
        return $this->getResult('response.trans', $key, $default);
    }

    public function getResponseExcept($key, $default = null)
    {
        return $this->getResult('response.except', $key, $default);
    }

    /**
     * getRequestTrans
     * @param $name
     * @param $key
     * @param null $default
     * @return array|\ArrayAccess|mixed
     */
    protected function getResult($name, $key, $default = null)
    {
        $payload = Arr::get($this->payload, $name);

        if (empty($key)) {
            return $payload;
        }

        if (substr($key, 0, 1) == '.') {
            $key = substr($key, 1);
        }

        return Arr::get($payload, $key, $default);
    }
}
