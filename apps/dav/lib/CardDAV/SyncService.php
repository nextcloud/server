<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

use OC\Accounts\AccountManager;
use OCP\AppFramework\Http;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service;
use Sabre\HTTP\ClientHttpException;
use Sabre\VObject\Reader;

class SyncService {
	private CardDavBackend $backend;
	private IUserManager $userManager;
	private LoggerInterface $logger;
	private ?array $localSystemAddressBook = null;
	private Converter $converter;
	protected string $certPath;

	public function __construct(CardDavBackend $backend,
								IUserManager $userManager,
								LoggerInterface $logger,
								Converter $converter) {
		$this->backend = $backend;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->converter = $converter;
		$this->certPath = '';
	}

	/**
	 * @throws \Exception
	 */
	public function syncRemoteAddressBook(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken, string $targetBookHash, string $targetPrincipal, array $targetProperties): string {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookHash, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		try {
			$response = $this->requestSyncReport($url, $userName, $addressBookUrl, $sharedSecret, $syncToken);
		} catch (ClientHttpException $ex) {
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
				$existingCard = $this->backend->getCard($addressBookId, $cardUri);
				if ($existingCard === false) {
					$this->backend->createCard($addressBookId, $cardUri, $vCard['body']);
				} else {
					$this->backend->updateCard($addressBookId, $cardUri, $vCard['body']);
				}
			} else {
				$this->backend->deleteCard($addressBookId, $cardUri);
			}
		}

		return $response['token'];
	}

	/**
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureSystemAddressBookExists(string $principal, string $uri, array $properties): ?array {
		$book = $this->backend->getAddressBooksByUri($principal, $uri);
		if (!is_null($book)) {
			return $book;
		}
		// FIXME This might break in clustered DB setup
		$this->backend->createAddressBook($principal, $uri, $properties);

		return $this->backend->getAddressBooksByUri($principal, $uri);
	}

	/**
	 * Check if there is a valid certPath we should use
	 */
	protected function getCertPath(): string {

		// we already have a valid certPath
		if ($this->certPath !== '') {
			return $this->certPath;
		}

		$certManager = \OC::$server->getCertificateManager();
		$certPath = $certManager->getAbsoluteBundlePath();
		if (file_exists($certPath)) {
			$this->certPath = $certPath;
		}

		return $this->certPath;
	}

	protected function getClient(string $url, string $userName, string $sharedSecret): Client {
		$settings = [
			'baseUri' => $url . '/',
			'userName' => $userName,
			'password' => $sharedSecret,
		];
		$client = new Client($settings);
		$certPath = $this->getCertPath();
		$client->setThrowExceptions(true);

		if ($certPath !== '' && strpos($url, 'http://') !== 0) {
			$client->addCurlSetting(CURLOPT_CAINFO, $this->certPath);
		}

		return $client;
	}

	protected function requestSyncReport(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken): array {
		$client = $this->getClient($url, $userName, $sharedSecret);

		$body = $this->buildSyncCollectionRequestBody($syncToken);

		$response = $client->request('REPORT', $addressBookUrl, $body, [
			'Content-Type' => 'application/xml'
		]);

		return $this->parseMultiStatus($response['body']);
	}

	protected function download(string $url, string $userName, string $sharedSecret, string $resourcePath): array {
		$client = $this->getClient($url, $userName, $sharedSecret);
		return $client->request('GET', $resourcePath);
	}

	private function buildSyncCollectionRequestBody(?string $syncToken): string {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:sync-collection');
		$sync = $dom->createElement('d:sync-token', $syncToken);
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
	 * @param string $body
	 * @return array
	 * @throws \Sabre\Xml\ParseException
	 */
	private function parseMultiStatus($body) {
		$xml = new Service();

		/** @var MultiStatus $multiStatus */
		$multiStatus = $xml->expect('{DAV:}multistatus', $body);

		$result = [];
		foreach ($multiStatus->getResponses() as $response) {
			$result[$response->getHref()] = $response->getResponseProperties();
		}

		return ['response' => $result, 'token' => $multiStatus->getSyncToken()];
	}

	/**
	 * @param IUser $user
	 */
	public function updateUser(IUser $user) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$addressBookId = $systemAddressBook['id'];
		$name = $user->getBackendClassName();
		$userId = $user->getUID();

		$cardId = "$name:$userId.vcf";
		if ($user->isEnabled()) {
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
			$name = $userOrCardId->getBackendClassName();
			$userId = $userOrCardId->getUID();

			$userOrCardId = "$name:$userId.vcf";
		}
		$this->backend->deleteCard($systemAddressBook['id'], $userOrCardId);
	}

	/**
	 * @return array|null
	 */
	public function getLocalSystemAddressBook() {
		if (is_null($this->localSystemAddressBook)) {
			$systemPrincipal = "principals/system/system";
			$this->localSystemAddressBook = $this->ensureSystemAddressBookExists($systemPrincipal, 'system', [
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
			]);
		}

		return $this->localSystemAddressBook;
	}

	public function syncInstance(\Closure $progressCallback = null) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$this->userManager->callForAllUsers(function ($user) use ($systemAddressBook, $progressCallback) {
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
}
