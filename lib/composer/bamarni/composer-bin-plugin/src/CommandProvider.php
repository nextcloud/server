<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Bamarni\Composer\Bin\Command\BinCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * @final Will be final in 2.x.
 */
class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [new BinCommand()];
    }
}
