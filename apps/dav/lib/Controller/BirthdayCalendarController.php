<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Controller;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Controller;
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
	 * @var IDBConnection
	 */
	protected $db;

	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * @var CalDavBackend
	 */
	protected $caldavBackend;

	/**
	 * @var IJobList
	 */
	protected $jobList;

	/**
	 * BirthdayCalendar constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IDBConnection $db
	 * @param IConfig $config
	 * @param IJobList $jobList
	 * @param IUserManager $userManager
	 * @param CalDavBackend $calDavBackend
	 */
	public function __construct($appName, IRequest $request,
		IDBConnection $db, IConfig $config,
		IJobList $jobList,
		IUserManager $userManager,
		CalDavBackend $calDavBackend) {
		parent::__construct($appName, $request);
		$this->db = $db;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->caldavBackend = $calDavBackend;
	}

	/**
	 * @return Response
	 * @AuthorizedAdminSetting(settings=OCA\DAV\Settings\CalDAVSettings)
	 */
	public function enable() {
		$this->config->setAppValue($this->appName, 'generateBirthdayCalendar', 'yes');

		// add background job for each user
		$this->userManager->callForSeenUsers(function (IUser $user) {
			$this->jobList->add(GenerateBirthdayCalendarBackgroundJob::class, [
				'userId' => $user->getUID(),
			]);
		});

		return new JSONResponse([]);
	}

	/**
	 * @return Response
	 * @AuthorizedAdminSetting(settings=OCA\DAV\Settings\CalDAVSettings)
	 */
	public function disable() {
		$this->config->setAppValue($this->appName, 'generateBirthdayCalendar', 'no');

		$this->jobList->remove(GenerateBirthdayCalendarBackgroundJob::class);
		$this->caldavBackend->deleteAllBirthdayCalendars();

		return new JSONResponse([]);
	}
}
