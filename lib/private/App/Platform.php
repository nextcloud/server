<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\App;

use OCP\IConfig;
use OCP\IBinaryFinder;

/**
 * Class Platform
 *
 * This class basically abstracts any kind of information which can be retrieved from the underlying system.
 *
 * @package OC\App
 */
class Platform {
	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
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
		$dbType = $this->config->getSystemValue('dbtype', 'sqlite');
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
