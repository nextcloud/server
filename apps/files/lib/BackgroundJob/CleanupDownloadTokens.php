<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\files\lib\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCA\Files\AppInfo\Application;
use OCP\IConfig;

class CleanupDownloadTokens extends TimedJob {
	private const INTERVAL_MINUTES = 24 * 60;
	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->interval = self::INTERVAL_MINUTES;
		$this->config = $config;
	}

	protected function run($argument) {
		$appKeys = $this->config->getAppKeys(Application::APP_ID);
		foreach ($appKeys as $key) {
			if (strpos($key, Application::DL_TOKEN_PREFIX) !== 0) {
				continue;
			}
			$dataStr = $this->config->getAppValue(Application::APP_ID, $key, '');
			if ($dataStr === '') {
				$this->config->deleteAppValue(Application::APP_ID, $key);
				continue;
			}
			$data = \json_decode($dataStr, true);
			if (!isset($data['lastActivity']) || (time() - $data['lastActivity']) > 24 * 60 * 2) {
				// deletes tokens that have not seen activity for 2 days
				// the period is chosen to allow continue of downloads with network interruptions in minde
				$this->config->deleteAppValue(Application::APP_ID, $key);
			}
		}
	}
}
