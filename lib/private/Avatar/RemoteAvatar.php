<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Avatar;

use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

class RemoteAvatar extends Avatar {
	private const int IMAGE_CACHE_AGE = 60 * 60 * 24; // One day
	private const int MAX_AVATAR_SIZE = 2 * 1024 * 1024;

	private ICloudId $cloudId;

	public function __construct(
		protected readonly ISimpleFolder $folder,
		protected readonly string $userId,
		protected IConfig $config,
		protected LoggerInterface $logger,
	) {
		parent::__construct($config, $logger);

		$cloudIdManager = Server::get(ICloudIdManager::class);
		$this->cloudId = $cloudIdManager->resolveCloudId($userId);
	}

	#[\Override]
	public function exists(): bool {
		return true;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->cloudId->getDisplayId();
	}

	/**
	 * Setting avatars isn't implemented for remote accounts
	 */
	#[\Override]
	public function set($data): void {
	}

	/**
	 * Removing avatars isn't implemented for remote accounts
	 */
	#[\Override]
	public function remove(bool $silent = false): void {
	}

	#[\Override]
	public function getFile(int $size, bool $darkTheme = false): ISimpleFile {
		if ($size === -1) {
			$filename = 'avatar' . ($darkTheme ? '-dark' : '');
		} else {
			$filename = 'avatar' . ($darkTheme ? '-dark' : '') . '.' . $size;
		}

		$avatar = null;
		$files = $this->folder->getDirectoryListing();
		foreach ($files as $file) {
			if (pathinfo($file->getName(), PATHINFO_FILENAME) === $filename) {
				$avatar = $file;
				break;
			}
		}

		if ($avatar !== null) {
			// check if a remote avatar is at least a day old, in case a new avatar was uploaded
			$isAvatarOld = (time() - $avatar->getMTime()) >= self::IMAGE_CACHE_AGE;
			if (!$isAvatarOld) {
				return $avatar;
			}
		}

		$url = rtrim($this->cloudId->getRemote(), '/') . '/index.php/avatar/' . rawurlencode($this->cloudId->getUser()) . '/' . $size;
		if ($darkTheme) {
			$url .= '/dark';
		}

		$clientService = Server::get(IClientService::class);
		$client = $clientService->newClient();
		$response = $client->get($url, [
			'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false),
			'timeout' => 5,
			'stream' => true,
		]);

		$contentType = $response->getHeader('Content-Type');
		if (str_starts_with($contentType, 'image/') === false) {
			throw new \Exception('Unknown filetype');
		}

		// The header only has the encoded size of compressed responses, so the body is limited while reading it too
		$contentLength = $response->getHeader('Content-Length');
		if (!ctype_digit($contentLength)) {
			throw new \Exception('Invalid remote avatar size');
		}
		if ((int)$contentLength > self::MAX_AVATAR_SIZE) {
			throw new \Exception('Remote avatar too large');
		}

		$avatar = $response->getBody();
		if ($avatar === null) {
			throw new \Exception('Failed to fetch remote avatar');
		}

		if (is_resource($avatar)) {
			$avatar = stream_get_contents($avatar, self::MAX_AVATAR_SIZE + 1);
			if ($avatar === false) {
				throw new \Exception('Failed to fetch remote avatar');
			}
		}

		if (strlen($avatar) > self::MAX_AVATAR_SIZE) {
			throw new \Exception('Remote avatar too large');
		}

		$ext = match ($contentType) {
			'image/png' => 'png',
			'image/jpg', 'image/jpeg' => 'jpg',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			default => 'png',
		};
		return $this->folder->newFile($filename . '.' . $ext, $avatar);
	}

	/**
	 * Handling user changes isn't implemented for remote accounts
	 */
	#[\Override]
	public function userChanged(string $feature, $oldValue, $newValue): void {
	}

	#[\Override]
	public function isCustomAvatar(): bool {
		return true;
	}
}
