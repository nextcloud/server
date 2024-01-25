<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\SetupChecks;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Notification\IManager;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use OCP\Support\Subscription\IRegistry;

class PushService implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IManager $notificationsManager,
		private IRegistry $subscriptionRegistry,
		private ITimeFactory $timeFactory,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Push service');
	}

	public function getCategory(): string {
		return 'system';
	}

	/**
	 * Check if is fair use of free push service
	 */
	private function isFairUseOfFreePushService(): bool {
		$rateLimitReached = (int) $this->config->getAppValue('notifications', 'rate_limit_reached', '0');
		if ($rateLimitReached >= ($this->timeFactory->now()->getTimestamp() - 7 * 24 * 3600)) {
			// Notifications app is showing a message already
			return true;
		}
		return $this->notificationsManager->isFairUseOfFreePushService();
	}

	public function run(): SetupResult {
		if ($this->subscriptionRegistry->delegateHasValidSubscription()) {
			return SetupResult::success($this->l10n->t('Valid enterprise license'));
		}

		if ($this->isFairUseOfFreePushService()) {
			return SetupResult::success($this->l10n->t('Free push service'));
		}

		return SetupResult::error(
			$this->l10n->t('This is the unsupported community build of Nextcloud. Given the size of this instance, performance, reliability and scalability cannot be guaranteed. Push notifications are limited to avoid overloading our free service. Learn more about the benefits of Nextcloud Enterprise at {link}.'),
			descriptionParameters:[
				'link' => [
					'type' => 'highlight',
					'id' => 'link',
					'name' => 'https://nextcloud.com/enterprise',
					'link' => 'https://nextcloud.com/enterprise',
				],
			],
		);
	}
}
