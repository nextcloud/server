<?php

declare(strict_types=1);

namespace Webauthn\CertificateChainChecker;

use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;

/**
 * @deprecated since v4.1. Please use Webauthn\MetadataService\CertificateChainChecker\CertificateChainValidator instead
 * @infection-ignore-all
 */
interface CertificateChainChecker extends CertificateChainValidator
{
}
