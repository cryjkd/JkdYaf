<?php

namespace Task;

use Swoole\Timer;

class JkdTask
{

    /**
     * 分发一个任务
     *
     * @param $taskClass
     * @param array $data
     * @return int
     */
    public static function dispatch($taskClass, array $data = []): int
    {
        $clas = new $taskClass();
        return Timer::after(1, function () use ($clas, $data) {
            $clas->handle($data);
        });
    }


    /**
     * 延迟分发任务
     *
     * @param $taskClass
     * @param int $ms
     * @param array $data
     */
    public static function delay($taskClass, int $ms, array $data = []): int
    {
        $clas = new $taskClass();
        return Timer::after($ms, function () use ($clas, $data) {
            $clas->handle($data);
        });
    }


    /**
     * 定时任务
     *
     * @param $taskClass
     * @param int $ms
     * @param array $data
     * @return int
     */
    public static function tick($taskClass, int $ms, array $data = []): int
    {
        $clas = new $taskClass();
        return Timer::tick($ms, function () use ($clas, $data) {
            $clas->handle($data);
        });
    }


    /**
     * 清除任务
     *
     * @param int $timerId
     * @return bool
     */
    public static function clear(int $timerId): bool
    {
        return Timer::clear($timerId);
    }

}