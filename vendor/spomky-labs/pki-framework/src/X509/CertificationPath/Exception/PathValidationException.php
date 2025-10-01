<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath\Exception;

use SpomkyLabs\Pki\X509\Exception\X509ValidationException;

/**
 * Exception thrown on certification path validation errors.
 */
final class PathValidationException extends X509ValidationException
{
}
