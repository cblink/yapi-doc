<?php

namespace Cblink\YApiDoc;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class YApiHandler
 * @package Tests
 */
class YApiHandler
{
    /**
     * @var YApiArgsTransform
     */
    protected $transform;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    public function __construct($app, $request, $response, array $transform = [])
    {
        $this->app = $app;

        $this->request = $request;

        $this->response = $response;

        $this->transform = new YApiArgsTransform($this->app, $request, $transform);
    }

    public function cache()
    {
        // 如果开启功能，才开始缓存文件
        if (!$this->app->config->get('yapi.open', false)) {
            return;
        }

        $json = [
            "tags" =>  [$this->transform->getModuleName()],
            "summary" =>  $this->transform->getApiName(),
            "description" =>  $this->transform->getDescription(),
            "parameters" =>  $this->getParameters(),
            "responses" => $this->getResponse()
        ];

        $fileName = $this->buildUrl($this->request->getMethod(), $this->request->route()->uri());


        $filePath = $this->app->config->get('yapi.cache_path', $this->app->storagePath() . '/app/yapi/');

        if (!file_exists($filePath)) {
            mkdir($filePath, 0777);
            $gitignore = \File::get(storage_path('app/public/.gitignore'));
            \File::put(sprintf('%s%s', $filePath, '.gitignore'), $gitignore);
        }

        \File::put(sprintf('%s/%s', rtrim($filePath), ltrim($fileName)), json_encode($json, JSON_UNESCAPED_UNICODE, 512));
    }

    /**
     * 获取参数内容
     *
     * @return array
     */
    protected function getParameters()
    {
        return array_merge(
            $this->getParams(),
            $this->gerQueryParameters(),
            $this->getJsonParameters()
        );
    }

    /**
     * 获取路由参数
     *
     * @return array
     */
    protected function getParams()
    {
        $params = $this->transform->getParams();

        $items = [];

        // 没有需要匹配的路由说明则跳过
        if (!empty($params)) {
            // 检查路由的每一个路由段
            foreach (explode('/', $this->request->route()->uri()) as $uri) {
                // 检查此路由段是否是变量
                if (preg_match('/^\{\??([A-Za-z0-9\-_]+)\}$/', $uri, $args)) {
                    // 如果检查到uri中存在变量，但不存在与参数匹配红，则跳过
                    if (!isset($params[$args[1]])) {
                        continue;
                    }
                    array_push($items, [
                        "name" => $args[1],
                        "in"=> "path",
                        "description"=> $params[$args[1]],
                        "required" => (substr($uri, 1, 1) == '?' ? false : true),
                        "type"=> "string"
                    ]);
                }
            }
        }

        return $items;
    }

    /**
     * 获取query参数
     *
     * @return array
     */
    protected function gerQueryParameters()
    {
        $parameters = [];

        foreach ($this->request->query->all() as $key => $value) {
            $description = sprintf(
                "%s %s",
                (is_array($value) ? 'array :': ''),
                $this->transform->getRequestTrans($key)
            );

            array_push($parameters, [
                'name' => $key,
                'required' => $this->transform->hasRequiredToRequest('*', $key),
                'description' => $description,
                'in' => 'query',
                'type' => 'string'
            ]);
        }

        return $parameters;
    }

    /**
     * 获取post json参数
     *
     * @return array
     */
    protected function getJsonParameters()
    {
        $data = $this->request->json()->all();

        $properties = [];

        foreach ($data as $key => $item) {
            $properties[$key] = $this->handlerArray(
                $this->transform->getRequestTrans($key),
                $item,
                [$key],
                'request'
            );
        }

        if ($properties) {
            return [[
                "name" => "root",
                "in" => "body",
                "schema" =>  [
                    "type" => "object",
                    "title" => "empty object",
                    "properties" =>  $properties,
                    "required" => Arr::except(array_keys($data), $this->transform->getRequestExcept('*'))
                ]
            ]];
        }

        return [];
    }

    /**
     * 获取返回内容
     *
     * @return array
     * @throws \Exception
     */
    protected function getResponse()
    {
        $response = $this->getOriginToArray();

        $return = [
            "200" =>  [
                "description" =>  "successful operation",
                "schema" =>  [
                    "type" =>  "object",
                    "title" =>  "empty object",
                    "properties" =>  [
                        "err_code" =>  ["type" =>  "integer", "description" =>  "错误码，0表示成功"],
                        "err_msg" =>  ["type" =>  "string", "description" =>  "错误说明，请求失败时范湖"],
                    ],
                    "required" =>  ["err_code"]
                ]
            ]
        ];

        if (isset($response['meta'])) {
            $return['200']['schema']['properties']['meta'] = [
                "type" =>  "object",
                "description" => '分页数据',
                "properties" => [
                    'current_page' => ['type' => 'integer', 'description' => '当前页数'],
                    'total' => ['type' => 'integer', 'description' => '总数量'],
                    'per_page' => ['type' => 'integer', 'description' => '每页数量'],
                ],
                'required' => ['current_page', 'per_page']
            ];
        }

        // 处理子集
        $data = $this->handlerArray(
            '返回数据',
            $response['data'],
            [],
            'response'
        );

        if (!empty($data)) {
            $return['200']['schema']['properties']['data'] = $data;
        }

        return $return;
    }

    /**
     * 将array数据转换成yapi文档格式
     *
     * @param $remark
     * @param array $payload
     * @param array $prefix
     * @param string $method
     * @return array
     */
    protected function handlerArray($remark, array $payload = [], array $prefix = [], $method = 'request')
    {
        if (empty($payload)) {
            return [];
        }

        $prefixString = implode('.', $prefix);

        $type = $this->getType($payload);

        $data = [
            'type' => $type,
            'description' => $remark,
            ($type == 'array' ? 'items' : 'properties') => []
        ];

        $transMethodString = sprintf('get%sTrans', ucfirst($method));
        $expectMethodString = sprintf('get%sExpect', ucfirst($method));

        if ($type === 'array') {

            // 数组返回只需要处理子集的数据
            foreach ($payload as $item) {
                foreach ($item as $val) {
                    array_push(
                        $data['items'],
                        $this->handlerArray(
                            '',
                            $val,
                            array_merge($prefix, ['*']),
                            $method
                        )
                    );
                    break;
                }
                break;
            }
        } else {
            foreach ($payload as $key => $item) {
                $currentString = implode('.', array_merge($prefix, [$key]));

                $currentRemark = $this->transform->{$transMethodString}($currentString);

                if (is_array($item)) {
                    $data['properties'][$key] = $this->handlerArray($currentRemark, $item);
                    $data['required'] = Arr::except(array_keys($item), $this->transform->$expectMethodString($prefixString));
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
     * 构建url
     *
     * @param $method
     * @param $url
     * @return string
     */
    protected function buildUrl($method, $url)
    {
        $uri = base64_encode(str_replace('?', '', $url));

        // 需要处理掉路由中的 .
        return sprintf("%s-%s.yapi", $method, $uri);
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

    /**
     * @return false|mixed|string
     * @throws \Exception
     */
    protected function getOriginToArray()
    {
        $response = $this->response->getContent();

        $response = json_decode($response, true);

        if (JSON_ERROR_NONE === json_last_error()) {
            return $response;
        }

        throw new \Exception('response not a json string');
    }
}
