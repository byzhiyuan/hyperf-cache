<?php
declare(strict_types=1);

namespace BY\HyperfCache\Cache\Drivers;


use Hyperf\Memory\TableManager;

class SwooleTableDriver
{
    protected $table;
    protected $options;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->table   = TableManager::get($options['table_name']);
    }

    public function memorySize()
    {
        return $this->table->getMemorySize();
    }

    public function get($key)
    {
        $parseKey = $this->parseKey($key);
        $res      = $this->table->get($parseKey);

        if ($this->valid($res)) {
            return json_decode($res['values'], true);
        } else {
            //停止删除键  由gc自动回收
            //$this->del($key);
            return NULL;
        }
    }


    protected function valid($res)
    {
        return $res && ($res['expire_at'] == -1 || $res['expire_at'] > time());
    }

    public function set($key, $value, $ttl = 1)
    {
        $expireAt = $ttl > 0 ? time() + $ttl : $ttl;
        $value    = json_encode($value);

        if (strlen($value) > $this->options['column_size']) {
            return false;
        }
        return $this->table->set($this->parseKey($key), ['values' => $value, 'expire_at' => $expireAt]);
    }


    public function del($key)
    {
        $parseKey = $this->parseKey($key);
        $res      = $this->table->get($parseKey);
        if ($this->valid($res)) {
            //将过期时间设置为0    gc机制回收
            $res['expire_at'] = 0;
            $this->table->set($parseKey, $res);
        }
    }


    public function gc($num = 10000)
    {
        $gc = [];

        foreach ($this->table as $key => $column) {
            if (!$this->valid($column)) {
                $gc[] = $key;
                $num--;
                if (!$num) {
                    break;
                }
            }
        }

        foreach ($gc as $value) {
            $this->table->del($value);
        }

        return $num;
    }

    protected function parseKey($key)
    {
        return md5($key);
    }

    public function cacheEmpty()
    {
        return $this->options['cacheEmpty'] ?? false;
    }
}
