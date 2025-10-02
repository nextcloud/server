<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Exception;

use Webauthn\Exception\CertificateRevocationListException as BaseCertificateRevocationListException;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Exception\CertificateRevocationListException instead
 */
final class CertificateRevocationListException extends BaseCertificateRevocationListException
{
}
