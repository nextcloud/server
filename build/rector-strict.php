<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$nextcloudDir = dirname(__DIR__);

return (require __DIR__ . '/rector-shared.php')
	->withPaths([
		$nextcloudDir . '/build/rector-strict.php',
		$nextcloudDir . '/core/BackgroundJobs/ExpirePreviewsJob.php',
		$nextcloudDir . '/lib/public/IContainer.php',
	])
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		typeDeclarationDocblocks: true,
		privatization: true,
		instanceOf: true,
		earlyReturn: true,
		rectorPreset: true,
		phpunitCodeQuality: true,
		doctrineCodeQuality: true,
		symfonyCodeQuality: true,
		symfonyConfigs: true,
	)->withPhpSets(
		php82: true,
	);
