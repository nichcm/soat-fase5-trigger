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
            protocol:     $data['protocol'],
            fileUrl:      $data['file_url'],
            fileName:     $data['file_name'],
            fileMimetype: $data['file_mimetype'],
            fileSize:     $data['file_size'],
            originalName: $data['original_name'],
            hashedName:   $data['hashed_name'],
        );
    }
}
