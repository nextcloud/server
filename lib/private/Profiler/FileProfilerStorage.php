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

			if ($url && !str_contains($csvUrl, $url) || $method && !str_contains($csvMethod, $method) || $statusCode && !str_contains($csvStatusCode, $statusCode)) {
				continue;
			}

			if (!empty($start) && $csvTime < $start) {
				continue;
			}

			if (!empty($end) && $csvTime > $end) {
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

		if (\function_exists('gzcompress')) {
			$file = 'compress.zlib://' . $file;
		}

		return $this->createProfileFromData($token, unserialize(file_get_contents($file)));
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

		$context = stream_context_create();

		if (\function_exists('gzcompress')) {
			$file = 'compress.zlib://' . $file;
			stream_context_set_option($context, 'zlib', 'level', 3);
		}

		if (file_put_contents($file, serialize($data), 0, $context) === false) {
			return false;
		}

		if (!$profileIndexed) {
			// Add to index
			if (false === $file = fopen($this->getIndexFilename(), 'a')) {
				return false;
			}

			fputcsv($file, [
				$profile->getToken(),
				$profile->getMethod(),
				$profile->getUrl(),
				$profile->getTime(),
				$profile->getParentToken(),
				$profile->getStatusCode(),
			]);
			fclose($file);
		}

		return true;
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

			if (\function_exists('gzcompress')) {
				$file = 'compress.zlib://' . $file;
			}

			$profile->addChild($this->createProfileFromData($token, unserialize(file_get_contents($file)), $profile));
		}

		return $profile;
	}
}
