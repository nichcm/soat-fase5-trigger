<?php

namespace App\Infrastructure\Console\Commands;

use App\Infrastructure\Queue\RabbitMQ;
use Illuminate\Console\Command;
use Throwable;

class QueueSetupCommand extends Command
{
    protected $signature   = 'queue:setup';
    protected $description = 'Declara exchanges, filas e bindings no RabbitMQ.';

    public function handle(RabbitMQ $rabbitMQ): int
    {
        try {
            $this->info('Configurando RabbitMQ...');
            $rabbitMQ->setup();
            $this->info('RabbitMQ configurado com sucesso.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Falha ao configurar RabbitMQ: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
