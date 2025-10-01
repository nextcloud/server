<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Event;

use Webauthn\Event\NullEventDispatcher as BaseNullEventDispatcher;

/**
 * @deprecated since 4.9.0 and will be removed in 5.0.0. Use Webauthn\Event\NullEventDispatcher instead
 */
final class NullEventDispatcher extends BaseNullEventDispatcher
{
}
