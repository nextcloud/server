<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Personal;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class ServerDevNotice implements ISettings {

	public function __construct(
		private IRegistry $registry,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	#[\Override]
	public function getForm(): TemplateResponse {
		$userFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());

		$hasInitialState = false;

		// The viewer loads itself; this only decides whether the notice has
		// a file to point at
		if ($userFolder->nodeExists('Reasons to use Nextcloud.pdf')) {
			$hasInitialState = true;
		}

		// Always load the script
		Util::addScript('settings', 'vue-settings-nextcloud-pdf');
		$this->initialState->provideInitialState('has-reasons-use-nextcloud-pdf', $hasInitialState);

		return new TemplateResponse('settings', 'settings/personal/development.notice', [
			'reasons-use-nextcloud-pdf-link' => $this->urlGenerator->linkToRoute('settings.Reasons.getPdf')
		]);
	}

	/**
	 * @return string|null the section ID, e.g. 'sharing'
	 */
	#[\Override]
	public function getSection(): ?string {
		if ($this->registry->delegateHasValidSubscription()) {
			return null;
		}

		return 'profile-contact';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	#[\Override]
	public function getPriority(): int {
		return 1000;
	}
}
