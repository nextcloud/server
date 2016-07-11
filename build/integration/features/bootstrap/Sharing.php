<?php
/**

 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sergio Bertolin <sbertolin@solidgear.es>
 * @author Sergio Bertolín <sbertolin@solidgear.es>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';



trait Sharing {
	use Provisioning;

	/** @var int */
	private $sharingApiVersion = 1;

	/** @var SimpleXMLElement */
	private $lastShareData = null;

	/** @var int */
	private $savedShareId = null;

	/**
	 * @Given /^as "([^"]*)" creating a share with$/
	 * @param string $user
	 * @param \Behat\Gherkin\Node\TableNode|null $body
	 */
	public function asCreatingAShareWith($user, $body) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares";
		$client = new Client();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}

		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $body->getRowsHash();
			if (array_key_exists('expireDate', $fd)){
				$dateModification = $fd['expireDate'];
				$fd['expireDate'] = date('Y-m-d', strtotime($dateModification));
			}
			$options['body'] = $fd;
		}

		try {
			$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}

		$this->lastShareData = $this->response->xml();
	}

	/**
	 * @When /^creating a share with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $body
	 */
	public function creatingShare($body) {
		$this->asCreatingAShareWith($this->currentUser, $body);
	}

	/**
	 * @Then /^Public shared file "([^"]*)" can be downloaded$/
	 */
	public function checkPublicSharedFile($filename) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0){
			$url = $this->lastShareData->data[0]->url;
		}
		else{
			$url = $this->lastShareData->data->url;
		}
		$fullUrl = $url . "/download";
		$options['save_to'] = "./$filename";
		$this->response = $client->get($fullUrl, $options);
		$finfo = new finfo;
		$fileinfo = $finfo->file("./$filename", FILEINFO_MIME_TYPE);
		PHPUnit_Framework_Assert::assertEquals($fileinfo, "text/plain");
		if (file_exists("./$filename")) {
			unlink("./$filename");
		}
	}

	/**
	 * @Then /^Public shared file "([^"]*)" with password "([^"]*)" can be downloaded$/
	 */
	public function checkPublicSharedFileWithPassword($filename, $password) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0){
			$token = $this->lastShareData->data[0]->token;
		}
		else{
			$token = $this->lastShareData->data->token;
		}

		$fullUrl = substr($this->baseUrl, 0, -4) . "public.php/webdav";
		$options['auth'] = [$token, $password];
		$options['save_to'] = "./$filename";
		$this->response = $client->get($fullUrl, $options);
		$finfo = new finfo;
		$fileinfo = $finfo->file("./$filename", FILEINFO_MIME_TYPE);
		PHPUnit_Framework_Assert::assertEquals($fileinfo, "text/plain");
		if (file_exists("./$filename")) {
			unlink("./$filename");
		}
	}

	/**
	 * @When /^Adding expiration date to last share$/
	 */
	public function addingExpirationDate() {
		$share_id = (string) $this->lastShareData->data[0]->id;
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares/$share_id";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$this->currentUser, $this->regularUser];
		}
		$date = date('Y-m-d', strtotime("+3 days"));
		$options['body'] = ['expireDate' => $date];
		$this->response = $client->send($client->createRequest("PUT", $fullUrl, $options));
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	/**
	 * @When /^Updating last share with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $body
	 */
	public function updatingLastShare($body) {
		$share_id = (string) $this->lastShareData->data[0]->id;
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares/$share_id";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$this->currentUser, $this->regularUser];
		}

		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $body->getRowsHash();
			if (array_key_exists('expireDate', $fd)){
				$dateModification = $fd['expireDate'];
				$fd['expireDate'] = date('Y-m-d', strtotime($dateModification));
			}
			$options['body'] = $fd;
		}

		try {
			$this->response = $client->send($client->createRequest("PUT", $fullUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}

		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	public function createShare($user,
								$path = null,
								$shareType = null,
								$shareWith = null,
								$publicUpload = null,
								$password = null,
								$permissions = null){
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares";
		$client = new Client();
		$options = [];

		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$fd = [];
		if (!is_null($path)){
			$fd['path'] = $path;
		}
		if (!is_null($shareType)){
			$fd['shareType'] = $shareType;
		}
		if (!is_null($shareWith)){
			$fd['shareWith'] = $shareWith;
		}
		if (!is_null($publicUpload)){
			$fd['publicUpload'] = $publicUpload;
		}
		if (!is_null($password)){
			$fd['password'] = $password;
		}
		if (!is_null($permissions)){
			$fd['permissions'] = $permissions;
		}

		$options['body'] = $fd;

		try {
			$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
			$this->lastShareData = $this->response->xml();
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	public function isFieldInResponse($field, $contentExpected){
		$data = $this->response->xml()->data[0];
		if ((string)$field == 'expiration'){
			$contentExpected = date('Y-m-d', strtotime($contentExpected)) . " 00:00:00";
		}
		if (count($data->element) > 0){
			foreach($data as $element) {
				if ($contentExpected == "A_TOKEN"){
					return (strlen((string)$element->$field) == 15);
				}
				elseif ($contentExpected == "A_NUMBER"){
					return is_numeric((string)$element->$field);
				}
				elseif($contentExpected == "AN_URL"){
					return $this->isExpectedUrl((string)$element->$field, "index.php/s/");
				}
				elseif ((string)$element->$field == $contentExpected){
					return True;
				}
				else{
					print($element->$field);
				}
			}

			return False;
		} else {
			if ($contentExpected == "A_TOKEN"){
					return (strlen((string)$data->$field) == 15);
			}
			elseif ($contentExpected == "A_NUMBER"){
					return is_numeric((string)$data->$field);
			}
			elseif($contentExpected == "AN_URL"){
					return $this->isExpectedUrl((string)$data->$field, "index.php/s/");
			}
			elseif ($data->$field == $contentExpected){
					return True;
			}
			return False;
		}
	}

	/**
	 * @Then /^File "([^"]*)" should be included in the response$/
	 *
	 * @param string $filename
	 */
	public function checkSharedFileInResponse($filename){
		PHPUnit_Framework_Assert::assertEquals(True, $this->isFieldInResponse('file_target', "/$filename"));
	}

	/**
	 * @Then /^File "([^"]*)" should not be included in the response$/
	 *
	 * @param string $filename
	 */
	public function checkSharedFileNotInResponse($filename){
		PHPUnit_Framework_Assert::assertEquals(False, $this->isFieldInResponse('file_target', "/$filename"));
	}

	/**
	 * @Then /^User "([^"]*)" should be included in the response$/
	 *
	 * @param string $user
	 */
	public function checkSharedUserInResponse($user){
		PHPUnit_Framework_Assert::assertEquals(True, $this->isFieldInResponse('share_with', "$user"));
	}

	/**
	 * @Then /^User "([^"]*)" should not be included in the response$/
	 *
	 * @param string $user
	 */
	public function checkSharedUserNotInResponse($user){
		PHPUnit_Framework_Assert::assertEquals(False, $this->isFieldInResponse('share_with', "$user"));
	}

	public function isUserOrGroupInSharedData($userOrGroup, $permissions = null){
		$data = $this->response->xml()->data[0];
		foreach($data as $element) {
			if ($element->share_with == $userOrGroup && ($permissions === null || $permissions == $element->permissions)){
				return True;
			}
		}
		return False;
	}

	/**
	 * @Given /^(file|folder|entry) "([^"]*)" of user "([^"]*)" is shared with user "([^"]*)"( with permissions ([\d]*))?$/
	 *
	 * @param string $filepath
	 * @param string $user1
	 * @param string $user2
	 */
	public function assureFileIsShared($entry, $filepath, $user1, $user2, $withPerms = null, $permissions = null){
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares" . "?path=$filepath";
		$client = new Client();
		$options = [];
		if ($user1 === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user1, $this->regularUser];
		}
		$this->response = $client->get($fullUrl, $options);
		if ($this->isUserOrGroupInSharedData($user2, $permissions)){
			return;
		} else {
			$this->createShare($user1, $filepath, 0, $user2, null, null, $permissions);
		}
		$this->response = $client->get($fullUrl, $options);
		PHPUnit_Framework_Assert::assertEquals(True, $this->isUserOrGroupInSharedData($user2, $permissions));
	}

	/**
	 * @Given /^(file|folder|entry) "([^"]*)" of user "([^"]*)" is shared with group "([^"]*)"( with permissions ([\d]*))?$/
	 *
	 * @param string $filepath
	 * @param string $user
	 * @param string $group
	 */
	public function assureFileIsSharedWithGroup($entry, $filepath, $user, $group, $withPerms = null, $permissions = null){
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares" . "?path=$filepath";
		$client = new Client();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$this->response = $client->get($fullUrl, $options);
		if ($this->isUserOrGroupInSharedData($group, $permissions)){
			return;
		} else {
			$this->createShare($user, $filepath, 1, $group, null, null, $permissions);
		}
		$this->response = $client->get($fullUrl, $options);
		PHPUnit_Framework_Assert::assertEquals(True, $this->isUserOrGroupInSharedData($group, $permissions));
	}

	/**
	 * @When /^Deleting last share$/
	 */
	public function deletingLastShare(){
		$share_id = $this->lastShareData->data[0]->id;
		$url = "/apps/files_sharing/api/v{$this->sharingApiVersion}/shares/$share_id";
		$this->sendingToWith("DELETE", $url, null);
	}

	/**
	 * @When /^Getting info of last share$/
	 */
	public function gettingInfoOfLastShare(){
		$share_id = $this->lastShareData->data[0]->id;
		$url = "/apps/files_sharing/api/v{$this->sharingApiVersion}/shares/$share_id";
		$this->sendingToWith("GET", $url, null);
	}

	/**
	 * @Then /^last share_id is included in the answer$/
	 */
	public function checkingLastShareIDIsIncluded(){
		$share_id = $this->lastShareData->data[0]->id;
		if (!$this->isFieldInResponse('id', $share_id)){
			PHPUnit_Framework_Assert::fail("Share id $share_id not found in response");
		}
	}

	/**
	 * @Then /^last share_id is not included in the answer$/
	 */
	public function checkingLastShareIDIsNotIncluded(){
		$share_id = $this->lastShareData->data[0]->id;
		if ($this->isFieldInResponse('id', $share_id)){
			PHPUnit_Framework_Assert::fail("Share id $share_id has been found in response");
		}
	}

	/**
	 * @Then /^Share fields of last share match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $body
	 */
	public function checkShareFields($body){
		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $body->getRowsHash();

			foreach($fd as $field => $value) {
				if (substr($field, 0, 10 ) === "share_with"){
					$value = str_replace("REMOTE", substr($this->remoteBaseUrl, 0, -5), $value);
					$value = str_replace("LOCAL", substr($this->localBaseUrl, 0, -5), $value);
				}
				if (substr($field, 0, 6 ) === "remote"){
					$value = str_replace("REMOTE", substr($this->remoteBaseUrl, 0, -4), $value);
					$value = str_replace("LOCAL", substr($this->localBaseUrl, 0, -4), $value);
				}
				if (!$this->isFieldInResponse($field, $value)){
					PHPUnit_Framework_Assert::fail("$field" . " doesn't have value " . "$value");
				}
			}
		}
	}

	/**
	 * @Then As :user remove all shares from the file named :fileName
	 */
	public function asRemoveAllSharesFromTheFileNamed($user, $fileName) {
		$url = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares?format=json";
		$client = new \GuzzleHttp\Client();
		$res = $client->get(
			$url,
			[
				'auth' => [
					$user,
					'123456',
				],
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);
		$json = json_decode($res->getBody()->getContents(), true);
		$deleted = false;
		foreach($json['ocs']['data'] as $data) {
			if (stripslashes($data['path']) === $fileName) {
				$id = $data['id'];
				$client->delete(
					$this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares/{$id}",
					[
						'auth' => [
							$user,
							'123456',
						],
						'headers' => [
							'Content-Type' => 'application/json',
						],
					]
				);
				$deleted = true;
			}
		}

		if($deleted === false) {
			throw new \Exception("Could not delete file $fileName");
		}
	}

	/**
	 * @When save last share id
	 */
	public function saveLastShareId()
	{
		$this->savedShareId = $this->lastShareData['data']['id'];
	}

	/**
	 * @Then share ids should match
	 */
	public function shareIdsShouldMatch()
	{
		if ($this->savedShareId !== $this->lastShareData['data']['id']) {
			throw new \Exception('Expected the same link share to be returned');
		}
	}
}

