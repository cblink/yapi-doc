<?php

namespace Cblink\YApiDoc\Bags;

/**
 * Class ParamsBag
 * @package Cblink\YApiDoc\Bags
 */
class ParamsBag extends BaseBag
{
    public function __construct($url, $params)
    {
        // 检查路由的每一个路由段
        foreach (explode('/', $url) as $uri) {
            // 检查此路由段是否是变量
            if (preg_match('/^\{\??([A-Za-z0-9\-_]+)\}$/', $uri, $args)) {
                // 如果检查到uri中存在变量，但不存在与参数匹配红，则跳过
                if (!isset($params[$args[1]])) {
                    continue;
                }

                array_push($this->items, [
                    "name" => $args[1],
                    "in"=> "path",
                    "description"=> $params[$args[1]],
                    "required" => (substr($uri, 1, 1) == '?' ? false : true),
                    "type"=> "string"
                ]);
            }
        }
    }
}
