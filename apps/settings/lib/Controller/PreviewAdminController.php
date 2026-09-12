<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OC\Preview\PreviewAdminConfig;
use OCA\Settings\Settings\Admin\Previews;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
class PreviewAdminController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreviewAdminConfig $previewAdminConfig,
		private IClientService $clientService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Update preview administration settings
	 *
	 * @param array<string, mixed> $settings Preview settings to persist
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>
	 * @throws OCSBadRequestException Invalid settings payload
	 *
	 * 200: Settings saved
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/admin/previews')]
	public function update(array $settings): DataResponse {
		try {
			$this->previewAdminConfig->setSettings($settings);
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		}

		return new DataResponse($this->previewAdminConfig->getSettings());
	}

	/**
	 * Test connectivity to an Imaginary preview service
	 *
	 * @param string|null $url Imaginary base URL
	 * @param string|null $key Optional Imaginary API key
	 * @return DataResponse<Http::STATUS_OK, array{status: string, httpCode?: int, error?: string}, array{}>
	 * @throws OCSBadRequestException Invalid URL
	 *
	 * 200: Connection test completed
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[ApiRoute(verb: 'POST', url: '/api/admin/previews/imaginary/test')]
	public function testImaginary(?string $url = null, ?string $key = null): DataResponse {
		try {
			$target = $this->previewAdminConfig->validateImaginaryUrl($url ?? '');
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage());
		}

		if ($target === '') {
			return new DataResponse([
				'status' => 'unconfigured',
			]);
		}

		try {
			$client = $this->clientService->newClient();
			$options = [
				'timeout' => 3,
				'connect_timeout' => 3,
				'nextcloud' => ['allow_local_address' => true],
			];
			if (is_string($key) && $key !== '') {
				$options['query'] = ['key' => $key];
			}
			$response = $client->get($target, $options);
			$statusCode = $response->getStatusCode();
			$reachable = $statusCode >= 200 && $statusCode < 500;
			return new DataResponse([
				'status' => $reachable ? 'reachable' : 'unreachable',
				'httpCode' => $statusCode,
			]);
		} catch (\Throwable $e) {
			$this->logger->info('Imaginary connection test failed', [
				'exception' => $e,
			]);
			return new DataResponse([
				'status' => 'unreachable',
				'error' => $e->getMessage(),
			]);
		}
	}
}
