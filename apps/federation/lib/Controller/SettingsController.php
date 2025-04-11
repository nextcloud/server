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
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class SettingsController extends OCSController {
	public function __construct(
		string $AppName,
		IRequest $request,
		private IL10N $l,
		private TrustedServers $trustedServers,
		private LoggerInterface $logger,
	) {
		parent::__construct($AppName, $request);
	}


	/**
	 * Add server to the list of trusted Nextcloud servers
	 *
	 * @param string $url The URL of the server to add
	 * @return DataResponse<Http::STATUS_OK, array{id: int, message: string, url: string}, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_CONFLICT, array{message: string}, array{}>
	 *
	 * 200: Server added successfully
	 * 404: Server not found at the given URL
	 * 409: Server is already in the list of trusted servers
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'POST', url: '/trusted-servers')]
	public function addServer(string $url): DataResponse {
		$this->checkServer(trim($url));

		// Add the server to the list of trusted servers, all is well
		$id = $this->trustedServers->addServer(trim($url));
		return new DataResponse([
			'url' => $url,
			'id' => $id,
			'message' => $this->l->t('Added to the list of trusted servers')
		]);
	}

	/**
	 * Add server to the list of trusted Nextcloud servers
	 *
	 * @param int $id The ID of the trusted server to remove
	 * @return DataResponse<Http::STATUS_OK, array{id: int}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: Server removed successfully
	 * 404: Server not found at the given ID
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'DELETE', url: '/trusted-servers/{id}', requirements: ['id' => '\d+'])]
	public function removeServer(int $id): DataResponse {
		try {
			$this->trustedServers->getServer($id);
		} catch (\Exception $e) {
			throw new OCSNotFoundException($this->l->t('No server found with ID: %s', [$id]));
		}

		try {
			$this->trustedServers->removeServer($id);
			return new DataResponse(['id' => $id]);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['e' => $e]);
			throw new OCSException($this->l->t('Could not remove server'), Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * List all trusted servers
	 *
	 * @return DataResponse<Http::STATUS_OK, list<array{id: int, status: int, url: string}>, array{}>
	 *
	 * 200: List of trusted servers
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	#[ApiRoute(verb: 'GET', url: '/trusted-servers')]
	public function getServers(): DataResponse {
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
		return new DataResponse($servers);
	}


	/**
	 * Check if the server should be added to the list of trusted servers or not.
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	protected function checkServer(string $url): void {
		if ($this->trustedServers->isTrustedServer($url) === true) {
			throw new OCSException($this->l->t('Server is already in the list of trusted servers.'), Http::STATUS_CONFLICT);
		}

		if ($this->trustedServers->isNextcloudServer($url) === false) {
			throw new OCSNotFoundException($this->l->t('No server to federate with found'));
		}
	}
}
