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

	/** @var IUserSession */
	private $session;

	/** @var ShareManager */
	private $shareManager;

	/** @var IRequest */
	private $request;

	/** @var string */
	private $userId;

	/** @var IUserManager */
	private $userManager;

	public function __construct(IUserSession $session, ShareManager $manager, IRequest $request, IUserManager $userManager) {
		$this->session = $session;
		$this->shareManager = $manager;
		$this->request = $request;
		$this->userManager = $userManager;
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
		if ($this->session && $this->session->getUser() !== null) {
			return $this->session->getUser()->getUID();
		}
		try {
			$shareToken = $this->request->getParam('token');
			$share = $this->shareManager->getShareByToken($shareToken);
			return $share->getShareOwner();
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
