<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\UpdateNotification\Settings;

use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\ServerVersion;
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
		private ServerVersion $serverVersion,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$lastUpdateCheckTimestamp = $this->appConfig->getValueInt('core', 'lastupdatedat');
		$lastUpdateCheck = $this->dateTimeFormatter->formatDateTime($lastUpdateCheckTimestamp);

		$channels = [
			'daily',
			'beta',
			'stable',
			'production',
		];
		$currentChannel = $this->serverVersion->getChannel();
		if ($currentChannel === 'git') {
			$channels[] = 'git';
		}

		$updateState = $this->updateChecker->getUpdateState();

		$notifyGroups = $this->appConfig->getValueArray(Application::APP_ID, 'notify_groups', ['admin']);

		$defaultUpdateServerURL = 'https://updates.nextcloud.com/updater_server/';
		$updateServerURL = $this->config->getSystemValue('updater.server.url', $defaultUpdateServerURL);
		$defaultCustomerUpdateServerURLPrefix = 'https://updates.nextcloud.com/customers/';

		$isDefaultUpdateServerURL = $updateServerURL === $defaultUpdateServerURL
			|| strpos($updateServerURL, $defaultCustomerUpdateServerURLPrefix) === 0;

		$hasValidSubscription = $this->subscriptionRegistry->delegateHasValidSubscription();

		// The web updater ("Open updater" button) must never be offered if
		// the /updater/ directory has been removed from this install - e.g.
		// Docker/Kubernetes images intentionally strip it since those
		// deployments are updated by replacing the container image, not via
		// the in-app web updater. Without this check, updaterEnabled could
		// be true (feature/config-wise) while the button does nothing when
		// clicked, because /updater/ simply isn't there to redirect to.
		$updaterDirExists = is_dir(\OC::$SERVERROOT . '/updater');

		$updaterEnabledFromState = empty($updateState['updaterEnabled']) ? false : $updateState['updaterEnabled'];

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
			// updaterEnabled now folds in the on-disk check: the "Open
			// updater" button will only ever render (in a rebuilt frontend
			// that consumes this flag) or be considered enabled (for any
			// backend logic keyed off this flag) when /updater/ genuinely
			// exists. This keeps the fix effective even without touching
			// the compiled frontend bundle, for backend-only consumers of
			// this state (e.g. occ commands, API responses).
			'updaterEnabled' => $updaterEnabledFromState && $updaterDirExists,
			// Exposed separately too, in case the frontend is rebuilt to
			// distinguish "feature disabled" from "directory missing" for
			// clearer messaging.
			'updaterDirExists' => $updaterDirExists,
			'versionIsEol' => empty($updateState['versionIsEol']) ? false : $updateState['versionIsEol'],
			'isDefaultUpdateServerURL' => $isDefaultUpdateServerURL,
			'updateServerURL' => $updateServerURL,
			'notifyGroups' => $this->getSelectedGroups($notifyGroups),
			'hasValidSubscription' => $hasValidSubscription,
		];
		$this->initialState->provideInitialState('data', $params);

		Util::addStyle(Application::APP_ID, 'settings-admin');
		Util::addScript(Application::APP_ID, 'settings-admin');
		return new TemplateResponse(Application::APP_ID, 'admin', [], '');
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
	 * @param string[] $groupIds
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

	#[\Override]
	public function getSection(): ?string {
		if (!$this->config->getSystemValueBool('updatechecker', true)) {
			// update checker is disabled so we do not show the section at all
			return null;
		}

		return 'overview';
	}

	#[\Override]
	public function getPriority(): int {
		return 11;
	}

	private function isWebUpdaterRecommended(): bool {
		return (int)$this->userManager->countUsersTotal(100) < 100;
	}
}
