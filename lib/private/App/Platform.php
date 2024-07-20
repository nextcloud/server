<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\App;

use OCP\IBinaryFinder;
use OCP\IConfig;

/**
 * Class Platform
 *
 * This class basically abstracts any kind of information which can be retrieved from the underlying system.
 *
 * @package OC\App
 */
class Platform {
	public function __construct(
		private IConfig $config,
	) {
	}

	public function getPhpVersion(): string {
		return phpversion();
	}

	public function getIntSize(): int {
		return PHP_INT_SIZE;
	}

	public function getOcVersion(): string {
		$v = \OCP\Util::getVersion();
		return implode('.', $v);
	}

	public function getDatabase(): string {
		$dbType = $this->config->getSystemValueString('dbtype', 'sqlite');
		if ($dbType === 'sqlite3') {
			$dbType = 'sqlite';
		}

		return $dbType;
	}

	public function getOS(): string {
		return php_uname('s');
	}

	/**
	 * @param $command
	 */
	public function isCommandKnown(string $command): bool {
		return \OCP\Server::get(IBinaryFinder::class)->findBinaryPath($command) !== false;
	}

	public function getLibraryVersion(string $name): ?string {
		$repo = new PlatformRepository();
		return $repo->findLibrary($name);
	}

	public function getArchitecture(): string {
		return php_uname('m');
	}
}
