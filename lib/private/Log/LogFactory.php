<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Log;

use OCP\IServerContainer;

class LogFactory {
	/** @var IServerContainer */
	private $c;

	public function __construct(IServerContainer $c) {
		$this->c = $c;
	}

	/**
	 * @param $type
	 * @return \OC\Log\Errorlog|File|\stdClass
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function get($type) {
		switch (strtolower($type)) {
			case 'errorlog':
				return new Errorlog();
			case 'syslog':
				return $this->c->resolve(Syslog::class);
			case 'file':
				return $this->buildLogFile();

			// Backwards compatibility for old and fallback for unknown log types
			case 'owncloud':
			case 'nextcloud':
			default:
			return $this->buildLogFile();
		}
	}

	protected function buildLogFile() {
		$config = $this->c->getConfig();
		$defaultLogFile = $config->getSystemValue('datadirectory', \OC::$SERVERROOT.'/data').'/nextcloud.log';
		$logFile = $config->getSystemValue('logfile', $defaultLogFile);
		$fallback = $defaultLogFile !== $logFile ? $defaultLogFile : '';

		return new File($logFile, $fallback);
	}
}
