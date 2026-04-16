<?php

namespace App\Providers;

use App\Domain\Interface\AnalysisGatewayInterface;
use App\Domain\Interface\TriggerRepositoryInterface;
use App\Infrastructure\Gateway\AnalysisGateway;
use App\Infrastructure\Queue\RabbitMQ;
use App\Infrastructure\Repository\TriggerRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RabbitMQ::class, fn() => new RabbitMQ());
        $this->app->bind(TriggerRepositoryInterface::class, TriggerRepository::class);
        $this->app->bind(AnalysisGatewayInterface::class, AnalysisGateway::class);
    }

    public function boot(): void {}
}
