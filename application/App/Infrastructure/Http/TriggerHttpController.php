<?php

namespace App\Infrastructure\Http;

use App\Application\UseCase\GetProtocolData\GetProtocolDataUseCase;
use App\Application\UseCase\GetProtocolStatus\GetProtocolStatusUseCase;
use App\Domain\Exception\DomainHttpException;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Throwable;

final class TriggerHttpController extends BaseHttpController
{
    public function __construct(
        private readonly GetProtocolStatusUseCase $getProtocolStatusUseCase,
        private readonly GetProtocolDataUseCase $getProtocolDataUseCase,
    ) {}

    public function getStatus(string $protocolUuid)
    {
        try {
            $validated = Validator::make(
                ['protocol_uuid' => $protocolUuid],
                ['protocol_uuid' => ['required', 'string', 'uuid']],
                ['protocol_uuid.required' => 'O protocol_uuid é obrigatório.',
                 'protocol_uuid.uuid'     => 'O protocol_uuid deve ser um UUID válido.'],
            );

            if ($validated->fails()) {
                throw new DomainHttpException(
                    $validated->errors()->first(),
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $result = $this->getProtocolStatusUseCase->execute($protocolUuid);

            return FacadesResponse::json(
                $this->success($result, "Status recuperado com sucesso."),
            );
        } catch (DomainHttpException $err) {
            return FacadesResponse::json(
                $this->error([], $err->getMessage()),
                $err->getCode(),
            );
        } catch (Throwable $err) {
            return FacadesResponse::json(
                $this->error([], $err->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function getData(string $protocolUuid)
    {
        try {
            $validated = Validator::make(
                ['protocol_uuid' => $protocolUuid],
                ['protocol_uuid' => ['required', 'string', 'uuid']],
                ['protocol_uuid.required' => 'O protocol_uuid é obrigatório.',
                 'protocol_uuid.uuid'     => 'O protocol_uuid deve ser um UUID válido.'],
            );

            if ($validated->fails()) {
                throw new DomainHttpException(
                    $validated->errors()->first(),
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $result = $this->getProtocolDataUseCase->execute($protocolUuid);

            return FacadesResponse::json(
                $this->success($result, "Dados recuperados com sucesso."),
            );
        } catch (DomainHttpException $err) {
            return FacadesResponse::json(
                $this->error([], $err->getMessage()),
                $err->getCode(),
            );
        } catch (Throwable $err) {
            return FacadesResponse::json(
                $this->error([], $err->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
