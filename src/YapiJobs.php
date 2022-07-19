<?php

namespace Cblink\YApiDoc;

use Cblink\YApi\YApiRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class YapiJobs implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('config');
    }

    public function handle()
    {
        $startMemory = memory_get_usage();

        foreach ($this->config['config'] ?? [] as $project => $config) {
            if (!file_exists($this->getCachePath($project))) {
                $this->line(sprintf("%s 文档无需更新！", $project));
                continue;
            }

            $swagger = $this->loadSwagger($project);

            if (empty($swagger['paths'])) {
                $this->line(sprintf("%s 文档无需更新！", $project));
                continue;
            }

            $this->upload($project, $config, $swagger);
        }

        $this->line(sprintf("内存消耗 %sk", (memory_get_usage() - $startMemory) / 1024));
    }

    /**
     * @param $project
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function loadSwagger($project)
    {
        $swagger = ["swagger" => "2.0", "paths" => []];

        $version = $this->getVersionData($project);

        $update = [];

        foreach (scandir($this->getCachePath($project)) as $file) {
            if ($this->continueFile($file)) {
                continue;
            }

            list($method, $uri, $content) = $this->getSwaggerByFile($project, $file);

            $key = sprintf('%s.%s', $method, str_replace('/', '.', $uri));
            $md5 = md5(serialize($content));

            if (array_key_exists($key, $version) && $md5 == $version[$key]) {
                continue;
            }

            $version[$key] = $md5;

            array_push($update, [
                'method' => $method,
                'url' => $uri,
            ]);

            $swagger['paths'][$uri][$method] = $content;
        }

        $this->makeVersionFile($project, $version);

        $this->makeUpdateFile($project, $update);

        return $swagger;
    }

    /**
     * @param $project
     * @return array|mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getVersionData($project)
    {
        $file = $this->getVersionFilePath($project);

        if (!File::exists($file)) {
            return [];
        }

        $data = json_decode(File::get($file), true);

        if (json_last_error()) {
            return [];
        }

        return $data;
    }

    /**
     * @param $name
     * @param array $data
     */
    public function makeVersionFile($name, array $data)
    {
        File::put($this->getVersionFilePath($name), json_encode($data));
    }

    /**
     * 生成更新文件
     *
     * @param $name
     * @param array $data
     */
    public function makeUpdateFile($name, array $data)
    {
        // 没有数据部新增记录
        if (!$data) {
            return;
        }

        if (!file_exists($path = storage_path('app/yapi/update'))) {
            mkdir($path, 0777, true);
        }

        File::put(sprintf('%s/%s-%s.json', $path, $name, date('YmdHis')), json_encode($data));
    }

    /**
     * 获取版本文件
     *
     * @param $name
     * @return string
     */
    public function getVersionFilePath($name)
    {
        return storage_path(sprintf('app/yapi/%s.version', $name));
    }

    /**
     * 获取缓存目录
     *
     * @param $project
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function getCachePath($project)
    {
        return storage_path(sprintf('app/yapi/docs/%s/', $project));
    }

    /**
     * 获取swagger信息
     *
     * @param $project
     * @param $file
     * @return array
     */
    public function getSwaggerByFile($project, $file)
    {
        $example = explode("-", substr($file, 0, -5));

        $method = strtolower($example[0]);
        $uri = base64_decode($example[1]);

        if (!isset($swagger['paths'][$uri])) {
            $swagger['paths'][$uri] = [];
        }

        $content = json_decode(file_get_contents(sprintf("%s%s", $this->getCachePath($project), $file)), true);

        return [$method, $uri, $content];
    }

    /**
     * 检测是否需要load的文件
     *
     * @param $file
     * @return bool
     */
    public function continueFile($file)
    {
        return $file == '.' || $file == '..' || substr($file, -5) !== '.yapi';
    }

    /**
     * 提交代码
     *
     * @param $project
     * @param $config
     * @param $swagger
     * @throws \Cblink\YApi\YApiException
     */
    public function upload($project, $config, $swagger)
    {
        $swaggerContent = json_encode($swagger, 448, 512);

        if (Arr::get($this->config, 'base_url')) {
            $yapi = new YApiRequest(Arr::get($this->config, 'base_url'));

            $yapi->setConfig($config['id'], $config['token'])
                ->importData($swaggerContent, Arr::get($this->config, 'merge', 'normal'));
        }

        if (Arr::get($this->config, 'openapi.enable')) {
            file_put_contents(
                Arr::get($this->config, 'openapi.path', public_path('openapi.json')),
                $swaggerContent
            );
        }

        $this->line(sprintf("%s 成功更新%s个文档!", $project, count($swagger['paths'])));
    }

    public function line($message)
    {
        dump($message);
    }
}
