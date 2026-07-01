<?php

class Session
{
    public static function start(): void
    {
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://' . getenv('REDIS_HOST') . ':' . getenv('REDIS_PORT'));
        session_start();
    }
}
