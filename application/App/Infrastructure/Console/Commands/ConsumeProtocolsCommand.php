<?php

namespace App\Infrastructure\Console\Commands;

use App\Application\UseCase\ProcessProtocol\ProcessProtocolInput;
use App\Application\UseCase\ProcessProtocol\ProcessProtocolUseCase;
use App\Infrastructure\Queue\RabbitMQ;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeProtocolsCommand extends Command
{
    protected $signature   = 'consume:protocols';
    protected $description = 'Consome continuamente a fila protocols e dispara análise na IA.';

    public function handle(RabbitMQ $rabbitMQ, ProcessProtocolUseCase $useCase): int
    {
        $this->info('Aguardando mensagens na fila protocols...');

        $rabbitMQ->consumeProtocols(function (AMQPMessage $message) use ($useCase) {
            $payload = json_decode($message->getBody(), true);

            $this->info("Processando protocolo: {$payload['protocol']}");

            try {
                $useCase->execute(ProcessProtocolInput::fromArray($payload));
                $message->ack();
                $this->info("Protocolo {$payload['protocol']} processado com sucesso.");
            } catch (Throwable $e) {
                $this->error("Erro ao processar protocolo {$payload['protocol']}: {$e->getMessage()}");
                $message->nack(requeue: false);
            }
        });

        return Command::SUCCESS;
    }
}
