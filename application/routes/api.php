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

Route::get("status/{protocol_uuid}", [TriggerHttpController::class, 'getStatus']);
Route::get("data/{protocol_uuid}", [TriggerHttpController::class, 'getData']);

Route::fallback(
    fn() => response()->json([
        "err" => true,
        "msg" => "Recurso não encontrado",
    ]),
);
