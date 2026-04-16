# soat-fase5-trigger

Serviço de trigger responsável por reagir a eventos externos e disparar fluxos internos via mensageria (RabbitMQ). Faz parte da arquitetura SOAT Fase 5.

## Requisitos

- Docker
- Docker Compose
- RabbitMQ acessível na rede `soat-net`

## Configuração

Copie o `.env.example` e ajuste as variáveis:

```bash
cp application/.env.example application/.env
```

Variáveis principais:

| Variável | Descrição | Exemplo |
|---|---|---|
| `RABBITMQ_HOST` | Hostname do RabbitMQ na rede Docker | `rabbit_mq_hostname` |
| `RABBITMQ_PORT` | Porta AMQP | `5672` |
| `RABBITMQ_USER` | Usuário | `guest` |
| `RABBITMQ_PASSWORD` | Senha | `guest` |
| `RABBITMQ_VHOST` | Virtual host | `/` |
| `ANALYSIS_SERVICE_URL` | URL base do serviço de IA | `http://analysis-service:8080` |

> O hostname do RabbitMQ deve ser o hostname do container, não `localhost`. Inspecione com `docker inspect <container> --format '{{.Config.Hostname}}'`.

## Rodando

```bash
docker compose up --build
```

A API estará disponível em `http://localhost:8002/api`.

> A rede `soat-net` precisa existir antes de subir os containers:
> ```bash
> docker network create soat-net
> ```

Na inicialização, o container automaticamente:
1. Instala as dependências
2. Roda as migrations
3. Declara as exchanges, filas e bindings no RabbitMQ (`queue:setup`)

## Rotas disponíveis

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/ping` | Health check do serviço |
| GET | `/api/status/{protocol_uuid}` | Retorna o status atual de um protocolo |
| GET | `/api/data/{protocol_uuid}` | Retorna o resultado da análise de um protocolo |

### Exemplos

**Health check**
```bash
curl http://localhost:8002/api/ping
```

**Status de um protocolo**
```bash
curl http://localhost:8002/api/status/550e8400-e29b-41d4-a716-446655440000
```

Resposta:
```json
{
  "err": false,
  "message": "Status recuperado com sucesso.",
  "data": {
    "protocol_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "EM_PROCESSAMENTO",
    "received_at": "2026-04-16 10:00:00"
  }
}
```

Valores possíveis de `status`:

| Status | Descrição |
|---|---|
| `RECEBIDO` | Protocolo recebido da fila, trigger criado |
| `EM_PROCESSAMENTO` | Enviado para a IA com sucesso |
| `ERRO_PROCESSAMENTO` | Falha ao enviar para a IA |
| `SUCESSO` | IA retornou análise com sucesso |
| `ERRO` | IA retornou erro na análise |

**Dados da análise**
```bash
curl http://localhost:8002/api/data/550e8400-e29b-41d4-a716-446655440000
```

Resposta (quando status é `SUCESSO`):
```json
{
  "err": false,
  "message": "Dados recuperados com sucesso.",
  "data": {
    "protocol": "550e8400-e29b-41d4-a716-446655440000",
    "components": [{}],
    "risks": [{}],
    "recommendations": [{}]
  }
}
```

## Filas RabbitMQ

O serviço declara e consome das seguintes filas:

| Fila | Direção | Descrição |
|---|---|---|
| `protocols_queue` | Consumida | Recebe protocolos publicados pelo upload-ms |
| `protocols_dlq_queue` | DLQ | Mensagens de `protocols_queue` que falharam |
| `analysis_response_queue` | Consumida | Recebe respostas da IA |
| `analysis_response_dlq_queue` | DLQ | Mensagens de `analysis_response_queue` que falharam |

### Configurando as filas manualmente

```bash
docker compose exec trigger php artisan queue:setup
```

### Monitorando as filas

Acesse o management UI do RabbitMQ em `http://127.0.0.1:15672` (credenciais padrão: `guest` / `guest`).

Em **Queues**, você pode visualizar mensagens pendentes, inspecionar payloads e monitorar o processamento em tempo real.

## Consumers

Os consumers ficam em loop contínuo aguardando mensagens. Execute cada um em um terminal separado (ou via Supervisor em produção):

**Consumer de protocolos** — recebe payload do upload-ms, cria o Trigger no banco e dispara a análise na IA:
```bash
docker compose exec trigger php artisan consume:protocols
```

Fluxo ao receber uma mensagem:
```
protocols_queue → cria Trigger no banco
               → salva AnalysisResponse{RECEBIDO}
               → POST para ANALYSIS_SERVICE_URL
               → salva AnalysisResponse{EM_PROCESSAMENTO} ou {ERRO_PROCESSAMENTO}
```

**Consumer de respostas da IA** — recebe o resultado da análise e atualiza o banco:
```bash
docker compose exec trigger php artisan consume:analysis-response
```

Fluxo ao receber uma mensagem:
```
analysis_response_queue → localiza o Trigger pelo protocol_uuid
                        → salva AnalysisResponse{SUCESSO} com o content completo
```

O `content` salvo é o payload completo da IA:
```json
{
  "protocol": "uuid-do-protocolo",
  "components": [{}],
  "risks": [{}],
  "recommendations": [{}]
}
```

Após isso, o endpoint `GET /api/data/{protocol_uuid}` passa a retornar os dados da análise.

### Simulando uma mensagem de teste

Para testar o consumer sem o upload-ms rodando, publique uma mensagem manualmente via tinker:

```bash
docker compose exec trigger php artisan tinker
```

```php
$rabbit = app(\App\Infrastructure\Queue\RabbitMQ::class);
$channel = $rabbit->createChannel();

$payload = json_encode([
    'protocol'      => '550e8400-e29b-41d4-a716-446655440000',
    'file_url'      => 'http://minio:9000/diagrams/abc123.pdf',
    'file_name'     => 'abc123.pdf',
    'file_mimetype' => 'application/pdf',
    'file_size'     => '204800',
    'original_name' => 'meu-diagrama.pdf',
    'hashed_name'   => 'abc123.pdf',
]);

$msg = new \PhpAmqpLib\Message\AMQPMessage($payload, [
    'content_type'  => 'application/json',
    'delivery_mode' => \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$channel->basic_publish($msg, 'protocols_exchange', 'protocols_routing_key');
$channel->close();
```

Depois rode o consumer e acompanhe o processamento:

```bash
docker compose exec trigger php artisan consume:protocols
```

## Rodando os testes

Com os containers no ar (`docker compose up -d`):

```bash
# Rodar os testes
docker compose exec trigger vendor/bin/phpunit

# Rodar com relatório de cobertura HTML (saída: var/coverage/)
docker compose exec trigger vendor/bin/phpunit --coverage-html var/coverage/html
```

Sem Docker (requer PHP 8.4 e extensões instaladas localmente):

```bash
cd application
composer install
vendor/bin/phpunit
```

## Postman

A collection está disponível em `soat-trigger-ms.postman_collection.json`.

Importe via **File → Import** e ajuste a variável `protocol_uuid` com um UUID real.

## Parando os containers

```bash
docker compose down
```
