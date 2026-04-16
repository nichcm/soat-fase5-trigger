<?php

use App\Infrastructure\Http\TriggerHttpController;
use Illuminate\Support\Facades\Route;

Route::get("ping", function () {
    return response()->json([
        "err" => false,
        "msg" => "pong",
        "service" => env("APP_NAME", "APP_ENV não definida"),
    ]);
});

// Route::get("data", [TriggerHttpController::class, 'getData']);
// Route::get("status", [TriggerHttpController::class, 'getStatus']);

Route::fallback(
    fn() => response()->json([
        "err" => true,
        "msg" => "Recurso não encontrado",
    ]),
);
