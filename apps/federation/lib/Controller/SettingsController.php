<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Controller;

use OCA\Federation\Settings\Admin;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\HintException;
use OCP\IL10N;
use OCP\IRequest;

class SettingsController extends OCSController {
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
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function addServer(string $url): JSONResponse {
		try {
			$this->checkServer(trim($url));
		} catch (HintException $e) {
			return new JSONResponse([
				'message' => 'error',
				'data' => [
					'message' => $e->getMessage(),
					'hint' => $e->getHint(),
				],
			], $e->getCode());
		}

		// Add the server to the list of trusted servers, all is well
		$id = $this->trustedServers->addServer(trim($url));
		return new JSONResponse([
			'message' => 'ok',
			'data' => [
				'url' => $url,
				'id' => $id,
				'message' => $this->l->t('Added to the list of trusted servers')
			],
		]);
	}

	/**
	 * Add server to the list of trusted Nextclouds.
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function removeServer(int $id): JSONResponse {
		$this->trustedServers->removeServer($id);
		return new JSONResponse([
			'message' => 'ok',
			'data' => ['id' => $id],
		]);
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
			throw new HintException($message, $hint, Http::STATUS_CONFLICT);
		}

		if ($this->trustedServers->isNextcloudServer($url) === false) {
			$message = 'No server to federate with found';
			$hint = $this->l->t('No server to federate with found');
			throw new HintException($message, $hint, Http::STATUS_NOT_FOUND);
		}

		return true;
	}
}
