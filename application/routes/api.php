<?php

use App\Infrastructure\Http\UploadHttpController;
use Illuminate\Support\Facades\Route;

Route::get("ping", function () {
    return response()->json([
        "err" => false,
        "msg" => "pong",
        "service" => env("APP_NAME", "APP_ENV não definida"),
    ]);
});

Route::post("upload", [UploadHttpController::class, 'upload']);

Route::fallback(
    fn() => response()->json([
        "err" => true,
        "msg" => "Recurso não encontrado",
    ]),
);
