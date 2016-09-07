<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Files\AppData;

use OC\SystemConfig;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class Factory {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var Folder */
	private $appDataFolder;

	/** @var SystemConfig */
	private $config;

	public function __construct(IRootFolder $rootFolder,
								SystemConfig $systemConfig) {

		$this->rootFolder = $rootFolder;
		$this->config = $systemConfig;
	}

	/**
	 * @return Folder
	 */
	private function getAppDataFolder() {
		if ($this->appDataFolder === null) {
			$instanceId = $this->config->getValue('instanceid', null);
			if ($instanceId === null) {
				throw new \RuntimeException('no instance id!');
			}

			/** @var string $instanceId */
			try {
				$appDataFolder = $this->rootFolder->get($instanceId);
			} catch (NotFoundException $e) {
				try {
					$appDataFolder = $this->rootFolder->newFolder($instanceId);
				} catch (NotPermittedException $e) {
					// Log
					return null;
				}
			}

			$this->appDataFolder = $appDataFolder;
		}

		return $this->appDataFolder;
	}

	/**
	 * @param string $appId
	 * @return AppData
	 */
	public function get($appId) {
		$appDataFolder = $this->getAppDataFolder();

		try {
			$appFolder = $appDataFolder->get($appId);
		} catch (NotFoundException $e) {
			try {
				$appFolder = $appDataFolder->newFolder($appId);
			} catch (NotPermittedException $e) {
				// Log
				return null;
			}
		}

		return new AppData($appFolder);
	}
}
