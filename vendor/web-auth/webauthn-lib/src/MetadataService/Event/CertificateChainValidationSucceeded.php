<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Event;

use Webauthn\Event\CertificateChainValidationSucceeded as BaseCertificateChainValidationSucceeded;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Event\CertificateChainValidationSucceeded instead
 */
final class CertificateChainValidationSucceeded extends BaseCertificateChainValidationSucceeded
{
}
