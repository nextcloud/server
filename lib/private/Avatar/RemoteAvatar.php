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
	private const IMAGE_CACHE_AGE = 60 * 60 * 24; // One day

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
			'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false)
		]);

		$contentType = $response->getHeader('Content-Type');
		if (str_starts_with($contentType, 'image/') === false) {
			throw new \Exception('Unknown filetype');
		}

		$avatar = $response->getBody();
		if ($avatar === null) {
			throw new \Exception('Failed to fetch remote avatar');
		}

		if (is_resource($avatar)) {
			$avatar = stream_get_contents($avatar);
			if ($avatar === false) {
				throw new \Exception('Failed to fetch remote avatar');
			}
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
