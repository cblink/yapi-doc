<?php

namespace Cblink\YApiDoc;

use Cblink\DTO\DTO;
use Illuminate\Support\Arr;

/**
 * Class YapiDTO
 * @package Cblink\YApiDoc
 * @property-read string $name
 * @property-read string $desc
 * @property-read string $category
 * @property-read array $params
 * @property-read array $project
 */
class YapiDTO extends DTO
{
    public function rules(): array
    {
        return [
            'project' => ['nullable', 'array'],
            'name' => ['required', 'string'],
            'params' => ['nullable', 'array'],
            'category' => ['nullable', 'string'],
            'desc' => ['nullable', 'string'],
            'request' => ['nullable', 'array'],
            'request.trans' => ['nullable' , 'array'],
            'request.except' => ['nullable', 'array'],
            'response' => ['nullable', 'array'],
            'response.trans' => ['nullable' , 'array'],
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
