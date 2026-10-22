<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Server;

require_once __DIR__ . '/../lib/base.php';

$options = getopt('', ['count:', 'variants::', 'prefix::']);
$count = isset($options['count']) ? filter_var($options['count'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$variants = isset($options['variants']) ? filter_var($options['variants'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 3;
$prefix = $options['prefix'] ?? 'preview-migration-fixture-' . date('YmdHis');

if ($count === false || $variants === false || !is_string($prefix) || $prefix === '') {
	fwrite(STDERR, "Usage: php build/generate-preview-migration-fixture.php --count=100000 [--variants=3] [--prefix=name]\n");
	exit(1);
}

$rootFolder = Server::get(\OCP\Files\IRootFolder::class);
$storage = $rootFolder->getMountPoint()->getStorage();
if (!$storage instanceof IStorage) {
	fwrite(STDERR, "The root mount point has no storage.\n");
	exit(1);
}

$cache = $storage->getCache();
$appDataDirectory = $rootFolder->getAppDataDirectoryName();
$appDataPath = $appDataDirectory . '/preview';
if ($cache->getId($appDataDirectory) === -1) {
	$cache->insert($appDataDirectory, [
		'size' => 0,
		'mtime' => time(),
		'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
		'permissions' => 31,
	]);
}

$previewRootId = $cache->getId($appDataPath);
if ($previewRootId === -1) {
	$previewRootId = $cache->insert($appDataPath, [
		'size' => 0,
		'mtime' => time(),
		'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
		'permissions' => 31,
	]);
}

$sourceRootPath = $prefix . '-sources';
if ($cache->getId($sourceRootPath) === -1) {
	$cache->insert($sourceRootPath, [
		'size' => 0,
		'mtime' => time(),
		'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
		'permissions' => 31,
	]);
}

$localStorageRoot = $storage->getLocalFile('');
if ($localStorageRoot === false) {
	fwrite(STDERR, "The root storage does not expose a local filesystem path.\n");
	exit(1);
}
$localPreviewRoot = $localStorageRoot . '/' . $appDataPath;
if (!is_dir($localPreviewRoot) && !mkdir($localPreviewRoot, 0770, true) && !is_dir($localPreviewRoot)) {
	throw new RuntimeException('Unable to create ' . $localPreviewRoot);
}

$content = str_repeat('0', 1024);
$created = 0;
$started = microtime(true);
$connection = Server::get(IDBConnection::class);
$connection->beginTransaction();

try {
	for ($index = 0; $index < $count; $index++) {
		$sourcePath = $sourceRootPath . '/' . $index;
		$sourceId = $cache->insert($sourcePath, [
			'size' => 1024,
			'mtime' => time(),
			'mimetype' => 'image/jpeg',
			'etag' => uniqid(),
			'permissions' => 27,
		]);

		$hashPath = implode('/', str_split(substr(md5((string)$sourceId), 0, 7)));
		$hashParts = explode('/', $hashPath);
		$cachePath = $appDataPath;
		$localFolder = $localPreviewRoot;
		foreach ($hashParts as $hashPart) {
			$cachePath .= '/' . $hashPart;
			$localFolder .= '/' . $hashPart;
			if ($cache->getId($cachePath) === -1) {
				$cache->insert($cachePath, [
					'size' => 0,
					'mtime' => time(),
					'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
					'permissions' => 31,
				]);
			}
		}

		$folderPath = $cachePath . '/' . $sourceId;
		$cache->insert($folderPath, [
			'size' => 0,
			'mtime' => time(),
			'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE,
			'permissions' => 31,
		]);
		$localFolder .= '/' . $sourceId;
		if (!is_dir($localFolder) && !mkdir($localFolder, 0770, true) && !is_dir($localFolder)) {
			throw new RuntimeException('Unable to create ' . $localFolder);
		}

		for ($variant = 0; $variant < $variants; $variant++) {
			$name = (string)(64 + ($variant * 64)) . '-' . (string)(64 + ($variant * 64)) . '-crop.jpg';
			$filePath = $folderPath . '/' . $name;
			$localFile = $localFolder . '/' . $name;
			file_put_contents($localFile, $content);
			$cache->insert($filePath, [
				'size' => 1024,
				'mtime' => time(),
				'mimetype' => 'image/jpeg',
				'permissions' => 27,
			]);
			$created++;
		}

		if (($index + 1) % 1000 === 0) {
			fwrite(STDOUT, sprintf("Created %d source files and %d previews\n", $index + 1, $created));
		}
	}
	$connection->commit();
} catch (\Throwable $e) {
	$connection->rollBack();
	throw $e;
}

Server::get(IAppConfig::class)->setValueBool('core', 'previewMovedDone', false);

fwrite(STDOUT, sprintf(
	"Created %d previews in %.1f seconds under %s\n",
	$created,
	microtime(true) - $started,
	$localPreviewRoot,
));
