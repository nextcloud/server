<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\BackgroundJobs;

use OCA\AppAPI\PublicFunctions;
use OCA\WebhookListeners\Db\AuthMethod;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class WebhookCall extends QueuedJob {
	public function __construct(
		private IClientService $clientService,
		private ICertificateManager $certificateManager,
		private WebhookListenerMapper $mapper,
		private LoggerInterface $logger,
		private IAppManager $appManager,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @param array $argument
	 */
	protected function run($argument): void {
		[$data, $webhookId] = $argument;
		$webhookListener = $this->mapper->getById($webhookId);
		$client = $this->clientService->newClient();
		$options = [
			'verify' => $this->certificateManager->getAbsoluteBundlePath(),
			'headers' => $webhookListener->getHeaders() ?? [],
			'body' => json_encode($data),
		];
		try {
			switch ($webhookListener->getAuthMethodEnum()) {
				case AuthMethod::None:
					break;
				case AuthMethod::Header:
					$authHeaders = $webhookListener->getAuthDataClear();
					$options['headers'] = array_merge($options['headers'], $authHeaders);
					break;
			}
			$webhookUri = $webhookListener->getUri();
			$exAppId = $webhookListener->getAppId();
			if ($exAppId !== null && str_starts_with($webhookUri, '/')) {
				// ExApp is awaiting a direct request to itself using AppAPI
				if (!$this->appManager->isInstalled('app_api')) {
					throw new RuntimeException('AppAPI is disabled or not installed.');
				}
				try {
					$appApiFunctions = Server::get(PublicFunctions::class);
				} catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
					throw new RuntimeException('Could not get AppAPI public functions.');
				}
				$exApp = $appApiFunctions->getExApp($exAppId);
				if ($exApp === null) {
					throw new RuntimeException('ExApp ' . $exAppId . ' is missing.');
				} elseif (!$exApp['enabled']) {
					throw new RuntimeException('ExApp ' . $exAppId . ' is disabled.');
				}
				$response = $appApiFunctions->exAppRequest($exAppId, $webhookUri, $webhookListener->getUserId(), $webhookListener->getHttpMethod(), [], $options);
				if (is_array($response) && isset($response['error'])) {
					throw new RuntimeException(sprintf('Error during request to ExApp(%s): %s', $exAppId, $response['error']));
				}
			} else {
				$response = $client->request($webhookListener->getHttpMethod(), $webhookUri, $options);
			}
			$statusCode = $response->getStatusCode();
			if ($statusCode >= 200 && $statusCode < 300) {
				$this->logger->debug('Webhook returned status code ' . $statusCode, ['body' => $response->getBody()]);
			} else {
				$this->logger->warning('Webhook(' . $webhookId . ') returned unexpected status code ' . $statusCode, ['body' => $response->getBody()]);
			}
		} catch (\Exception $e) {
			$this->logger->error('Webhook(' . $webhookId . ') call failed: ' . $e->getMessage(), ['exception' => $e]);
		}
	}
}
