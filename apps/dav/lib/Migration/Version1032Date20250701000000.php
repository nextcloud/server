<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;

class Version1032Date20250701000000 extends SimpleMigrationStep {

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
		private readonly IManager $notificationManager,
		private readonly LoggerInterface $logger,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// If the system address book is not exposed there is nothing to do
		if ($this->appConfig->getAppValueBool('system_addressbook_exposed', true) === false) {
			return;
		}
		// We use count seen because getting a user count from the backend can be very slow
		$limit = $this->appConfig->getAppValueBool('system_addressbook_limit', 5000);
		if ($this->userManager->countSeenUsers() <= $limit) {
			return;
		}
		$this->appConfig->setAppValueBool('system_addressbook_exposed', false);
		$this->logger->warning('System address book disabled because user limit reached');
		// Notify all admin users about the system address book being disabled
		foreach ($this->groupManager->get('admin')->getUsers() as $user) {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp(Application::APP_ID)
				->setUser($user->getUID())
				->setDateTime(new \DateTime())
				->setSubject('SystemSystemAddressBookDisabled', []);
			$this->notificationManager->notify($notification);
		}
	}

}
