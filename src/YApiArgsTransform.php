<?php

namespace Cblink\YApiDoc;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;

/**
 * Class YApiArgsTransform
 * @package Tests
 */
class YApiArgsTransform
{
    /**
     * string [name]                   接口名称
     * string [module]                 模块名称
     * string [desc]                   接口简介
     * array  [params]                 路由参数说明
     * array  [req_trans]              请求参数说明，一维数组，子集使用 . 进行处理，数字使用 *
     * array  [req_except]             请求参数非必填项，一维数组，子集使用 . 进行处理，数字使用 *
     * array  [res_trans]              返回值说明，一维数组，子集使用 . 进行处理，数字使用 *
     * array  [res_except]             返回值非必定返回字段，一维数组，子集使用 . 进行处理，数字使用 *
     *
     * @var array
     */
    protected $payload = [];

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    public function __construct($app, $request, array $payload = [])
    {
        $this->app = $app;
        $this->request =$request;
        $this->payload = $payload;
    }


    /**
     * 获取路由参数说明
     *
     * @return array
     */
    public function getParams()
    {
        return Arr::get($this->payload, 'params', []);
    }

    /**
     * 获取模块名称
     *
     * @return string
     */
    public function getModuleName()
    {
        $moduleName = Arr::get($this->payload, 'module', '');

        // 如果没有指定名称，则尝试去配置中获取
        if (empty($moduleName)) {
            $modules = $this->app->config->get('yapi.modules', []);

            if ($modules) {
                $uri = $this->request->route()->uri();

                foreach ($modules as $prefixUri => $config) {
                    if ($prefixUri == substr($uri, 0, mb_strlen($prefixUri))) {
                        $moduleName = Arr::get($config, 'name', '');
                        break;
                    }
                }
            }
        }

        return $moduleName;
    }

    /**
     * 获取接口名称
     *
     * @return string
     */
    public function getApiName()
    {
        return Arr::get($this->payload, 'name', '');
    }

    /**
     * 获取接口简介
     *
     * @return string
     */
    public function getDescription()
    {
        return Arr::get($this->payload, 'desc', '');
    }

    /**
     * 获取参数格式化备注信息
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getRequestTrans(string $key, string $default = '')
    {
        return $this->get('req_trans', $key, $default);
    }

    /**
     * 获取需要排除的键名
     *
     * @param string $key
     * @param array $default
     * @return string
     */
    public function getRequestExcept(string $key, $default = [])
    {
        return $this->get('req_except', $key, $default);
    }

    /**
     * 判断请求参数是否必填
     *
     * @param string $filleable
     * @param string $key
     * @return string
     */
    public function hasRequiredToRequest(string $filleable, string $key)
    {
        return $this->has('req_except', $filleable, $key);
    }

    /**
     * 获取返回参数备注信息
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getResponseTrans(string $key, string $default = '')
    {
        return $this->get('res_trans', $key, $default);
    }

    /**
     * 获取需要排除的键名
     *
     * @param string $key
     * @param array $default
     * @return string
     */
    public function getResponseExcept(string $key, array $default = [])
    {
        return $this->get('res_except', $key, $default);
    }

    /**
     * 判断返回值必须是否会存在
     *
     * @param string $filleable
     * @param string $key
     * @return string
     */
    public function hasRequiredToResponse(string $filleable, string $key)
    {
        return $this->has('res_except', $filleable, $key);
    }

    /**
     * @param $prefix
     * @param $key
     * @param string $default
     * @return string
     */
    protected function get($prefix, $key, $default = '')
    {
        if (isset($this->payload[$prefix])) {
            if ($key == '*') {
                return $this->payload[$prefix];
            }

            if (array_key_exists($key, $this->payload[$prefix])) {
                return $this->payload[$prefix][$key];
            }
        }

        return $default;
    }

    /**
     * 判断值是否存在与某个数据中
     *
     * @param $field
     * @param $filleable
     * @param $key
     * @return bool
     */
    protected function has($field, $filleable, $key)
    {
        if (isset($this->payload[$field]) && isset($this->payload[$field][$filleable])) {
            $filleable = $this->payload[$field][$filleable];

            return is_array($filleable) && in_array($key, $filleable);
        }

        return true;
    }
}
