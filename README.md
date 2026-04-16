# soat-fase5-trigger

Serviço de trigger responsável por reagir a eventos externos e disparar fluxos internos via mensageria (RabbitMQ). Faz parte da arquitetura SOAT Fase 5.

## Requisitos

- Docker
- Docker Compose

## Rodando

```bash
docker compose up --build
```

A API estará disponível em `http://localhost:8002/api`.

> A rede `soat-net` precisa existir antes de subir os containers:
> ```bash
> docker network create soat-net
> ```

## Rotas disponíveis

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/ping` | Health check do serviço |

### Exemplos

**Health check**
```bash
curl http://localhost:8002/api/ping
```

Resposta:
```json
{
  "err": false,
  "msg": "pong",
  "service": "soat-trigger"
}
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

## Parando os containers

```bash
docker compose down
```
