<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use Sabre\DAV\Client;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service;

class SyncService {

	/** @var CardDavBackend */
	private $backend;

	public function __construct(CardDavBackend $backend) {
		$this->backend = $backend;
	}

	public function syncRemoteAddressBook($url, $userName, $sharedSecret, $syncToken, $targetBookId, $targetPrincipal, $targetProperties) {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookId, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		$response = $this->requestSyncReport($url, $userName, $sharedSecret, $syncToken);

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

	protected function ensureSystemAddressBookExists($principal, $id, $properties) {
		$book = $this->backend->getAddressBooksByUri($id);
		if (!is_null($book)) {
			return $book;
		}
		$this->backend->createAddressBook($principal, $id, $properties);

		return $this->backend->getAddressBooksByUri($id);
	}

	/**
	 * @param string $url
	 * @param string $userName
	 * @param string $sharedSecret
	 * @param string $syncToken
	 * @return array
	 */
	private function requestSyncReport($url, $userName, $sharedSecret, $syncToken) {
		$settings = [
			'baseUri' => $url,
			'userName' => $userName,
			'password' => $sharedSecret,
		];
		$client = new Client($settings);
		$client->setThrowExceptions(true);

		$addressBookUrl = "/remote.php/dav/addressbooks/system/system/system";
		$body = $this->buildSyncCollectionRequestBody($syncToken);

		$response = $client->request('REPORT', $addressBookUrl, $body, [
			'Content-Type' => 'application/xml'
		]);

//		if ((int)$response->getStatus() >= 400) {
//			throw new Exception('HTTP error: ' . $response->getStatus());
//		}

		$result = $this->parseMultiStatus($response['body']);

		return $result;
	}

	private function download($url, $sharedSecret, $changeSet) {
		$settings = [
			'baseUri' => $url,
			'userName' => 'system',
			'password' => $sharedSecret,
		];
		$client = new Client($settings);
		$client->setThrowExceptions(true);

		$response = $client->request('GET', $changeSet);
		return $response;
	}

	function buildSyncCollectionRequestBody($synToken) {

		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:sync-collection');
		$sync = $dom->createElement('d:sync-token', $synToken);
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


}
