<?php

namespace Cblink\YApiDoc;

/**
 * Class TestResponse
 * @package Tests
 */
class TestResponse extends \Illuminate\Testing\TestResponse
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var
     */
    protected $args;

    public function __construct($app, $response, $request)
    {
        $this->app = $app;
        $this->request = $request;
        parent::__construct($response);
    }

    /**
     * @param array $payload
     * @return $this
     */
    public function makeYApi(array $payload = [])
    {
        $yapi = new YApiHandler($this->app, $this->request, $this->baseResponse, $payload);

        $yapi->cache();

        return $this;
    }
}
