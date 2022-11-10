<?php
declare(strict_types=1);
return [

    'enable' => false,

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

