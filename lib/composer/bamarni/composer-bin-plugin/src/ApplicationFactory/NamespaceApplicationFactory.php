<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\ApplicationFactory;

use Composer\Console\Application;

interface NamespaceApplicationFactory
{
    public function create(Application $existingApplication): Application;
}
