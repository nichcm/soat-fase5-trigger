<?php

namespace App\Infrastructure\Queue;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use RuntimeException;
use Throwable;

class RabbitMQ
{
    public readonly AMQPStreamConnection $connection;

    public function __construct()
    {
        logger()->info("RabbitMQ Message Broker started.", []);

        if (in_array(null, [
            env("RABBITMQ_HOST"),
            env("RABBITMQ_PORT"),
            env("RABBITMQ_USER"),
            env("RABBITMQ_PASSWORD"),
            env("RABBITMQ_VHOST"),
        ])) {
            throw new Exception("Message Broker nao configurado corretamente.", 1);
        }

        $this->connect();
    }

    public function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            env("RABBITMQ_HOST"),
            env("RABBITMQ_PORT"),
            env("RABBITMQ_USER"),
            env("RABBITMQ_PASSWORD"),
            env("RABBITMQ_VHOST"),
            true
        );
    }

    public function createChannel()
    {
        if (! $this->connection || ! $this->connection?->isConnected()) {
            $this->reconnect();
        }

        return $this->connection->channel();
    }

    private function reconnect(): void
    {
        $attempts = 0;

        while ($attempts < 5) {
            try {
                sleep(2 ** $attempts);
                $this->connect();
                return;
            } catch (Throwable $e) {
                $attempts++;
                logger()->warning("RabbitMQ reconnect attempt {$attempts}", ['err' => $e->getMessage()]);
            }
        }

        throw new RuntimeException("RabbitMQ: falhou ao reconectar após 5 tentativas");
    }

    public function setup(): void
    {
        $channel = $this->createChannel();

        // --- Fila de protocolos (publicada pelo upload-ms, consumida aqui) ---
        $channel->exchange_declare("protocols_exchange",     "direct", false, true, false);
        $channel->exchange_declare("protocols_dlx_exchange", "direct", false, true, false);

        $channel->queue_declare(
            "protocols_queue",
            false, true, false, false, false,
            new AMQPTable([
                "x-dead-letter-exchange"    => "protocols_dlx_exchange",
                "x-dead-letter-routing-key" => "protocols_dlq_routing_key",
                "x-message-ttl"             => 5000,
            ]),
        );

        $channel->queue_declare("protocols_dlq_queue", false, true, false, false);

        $channel->queue_bind("protocols_queue",     "protocols_exchange",     "protocols_routing_key");
        $channel->queue_bind("protocols_dlq_queue", "protocols_dlx_exchange", "protocols_dlq_routing_key");

        // --- Fila de respostas de análise (publicada pela IA, consumida aqui) ---
        $channel->exchange_declare("analysis_response_exchange",     "direct", false, true, false);
        $channel->exchange_declare("analysis_response_dlx_exchange", "direct", false, true, false);

        $channel->queue_declare(
            "analysis_response_queue",
            false, true, false, false, false,
            new AMQPTable([
                "x-dead-letter-exchange"    => "analysis_response_dlx_exchange",
                "x-dead-letter-routing-key" => "analysis_response_dlq_routing_key",
                "x-message-ttl"             => 5000,
            ]),
        );

        $channel->queue_declare("analysis_response_dlq_queue", false, true, false, false);

        $channel->queue_bind("analysis_response_queue",     "analysis_response_exchange",     "analysis_response_routing_key");
        $channel->queue_bind("analysis_response_dlq_queue", "analysis_response_dlx_exchange", "analysis_response_dlq_routing_key");

        $channel->close();

        logger()->info("RabbitMQ setup concluído: exchanges, filas e bindings criados.");
    }

    public function consumeProtocols(callable $callback): void
    {
        $channel = $this->createChannel();

        $channel->basic_consume(
            queue:        "protocols_queue",
            consumer_tag: "",
            no_local:     false,
            no_ack:       false,
            exclusive:    false,
            nowait:       false,
            callback:     function (AMQPMessage $message) use ($callback, &$channel) {
                try {
                    $callback($message, $channel);
                } catch (Throwable $e) {
                    logger()->error("Erro ao processar mensagem de protocols.", [
                        'err'  => $e->getMessage(),
                        'body' => $message->getBody(),
                    ]);
                }
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function consumeAnalysisResponse(callable $callback): void
    {
        $channel = $this->createChannel();

        $channel->basic_consume(
            queue:        "analysis_response_queue",
            consumer_tag: "",
            no_local:     false,
            no_ack:       false,
            exclusive:    false,
            nowait:       false,
            callback:     function (AMQPMessage $message) use ($callback, &$channel) {
                try {
                    $callback($message, $channel);
                } catch (Throwable $e) {
                    logger()->error("Erro ao processar mensagem de analysis_response.", [
                        'err'  => $e->getMessage(),
                        'body' => $message->getBody(),
                    ]);
                }
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
