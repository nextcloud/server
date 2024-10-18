<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Settings\CalDAVSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;

class BirthdayCalendarController extends Controller {

	/**
	 * BirthdayCalendar constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IDBConnection $db
	 * @param IConfig $config
	 * @param IJobList $jobList
	 * @param IUserManager $userManager
	 * @param CalDavBackend $caldavBackend
	 */
	public function __construct(
		$appName,
		IRequest $request,
		protected IDBConnection $db,
		protected IConfig $config,
		protected IJobList $jobList,
		protected IUserManager $userManager,
		protected CalDavBackend $caldavBackend,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return Response
	 */
	#[AuthorizedAdminSetting(settings: CalDAVSettings::class)]
	public function enable() {
		$this->config->setAppValue($this->appName, 'generateBirthdayCalendar', 'yes');

		// add background job for each user
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			$this->jobList->add(GenerateBirthdayCalendarBackgroundJob::class, [
				'userId' => $user->getUID(),
			]);
		});

		return new JSONResponse([]);
	}

	/**
	 * @return Response
	 */
	#[AuthorizedAdminSetting(settings: CalDAVSettings::class)]
	public function disable() {
		$this->config->setAppValue($this->appName, 'generateBirthdayCalendar', 'no');

		$this->jobList->remove(GenerateBirthdayCalendarBackgroundJob::class);
		$this->caldavBackend->deleteAllBirthdayCalendars();

		return new JSONResponse([]);
	}
}
