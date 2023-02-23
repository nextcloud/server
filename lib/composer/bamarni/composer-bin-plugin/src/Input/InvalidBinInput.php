<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Input;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use function sprintf;

final class InvalidBinInput extends RuntimeException
{
    public static function forBinInput(InputInterface $input): self
    {
        return new self(
            sprintf(
                'Could not parse the input "%s". Expected the input to be in the format "bin <namespaceName> <commandToExecuteInBinNamespace>", for example "bin all update --prefer-lowest".',
                $input->__toString()
            )
        );
    }

    public static function forNamespaceInput(InputInterface $input): self
    {
        return new self(
            sprintf(
                'Could not parse the input (executed within the namespace) "%s".',
                $input->__toString()
            )
        );
    }
}
