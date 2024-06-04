<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\Capabilities\ICapability;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller about the endpoint /ocm-provider/
 *
 * @since 28.0.0
 */
class OCMController extends Controller {
	public function __construct(
		IRequest $request,
		private IConfig $config,
		private LoggerInterface $logger
	) {
		parent::__construct('core', $request);
	}

	/**
	 * generate a OCMProvider with local data and send it as DataResponse.
	 * This replaces the old PHP file ocm-provider/index.php
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @psalm-suppress MoreSpecificReturnType
	 * @psalm-suppress LessSpecificReturnStatement
	 * @return DataResponse<Http::STATUS_OK, array{enabled: bool, apiVersion: string, endPoint: string, resourceTypes: array{name: string, shareTypes: string[], protocols: array{webdav: string}}[]}, array{X-NEXTCLOUD-OCM-PROVIDERS: true, Content-Type: 'application/json'}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: OCM Provider details returned
	 * 500: OCM not supported
	 */
	#[FrontpageRoute(verb: 'GET', url: '/ocm-provider/')]
	public function discovery(): DataResponse {
		try {
			$cap = Server::get(
				$this->config->getAppValue(
					'core',
					'ocm_providers',
					'\OCA\CloudFederationAPI\Capabilities'
				)
			);

			if (!($cap instanceof ICapability)) {
				throw new Exception('loaded class does not implements OCP\Capabilities\ICapability');
			}

			return new DataResponse(
				$cap->getCapabilities()['ocm'] ?? ['enabled' => false],
				Http::STATUS_OK,
				[
					'X-NEXTCLOUD-OCM-PROVIDERS' => true,
					'Content-Type' => 'application/json'
				]
			);
		} catch (ContainerExceptionInterface|Exception $e) {
			$this->logger->error('issue during OCM discovery request', ['exception' => $e]);

			return new DataResponse(
				['message' => '/ocm-provider/ not supported'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}
