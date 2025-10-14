<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\ConfigLexicon;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Notification\IManager;
use OCP\ServerVersion;

class DisableSystemAddressBook implements IRepairStep {

	public function __construct(
		private readonly ServerVersion $serverVersion,
		private readonly IAppConfig $appConfig,
		private readonly IUserManager $userManager,
		private readonly IGroupManager $groupManager,
		private readonly IManager $notificationManager,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Disable system address book';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		// If the system address book exposure was previously set skip the repair step
		if ($this->appConfig->hasAppKey(ConfigLexicon::SYSTEM_ADDRESSBOOK_EXPOSED) === true) {
			$output->info('Skipping repair step system address book exposed was previously set');
			return;
		}
		// We use count seen because getting a user count from the backend can be very slow
		$limit = $this->appConfig->getAppValueInt('system_addressbook_limit', 5000);
		if ($this->userManager->countSeenUsers() <= $limit) {
			$output->info("Skipping repair step system address book has less then the threshold $limit of contacts no need to disable");
			return;
		}
		$this->appConfig->setAppValueBool(ConfigLexicon::SYSTEM_ADDRESSBOOK_EXPOSED, false);
		$output->warning("System address book disabled because it has more then the threshold of $limit contacts this can be re-enabled later");
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
