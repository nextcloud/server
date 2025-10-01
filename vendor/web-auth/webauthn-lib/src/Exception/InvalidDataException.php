<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

final class InvalidDataException extends WebauthnException
{
    public function __construct(
        public readonly mixed $data,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public static function create(mixed $data, string $message, ?Throwable $previous = null): self
    {
        return new self($data, $message, $previous);
    }
}
