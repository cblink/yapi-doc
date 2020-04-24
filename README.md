## yapi-doc

结合现有的接口集成测试，只需增加几行备注信息，即可自动创建yap文档。

> 支持的laravel版本

- laravel 6.x
- laravel 7.x


#### 安装

```shell script
composer require cblink/yapi-doc --dev
```

### 配置代码

添加至config/app.php providers中

```php
[
   // ...
  'providers' => [
    //...
    Cblink\YApiDoc\ServiceProvider::class,
  ]
];
```

导入配置文件，配置文件名必须为yapi.php
```shell script
php artisan vendor:publish --provider=Cblink\YApiDoc\ServiceProvider::class
```


### 配置说明
```php
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
    // 为了省事，不同前缀的api自动处理到YAPI不同的分组中
    // 也可以在文档生成的部分自定义分组名称
    // 格式： 路由前缀 => 分组名称， 路由前缀不需要已 / 开头，
    'modules' => [
        'api' => '公共接口'
    ],

    // 文件缓存默认存放于storage_path/app/yapi/目录下
    // 可以增加此参数自定义存放目录
    // 'cache_path' => storage_path('/app/yapi/')

    // 处理文档的生成需要花费不少的时间,可以通过设置时间参数跳过文档的生成
    // 时间单位 s
    // 注意：此参数增加之后，同一接口地址，无论接口是否修改，此时间内这生成一次
    // 默认为 0，即每次都更新
    // 'updated_time' => 3600
];
```

### 使用

-  修改 `test/TestCase.php`
-  引用 `Cblink\YApiDoc\MakeYApiDocTrait`
```php
abstract class TestCase
{
    // ...    

    // 基类中引用
    use Cblink\YApiDoc\MakeYApiDocTrait;
    // ...
}
```

- 生成文档
```php

// 集成测试
$response = $this->get('/api/test', [
    'status' => 1,
    'page' => [
        'page' => 1,
        'page_size' => 15
    ]
]);

// 此处仅为示例，为了可以更好的理解 makeYApi参数的使用
// 实际单元测试方式自行编写
// 此部分编写不会影响到文档的生成
$response->assertReturn([
    'err_code' => 0,
    'data' => [
        [
            'name' => 'xxabb',
            'status' => 2
        ]   
    ],
    'meta' => [
        'page' => 1
    ]   
]);

// 执行完成后 使用makeYApi生成文档
$response->makeYApi([
    'name' => '这是接口名称',
    // 此名称会优先使用，如不填将会去取配置中的名称
    'module' => '分组名称',
    // 需要为请求参数增加说明
    'req_trans' => [
        'status' => '状态',
        'page.page_size' => '每页数量',
    ],
    // 设置请求参数非必填字段
    'req_expect' => [
        'page' => ['page_size']
    ],
    // 需要为返回值增加说明，
    // 默认增加到 data下的字段
    'res_trans' => [
        // * 表示数组
        '*.name' => '名称',
        '*.status' => '状态',
    ],
    // 非必定返回的值
    'res_expect' => [
        // 格式，父级节点 => [子节点]
        '*' => ['name']
    ]
]);



``` 

### 发布文档

```shell script

# 单元测试运行完后，可以使用php artisan yapi 进行文档的发布
# 如果 auto_uploads 未开启，将会生成swagger.json文件到本地，你可以手动提交
# 文件路径  storage_path('app/yapi.json')

php artisan yapi
```
