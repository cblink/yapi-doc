<?php


namespace Cblink\YApiDoc\Bags;

use Illuminate\Support\Arr;

class ResponseBag extends BaseBag
{
    protected $dto;

    protected $config;

    public function __construct($content, $dto, array $config = [])
    {
        $this->dto = $dto;

        $this->config = $config;

        $response = $this->getOriginToArray($content);

        $publicData = $this->getOptions(Arr::get($config, 'public.data', []), $response);

        $this->items = [
            "200" =>  [
                "description" =>  "successful operation",
                "schema" =>  [
                    "type" =>  "object",
                    "title" =>  "empty object",
                ]
            ]
        ];

        $this->items['200']['schema'] = array_merge($publicData, $this->items['200']['schema']);

        $payload = $this->getPayload($response);

        // 处理子集
        $data = $this->handlerArray($payload, [], 'response');

        if (!empty($data)) {
            $this->items['200']['schema']['properties'][Arr::get($config, 'public.prefix')] = $data;
        }
    }

    /**
     * @param $content
     * @return false|mixed|string
     */
    protected function getOriginToArray($content)
    {
        $content = json_decode($content, true);

        if (JSON_ERROR_NONE === json_last_error()) {
            return $content;
        }

        return [];
    }

    /**
     * @param $options
     * @param $response
     * @return array[]
     */
    protected function getOptions($options, $response)
    {
        $item = ['properties' => [], 'required' => []];

        foreach ($options as $key => $val) {
            $must = $val['must'] ?? false;
            $type = $val['type'] ?? 'string';

            if (!$must && !Arr::has($response, $key)) {
                continue;
            }

            if (array_key_exists('required', $val) && $val['required']) {
                array_push($item['required'], $key);
            }

            $item['properties'][$key] = [
                'type' => isset($val['children']) ? 'object' : (isset($response[$key]) ? $this->formatReturnType($response[$key]) : $type),
                'description' => $val['plan'] ?? '',
            ];

            if (!empty($val['children'])) {
                $item['properties'][$key] = array_merge($item['properties'][$key], $this->getOptions($val['children'], $response[$key] ?? []));
            }
        }

        return $item;
    }

    /**
     * @param $response
     * @return mixed
     */
    public function getPayload($response)
    {
        $prefix = Arr::get($this->config, 'public.prefix');

        if (!empty($prefix) && isset($response[$prefix])) {
            return $response[$prefix];
        }

        return $response;
    }
}
