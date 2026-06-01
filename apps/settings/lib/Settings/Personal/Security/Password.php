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

	public function __construct(
		private IUserManager $userManager,
		private ?string $userId,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$user = $this->userManager->get($this->userId);
		$passwordChangeSupported = false;
		if ($user !== null) {
			$passwordChangeSupported = $user->canChangePassword();
		}

		return new TemplateResponse('settings', 'settings/personal/security/password', [
			'passwordChangeSupported' => $passwordChangeSupported,
		]);
	}

	#[\Override]
	public function getSection(): string {
		return 'security';
	}

	#[\Override]
	public function getPriority(): int {
		return 10;
	}
}
