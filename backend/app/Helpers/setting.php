<?php

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Repositories\StoreSettingRepo::class)->get($key, $default);
    }
}
