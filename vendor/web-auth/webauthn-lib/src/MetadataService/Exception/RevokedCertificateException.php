<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Exception;

use Webauthn\Exception\RevokedCertificateException as BaseRevokedCertificateException;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Exception\RevokedCertificateException instead
 */
final class RevokedCertificateException extends BaseRevokedCertificateException
{
}
