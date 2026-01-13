<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Config;

use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

class UserContext {

	/** @var string */
	private $userId;

	public function __construct(
		private IUserSession $session,
		private ShareManager $shareManager,
		private IRequest $request,
		private IUserManager $userManager,
	) {
	}

	public function getSession(): IUserSession {
		return $this->session;
	}

	public function setUserId(string $userId): void {
		$this->userId = $userId;
	}

	protected function getUserId(): ?string {
		if ($this->userId !== null) {
			return $this->userId;
		}
		if ($this->session->getUser() !== null) {
			return $this->session->getUser()->getUID();
		}
		try {
			$shareToken = $this->request->getParam('token');
			if ($shareToken !== null) {
				$share = $this->shareManager->getShareByToken($shareToken);
				return $share->getShareOwner();
			}
		} catch (ShareNotFound $e) {
		}

		return null;
	}

	protected function getUser(): ?IUser {
		$userId = $this->getUserId();
		if ($userId !== null) {
			return $this->userManager->get($userId);
		}
		return null;
	}
}
