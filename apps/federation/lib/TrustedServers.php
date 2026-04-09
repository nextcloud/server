<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation;

use OCA\Federation\BackgroundJob\RequestSharedSecret;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class TrustedServers {

	/** after a user list was exchanged at least once successfully */
	public const STATUS_OK = 1;
	/** waiting for shared secret or initial user list exchange */
	public const STATUS_PENDING = 2;
	/** something went wrong, misconfigured server, software bug,... user interaction needed */
	public const STATUS_FAILURE = 3;
	/** remote server revoked access */
	public const STATUS_ACCESS_REVOKED = 4;

	/** @var list<array{id: int, url: string, url_hash: string, shared_secret: ?string, status: int, sync_token: ?string}>|null */
	private ?array $trustedServersCache = null;

	public function __construct(
		private DbHandler $dbHandler,
		private IClientService $httpClientService,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private ISecureRandom $secureRandom,
		private IConfig $config,
		private IEventDispatcher $dispatcher,
		private ITimeFactory $timeFactory,
	) {
	}

	/**
	 * Add server to the list of trusted servers
	 */
	public function addServer(string $url): int {
		$url = $this->updateProtocol($url);
		$result = $this->dbHandler->addServer($url);
		if ($result) {
			$token = $this->secureRandom->generate(16);
			$this->dbHandler->addToken($url, $token);
			$this->jobList->add(
				RequestSharedSecret::class,
				[
					'url' => $url,
					'token' => $token,
					'created' => $this->timeFactory->getTime()
				]
			);
		}

		return $result;
	}

	/**
	 * Get shared secret for the given server
	 */
	public function getSharedSecret(string $url): string {
		return $this->dbHandler->getSharedSecret($url);
	}

	/**
	 * Add shared secret for the given server
	 */
	public function addSharedSecret(string $url, string $sharedSecret): void {
		$this->dbHandler->addSharedSecret($url, $sharedSecret);
	}

	/**
	 * Remove server from the list of trusted servers
	 */
	public function removeServer(int $id): void {
		$server = $this->dbHandler->getServerById($id);
		$this->dbHandler->removeServer($id);
		$this->dispatcher->dispatchTyped(new TrustedServerRemovedEvent($server['url_hash'], $server['url']));

	}

	/**
	 * Get all trusted servers
	 *
	 * @return list<array{id: int, url: string, url_hash: string, shared_secret: ?string, status: int, sync_token: ?string}>
	 * @throws \Exception
	 */
	public function getServers(): ?array {
		if ($this->trustedServersCache === null) {
			$this->trustedServersCache = $this->dbHandler->getAllServer();
		}
		return $this->trustedServersCache;
	}

	/**
	 * Get a trusted server
	 *
	 * @return array{id: int, url: string, url_hash: string, shared_secret: ?string, status: int, sync_token: ?string}
	 * @throws Exception
	 */
	public function getServer(int $id): ?array {
		if ($this->trustedServersCache === null) {
			$this->trustedServersCache = $this->dbHandler->getAllServer();
		}

		foreach ($this->trustedServersCache as $server) {
			if ($server['id'] === $id) {
				return $server;
			}
		}

		throw new \Exception('No server found with ID: ' . $id);
	}

	/**
	 * Check if given server is a trusted Nextcloud server
	 */
	public function isTrustedServer(string $url): bool {
		return $this->dbHandler->serverExists($url);
	}

	/**
	 * Set server status
	 */
	public function setServerStatus(string $url, int $status): void {
		$this->dbHandler->setServerStatus($url, $status);
	}

	/**
	 * Get server status
	 */
	public function getServerStatus(string $url): int {
		return $this->dbHandler->getServerStatus($url);
	}

	/**
	 * Check if URL point to a ownCloud/Nextcloud server
	 */
	public function isNextcloudServer(string $url): bool {
		$isValidNextcloud = false;
		$client = $this->httpClientService->newClient();
		try {
			$result = $client->get(
				$url . '/status.php',
				[
					'timeout' => 3,
					'connect_timeout' => 3,
					'verify' => !$this->config->getSystemValue('sharing.federation.allowSelfSignedCertificates', false),
				]
			);
			if ($result->getStatusCode() === Http::STATUS_OK) {
				$body = $result->getBody();
				if (is_resource($body)) {
					$body = stream_get_contents($body) ?: '';
				}
				$isValidNextcloud = $this->checkNextcloudVersion($body);
			}
		} catch (\Exception $e) {
			$this->logger->error('No Nextcloud server.', [
				'exception' => $e,
			]);
			return false;
		}

		return $isValidNextcloud;
	}

	/**
	 * Check if ownCloud/Nextcloud version is >= 9.0
	 * @throws HintException
	 */
	protected function checkNextcloudVersion(string $status): bool {
		$decoded = json_decode($status, true);
		if (!empty($decoded) && isset($decoded['version'])) {
			if (!version_compare($decoded['version'], '9.0.0', '>=')) {
				throw new HintException('Remote server version is too low. 9.0 is required.');
			}
			return true;
		}
		return false;
	}

	/**
	 * Check if the URL contain a protocol, if not add https
	 */
	protected function updateProtocol(string $url): string {
		if (
			strpos($url, 'https://') === 0
			|| strpos($url, 'http://') === 0
		) {
			return $url;
		}

		return 'https://' . $url;
	}
}
