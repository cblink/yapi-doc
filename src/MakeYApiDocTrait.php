<?php

namespace Cblink\YApiDoc;

use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Trait RequestTrait
 * @method TestResponse patch($uri, array $data = [], array $headers = [])
 * @method TestResponse patchJson($uri, array $data = [], array $headers = [])
 * @method TestResponse post($uri, array $data = [], array $headers = [])
 * @method TestResponse postJson($uri, array $data = [], array $headers = [])
 * @method TestResponse put($uri, array $data = [], array $headers = [])
 * @method TestResponse putJson($uri, array $data = [], array $headers = [])
 * @method TestResponse delete($uri, array $data = [], array $headers = [])
 * @method TestResponse deleteJson($uri, array $data = [], array $headers = [])
 * @method TestResponse options($uri, array $data = [], array $headers = [])
 * @method TestResponse optionsJson($uri, array $data = [], array $headers = [])
 * @method TestResponse json($method, $uri, array $data = [], array $headers = [])
 */
trait MakeYApiDocTrait
{
    /**
     * Call the given URI and return the Response.
     *
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     * @return TestResponse
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class);

        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($uri),
            $method,
            $parameters,
            $cookies,
            $files,
            array_replace($this->serverVariables, $server),
            $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        if ($this->followRedirects) {
            $response = $this->followRedirects($response);
        }

        $kernel->terminate($request, $response);

        return new TestResponse($this->app, $response, $request);
    }
}
