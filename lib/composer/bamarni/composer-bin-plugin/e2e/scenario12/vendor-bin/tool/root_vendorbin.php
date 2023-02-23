<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Composer\InstalledVersions;

echo "Get versions installed in vendor-bin/too; executed from vendor-bin/tool.".PHP_EOL;
echo InstalledVersions::getPrettyVersion('psr/log').PHP_EOL;
