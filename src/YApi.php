<?php

namespace Cblink\YApiDoc;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Cblink\YApiDoc\Bags\JsonBag;
use Cblink\YApiDoc\Bags\QueryBag;
use Cblink\YApiDoc\Bags\ParamsBag;
use Cblink\YApiDoc\Bags\ResponseBag;
use Illuminate\Support\Facades\File;

/**
 * Class YApiHandler
 * @package Tests
 */
class YApi
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var
     */
    protected $dto;

    /**
     * @var ParamsBag
     */
    protected $paramBag;
    protected $queryBag;
    protected $headerBag;

    protected $jsonBag;
    protected $responseBag;

    protected $useUrl = false;

    protected $config;

    /**
     * YApiHandler constructor.
     * @param Request $request
     * @param Response $response
     * @param YapiDTO $dto
     * @param array $config
     */
    public function __construct($request, $response, YapiDTO $dto, array $config = [])
    {
        $this->request = $request;
        $this->response = $response;
        $this->dto = $dto;

        $this->config = $config;

        $this->paramBag = new ParamsBag($this->getUri(), $dto->params);
        $this->queryBag = new QueryBag($request->query->all(), $dto, $this->config);
        $this->jsonBag = new JsonBag($request->request->all(), $dto);
        $this->responseBag = new ResponseBag($response->getContent(), $dto, $this->config);
    }

    /**
     * 生成文件
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     */
    public function make()
    {
        $fileName = $this->buildUrl($this->request->getMethod(), $this->getUri());

        $data = $this->getJsonData();

        foreach (($this->dto->project ?? ['default']) as $project) {
            $file = $this->getFilePath($project, $fileName);

            $this->putFile($file, $data);
        }
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->useUrl ? $this->request->path() : $this->request->route()->uri();
    }

    /**
     * 使用完整的url作为接口，忽略实际路由中的参数匹配
     *
     * @return $this
     */
    public function useUrl()
    {
        $this->useUrl = true;

        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getJsonData()
    {
        return [
            "tags" =>  [$this->dto->getCategoryName($this->request->route()->uri())],
            "summary" =>  $this->dto->name,
            "description" =>  $this->dto->desc,
            "parameters" =>  array_merge(
                $this->paramBag->toArray(),
                $this->queryBag->toArray(),
                $this->jsonBag->toArray()
            ),
            "responses" => $this->responseBag->toArray()
        ];
    }

    /**
     * 写入文件
     * @param $file
     * @param $data
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function putFile($file, $data)
    {
        if (File::exists($file)) {
            $origin = json_decode(File::get($file), true);
            $data = array_replace_recursive($origin, $data);
        }

        File::put($file, json_encode($data, JSON_UNESCAPED_UNICODE, 512));
    }

    /**
     * 获取路径
     *
     * @param $project
     * @param $fileName
     * @return string
     */
    protected function getFilePath($project, $fileName)
    {
        $filePath = storage_path(sprintf('/app/yapi/docs/%s/', $project));

        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }

        $file = sprintf('%s/%s', rtrim($filePath), ltrim($fileName));

        return $file;
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
}
