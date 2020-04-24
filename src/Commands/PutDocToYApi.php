<?php

namespace Cblink\YApiDoc\Commands;

use Cblink\YApi\YApi;
use Illuminate\Console\Command;

class PutDocToYApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yapi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update local docs to yapi';

    /**
     * @throws \Cblink\YApi\YApiException
     */
    public function handle()
    {
        $startMemory = memory_get_usage();

        $path = config('yapi/cache_path', storage_path('app/yapi/'));

        $files = scandir($path);

        $swagger = [
            "swagger" => "2.0",
            "paths" => []
        ];

        $yapi = new YApi(config('yapi.base_url'));

        foreach ($files as $file) {
            if (
                $file == '.' ||
                $files == '..' ||
                substr($file, -5) !== '.yapi'
            ) {
                continue;
            }

            $example = explode("-", substr($file, 0, -5));

            $method = strtolower($example[0]);
            $uri = base64_decode($example[1]);

            if (!isset($swagger['paths'][$uri])) {
                $swagger['paths'][$uri] = [];
            }

            $content = json_decode(file_get_contents(sprintf("%s%s", $path, $file)), true);

            $swagger['paths'][$uri][$method] = $content;
        }

        $endMemory = memory_get_usage();

        $this->line(sprintf("memory used %sk", ($endMemory-$startMemory) / 1024));

        if (config('yapi.auto_uploads', false)) {
            $yapi->setConfig(
                config('yapi.project_id'),
                config('yapi.token')
            )->importData(
                json_encode($swagger, JSON_UNESCAPED_UNICODE, 512),
                config('yapi.merge', 'normal')
            );

            $this->line("docs sync success!");
        } else {
            $filePath = storage_path('app/yap.json');
            \File::put($filePath, json_encode($swagger, JSON_UNESCAPED_UNICODE, 512));

            $this->line(sprintf("yapi cache file to %s!", $filePath));
        }
    }
}
