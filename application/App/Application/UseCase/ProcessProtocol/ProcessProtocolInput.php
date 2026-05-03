<?php

namespace App\Application\UseCase\ProcessProtocol;

class ProcessProtocolInput
{
    public function __construct(
        public readonly string $protocol,
        public readonly string $fileUrl,
        public readonly string $fileName,
        public readonly string $fileMimetype,
        public readonly string $fileSize,
        public readonly string $originalName,
        public readonly string $hashedName,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            protocol: $data['protocol'],
            fileUrl: $data['storage_endpoint'],
            fileName: $data['file_unique_name'],
            fileMimetype: $data['file_mime_type'],
            fileSize: $data['file_size'],
            originalName: $data['file_original_name'],
            hashedName: $data['file_unique_name'],
        );
    }
}
