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
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
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
	 * Add server to the list of trusted Nextcloud servers
	 *
	 * @param string $url The URL of the server to add
	 * @return JSONResponse<Http::STATUS_OK, array{data: array{id: int, message: string, url: string}, status: 'ok'}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND|Http::STATUS_CONFLICT, array{data: array{hint: string, message: string}, status: 'error'}, array{}>
	 *
	 * 200: Server added successfully
	 * 404: Server not found at the given URL
	 * 409: Server is already in the list of trusted servers
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function addServer(string $url): JSONResponse {
		$check = $this->checkServer(trim($url));
		if ($check instanceof JSONResponse) {
			return $check;
		}

		// Add the server to the list of trusted servers, all is well
		$id = $this->trustedServers->addServer(trim($url));
		return new JSONResponse([
			'status' => 'ok',
			'data' => [
				'url' => $url,
				'id' => $id,
				'message' => $this->l->t('Added to the list of trusted servers')
			],
		]);
	}

	/**
	 * Add server to the list of trusted Nextcloud servers
	 *
	 * @param int $id The ID of the trusted server to remove
	 * @return JSONResponse<Http::STATUS_OK, array{data: array{id: int}, status: 'ok'}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, array{data: array{message: string}, status: 'error'}, array{}>
	 *
	 * 200: Server removed successfully
	 * 404: Server not found at the given ID
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function removeServer(int $id): JSONResponse {
		try {
			$this->trustedServers->removeServer($id);
			return new JSONResponse([
				'status' => 'ok',
				'data' => ['id' => $id],
			]);
		} catch (\Exception $e) {
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $e->getMessage(),
				],
			], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * List all trusted servers
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{data: list<array{id: int, status: int, url: string}>, status: 'ok'}, array{}>
	 *
	 * 200: List of trusted servers
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function getServers(): JSONResponse {
		$servers = $this->trustedServers->getServers();

		// obfuscate the shared secret
		$servers = array_map(function ($server) {
			return [
				'url' => $server['url'],
				'id' => $server['id'],
				'status' => $server['status'],
			];
		}, $servers);

		// return the list of trusted servers
		return new JSONResponse([
			'status' => 'ok',
			'data' => $servers,
		]);
	}


	/**
	 * Check if the server should be added to the list of trusted servers or not.
	 *
	 * @return JSONResponse<Http::STATUS_NOT_FOUND|Http::STATUS_CONFLICT, array{data: array{hint: string, message: string}, status: 'error'}, array{}>|null
	 *
	 * 404: Server not found at the given URL
	 * 409: Server is already in the list of trusted servers
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	protected function checkServer(string $url): ?JSONResponse {
		if ($this->trustedServers->isTrustedServer($url) === true) {
			$message = 'Server is already in the list of trusted servers.';
			$hint = $this->l->t('Server is already in the list of trusted servers.');
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $message,
					'hint' => $hint,
				],
			], Http::STATUS_CONFLICT);
		}

		if ($this->trustedServers->isNextcloudServer($url) === false) {
			$message = 'No server to federate with found';
			$hint = $this->l->t('No server to federate with found');
			return new JSONResponse([
				'status' => 'error',
				'data' => [
					'message' => $message,
					'hint' => $hint,
				],
			], Http::STATUS_NOT_FOUND);
		}

		return null;
	}
}
