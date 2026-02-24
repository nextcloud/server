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
		$nextcloudDir . '/apps/dav/lib/Connector/Sabre/Node.php',
		$nextcloudDir . '/apps/files_versions/lib/Versions/IMetadataVersion.php',
		$nextcloudDir . '/lib/private/Settings/AuthorizedGroup.php',
		$nextcloudDir . '/lib/private/Settings/AuthorizedGroupMapper.php',
		$nextcloudDir . '/apps/settings/lib/Service/AuthorizedGroupService.php',
		$nextcloudDir . '/lib/private/Files/Storage/Storage.php',
		$nextcloudDir . '/lib/private/Files/Storage/Wrapper/Wrapper.php',
		$nextcloudDir . '/lib/private/Files/Storage/StorageFactory.php',
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
