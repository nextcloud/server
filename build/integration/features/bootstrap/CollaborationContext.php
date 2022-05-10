<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

class CollaborationContext implements Context {
	use Provisioning;
	use AppConfiguration;

	/**
	 * @Then /^get autocomplete for "([^"]*)"$/
	 * @param TableNode|null $formData
	 */
	public function getAutocomplete(string $search, TableNode $formData): void {
		$query = $search === 'null' ? null : $search;

		$this->sendRequestForJSON('GET', '/core/autocomplete/get?itemType=files&itemId=123&search=' . $query, [
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
			return $data;
		}, $suggestions, $formData->getHash()));
	}

	protected function resetAppConfigs(): void {
		$this->deleteServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_group');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_phone');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match_userid');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_display_name');
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
