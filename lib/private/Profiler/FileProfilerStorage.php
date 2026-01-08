<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profiler;

use OCP\Profiler\IProfile;

/**
 * Storage for profiler using files.
 */
class FileProfilerStorage {
	// Folder where profiler data are stored.
	private string $folder;

	/** @psalm-suppress UndefinedClass */
	public const allowedClasses = [
		\OCA\Profiler\DataCollector\EventLoggerDataProvider::class,
		\OCA\Profiler\DataCollector\HttpDataCollector::class,
		\OCA\Profiler\DataCollector\MemoryDataCollector::class,
		\OCA\User_LDAP\DataCollector\LdapDataCollector::class,
		\OC\Memcache\ProfilerWrapperCache::class,
		\OC\Profiler\RoutingDataCollector::class,
		\OC\DB\DbDataCollector::class,
	];

	/**
	 * Constructs the file storage using a "dsn-like" path.
	 *
	 * Example : "file:/path/to/the/storage/folder"
	 *
	 * @throws \RuntimeException
	 */
	public function __construct(string $folder) {
		$this->folder = $folder;

		if (!is_dir($this->folder) && @mkdir($this->folder, 0777, true) === false && !is_dir($this->folder)) {
			throw new \RuntimeException(sprintf('Unable to create the storage directory (%s).', $this->folder));
		}
	}

	public function find(?string $url, ?int $limit, ?string $method, ?int $start = null, ?int $end = null, ?string $statusCode = null): array {
		$file = $this->getIndexFilename();

		if (!file_exists($file)) {
			return [];
		}

		$file = fopen($file, 'r');
		fseek($file, 0, \SEEK_END);

		$result = [];
		while (\count($result) < $limit && $line = $this->readLineFromFile($file)) {
			$values = str_getcsv($line);
			[$csvToken, $csvMethod, $csvUrl, $csvTime, $csvParent, $csvStatusCode] = $values;
			$csvTime = (int)$csvTime;

			if (($url && !str_contains($csvUrl, $url))
				|| ($method && !str_contains($csvMethod, $method))
				|| ($statusCode && !str_contains($csvStatusCode, $statusCode))) {
				continue;
			}

			if ($start !== null && $csvTime < $start) {
				continue;
			}

			if ($end !== null && $csvTime > $end) {
				continue;
			}

			$result[$csvToken] = [
				'token' => $csvToken,
				'method' => $csvMethod,
				'url' => $csvUrl,
				'time' => $csvTime,
				'parent' => $csvParent,
				'status_code' => $csvStatusCode,
			];
		}

		fclose($file);

		return array_values($result);
	}

