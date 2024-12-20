<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Controller;

use OCA\Federation\Settings\Admin;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\HintException;
use OCP\IL10N;
use OCP\IRequest;

class SettingsController extends Controller {
	public function __construct(
		string $AppName,
		IRequest $request,
		private IL10N $l,
		private TrustedServers $trustedServers,
	) {
		parent::__construct($AppName, $request);
	}


	/**
	 * Add server to the list of trusted Nextclouds.
	 *
	 * @throws HintException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function addServer(string $url): DataResponse {
		$this->checkServer(trim($url));
		$id = $this->trustedServers->addServer(trim($url));

		return new DataResponse([
			'url' => $url,
			'id' => $id,
			'message' => $this->l->t('Added to the list of trusted servers')
		]);
	}

	/**
	 * Add server to the list of trusted Nextclouds.
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function removeServer(int $id): DataResponse {
		$this->trustedServers->removeServer($id);
		return new DataResponse();
	}

	/**
	 * Check if the server should be added to the list of trusted servers or not.
	 *
	 * @throws HintException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	protected function checkServer(string $url): bool {
		if ($this->trustedServers->isTrustedServer($url) === true) {
			$message = 'Server is already in the list of trusted servers.';
			$hint = $this->l->t('Server is already in the list of trusted servers.');
			throw new HintException($message, $hint);
		}

		if ($this->trustedServers->isNextcloudServer($url) === false) {
			$message = 'No server to federate with found';
			$hint = $this->l->t('No server to federate with found');
			throw new HintException($message, $hint);
		}

		return true;
	}
}
