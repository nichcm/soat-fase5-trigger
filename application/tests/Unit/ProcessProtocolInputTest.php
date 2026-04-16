<?php

namespace Tests\Unit;

use App\Application\UseCase\ProcessProtocol\ProcessProtocolInput;
use PHPUnit\Framework\TestCase;

class ProcessProtocolInputTest extends TestCase
{
    private array $payload = [
        'protocol'      => '550e8400-e29b-41d4-a716-446655440000',
        'file_url'      => 'http://minio:9000/diagrams/abc123.pdf',
        'file_name'     => 'abc123.pdf',
        'file_mimetype' => 'application/pdf',
        'file_size'     => '204800',
        'original_name' => 'meu-diagrama.pdf',
        'hashed_name'   => 'abc123.pdf',
    ];

    public function test_pode_ser_instanciado_diretamente(): void
    {
        $input = new ProcessProtocolInput(
            protocol:     '550e8400-e29b-41d4-a716-446655440000',
            fileUrl:      'http://minio:9000/diagrams/abc123.pdf',
            fileName:     'abc123.pdf',
            fileMimetype: 'application/pdf',
            fileSize:     '204800',
            originalName: 'meu-diagrama.pdf',
            hashedName:   'abc123.pdf',
        );

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $input->protocol);
        $this->assertSame('http://minio:9000/diagrams/abc123.pdf', $input->fileUrl);
        $this->assertSame('abc123.pdf', $input->fileName);
        $this->assertSame('application/pdf', $input->fileMimetype);
        $this->assertSame('204800', $input->fileSize);
        $this->assertSame('meu-diagrama.pdf', $input->originalName);
        $this->assertSame('abc123.pdf', $input->hashedName);
    }

    public function test_from_array_mapeia_campos_corretamente(): void
    {
        $input = ProcessProtocolInput::fromArray($this->payload);

        $this->assertSame($this->payload['protocol'],      $input->protocol);
        $this->assertSame($this->payload['file_url'],      $input->fileUrl);
        $this->assertSame($this->payload['file_name'],     $input->fileName);
        $this->assertSame($this->payload['file_mimetype'], $input->fileMimetype);
        $this->assertSame($this->payload['file_size'],     $input->fileSize);
        $this->assertSame($this->payload['original_name'], $input->originalName);
        $this->assertSame($this->payload['hashed_name'],   $input->hashedName);
    }

    public function test_from_array_retorna_instancia_correta(): void
    {
        $input = ProcessProtocolInput::fromArray($this->payload);

        $this->assertInstanceOf(ProcessProtocolInput::class, $input);
    }
}
