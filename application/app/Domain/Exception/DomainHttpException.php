<?php

namespace App\Domain\Exception;

use DomainException;

class DomainHttpException extends DomainException
{
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
