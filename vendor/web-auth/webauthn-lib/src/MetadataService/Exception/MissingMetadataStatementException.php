<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Exception;

use Webauthn\Exception\MissingMetadataStatementException as BaseMissingMetadataStatementException;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Exception\MissingMetadataStatementException instead
 */
final class MissingMetadataStatementException extends BaseMissingMetadataStatementException
{
}
