<?php

declare(strict_types=1);

namespace Webauthn;

use Webauthn\MetadataService\CertificateChain\CertificateToolbox as BaseCertificateToolbox;

/**
 * @deprecated since v4.1. Please use Webauthn\MetadataService\CertificateChainChecker\PhpCertificateChainValidator instead
 * @infection-ignore-all
 */
class CertificateToolbox extends BaseCertificateToolbox
{
}
