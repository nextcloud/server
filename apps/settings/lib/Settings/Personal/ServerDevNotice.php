<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Personal;

use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class ServerDevNotice implements ISettings {

	public function __construct(
		private IRegistry $registry,
		private IEventDispatcher $eventDispatcher,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$userFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());

		$hasInitialState = false;

		// If the Reasons to use Nextcloud.pdf file is here, let's init Viewer, also check that Viewer is there
		if (class_exists(LoadViewer::class) && $userFolder->nodeExists('Reasons to use Nextcloud.pdf')) {
			/**
			 * @psalm-suppress UndefinedClass, InvalidArgument
			 */
			$this->eventDispatcher->dispatch(LoadViewer::class, new LoadViewer());
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
	public function getSection(): ?string {
		if ($this->registry->delegateHasValidSubscription()) {
			return null;
		}

		return 'personal-info';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 1000;
	}
}
