<?php

return [
    // yap请求地址
    'base_url' => 'http://xxxxxxxx/',
    // 文档合并方式，"normal"(普通模式) , "good"(智能合并), "merge"(完全覆盖)
    'merge' => 'merge',

    'config' => [
        'default' => [
            'id' => 1,
            'token' => ''
        ],
    ],

    'openapi' => [
        'enable' => false,
        'path' => public_path('openapi.json'),
    ],

    'public' => [
        // 前缀
        'prefix' => 'data',

        // 公共的请求参数,query部分
        'query' => [
            'page' => ['plan' => '页码，默认1'],
            'page_size' => ['plan' => '每页数量，不超过200，默认15'],
            'is_all' => ['plan' => '是否获取全部数据，不超过1000条'],
        ],

        // 公共的响应参数
        'data' => [
            'err_code' => ['plan' => '错误码，0表示成功', 'must' => true, 'required' => true],
            'err_msg' => ['plan' => '错误说明，请求失败时范湖', 'must' => true],
            'meta' => [
                'plan' => '分页数据',
                'must' => false,
                'children' => [
                    'current_page' => ['plan' => '当前页数'],
                    'total' => ['plan' => '总数量'],
                    'per_page' => ['plan' => '每页数量'],
                ]
            ]
        ]
    ]
];
