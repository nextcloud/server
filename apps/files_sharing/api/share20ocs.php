<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OCA\Files_Sharing\API;

class Share20OCS {

	/** @var OC\Share20\Manager */
	private $shareManager;

	/** @var OCP\IGroupManager */
	private $groupManager;

	/** @var OCP\IUserManager */
	private $userManager;

	/** @var OCP\IRequest */
	private $request;

	/** @var OCP\Files\Folder */
	private $userFolder;

	public function __construct(\OC\Share20\Manager $shareManager,
	                            \OCP\IGroupManager $groupManager,
	                            \OCP\IUserManager $userManager,
	                            \OCP\IRequest $request,
								\OCP\Files\Folder $userFolder) {
		$this->shareManager = $shareManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->request = $request;
		$this->userFolder = $userFolder;
	}

	/**
	 * Delete a share
	 *
	 * @param int $id
	 * @return \OC_OCS_Result
	 */
	public function deleteShare($id) {
		try {
			$share = $this->shareManager->getShareById($id);
		} catch (\OC\Share20\Exception\ShareNotFound $e) {
			return new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		}

		/*
		 * FIXME
		 * User the old code path for remote shares until we have our remoteshareprovider
		 */
		if ($share->getShareType() === \OCP\Share::SHARE_TYPE_REMOTE) {
			\OCA\Files_Sharing\API\Local::deleteShare(['id' => $id]);
		}

		try {
			$this->shareManager->deleteShare($share);
		} catch (\OC\Share20\Exception\BackendError $e) {
			return new \OC_OCS_Result(null, 404, 'could not delete share');
		}

		return new \OC_OCS_Result();
	}
}
