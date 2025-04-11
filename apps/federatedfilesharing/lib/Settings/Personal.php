<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Settings;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class Personal implements ISettings {
	public function __construct(
		private FederatedShareProvider $federatedShareProvider,
		private IUserSession $userSession,
		private Defaults $defaults,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm(): TemplateResponse {
		$cloudID = $this->userSession->getUser()->getCloudId();
		$url = 'https://nextcloud.com/sharing#' . $cloudID;

		$this->initialState->provideInitialState('color', $this->defaults->getDefaultColorPrimary());
		$this->initialState->provideInitialState('textColor', $this->defaults->getDefaultTextColorPrimary());
		$this->initialState->provideInitialState('logoPath', $this->defaults->getLogo());
		$this->initialState->provideInitialState('reference', $url);
		$this->initialState->provideInitialState('cloudId', $cloudID);
		$this->initialState->provideInitialState('docUrlFederated', $this->urlGenerator->linkToDocs('user-sharing-federated'));

		return new TemplateResponse('federatedfilesharing', 'settings-personal', [], TemplateResponse::RENDER_AS_BLANK);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 */
	public function getSection(): ?string {
		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled() ||
			$this->federatedShareProvider->isIncomingServer2serverGroupShareEnabled()) {
			return 'sharing';
		}
		return null;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority(): int {
		return 40;
	}
}
