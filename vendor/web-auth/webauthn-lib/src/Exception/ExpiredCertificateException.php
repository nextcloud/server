<?php

declare(strict_types=1);

namespace Webauthn\Exception;

use Throwable;

/**
 * @final
 */
class ExpiredCertificateException extends CertificateException
{
    public static function create(
        string $certificate,
        string $message = 'Expired certificate',
        ?Throwable $previous = null
    ): self {
        return new self($certificate, $message, $previous);
    }
}
