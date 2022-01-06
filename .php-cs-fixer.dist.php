<?php

declare(strict_types=1);

require_once './vendor-bin/cs-fixer/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
	->getFinder()
	->ignoreVCSIgnored(true)
	->exclude('config')
	->exclude('data')
	->notPath('3rdparty')
	->notPath('build/integration/vendor')
	->notPath('build/lib')
	->notPath('build/node_modules')
	->notPath('build/stubs')
	->notPath('composer')
	->notPath('node_modules')
	->notPath('vendor')
	->in(__DIR__);
return $config;
