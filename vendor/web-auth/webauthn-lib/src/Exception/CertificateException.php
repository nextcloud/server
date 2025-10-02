<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

class CertificateException extends MetadataServiceException
{
    public function __construct(
        public readonly string $certificate,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }
}
