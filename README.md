#### Hyperf-cache

##### 1.安装
在项目中 `composer.json` 的 `repositories` 项中增加
``` 
{
    ....
    "repositories":{
        "0":{
            "type":"composer",
            "url":"http://composer.exampleol.net/"
        }
        ....
    }
}
```
修改完成后执行 
```bash
$ composer require byzhiyuan/hyperf-cache
$ php bin/hyperf.php vendor:publish byzhiyuan/hyperf-cache
```
如果遇到错误信息为:
`Your configuration does not allow connections to. See https://getcomposer.org/doc/06-config.md#secure-http for details` 
执行以下命令
```bash
$ composer config secure-http false
```
##### 3.配置文件说明
1. 增加 Cache Prifix支持 `config/autoload/cache.php`
```php
<?php
return [
    'default' => [
        //使用的驱动
        'driver' => BY\HyperfCache\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'cache.',
        //pool 对应config/autoload/redis.php key 
        'pool' => 'default',
    ],
    'cache1' => [
       //使用的驱动
       'driver' => BY\HyperfCache\RedisDriver::class,
       'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
       'prefix' => 'cache.',
       //pool 对应config/autoload/redis.php key
       'pool' => 'redis1',
    ],
];
//调用cache1
ApplicationContext::getContainer()->get(CacheManager::class)->getDriver('cache1')->set();
// 默认调用
ApplicationContext::getContainer()->get(Cache::class)->set();
```
2.多层Cache支持 `config/autoload/byzhiyuan_cache.php`
```php
<?php

return [

    'enable' => false, // false 表示不启用cache

    'drivers' => [
        'swoole_table' => [
            'driver'      => 'swooleTable',
            //最大记录条数
            //如果 size 不是为 2 的 N 次方，如 1024、8192、65536 等，底层会自动调整为接近的一个数字，如果小于 1024 则默认成 1024，即 1024 是最小值。
            //由于 Table 底层是建立在共享内存之上，所以无法动态扩容。所以 $size 必须在创建前自己计算设置好，Table 能存储的最大行数与 $size 正相关，但不完全一致，如 $size 为 1024 实际可存储的行数小于 1024，如果 $size 过大，机器内存不足 Table 会创建失败。
            //文档介绍 https://wiki.swoole.com/#/memory/table?id=%e9%ab%98%e6%80%a7%e8%83%bd%e5%85%b1%e4%ba%ab%e5%86%85%e5%ad%98-table
            'size'        => 1024,
            //每条数据最大长度， 超过长度将被截断. 单位 byte
            'column_size' => 2048,
        ],

        'cache' => [
            'driver'     => 'cache',
            'connection' => 'default',
        ],
    ],
];

```

```php
<?php
class UserDS{
     /**
      * @CacheAnnotation(key = {"uid"}, cacheEmpty=false, drivers = {
      *     {"driver" = "swoole_table",  "ttl" = 10},
      *     {"driver" = "cache",         "ttl" = 60,  listener="update-user-base"},
      * })
      */
    public function findByUid($uid) {
       return  ['uid'=>1,'name'=>"张三",'avatar'=>"http://xxxxx"];
    } 
}

```
`@CacheAnnotaion` 注解支持缓存一个方法的返回参数。
主要参数有  
key: 用来指明cache的键由哪些参数组成  
cacheEmpty: 当被注解方法返回 empty 时 是否缓存  
drivers: 指明缓存所用的方式  
drivers.driver 对应 `byzhiyuan_cache.dirvers` 配置  
drivers.ttl    缓存有效时间 单位 秒  
listener:  缓存更新监听器,在注解中设置好listener的名称，在需要修改缓存数据的方法中dispatch(派发)CacheEvent事件，并传入两个参数:在注解中设置的listener名称和要删除的缓存key，例如：  
```php
$this->eventDispatcher->dispatch(new CacheEvent('update-user-base', ['uid' => $uid]));
```



### 版本改动:
v1.0.0   增加 hyperf-cache cache redis支持指定poolName
