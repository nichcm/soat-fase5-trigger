<?php

namespace App\Infrastructure\Console\Commands;

use App\Application\UseCase\ProcessAnalysisResponse\ProcessAnalysisResponseInput;
use App\Application\UseCase\ProcessAnalysisResponse\ProcessAnalysisResponseUseCase;
use App\Infrastructure\Queue\RabbitMQ;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeAnalysisResponseCommand extends Command
{
    protected $signature   = 'consume:analysis-response';
    protected $description = 'Consome continuamente a fila analysis_response e atualiza o banco com o resultado da IA.';

    public function handle(RabbitMQ $rabbitMQ, ProcessAnalysisResponseUseCase $useCase): int
    {
        $this->info('Aguardando mensagens na fila analysis_response...');

        $rabbitMQ->consumeAnalysisResponse(function (AMQPMessage $message) use ($useCase) {
            $raw = $message->getBody();

            // Converte repr Python → JSON válido
            $json = preg_replace("/'([^']*)'/", '"$1"', $raw);
            $json = str_replace(['True', 'False', 'None'], ['true', 'false', 'null'], $json);

            $payload = json_decode($json, true);

            $this->info("Processando resposta de análise para o protocolo: {$payload['protocol']}");

            try {
               
                $useCase->execute(ProcessAnalysisResponseInput::fromArray( $payload));
                $message->ack();
                $this->info("Análise do protocolo salva com sucesso.");
            } catch (Throwable $e) {
                $this->error("Erro ao processar análise do protocolo: {$e->getMessage()}");
                $message->nack(requeue: false);
            }
        });

        return Command::SUCCESS;
    }
}
