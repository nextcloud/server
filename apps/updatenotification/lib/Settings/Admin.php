<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Settings;

use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {
	public function __construct(
		private IConfig $config,
		private IAppConfig $appConfig,
		private UpdateChecker $updateChecker,
		private IGroupManager $groupManager,
		private IDateTimeFormatter $dateTimeFormatter,
		private IFactory $l10nFactory,
		private IRegistry $subscriptionRegistry,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private IInitialState $initialState,
	) {
	}

	public function getForm(): TemplateResponse {
		$lastUpdateCheckTimestamp = $this->appConfig->getValueInt('core', 'lastupdatedat');
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
		return (int)$this->userManager->countUsersTotal(100) < 100;
	}
}
