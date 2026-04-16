<?php

namespace App\Infrastructure\Http;

class BaseHttpController
{
    public function success(array $data = [], string $message = 'Success')
    {
        return [
            "err" => false,
            "message" => $message,
            "data" => $data
        ];
    }

    public function error(array $data = [], string $message = 'Error')
    {
        return [
            "err" => true,
            "message" => $message,
            "data" => $data
        ];
    }
}
