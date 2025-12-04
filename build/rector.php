<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use Nextcloud\Rector\Set\NextcloudSets;
use PhpParser\Node;
use Rector\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Rector\ValueObject\Application\File;

$nextcloudDir = dirname(__DIR__);

class NextcloudNamespaceSkipVoter implements ClassNameImportSkipVoterInterface {
	private array $namespacePrefixes = [
		'OC',
		'OCA',
		'OCP',
	];
	private array $skippedClassNames = [
		'Backend',
		'Connection',
		'Exception',
		'IManager',
		'IProvider',
		'Manager',
		'Plugin',
		'Provider',
	];
	public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node) : bool {
		if (in_array($fullyQualifiedObjectType->getShortName(), $this->skippedClassNames)) {
			// Skip common class names to avoid confusion
			return true;
		}
		foreach ($this->namespacePrefixes as $prefix) {
			if (str_starts_with($fullyQualifiedObjectType->getClassName(), $prefix . '\\')) {
				// Import Nextcloud namespaces
				return false;
			}
		}
		// Skip everything else
		return true;
	}
}

$config = RectorConfig::configure()
	->withPaths([
		$nextcloudDir . '/apps',
		$nextcloudDir . '/core',
		$nextcloudDir . '/ocs',
		$nextcloudDir . '/ocs-provider',
		$nextcloudDir . '/console.php',
		$nextcloudDir . '/cron.php',
		$nextcloudDir . '/index.php',
		$nextcloudDir . '/occ',
		$nextcloudDir . '/public.php',
		$nextcloudDir . '/remote.php',
		$nextcloudDir . '/status.php',
		$nextcloudDir . '/version.php',
		$nextcloudDir . '/lib/private',
		$nextcloudDir . '/tests',
		// $nextcloudDir . '/config',
		// $nextcloudDir . '/themes',
	])
	->withSkip([
		$nextcloudDir . '/apps/*/3rdparty/*',
		$nextcloudDir . '/apps/*/build/stubs/*',
		$nextcloudDir . '/apps/*/composer/*',
		$nextcloudDir . '/apps/*/config/*',
		$nextcloudDir . '/lib/private/AppFramework/Middleware/Security/RateLimitingMiddleware.php',
		$nextcloudDir . '/lib/private/Files/Node/*',
		// The mock classes are excluded, as the tests explicitly test the annotations which should not be migrated to attributes
		$nextcloudDir . '/tests/lib/AppFramework/Middleware/Mock/*',
		$nextcloudDir . '/tests/lib/AppFramework/Middleware/Security/Mock/*',
	])
	// uncomment to reach your current PHP version
	// ->withPhpSets()
	->withImportNames(importShortClasses:false)
	->withTypeCoverageLevel(0)
	->withConfiguredRule(ClassPropertyAssignToConstructorPromotionRector::class, [
		'inline_public' => true,
		'rename_property' => true,
	])
	->withSets([
		NextcloudSets::NEXTCLOUD_27,
		PHPUnitSetList::PHPUNIT_100,
	]);

$config->registerService(NextcloudNamespaceSkipVoter::class, tag:ClassNameImportSkipVoterInterface::class);

/* Ignore all files ignored by git */
$ignoredEntries = shell_exec('git status --porcelain --ignored ' . escapeshellarg($nextcloudDir));
$ignoredEntries = explode("\n", $ignoredEntries);
$ignoredEntries = array_filter($ignoredEntries, static fn (string $line) => str_starts_with($line, '!! '));
$ignoredEntries = array_map(static fn (string $line) => substr($line, 3), $ignoredEntries);
$ignoredEntries = array_values($ignoredEntries);

foreach ($ignoredEntries as $ignoredEntry) {
	if (str_ends_with($ignoredEntry, '/')) {
		$config->withSkip([$ignoredEntry . '*']);
	} else {
		$config->withSkip([$ignoredEntry . '/*']);
	}
}

return $config;
