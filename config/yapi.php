<?php

return [
    // 是否开启yapi文档的生成
    'open' => true,
    // yap请求地址
    'base_url' => 'http://xxxxx/',
    // 文档合并方式，"normal"(普通模式) , "good"(智能合并), "merge"(完全覆盖)
    'merge' => 'normal',
    // 项目id
    'project_id' => 0,
    // token
    'token' => 'xxxxxxxxxxxxx',

    // 如果开启，命令执行完成后将会自动覆盖线上接口，否则将生产缓存文件
    'auto_uploads' => true,

    'modules' => [
        'api' => '公共接口'
    ],
];
