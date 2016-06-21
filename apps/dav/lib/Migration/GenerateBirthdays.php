<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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


namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class GenerateBirthdays implements IRepairStep {

	/** @var BirthdayService */
	private $birthdayService;

	/** @var IUserManager */
	private $userManager;

	/**
	 * GenerateBirthdays constructor.
	 *
	 * @param BirthdayService $birthdayService
	 * @param IUserManager $userManager
	 */
	public function __construct(BirthdayService $birthdayService, IUserManager $userManager) {
		$this->birthdayService = $birthdayService;
		$this->userManager = $userManager;
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Regenerate birthday calendar for all users';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {

		$output->startProgress();
		$this->userManager->callForAllUsers(function($user) use ($output) {
			/** @var IUser $user */
			$output->advance(1, $user->getDisplayName());
			$this->birthdayService->syncUser($user->getUID());
		});
		$output->finishProgress();
	}
}
