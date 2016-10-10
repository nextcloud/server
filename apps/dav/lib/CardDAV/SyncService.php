<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CardDAV;

use OCP\AppFramework\Http;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service;
use Sabre\HTTP\ClientHttpException;
use Sabre\VObject\Reader;

class SyncService {

	/** @var CardDavBackend */
	private $backend;

	/** @var IUserManager */
	private $userManager;

	/** @var ILogger */
	private $logger;

	/** @var array */
	private $localSystemAddressBook;

	public function __construct(CardDavBackend $backend, IUserManager $userManager, ILogger $logger) {
		$this->backend = $backend;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * @param string $url
	 * @param string $userName
	 * @param string $sharedSecret
	 * @param string $syncToken
	 * @param int $targetBookId
	 * @param string $targetPrincipal
	 * @param array $targetProperties
	 * @return string
	 * @throws \Exception
	 */
	public function syncRemoteAddressBook($url, $userName, $sharedSecret, $syncToken, $targetBookId, $targetPrincipal, $targetProperties) {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookId, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		try {
			$response = $this->requestSyncReport($url, $userName, $sharedSecret, $syncToken);
		} catch (ClientHttpException $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// remote server revoked access to the address book, remove it
				$this->backend->deleteAddressBook($addressBookId);
				$this->logger->info('Authorization failed, remove address book: ' . $url, ['app' => 'dav']);
				throw $ex;
			}
		}

		// 3. apply changes
		// TODO: use multi-get for download
		foreach ($response['response'] as $resource => $status) {
			$cardUri = basename($resource);
			if (isset($status[200])) {
				$vCard = $this->download($url, $sharedSecret, $resource);
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
	 * @param string $principal
	 * @param string $id
	 * @param array $properties
	 * @return array|null
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureSystemAddressBookExists($principal, $id, $properties) {
		$book = $this->backend->getAddressBooksByUri($principal, $id);
		if (!is_null($book)) {
			return $book;
		}
		$this->backend->createAddressBook($principal, $id, $properties);

		return $this->backend->getAddressBooksByUri($principal, $id);
	}

	/**
	 * @param string $url
	 * @param string $userName
	 * @param string $sharedSecret
	 * @param string $syncToken
	 * @return array
	 */
	protected function requestSyncReport($url, $userName, $sharedSecret, $syncToken) {
		$settings = [
			'baseUri' => $url . '/',
			'userName' => $userName,
			'password' => $sharedSecret,
		];
		$client = new Client($settings);
		$client->setThrowExceptions(true);

		$addressBookUrl = "remote.php/dav/addressbooks/system/system/system";
		$body = $this->buildSyncCollectionRequestBody($syncToken);

		$response = $client->request('REPORT', $addressBookUrl, $body, [
			'Content-Type' => 'application/xml'
		]);

		$result = $this->parseMultiStatus($response['body']);

		return $result;
	}

	/**
	 * @param string $url
	 * @param string $sharedSecret
	 * @param string $resourcePath
	 * @return array
	 */
	protected function download($url, $sharedSecret, $resourcePath) {
		$settings = [
			'baseUri' => $url,
			'userName' => 'system',
			'password' => $sharedSecret,
		];
		$client = new Client($settings);
		$client->setThrowExceptions(true);

		$response = $client->request('GET', $resourcePath);
		return $response;
	}

	/**
	 * @param string|null $syncToken
	 * @return string
	 */
	private function buildSyncCollectionRequestBody($syncToken) {

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
		$body = $dom->saveXML();

		return $body;
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
	public function updateUser($user) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$addressBookId = $systemAddressBook['id'];
		$converter = new Converter();
		$name = $user->getBackendClassName();
		$userId = $user->getUID();

		$cardId = "$name:$userId.vcf";
		$card = $this->backend->getCard($addressBookId, $cardId);
		if ($card === false) {
			$vCard = $converter->createCardFromUser($user);
			$this->backend->createCard($addressBookId, $cardId, $vCard->serialize());
		} else {
			$vCard = Reader::read($card['carddata']);
			if ($converter->updateCard($vCard, $user)) {
				$this->backend->updateCard($addressBookId, $cardId, $vCard->serialize());
			}
		}
	}

	/**
	 * @param IUser|string $userOrCardId
	 */
	public function deleteUser($userOrCardId) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		if ($userOrCardId instanceof IUser){
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
		$this->userManager->callForAllUsers(function($user) use ($systemAddressBook, $progressCallback) {
			$this->updateUser($user);
			// avatar fetching sets up FS, need to clear again
			\OC_Util::tearDownFS();
			if (!is_null($progressCallback)) {
				$progressCallback();
			}
		});

		// remove no longer existing
		$allCards = $this->backend->getCards($systemAddressBook['id']);
		foreach($allCards as $card) {
			$vCard = Reader::read($card['carddata']);
			$uid = $vCard->UID->getValue();
			// load backend and see if user exists
			if (!$this->userManager->userExists($uid)) {
				$this->deleteUser($card['uri']);
			}
		}
	}


}
