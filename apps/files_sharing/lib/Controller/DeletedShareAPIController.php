<?php
declare(strict_types=1);
/**
 * @Copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 * @Copyright 2018, John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;

class DeletedShareAPIController extends OCSController {

	/** @var ShareManager */
	private $shareManager;

	/** @var string */
	private $userId;

	/** @var IUserManager */
	private $userManager;

	public function __construct(string $appName,
								IRequest $request,
								ShareManager $shareManager,
								string $UserId,
								IUserManager $userManager) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManager;
		$this->userId = $UserId;
		$this->userManager = $userManager;
	}

	private function formatShare(IShare $share): array {
		return [
			'id' => $share->getFullId(),
			'uid_owner' => $share->getShareOwner(),
			'displayname_owner' => $this->userManager->get($share->getShareOwner())->getDisplayName(),
			'path' => $share->getTarget(),
		];
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(): DataResponse {
		$shares = $this->shareManager->getSharedWith($this->userId, \OCP\Share::SHARE_TYPE_GROUP, null, -1, 0);

		// Only get deleted shares
		$shares = array_filter($shares, function(IShare $share) {
			return $share->getPermissions() === 0;
		});

		// Only get shares where the owner still exists
		$shares = array_filter($shares, function (IShare $share) {
			return $this->userManager->userExists($share->getShareOwner());
		});

		$shares = array_map(function (IShare $share) {
			return $this->formatShare($share);
		}, $shares);

		return new DataResponse($shares);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @throws OCSException
	 */
	public function undelete(string $id): DataResponse {
		try {
			$share = $this->shareManager->getShareById($id, $this->userId);
		} catch (ShareNotFound $e) {
			throw new OCSNotFoundException('Share not found');
		}

		if ($share->getPermissions() !== 0) {
			throw new OCSNotFoundException('No deleted share found');
		}

		try {
			$this->shareManager->restoreShare($share, $this->userId);
		} catch (GenericShareException $e) {
			throw new OCSException('Something went wrong');
		}

		return new DataResponse([]);
	}
}
