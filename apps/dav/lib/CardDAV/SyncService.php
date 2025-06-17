<?php


/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Http;
use OCP\DB\Exception;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service;
use Sabre\VObject\Reader;
use Sabre\Xml\ParseException;
use function is_null;

class SyncService {

	use TTransactional;
	private ?array $localSystemAddressBook = null;
	protected string $certPath;

	public function __construct(
		private CardDavBackend $backend,
		private IUserManager $userManager,
		private IDBConnection $dbConnection,
		private LoggerInterface $logger,
		private Converter $converter,
		private IClientService $clientService,
		private IConfig $config,
	) {
		$this->certPath = '';
	}

	/**
	 * @psalm-return list{0: ?string, 1: boolean}
	 * @throws \Exception
	 */
	public function syncRemoteAddressBook(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken, string $targetBookHash, string $targetPrincipal, array $targetProperties): array {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookHash, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		try {
			$response = $this->requestSyncReport($url, $userName, $addressBookUrl, $sharedSecret, $syncToken);
		} catch (ClientExceptionInterface $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// remote server revoked access to the address book, remove it
				$this->backend->deleteAddressBook($addressBookId);
				$this->logger->error('Authorization failed, remove address book: ' . $url, ['app' => 'dav']);
				throw $ex;
			}
			$this->logger->error('Client exception:', ['app' => 'dav', 'exception' => $ex]);
			throw $ex;
		}

		// 3. apply changes
		// TODO: use multi-get for download
		foreach ($response['response'] as $resource => $status) {
			$cardUri = basename($resource);
			if (isset($status[200])) {
				$vCard = $this->download($url, $userName, $sharedSecret, $resource);
				$this->atomic(function () use ($addressBookId, $cardUri, $vCard): void {
					$existingCard = $this->backend->getCard($addressBookId, $cardUri);
					if ($existingCard === false) {
						$this->backend->createCard($addressBookId, $cardUri, $vCard);
					} else {
						$this->backend->updateCard($addressBookId, $cardUri, $vCard);
					}
				}, $this->dbConnection);
			} else {
				$this->backend->deleteCard($addressBookId, $cardUri);
			}
		}

