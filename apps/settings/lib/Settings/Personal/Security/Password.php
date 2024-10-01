<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Personal\Security;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserManager;
use OCP\Settings\ISettings;

class Password implements ISettings {

	/** @var IUserManager */
	private $userManager;

	/** @var string|null */
	private $uid;

	public function __construct(IUserManager $userManager,
		?string $UserId) {
		$this->userManager = $userManager;
		$this->uid = $UserId;
	}

	public function getForm(): TemplateResponse {
		$user = $this->userManager->get($this->uid);
		$passwordChangeSupported = false;
		if ($user !== null) {
			$passwordChangeSupported = $user->canChangePassword();
		}

		return new TemplateResponse('settings', 'settings/personal/security/password', [
			'passwordChangeSupported' => $passwordChangeSupported,
		]);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 10;
	}
}
