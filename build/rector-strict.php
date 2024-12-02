<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$nextcloudDir = dirname(__DIR__);

return (require __DIR__ . '/rector-shared.php')
	->withPaths([
		$nextcloudDir . '/build/rector-strict.php',
		$nextcloudDir . '/apps/files/lib/Controller/ResumableUploadController.php',
		$nextcloudDir . '/apps/files/lib/Db/ResumableUpload.php',
		$nextcloudDir . '/apps/files/lib/Db/ResumableUploadMapper.php',
		$nextcloudDir . '/apps/files/lib/Migration/Version2003Date20241126094807.php',
		$nextcloudDir . '/apps/files/lib/Response/AProblemResponse.php',
		$nextcloudDir . '/apps/files/lib/Response/CompleteUploadResponse.php',
		$nextcloudDir . '/apps/files/lib/Response/MismatchingOffsetResponse.php',
		$nextcloudDir . '/apps/files/tests/Controller/ResumableUploadControllerTest.php',
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
