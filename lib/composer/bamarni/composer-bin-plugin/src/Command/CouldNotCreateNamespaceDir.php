<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Command;

use RuntimeException;
use function sprintf;

final class CouldNotCreateNamespaceDir extends RuntimeException
{
    public static function forNamespace(string $namespace): self
    {
        return new self(
            sprintf(
                'Could not create the directory "%s".',
                $namespace
            )
        );
    }
}
