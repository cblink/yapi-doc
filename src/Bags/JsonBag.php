<?php


namespace Cblink\YApiDoc\Bags;

class JsonBag extends BaseBag
{
    protected $dto;

    public function __construct($data, $dto)
    {
        $this->dto = $dto;

        $this->items = $data ? [[
            "name" => "root",
            "in" => "body",
            "schema" =>  array_merge([
                "type" => "object",
                "title" => "empty object",
            ], $this->handlerArray($data, [], 'request'))
        ]] : [];
    }
}
