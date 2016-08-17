<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OC\Settings\Admin;

use OC\Log\Owncloud as LogFile;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Logging implements ISettings {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$logType = $this->config->getSystemValue('log_type', 'file');
		$showLog = ($logType === 'file' || $logType === 'owncloud');

		$numEntriesToLoad = 5;
		$entries = LogFile::getEntries($numEntriesToLoad + 1);
		$entriesRemaining = count($entries) > $numEntriesToLoad;
		$entries = array_slice($entries, 0, $numEntriesToLoad);

		$logFileExists = file_exists(LogFile::getLogFilePath()) ;
		$logFileSize = $logFileExists ? filesize(LogFile::getLogFilePath()) : 0;

		$parameters = [
			'loglevel'         => $this->config->getSystemValue('loglevel', 2),
			'entries'          => $entries,
			'entriesremain'    => $entriesRemaining,
			'doesLogFileExist' => $logFileExists,
			'logFileSize'      => $logFileSize,
			'showLog'          => $showLog,
		];

		return new TemplateResponse('settings', 'admin/logging', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'logging';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 0;
	}
}
