<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\UpdateNotification\Settings;

use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	/** @var IConfig */
	private $config;
	/** @var UpdateChecker */
	private $updateChecker;
	/** @var IDateTimeFormatter */
	private $dateTimeFormatter;

	/**
	 * @param IConfig $config
	 * @param UpdateChecker $updateChecker
	 * @param IDateTimeFormatter $dateTimeFormatter
	 */
	public function __construct(IConfig $config,
								UpdateChecker $updateChecker,
								IDateTimeFormatter $dateTimeFormatter) {
		$this->config = $config;
		$this->updateChecker = $updateChecker;
		$this->dateTimeFormatter = $dateTimeFormatter;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$lastUpdateCheckTimestamp = $this->config->getAppValue('core', 'lastupdatedat');
		$lastUpdateCheck = $this->dateTimeFormatter->formatDateTime($lastUpdateCheckTimestamp);

		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = Util::getChannel();

		// Remove the currently used channel from the channels list
		if(($key = array_search($currentChannel, $channels, true)) !== false) {
			unset($channels[$key]);
		}
		$updateState = $this->updateChecker->getUpdateState();

		$notifyGroups = json_decode($this->config->getAppValue('updatenotification', 'notify_groups', '["admin"]'), true);

		$defaultUpdateServerURL = 'https://updates.nextcloud.com/updater_server/';
		$updateServerURL = $this->config->getSystemValue('updater.server.url', $defaultUpdateServerURL);

		$params = [
			'isNewVersionAvailable' => !empty($updateState['updateAvailable']),
			'isUpdateChecked' => $lastUpdateCheckTimestamp > 0,
			'lastChecked' => $lastUpdateCheck,
			'currentChannel' => $currentChannel,
			'channels' => $channels,
			'newVersionString' => empty($updateState['updateVersion']) ? '' : $updateState['updateVersion'],
			'downloadLink' => empty($updateState['downloadLink']) ? '' : $updateState['downloadLink'],
			'updaterEnabled' => empty($updateState['updaterEnabled']) ? false : $updateState['updaterEnabled'],
			'isDefaultUpdateServerURL' => $updateServerURL === $defaultUpdateServerURL,
			'updateServerURL' => $updateServerURL,
			'notify_groups' => implode('|', $notifyGroups),
		];

		return new TemplateResponse('updatenotification', 'admin', $params, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'server';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 1;
	}
}
