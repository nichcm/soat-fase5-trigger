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

    // public function __destruct()
    // {
    //     logger()->info("RabbitMQ Message Broker stopped.", []);

    //     if ($this->connection) {
    //         $this->connection->close();
    //     }
    // }

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

        $channel->exchange_declare("uploads_exchange", "direct", false, true, false);
        $channel->exchange_declare("uploads_dlx_exchange", "direct", false, true, false); // DLX (Dead Letter Exchange) para mensagens que não puderem ser processadas

        // Fila principal com DLQ configurada:
        $channel->queue_declare(
            "uploads_queue",
            false, // passive: se true, verifica se a fila existe sem criá-la. Se a fila não existir, lança uma exceção. Se false, cria a fila se ela não existir.
            true, // durable: se true, a fila sobreviverá a reinicializações do RabbitMQ. As mensagens também devem ser marcadas como persistentes para garantir que não sejam perdidas.
            false, // exclusive: se true, a fila só pode ser usada pela conexão que a declarou e será excluída quando a conexão fechar. Se false, a fila pode ser compartilhada entre conexões.
            false, // auto_delete: se true, a fila será excluída automaticamente quando não houver mais consumidores. Se false, a fila permanecerá mesmo sem consumidores.
            false, // nowait: se true, o servidor não enviará uma resposta ao cliente. O cliente não receberá confirmação de que a fila foi declarada com sucesso. Se false, o cliente aguardará uma resposta do servidor.
            new AMQPTable(
                [
                    "x-dead-letter-exchange"    => "uploads_dlx_exchange", // Especifica a DLX para onde as mensagens serão enviadas se não puderem ser processadas (ex: após várias tentativas de consumo falharem).
                    "x-dead-letter-routing-key" => "uploads_dlq_routing_key", // Especifica a routing key para as mensagens na DLX, permitindo roteá-las para uma fila específica de DLQ.
                    "x-message-ttl"             => 5000, // Tempo de vida das mensagens na fila em milissegundos. Após esse tempo, as mensagens expiram e são movidas para a DLX (se configurada) ou descartadas.
                ]
            ), // arguments: um array associativo de argumentos adicionais para a fila. Por exemplo, para configurar uma DLQ, você pode usar:
        );

        $channel->queue_declare(
            "uploads_dlq_queue",
            false,
            true,
            false,
            false,
        );

        $channel->queue_bind(
            "uploads_queue",
            "uploads_exchange",
            "uploads_routing_key",
        );

        $channel->queue_bind(
            "uploads_dlq_queue",
            "uploads_dlx_exchange",
            "uploads_dlq_routing_key",
        );

        if ($channel) {
            $channel->close();
        }
    }

    public function publishUpload(array $messagePayload): bool
    {
        if (sizeof($messagePayload) === 0) return false;

        $channel = $this->createChannel();

        $channel->basic_publish(
            msg: new AMQPMessage(json_encode($messagePayload), [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // ! IMPORTANTE: persiste no disco
            ]),

            exchange: "uploads_exchange",

            routing_key: "uploads_routing_key", // o routing_key deve ser o nome da fila para onde a mensagem vai diretamente.

            mandatory: false, // Se true, exige que a mensagem seja roteada para pelo menos uma fila. Caso contrário, o RabbitMQ retorna a mensagem ao publicador via basic.return
            // false (padrão): se a mensagem não puder ser roteada, ela é descartada silenciosamente.
            // true: se nenhuma fila receber a mensagem, ela é devolvida ao publicador, que deve tratar o retorno (ex: log, re-publicação).
            // Útil para garantir que a mensagem não seja perdida por erro de roteamento.

            immediate: false, // exige que a mensagem seja entregue imediatamente a um consumidor. Se não houver consumidor pronto, a mensagem é retornada.
            // Este parâmetro é obsoleto no RabbitMQ (removido na versão 3.0+). Definir como true geralmente resulta em erro ou é ignorado.
            // Manter como false.

            ticket: null // Usado em versões antigas do RabbitMQ com ACL (Access Control Lists). Representa um ticket de autenticação.
            // Em versões recentes do RabbitMQ e da biblioteca, esse parâmetro não é mais utilizado. Deixar como null.
        );

        if ($channel) {
            $channel->close();
        }

        return true;
    }

    public function consumeUploads(callable $callback): void
    {
        $channel = $this->createChannel();

        $channel->basic_consume(
            queue: "uploads_queue",
            consumer_tag: "",
            no_local: false, // Se true, o consumidor não receberá mensagens publicadas pela mesma conexão. Se false, o consumidor pode receber mensagens publicadas pela mesma conexão. Útil para evitar que um produtor consuma suas próprias mensagens.
            no_ack: false, // ! IMPORTANTE: desabilita o auto-ack para garantir que as mensagens sejam processadas com segurança. O consumidor deve enviar manualmente um ack após processar a mensagem.
            exclusive: false, // Se true, a fila só pode ser consumida por esta conexão e será fechada quando a conexão for encerrada. Se false, a fila pode ser consumida por várias conexões.
            nowait: false, // Se true, o servidor não enviará uma resposta ao cliente. O cliente não receberá confirmação de que o consumidor foi registrado com sucesso. Se false, o cliente aguardará uma resposta do servidor.
            callback: function (AMQPMessage $message) use ($callback, &$channel) {
                try {
                    // $payload = json_decode($message->getBody(), true);
                    $callback($message, $channel);
                    // $message->ack(); // Envia um ack (acknowledgement, ou seja, confirmação) manual após o processamento bem-sucedido da mensagem.
                } catch (Throwable $e) {
                    logger()->error("Erro ao processar mensagem do RabbitMQ", ['err' => $e->getMessage(), 'message_body' => $message->getBody()]);
                    // Se ocorrer um erro, não enviamos o ack, permitindo que a mensagem seja reentregue ou encaminhada para a DLQ após várias tentativas falharem.
                }
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
