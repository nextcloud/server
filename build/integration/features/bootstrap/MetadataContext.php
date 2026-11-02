<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Sabre\DAV\Client as SClient;

require __DIR__ . '/autoload.php';

class MetadataContext implements Context {
	private string $davPath = '/remote.php/dav';

	public function __construct(
		private string $baseUrl,
		private array $admin,
		private string $regular_user_password,
	) {
		// in case of ci deployment we take the server url from the environment
		$testServerUrl = getenv('TEST_SERVER_URL');
		if ($testServerUrl !== false) {
			$this->baseUrl = substr($testServerUrl, 0, -5);
		}
	}

	#[When('User :user sets the :metadataKey prop with value :metadataValue on :fileName')]
	public function userSetsProp(string $user, string $metadataKey, string $metadataValue, string $fileName) {
		$client = new SClient([
			'baseUri' => $this->baseUrl,
			'userName' => $user,
			'password' => '123456',
			'authType' => SClient::AUTH_BASIC,
		]);

		$body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.com/ns">
  <d:set>
   <d:prop>
      <nc:' . $metadataKey . '>' . $metadataValue . '</nc:' . $metadataKey . '>
    </d:prop>
  </d:set>
</d:propertyupdate>';

		$davUrl = $this->getDavUrl($user, $fileName);
		$client->request('PROPPATCH', $this->baseUrl . $davUrl, $body);
	}

	#[Given('User :user links :fileName as live photo to :peerName')]
	public function userLinksLivePhoto(string $user, string $fileName, string $peerName) {
		$client = new SClient([
			'baseUri' => $this->baseUrl,
			'userName' => $user,
			'password' => '123456',
			'authType' => SClient::AUTH_BASIC,
		]);

		$response = $client->propFind($this->baseUrl . $this->getDavUrl($user, $peerName), ['{http://owncloud.org/ns}fileid']);
		$peerId = $response['{http://owncloud.org/ns}fileid'];

		$response = $client->propPatch($this->baseUrl . $this->getDavUrl($user, $fileName), [
			'{http://nextcloud.org/ns}metadata-files-live-photo' => (string)$peerId,
		]);
		Assert::assertTrue($response);
	}

	#[When('User :user deletes the :metadataKey prop on :fileName')]
	public function userDeletesProp(string $user, string $metadataKey, string $fileName) {
		$client = new SClient([
			'baseUri' => $this->baseUrl,
			'userName' => $user,
			'password' => '123456',
			'authType' => SClient::AUTH_BASIC,
		]);

		$body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.com/ns">
  <d:remove>
   <d:prop>
      <nc:' . $metadataKey . '></nc:' . $metadataKey . '>
    </d:prop>
  </d:remove>
</d:propertyupdate>';

		$davUrl = $this->getDavUrl($user, $fileName);
		$client->request('PROPPATCH', $this->baseUrl . $davUrl, $body);
	}

	#[Then('User :user should see the prop :metadataKey equal to :metadataValue for file :fileName')]
	public function checkPropForFile(string $user, string $metadataKey, string $metadataValue, string $fileName) {
		$client = new SClient([
			'baseUri' => $this->baseUrl,
			'userName' => $user,
			'password' => '123456',
			'authType' => SClient::AUTH_BASIC,
		]);

		$body = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.com/ns">
   <d:prop>
      <nc:' . $metadataKey . '></nc:' . $metadataKey . '>
    </d:prop>
</d:propfind>';

		$davUrl = $this->getDavUrl($user, $fileName);
		$response = $client->request('PROPFIND', $this->baseUrl . $davUrl, $body);
		$parsedResponse = $client->parseMultistatus($response['body']);

		Assert::assertEquals($parsedResponse[$davUrl]['200']['{http://nextcloud.com/ns}' . $metadataKey], $metadataValue);
	}

	#[Then('User :user should not see the prop :metadataKey for file :fileName')]
	public function checkPropDoesNotExistsForFile(string $user, string $metadataKey, string $fileName) {
		$client = new SClient([
			'baseUri' => $this->baseUrl,
			'userName' => $user,
			'password' => '123456',
			'authType' => SClient::AUTH_BASIC,
		]);

		$body = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.com/ns">
   <d:prop>
      <nc:' . $metadataKey . '></nc:' . $metadataKey . '>
    </d:prop>
</d:propfind>';

		$davUrl = $this->getDavUrl($user, $fileName);
		$response = $client->request('PROPFIND', $this->baseUrl . $davUrl, $body);
		$parsedResponse = $client->parseMultistatus($response['body']);

		Assert::assertEquals($parsedResponse[$davUrl]['404']['{http://nextcloud.com/ns}' . $metadataKey], null);
	}

	private function getDavUrl(string $user, string $fileName) {
		return $this->davPath . '/files/' . $user . $fileName;
	}
}