		return [
			$response['token'],
			$response['truncated'],
		];
	}

	/**
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureSystemAddressBookExists(string $principal, string $uri, array $properties): ?array {
		try {
			return $this->atomic(function () use ($principal, $uri, $properties) {
				$book = $this->backend->getAddressBooksByUri($principal, $uri);
				if (!is_null($book)) {
					return $book;
				}
				$this->backend->createAddressBook($principal, $uri, $properties);

				return $this->backend->getAddressBooksByUri($principal, $uri);
			}, $this->dbConnection);
		} catch (Exception $e) {
			// READ COMMITTED doesn't prevent a nonrepeatable read above, so
			// two processes might create an address book here. Ignore our
			// failure and continue loading the entry written by the other process
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}

			// If this fails we might have hit a replication node that does not
			// have the row written in the other process.
			// TODO: find an elegant way to handle this
			$ab = $this->backend->getAddressBooksByUri($principal, $uri);
			if ($ab === null) {
				throw new Exception('Could not create system address book', $e->getCode(), $e);
			}
			return $ab;
		}
	}

	public function ensureLocalSystemAddressBookExists(): ?array {
		return $this->ensureSystemAddressBookExists('principals/system/system', 'system', [
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
		]);
	}

	private function prepareUri(string $host, string $path): string {
		/*
		 * The trailing slash is important for merging the uris.
		 *
		 * $host is stored in oc_trusted_servers.url and usually without a trailing slash.
		 *
		 * Example for a report request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = 'remote.php/dav/addressbooks/system/system/system'
		 *
		 * Without the trailing slash, the webroot is missing:
		 * https://server.internal/remote.php/dav/addressbooks/system/system/system
		 *
		 * Example for a download request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = '/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf'
		 *
		 * The response from the remote usually contains the webroot already and must be normalized to:
		 * https://server.internal/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf
		 */
		$host = rtrim($host, '/') . '/';

		$uri = \GuzzleHttp\Psr7\UriResolver::resolve(
			\GuzzleHttp\Psr7\Utils::uriFor($host),
			\GuzzleHttp\Psr7\Utils::uriFor($path)
		);

		return (string)$uri;
	}

	/**
	 * @return array{response: array<string, array<array-key, mixed>>, token: ?string, truncated: bool}
	 * @throws ClientExceptionInterface
	 * @throws ParseException
	 */
	protected function requestSyncReport(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken): array {
		$client = $this->clientService->newClient();
		$uri = $this->prepareUri($url, $addressBookUrl);

		$options = [
			'auth' => [$userName, $sharedSecret],
			'body' => $this->buildSyncCollectionRequestBody($syncToken),
			'headers' => ['Content-Type' => 'application/xml'],
			'timeout' => $this->config->getSystemValueInt('carddav_sync_request_timeout', IClient::DEFAULT_REQUEST_TIMEOUT),
			'verify' => !$this->config->getSystemValue('sharing.federation.allowSelfSignedCertificates', false),
		];

		$response = $client->request(
			'REPORT',
			$uri,
			$options
		);

		$body = $response->getBody();
		assert(is_string($body));

		return $this->parseMultiStatus($body, $addressBookUrl);
	}

	protected function download(string $url, string $userName, string $sharedSecret, string $resourcePath): string {
		$client = $this->clientService->newClient();
		$uri = $this->prepareUri($url, $resourcePath);

		$options = [
			'auth' => [$userName, $sharedSecret],
			'verify' => !$this->config->getSystemValue('sharing.federation.allowSelfSignedCertificates', false),
		];

		$response = $client->get(
			$uri,
			$options
		);

		return (string)$response->getBody();
	}

	private function buildSyncCollectionRequestBody(?string $syncToken): string {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:sync-collection');
		$sync = $dom->createElement('d:sync-token', $syncToken ?? '');
		$prop = $dom->createElement('d:prop');
		$cont = $dom->createElement('d:getcontenttype');
		$etag = $dom->createElement('d:getetag');

		$prop->appendChild($cont);
		$prop->appendChild($etag);
		$root->appendChild($sync);
		$root->appendChild($prop);
		$dom->appendChild($root);
		return $dom->saveXML();
	}

	/**
	 * @return array{response: array<string, array<array-key, mixed>>, token: ?string, truncated: bool}
	 * @throws ParseException
	 */
	private function parseMultiStatus(string $body, string $addressBookUrl): array {
		/** @var MultiStatus $multiStatus */
		$multiStatus = (new Service())->expect('{DAV:}multistatus', $body);

		$result = [];
		$truncated = false;

		foreach ($multiStatus->getResponses() as $response) {
			$href = $response->getHref();
			if ($response->getHttpStatus() === '507' && $this->isResponseForRequestUri($href, $addressBookUrl)) {
				$truncated = true;
			} else {
				$result[$response->getHref()] = $response->getResponseProperties();
			}
		}

		return ['response' => $result, 'token' => $multiStatus->getSyncToken(), 'truncated' => $truncated];
	}

	/**
	 * Determines whether the provided response URI corresponds to the given request URI.
	 */
	private function isResponseForRequestUri(string $responseUri, string $requestUri): bool {
		/*
		 * Example response uri:
		 *
		 * /remote.php/dav/addressbooks/system/system/system/
		 * /cloud/remote.php/dav/addressbooks/system/system/system/ (when installed in a subdirectory)
		 *
		 * Example request uri:
		 *
		 * remote.php/dav/addressbooks/system/system/system
		 *
		 * References:
		 * https://github.com/nextcloud/3rdparty/blob/e0a509739b13820f0a62ff9cad5d0fede00e76ee/sabre/dav/lib/DAV/Sync/Plugin.php#L172-L174
		 * https://github.com/nextcloud/server/blob/b40acb34a39592070d8455eb91c5364c07928c50/apps/federation/lib/SyncFederationAddressBooks.php#L41
		 */
		return str_ends_with(
			rtrim($responseUri, '/'),
			rtrim($requestUri, '/')
		);
	}

	/**
	 * @param IUser $user
	 */
	public function updateUser(IUser $user): void {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$addressBookId = $systemAddressBook['id'];

		$cardId = self::getCardUri($user);
		if ($user->isEnabled()) {
			$this->atomic(function () use ($addressBookId, $cardId, $user): void {
				$card = $this->backend->getCard($addressBookId, $cardId);
				if ($card === false) {
					$vCard = $this->converter->createCardFromUser($user);
					if ($vCard !== null) {
						$this->backend->createCard($addressBookId, $cardId, $vCard->serialize(), false);
					}
				} else {
					$vCard = $this->converter->createCardFromUser($user);
					if (is_null($vCard)) {
						$this->backend->deleteCard($addressBookId, $cardId);
					} else {
						$this->backend->updateCard($addressBookId, $cardId, $vCard->serialize());
					}
				}
			}, $this->dbConnection);
		} else {
			$this->backend->deleteCard($addressBookId, $cardId);
		}
	}

	/**
	 * @param IUser|string $userOrCardId
	 */
	public function deleteUser($userOrCardId) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		if ($userOrCardId instanceof IUser) {
			$userOrCardId = self::getCardUri($userOrCardId);
		}
		$this->backend->deleteCard($systemAddressBook['id'], $userOrCardId);
	}

	/**
	 * @return array|null
	 */
	public function getLocalSystemAddressBook() {
		if (is_null($this->localSystemAddressBook)) {
			$this->localSystemAddressBook = $this->ensureLocalSystemAddressBookExists();
		}

		return $this->localSystemAddressBook;
	}

	/**
	 * @return void
	 */
	public function syncInstance(?\Closure $progressCallback = null) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$this->userManager->callForAllUsers(function ($user) use ($systemAddressBook, $progressCallback): void {
			$this->updateUser($user);
			if (!is_null($progressCallback)) {
				$progressCallback();
			}
		});

		// remove no longer existing
		$allCards = $this->backend->getCards($systemAddressBook['id']);
		foreach ($allCards as $card) {
			$vCard = Reader::read($card['carddata']);
			$uid = $vCard->UID->getValue();
			// load backend and see if user exists
			if (!$this->userManager->userExists($uid)) {
				$this->deleteUser($card['uri']);
			}
		}
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public static function getCardUri(IUser $user): string {
		return $user->getBackendClassName() . ':' . $user->getUID() . '.vcf';
	}
}
