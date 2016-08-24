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

use OC\Files\SimpleFS\SimpleFolder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OC\SystemConfig;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class AppData extends SimpleRoot implements IAppData {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var SystemConfig */
	private $config;

	/** @var string */
	private $appId;

	/**
	 * AppData constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param SystemConfig $systemConfig
	 * @param string $appId
	 */
	public function __construct(IRootFolder $rootFolder,
								SystemConfig $systemConfig,
								$appId) {

		$this->rootFolder = $rootFolder;
		$this->config = $systemConfig;
		$this->appId = $appId;
	}

	/**
	 * @return Folder
	 * @throws \RuntimeException
	 */
	private function getAppDataFolder() {
		if ($this->folder === null) {
			$instanceId = $this->config->getValue('instanceid', null);
			if ($instanceId === null) {
				throw new \RuntimeException('no instance id!');
			}

			$name = 'appdata_' . $instanceId;

			try {
				$appDataFolder = $this->rootFolder->get($name);
			} catch (NotFoundException $e) {
				try {
					$appDataFolder = $this->rootFolder->newFolder($name);
				} catch (NotPermittedException $e) {
					throw new \RuntimeException('Could not get appdata folder');
				}
			}

			try {
				$appDataFolder = $appDataFolder->get($this->appId);
			} catch (NotFoundException $e) {
				try {
					$appDataFolder = $appDataFolder->newFolder($this->appId);
				} catch (NotPermittedException $e) {
					throw new \RuntimeException('Could not get appdata folder for ' . $this->appId);
				}
			}

			$this->folder = $appDataFolder;
		}

		return $this->folder;
	}

	/**
	 * @inheritdoc
	 */
	public function getFolder($name) {
		$node = $this->getAppDataFolder()->get($name);

		/** @var Folder $node */
		return new SimpleFolder($node);
	}

	/**
	 * @inheritdoc
	 */
	public function newFolder($name) {
		$folder = $this->getAppDataFolder()->newFolder($name);

		return new SimpleFolder($folder);
	}

	public function getDirectoryListing() {
		$listing = $this->getAppDataFolder()->getDirectoryListing();

		$fileListing = array_map(function(Node $file) {
			return new SimpleFolder($file);
		}, $listing);

		return $fileListing;
	}
}
