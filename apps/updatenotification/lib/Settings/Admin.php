<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\UpdateNotification\Settings;

use OC\User\Backend;
use OCP\User\Backend\ICountUsersBackend;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IGroupManager;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {
	private IConfig $config;
	private UpdateChecker $updateChecker;
	private IGroupManager $groupManager;
	private IDateTimeFormatter $dateTimeFormatter;
	private IFactory $l10nFactory;
	private IRegistry $subscriptionRegistry;
	private IUserManager $userManager;
	private LoggerInterface $logger;
	private IInitialState $initialState;

	public function __construct(
		IConfig $config,
		UpdateChecker $updateChecker,
		IGroupManager $groupManager,
		IDateTimeFormatter $dateTimeFormatter,
		IFactory $l10nFactory,
		IRegistry $subscriptionRegistry,
		IUserManager $userManager,
		LoggerInterface $logger,
		IInitialState $initialState 
	) {
		$this->config = $config;
		$this->updateChecker = $updateChecker;
		$this->groupManager = $groupManager;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->l10nFactory = $l10nFactory;
		$this->subscriptionRegistry = $subscriptionRegistry;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->initialState = $initialState;
	}

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
		if ($currentChannel === 'git') {
			$channels[] = 'git';
		}

		$updateState = $this->updateChecker->getUpdateState();

		$notifyGroups = json_decode($this->config->getAppValue('updatenotification', 'notify_groups', '["admin"]'), true);

		$defaultUpdateServerURL = 'https://updates.nextcloud.com/updater_server/';
		$updateServerURL = $this->config->getSystemValue('updater.server.url', $defaultUpdateServerURL);
		$defaultCustomerUpdateServerURLPrefix = 'https://updates.nextcloud.com/customers/';

		$isDefaultUpdateServerURL = $updateServerURL === $defaultUpdateServerURL
			|| strpos($updateServerURL, $defaultCustomerUpdateServerURLPrefix) === 0;

		$hasValidSubscription = $this->subscriptionRegistry->delegateHasValidSubscription();

		$params = [
			'isNewVersionAvailable' => !empty($updateState['updateAvailable']),
			'isUpdateChecked' => $lastUpdateCheckTimestamp > 0,
			'lastChecked' => $lastUpdateCheck,
			'currentChannel' => $currentChannel,
			'channels' => $channels,
			'newVersion' => empty($updateState['updateVersion']) ? '' : $updateState['updateVersion'],
			'newVersionString' => empty($updateState['updateVersionString']) ? '' : $updateState['updateVersionString'],
			'downloadLink' => empty($updateState['downloadLink']) ? '' : $updateState['downloadLink'],
			'changes' => $this->filterChanges($updateState['changes'] ?? []),
			'webUpdaterEnabled' => !$this->config->getSystemValue('upgrade.disable-web', false),
			'isWebUpdaterRecommended' => $this->isWebUpdaterRecommended(),
			'updaterEnabled' => empty($updateState['updaterEnabled']) ? false : $updateState['updaterEnabled'],
			'versionIsEol' => empty($updateState['versionIsEol']) ? false : $updateState['versionIsEol'],
			'isDefaultUpdateServerURL' => $isDefaultUpdateServerURL,
			'updateServerURL' => $updateServerURL,
			'notifyGroups' => $this->getSelectedGroups($notifyGroups),
			'hasValidSubscription' => $hasValidSubscription,
		];
		$this->initialState->provideInitialState('data', $params);

		return new TemplateResponse('updatenotification', 'admin', [], '');
	}

	protected function filterChanges(array $changes): array {
		$filtered = [];
		if (isset($changes['changelogURL'])) {
			$filtered['changelogURL'] = $changes['changelogURL'];
		}
		if (!isset($changes['whatsNew'])) {
			return $filtered;
		}

		$iterator = $this->l10nFactory->getLanguageIterator();
		do {
			$lang = $iterator->current();
			if (isset($changes['whatsNew'][$lang])) {
				$filtered['whatsNew'] = $changes['whatsNew'][$lang];
				return $filtered;
			}
			$iterator->next();
		} while ($lang !== 'en' && $iterator->valid());

		return $filtered;
	}

	/**
	 * @param list<string> $groupIds
	 * @return list<array{id: string, displayname: string}>
	 */
	protected function getSelectedGroups(array $groupIds): array {
		$result = [];
		foreach ($groupIds as $groupId) {
			$group = $this->groupManager->get($groupId);

			if ($group === null) {
				continue;
			}

			$result[] = ['id' => $group->getGID(), 'displayname' => $group->getDisplayName()];
		}

		return $result;
	}

	public function getSection(): string {
		return 'overview';
	}

	public function getPriority(): int {
		return 11;
	}

	private function isWebUpdaterRecommended(): bool {
		return $this->getUserCount() < 100;
	}

	/**
	 * @see https://github.com/nextcloud/server/blob/39494fbf794d982f6f6551c984e6ca4c4e947d01/lib/private/Support/Subscription/Registry.php#L188-L216 implementation reference
	 */
	private function getUserCount(): int {
		$userCount = 0;
		$backends = $this->userManager->getBackends();
		foreach ($backends as $backend) {
			// TODO: change below to 'if ($backend instanceof ICountUsersBackend) {'
			if ($backend->implementsActions(Backend::COUNT_USERS)) {
				/** @var ICountUsersBackend $backend */
				$backendUsers = $backend->countUsers();
				if ($backendUsers !== false) {
					$userCount += $backendUsers;
				}
			}
		}

		return $userCount;
	}
}
