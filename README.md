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
导入配置文件，配置文件名必须为yapi.php
```shell script
php artisan vendor:publish --provider=Cblink\YApiDoc\ServiceProvider::class
```


### 配置说明

配置比较麻烦，先凑合着用吧，这个等有空了再改

```php
return [
   // yap请求地址
       'base_url' => 'http://yapi.cblink.net/',
       // 文档合并方式，"normal"(普通模式) , "good"(智能合并), "merge"(完全覆盖)
       'merge' => 'merge',
   
        // token配置
       'config' => [
            // key名称可以自定义，默认是 default ，可以增加多个token配置
           'default' => [
                // yapi project id
               'id' => 1,
                // yapi project token
               'token' => 'xxxxxx'
           ],
       ],
   
       'public' => [
           // 返回值前缀
           'prefix' => 'data',
   
           // 公共的请求参数,query部分
           'query' => [
                // 格式 key => [] , key为返回值名称
                // plan 的返回参数说明，
                // must，无论接口返回是否返回，都出现在yapi文档中
                // required，是否必须填写
               'page' => ['plan' => '页码，默认1'],
               'page_size' => ['plan' => '每页数量，不超过200，默认15'],
               'is_all' => ['plan' => '是否获取全部数据，不超过1000条'],
           ],
   
           // 公共的响应参数
           'data' => [
                // 格式 key => [] , key为返回值名称
                // plan 的返回参数说明，
                // must，无论接口返回是否返回，都出现在yapi文档中
                // required，是否必须出现
                // children，子集信息，参数和上边一样
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
];
```

### 使用

看下面示例使用

```php
use Tests\TestCase;

class BaseTestCase extends TestCase
{
    public function test_store_products()
    {
        // 接口请求
         $res = $this->post('/api/product', [
            'product_name' => '商品名称',
            'price' => 500
        ]);
          
        // 参数 request, Illuminate\Http\Request
        $request = $this->app['request'];
        // 参数 response，Illuminate\Http\Response
        $response = $res->baseResponse;
        // 参数 dto
        $dto = new \Cblink\YApiDoc\YapiDTO([
            // 这里填多项会同时发布到多个项目组中
            'project' => ['default', 'custom'],
            'name' => '接口名称',
            'category' => '接口分组',
            'request' => [
                'trans' => [
                    ''
                ],
                'expect' => [
                ],       
            ],
            'response' => [
                'trans' => [
                    // 数组的说明,如果是需要给数组加说明，需要带上*
                    // 多层级可以嵌套,   *.products.*.name => '订单商品名称'
                    '*.name' => '',
                    'price' => ''
                ],
                // 允许非必填的字段
                'expect' => [
                    '*' => ['name', 'price'],
                    'price',
                    'aaa' => ['name']
                ],   
            ]    
        ]);
        
        $yapi = new \Cblink\YApiDoc\YApi($request, $response, $dto);
        
        // 生成文件
        $yapi->make();
    }
}
```

### 发布文档

```shell script
php artisan upload:yapi
```
