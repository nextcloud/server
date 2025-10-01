<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Exception;
use Throwable;

class MetadataServiceException extends Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