	public function purge(): void {
		$flags = \FilesystemIterator::SKIP_DOTS;
		$iterator = new \RecursiveDirectoryIterator($this->folder, $flags);
		$iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $file) {
			$path = $file->getPathname();
			if (is_file($path)) {
				unlink($path);
			} else {
				rmdir($path);
			}
		}
	}

	public function read(string $token): ?IProfile {
		if (!$token || !file_exists($file = $this->getFilename($token))) {
			return null;
		}

		$h = fopen($file, 'r');
		flock($h, \LOCK_SH);
		$data = stream_get_contents($h);
		flock($h, \LOCK_UN);
		fclose($h);

		if (\function_exists('gzdecode')) {
			$data = @gzdecode($data) ?: $data;
		}

		if (!$data = unserialize($data, ['allowed_classes' => self::allowedClasses])) {
			return null;
		}

		return $this->createProfileFromData($token, $data);
	}

	/**
	 * @throws \RuntimeException
	 */
	public function write(IProfile $profile): bool {
		$file = $this->getFilename($profile->getToken());

		$profileIndexed = is_file($file);
		if (!$profileIndexed) {
			// Create directory
			$dir = \dirname($file);
			if (!is_dir($dir) && @mkdir($dir, 0777, true) === false && !is_dir($dir)) {
				throw new \RuntimeException(sprintf('Unable to create the storage directory (%s).', $dir));
			}
		}

		$profileToken = $profile->getToken();
		// when there are errors in sub-requests, the parent and/or children tokens
		// may equal the profile token, resulting in infinite loops
		$parentToken = $profile->getParentToken() !== $profileToken ? $profile->getParentToken() : null;
		$childrenToken = array_filter(array_map(function (IProfile $p) use ($profileToken) {
			return $profileToken !== $p->getToken() ? $p->getToken() : null;
		}, $profile->getChildren()));

		// Store profile
		$data = [
			'token' => $profileToken,
			'parent' => $parentToken,
			'children' => $childrenToken,
			'data' => $profile->getCollectors(),
			'method' => $profile->getMethod(),
			'url' => $profile->getUrl(),
			'time' => $profile->getTime(),
			'status_code' => $profile->getStatusCode(),
		];

		$data = serialize($data);

		if (\function_exists('gzencode')) {
			$data = gzencode($data, 3);
		}

		if (file_put_contents($file, $data, \LOCK_EX) === false) {
			return false;
		}

		if (!$profileIndexed) {
			// Add to index
			if (false === $file = fopen($this->getIndexFilename(), 'a')) {
				return false;
			}

			fputcsv($file, array_map([$this, 'escapeFormulae'], [
				$profile->getToken(),
				$profile->getMethod(),
				$profile->getUrl(),
				$profile->getTime(),
				$profile->getParentToken(),
				$profile->getStatusCode(),
			]), escape: '');
			fclose($file);
		}

		return true;
	}

	protected function escapeFormulae(?string $value): ?string {
		if ($value !== null && preg_match('/^[=+\-@\t\r]/', $value)) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Gets filename to store data, associated to the token.
	 *
	 * @return string The profile filename
	 */
	protected function getFilename(string $token): string {
		// Uses 4 last characters, because first are mostly the same.
		$folderA = substr($token, -2, 2);
		$folderB = substr($token, -4, 2);

		return $this->folder . '/' . $folderA . '/' . $folderB . '/' . $token;
	}

	/**
	 * Gets the index filename.
	 *
	 * @return string The index filename
	 */
	protected function getIndexFilename(): string {
		return $this->folder . '/index.csv';
	}

	/**
	 * Reads a line in the file, backward.
	 *
	 * This function automatically skips the empty lines and do not include the line return in result value.
	 *
	 * @param resource $file The file resource, with the pointer placed at the end of the line to read
	 *
	 * @return ?string A string representing the line or null if beginning of file is reached
	 */
	protected function readLineFromFile($file): ?string {
		$line = '';
		$position = ftell($file);

		if ($position === 0) {
			return null;
		}

		while (true) {
			$chunkSize = min($position, 1024);
			$position -= $chunkSize;
			fseek($file, $position);

			if ($chunkSize === 0) {
				// bof reached
				break;
			}

			$buffer = fread($file, $chunkSize);

			if (false === ($upTo = strrpos($buffer, "\n"))) {
				$line = $buffer . $line;
				continue;
			}

			$position += $upTo;
			$line = substr($buffer, $upTo + 1) . $line;
			fseek($file, max(0, $position), \SEEK_SET);

			if ($line !== '') {
				break;
			}
		}

		return $line === '' ? null : $line;
	}

	protected function createProfileFromData(string $token, array $data, ?IProfile $parent = null): IProfile {
		$profile = new Profile($token);
		$profile->setMethod($data['method']);
		$profile->setUrl($data['url']);
		$profile->setTime($data['time']);
		$profile->setStatusCode($data['status_code']);
		$profile->setCollectors($data['data']);

		if (!$parent && $data['parent']) {
			$parent = $this->read($data['parent']);
		}

		if ($parent) {
			$profile->setParent($parent);
		}

		foreach ($data['children'] as $token) {
			if (!$token || !file_exists($file = $this->getFilename($token))) {
				continue;
			}

			$h = fopen($file, 'r');
			flock($h, \LOCK_SH);
			$data = stream_get_contents($h);
			flock($h, \LOCK_UN);
			fclose($h);

			if (\function_exists('gzdecode')) {
				$data = @gzdecode($data) ?: $data;
			}

			if (!$data = unserialize($data, ['allowed_classes' => self::allowedClasses])) {
				continue;
			}

			$profile->addChild($this->createProfileFromData($token, $data, $profile));
		}

		return $profile;
	}
}
