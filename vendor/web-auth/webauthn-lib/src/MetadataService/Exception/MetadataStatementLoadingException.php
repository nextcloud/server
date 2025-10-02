<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Exception;

use Webauthn\Exception\MetadataStatementLoadingException as BaseMetadataStatementLoadingException;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Exception\MetadataStatementLoadingException instead
 */
final class MetadataStatementLoadingException extends BaseMetadataStatementLoadingException
{
}
