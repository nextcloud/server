<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

class CollaborationContext implements Context {
	use Provisioning;
	use AppConfiguration;
	use WebDav;

	/**
	 * @Then /^get autocomplete for "([^"]*)"$/
	 * @param TableNode|null $formData
	 */
	public function getAutocompleteForUser(string $search, TableNode $formData): void {
		$this->getAutocompleteWithType(0, $search, $formData);
	}

	/**
	 * @Then /^get email autocomplete for "([^"]*)"$/
	 * @param TableNode|null $formData
	 */
	public function getAutocompleteForEmail(string $search, TableNode $formData): void {
		$this->getAutocompleteWithType(4, $search, $formData);
	}

	private function getAutocompleteWithType(int $type, string $search, TableNode $formData): void {
		$query = $search === 'null' ? null : $search;

		$this->sendRequestForJSON('GET', '/core/autocomplete/get?itemType=files&itemId=123&shareTypes[]=' . $type . '&search=' . $query, [
			'itemType' => 'files',
			'itemId' => '123',
			'search' => $query,
		]);
		$this->theHTTPStatusCodeShouldBe(200);

		$data = json_decode($this->response->getBody()->getContents(), true);
		$suggestions = $data['ocs']['data'];

		Assert::assertCount(count($formData->getHash()), $suggestions, 'Suggestion count does not match');
		Assert::assertEquals($formData->getHash(), array_map(static function ($suggestion, $expected) {
			$data = [];
			if (isset($expected['id'])) {
				$data['id'] = $suggestion['id'];
			}
			if (isset($expected['source'])) {
				$data['source'] = $suggestion['source'];
			}
			if (isset($expected['status'])) {
				$data['status'] = json_encode($suggestion['status']);
			}
			return $data;
		}, $suggestions, $formData->getHash()));
	}

	/**
	 * @Given /^there is a contact in an addressbook$/
	 */
	public function thereIsAContactInAnAddressbook() {
		$this->usingNewDavPath();
		try {
			$destination = '/users/admin/myaddressbook';
			$data = '<x0:mkcol xmlns:x0="DAV:"><x0:set><x0:prop><x0:resourcetype><x0:collection/><x4:addressbook xmlns:x4="urn:ietf:params:xml:ns:carddav"/></x0:resourcetype><x0:displayname>myaddressbook</x0:displayname></x0:prop></x0:set></x0:mkcol>';
			$this->response = $this->makeDavRequest($this->currentUser, 'MKCOL', $destination, ['Content-Type' => 'application/xml'], $data, 'addressbooks');
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}

		try {
			$destination = '/users/admin/myaddressbook/contact1.vcf';
			$data = <<<EOF
BEGIN:VCARD
VERSION:4.0
PRODID:-//Nextcloud Contacts v4.0.2
UID:a0f4088a-4dca-4308-9b63-09a1ebcf78f3
FN:A person
ADR;TYPE=HOME:;;;;;;
EMAIL;TYPE=HOME:user@example.com
REV;VALUE=DATE-AND-OR-TIME:20211130T140111Z
END:VCARD
EOF;
			$this->response = $this->makeDavRequest($this->currentUser, 'PUT', $destination, [], $data, 'addressbooks');
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	protected function resetAppConfigs(): void {
		$this->deleteServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_group');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_phone');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match_userid');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match_email');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn');
		$this->deleteServerConfig('core', 'shareapi_only_share_with_group_members');
	}

	/**
	 * @Given /^user "([^"]*)" has status "([^"]*)"$/
	 * @param string $user
	 * @param string $status
	 */
	public function assureUserHasStatus($user, $status) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/user_status/api/v1/user_status/status";
		$client = new Client();
		$options = [
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}

		$options['form_params'] = [
			'statusType' => $status
		];

		$this->response = $client->put($fullUrl, $options);
		$this->theHTTPStatusCodeShouldBe(200);

		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/user_status/api/v1/user_status";
		unset($options['form_params']);
		$this->response = $client->get($fullUrl, $options);
		$this->theHTTPStatusCodeShouldBe(200);

		$returnedStatus = json_decode(json_encode(simplexml_load_string($this->response->getBody()->getContents())->data), true)['status'];
		Assert::assertEquals($status, $returnedStatus);
	}

	/**
	 * @param string $user
	 * @return null|array
	 */
	public function getStatusList(string $user): ?array {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/user_status/api/v1/statuses";
		$client = new Client();
		$options = [
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}

		$this->response = $client->get($fullUrl, $options);
		$this->theHTTPStatusCodeShouldBe(200);

		$contents = $this->response->getBody()->getContents();
		return json_decode(json_encode(simplexml_load_string($contents)->data), true);
	}

	/**
	 * @Given /^user statuses for "([^"]*)" list "([^"]*)" with status "([^"]*)"$/
	 * @param string $user
	 * @param string $statusUser
	 * @param string $status
	 */
	public function assertStatusesList(string $user, string $statusUser, string $status): void {
		$statusList = $this->getStatusList($user);
		Assert::assertArrayHasKey('element', $statusList, 'Returned status list empty or broken');
		if (array_key_exists('userId', $statusList['element'])) {
			// If only one user has a status set, the API returns their status directly
			Assert::assertArrayHasKey('status', $statusList['element'], 'Returned status list empty or broken');
			$filteredStatusList = [ $statusList['element']['userId'] => $statusList['element']['status'] ];
		} else {
			// If more than one user have their status set, the API returns an array of their statuses
			$filteredStatusList = array_column($statusList['element'], 'status', 'userId');
		}
		Assert::assertArrayHasKey($statusUser, $filteredStatusList, 'User not listed in statuses: ' . $statusUser);
		Assert::assertEquals($status, $filteredStatusList[$statusUser]);
	}

	/**
	 * @Given /^user statuses for "([^"]*)" are empty$/
	 * @param string $user
	 */
	public function assertStatusesEmpty(string $user): void {
		$statusList = $this->getStatusList($user);
		Assert::assertEmpty($statusList);
	}
}
