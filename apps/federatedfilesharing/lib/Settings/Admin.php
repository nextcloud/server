<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Settings;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\GlobalScale\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	/**
	 * Admin constructor.
	 */
	public function __construct(
		private FederatedShareProvider $fedShareProvider,
		private IConfig $gsConfig,
		private IL10N $l,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialState,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {

		$this->initialState->provideInitialState('internalOnly', $this->gsConfig->onlyInternalFederation());
		$this->initialState->provideInitialState('sharingFederatedDocUrl', $this->urlGenerator->linkToDocs('admin-sharing-federated'));
		$this->initialState->provideInitialState('outgoingServer2serverShareEnabled', $this->fedShareProvider->isOutgoingServer2serverShareEnabled());
		$this->initialState->provideInitialState('incomingServer2serverShareEnabled', $this->fedShareProvider->isIncomingServer2serverShareEnabled());
		$this->initialState->provideInitialState('federatedGroupSharingSupported', $this->fedShareProvider->isFederatedGroupSharingSupported());
		$this->initialState->provideInitialState('outgoingServer2serverGroupShareEnabled', $this->fedShareProvider->isOutgoingServer2serverGroupShareEnabled());
		$this->initialState->provideInitialState('incomingServer2serverGroupShareEnabled', $this->fedShareProvider->isIncomingServer2serverGroupShareEnabled());
		$this->initialState->provideInitialState('lookupServerEnabled', $this->fedShareProvider->isLookupServerQueriesEnabled());
		$this->initialState->provideInitialState('lookupServerUploadEnabled', $this->fedShareProvider->isLookupServerUploadEnabled());
		$this->initialState->provideInitialState('federatedTrustedShareAutoAccept', $this->fedShareProvider->isFederatedTrustedShareAutoAccept());

		return new TemplateResponse('federatedfilesharing', 'settings-admin', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'sharing';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 20;
	}

	public function getName(): ?string {
		return $this->l->t('Federated Cloud Sharing');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'files_sharing' => [
				'outgoing_server2server_share_enabled',
				'incoming_server2server_share_enabled',
				'federatedGroupSharingSupported',
				'outgoingServer2serverGroupShareEnabled',
				'incomingServer2serverGroupShareEnabled',
				'lookupServerEnabled',
				'lookupServerUploadEnabled',
				'federatedTrustedShareAutoAccept',
			],
		];
	}
}
