<?php
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OCA\Files_External\Config;

use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

class UserContext {

	/** @var IUserSession */
	private $session;

	/** @var ShareManager */
	private $shareManager;

	/** @var IRequest */
	private $request;

	private $user;

	public function __construct(IUserSession $session, ShareManager $manager, IRequest $request) {
		$this->session = $session;
		$this->shareManager = $manager;
		$this->request = $request;
	}

	public function getSession(): IUserSession {
		return $this->session;
	}

	public function setUser($user): void {
		$this->user = $user;
	}

	protected function getUserId(): ?string {
		if ($this->user !== null) {
			return $this->user;
		}
		if($this->session && $this->session->getUser() !== null) {
			return $this->session->getUser()->getUID();
		}
		try {
			$shareToken = $this->request->getParam('token');
			$share = $this->shareManager->getShareByToken($shareToken);
			return $share->getShareOwner();
		} catch (ShareNotFound $e) {}

		return null;
	}

}
