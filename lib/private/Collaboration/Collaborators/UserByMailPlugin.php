<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OC\KnownUser\KnownUserService;
use OCP\Contacts\IManager;
use OCP\Federation\ICloudIdManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Share\IShare;

/**
 * Dummy subclass to initialize a MailPlugin with a specific share type.
 */
class UserByMailPlugin extends MailPlugin {

	public function __construct(
		IManager $contactsManager,
		ICloudIdManager $cloudIdManager,
		IConfig $config,
		IGroupManager $groupManager,
		KnownUserService $knownUserService,
		IUserSession $userSession,
		IMailer $mailer,
		mixed $shareWithGroupOnlyExcludeGroupsList = [],
	) {
		parent::__construct(
			$contactsManager,
			$cloudIdManager,
			$config,
			$groupManager,
			$knownUserService,
			$userSession,
			$mailer,
			$shareWithGroupOnlyExcludeGroupsList,
			IShare::TYPE_USER,
		);
	}
}
