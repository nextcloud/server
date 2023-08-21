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

// Ignore additional app directories
$rootDir = new \DirectoryIterator(__DIR__);
foreach ($rootDir as $node) {
	if (str_starts_with($node->getFilename(), 'apps')) {
		$return = shell_exec('git check-ignore ' . escapeshellarg($node->getFilename() . '/'));

		if ($return !== null) {
			$config->getFinder()->exclude($node->getFilename());
		}
	}
}

return $config;
