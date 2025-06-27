<?php
namespace app\common\library;

class RedisManager
{
    private static $instance;
    private $redis;

    private function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        // $this->redis->auth('password');
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new RedisManager();
        }
        return self::$instance;
    }

    public function getRedis()
    {
        return $this->redis;
    }
}