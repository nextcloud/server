<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Exception;

use Webauthn\Exception\MetadataStatementException as BaseMetadataStatementException;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Exception\MetadataStatementException instead
 */
class MetadataStatementException extends BaseMetadataStatementException
{
}
