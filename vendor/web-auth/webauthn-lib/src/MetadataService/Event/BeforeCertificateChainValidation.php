<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Event;

use Webauthn\Event\BeforeCertificateChainValidation as BaseBeforeCertificateChainValidation;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Event\BeforeCertificateChainValidation instead
 */
final class BeforeCertificateChainValidation extends BaseBeforeCertificateChainValidation
{
}
