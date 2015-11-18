<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Features context.
 */
class FeatureContext implements Context, SnippetAcceptingContext {

	/** @var string */
	private $baseUrl = '';

	/** @var ResponseInterface */
	private $response = null;

	/** @var string */
	private $currentUser = '';

	/** @var int */
	private $apiVersion = 1;

	/** @var int */
	private $sharingApiVersion = 1;

	/** @var SimpleXMLElement */
	private $lastShareData = null;

	/** @var array */
	private $createdUsers = [];

	/** @var array */
	private $createdGroups = [];

	public function __construct($baseUrl, $admin, $regular_user_password) {

		// Initialize your context here
		$this->baseUrl = $baseUrl;
		$this->adminUser = $admin;
		$this->regularUser = $regular_user_password;

		// in case of ci deployment we take the server url from the environment
		$testServerUrl = getenv('TEST_SERVER_URL');
		if ($testServerUrl !== false) {
			$this->baseUrl = $testServerUrl;
		}
	}

	/**
	 * @When /^sending "([^"]*)" to "([^"]*)"$/
	 */
	public function sendingTo($verb, $url) {
		$this->sendingToWith($verb, $url, null);
	}

	/**
	 * Parses the xml answer to get ocs response which doesn't match with
	 * http one in v1 of the api.
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function getOCSResponse($response) {
		return $response->xml()->meta[0]->statuscode;
	}

	/**
	 * Parses the xml answer to get the array of users returned.
	 * @param ResponseInterface $resp
	 * @return array
	 */
	public function getArrayOfUsersResponded($resp) {
		$listCheckedElements = $resp->xml()->data[0]->users[0]->element;
		$extractedElementsArray = json_decode(json_encode($listCheckedElements), 1);
		return $extractedElementsArray;
	}

	/**
	 * Parses the xml answer to get the array of groups returned.
	 * @param ResponseInterface $resp
	 * @return array
	 */
	public function getArrayOfGroupsResponded($resp) {
		$listCheckedElements = $resp->xml()->data[0]->groups[0]->element;
		$extractedElementsArray = json_decode(json_encode($listCheckedElements), 1);
		return $extractedElementsArray;
	}

	/**
	 * Parses the xml answer to get the array of subadmins returned.
	 * @param ResponseInterface $resp
	 * @return array
	 */
	public function getArrayOfSubadminsResponded($resp) {
		$listCheckedElements = $resp->xml()->data[0]->element;
		$extractedElementsArray = json_decode(json_encode($listCheckedElements), 1);
		return $extractedElementsArray;
	}

	/**
	 * Parses the xml answer to get the array of apps returned.
	 * @param ResponseInterface $resp
	 * @return array
	 */
	public function getArrayOfAppsResponded($resp) {
		$listCheckedElements = $resp->xml()->data[0]->apps[0]->element;
		$extractedElementsArray = json_decode(json_encode($listCheckedElements), 1);
		return $extractedElementsArray;
	}

	/**
	 * This function is needed to use a vertical fashion in the gherkin tables.
	 */
	public function simplifyArray($arrayOfArrays){
		$a = array_map(function($subArray) { return $subArray[0]; }, $arrayOfArrays);
		return $a;
	}

	/**
	 * @Then /^users returned are$/
	 * @param \Behat\Gherkin\Node\TableNode|null $usersList
	 */
	public function theUsersShouldBe($usersList) {
		if ($usersList instanceof \Behat\Gherkin\Node\TableNode) {
			$users = $usersList->getRows();
			$usersSimplified = $this->simplifyArray($users);
			$respondedArray = $this->getArrayOfUsersResponded($this->response);
			PHPUnit_Framework_Assert::assertEquals($usersSimplified, $respondedArray, "", 0.0, 10, true);
		}

	}

	/**
	 * @Then /^groups returned are$/
	 * @param \Behat\Gherkin\Node\TableNode|null $groupsList
	 */
	public function theGroupsShouldBe($groupsList) {
		if ($groupsList instanceof \Behat\Gherkin\Node\TableNode) {
			$groups = $groupsList->getRows();
			$groupsSimplified = $this->simplifyArray($groups);
			$respondedArray = $this->getArrayOfGroupsResponded($this->response);
			PHPUnit_Framework_Assert::assertEquals($groupsSimplified, $respondedArray, "", 0.0, 10, true);
		}

	}

	/**
	 * @Then /^subadmin groups returned are$/
	 * @param \Behat\Gherkin\Node\TableNode|null $groupsList
	 */
	public function theSubadminGroupsShouldBe($groupsList) {
		if ($groupsList instanceof \Behat\Gherkin\Node\TableNode) {
			$groups = $groupsList->getRows();
			$groupsSimplified = $this->simplifyArray($groups);
			$respondedArray = $this->getArrayOfSubadminsResponded($this->response);
			PHPUnit_Framework_Assert::assertEquals($groupsSimplified, $respondedArray, "", 0.0, 10, true);
		}

	}

	/**
	 * @Then /^subadmin users returned are$/
	 * @param \Behat\Gherkin\Node\TableNode|null $groupsList
	 */
	public function theSubadminUsersShouldBe($groupsList) {
		$this->theSubadminGroupsShouldBe($groupsList);
	}

	/**
	 * @Then /^apps returned are$/
	 * @param \Behat\Gherkin\Node\TableNode|null $appList
	 */
	public function theAppsShouldBe($appList) {
		if ($appList instanceof \Behat\Gherkin\Node\TableNode) {
			$apps = $appList->getRows();
			$appsSimplified = $this->simplifyArray($apps);
			$respondedArray = $this->getArrayOfAppsResponded($this->response);
			PHPUnit_Framework_Assert::assertEquals($appsSimplified, $respondedArray, "", 0.0, 10, true);
		}

	}

	/**
	 * @Then /^the OCS status code should be "([^"]*)"$/
	 */
	public function theOCSStatusCodeShouldBe($statusCode) {
		PHPUnit_Framework_Assert::assertEquals($statusCode, $this->getOCSResponse($this->response));
	}

	/**
	 * @Then /^the HTTP status code should be "([^"]*)"$/
	 */
	public function theHTTPStatusCodeShouldBe($statusCode) {
		PHPUnit_Framework_Assert::assertEquals($statusCode, $this->response->getStatusCode());
	}

	/**
	 * @Given /^As an "([^"]*)"$/
	 */
	public function asAn($user) {
		$this->currentUser = $user;
	}

	/**
	 * @Given /^using api version "([^"]*)"$/
	 */
	public function usingApiVersion($version) {
		$this->apiVersion = $version;
	}

	/**
	 * @Given /^user "([^"]*)" exists$/
	 */
	public function assureUserExists($user) {
		try {
			$this->userExists($user);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$previous_user = $this->currentUser;
			$this->currentUser = "admin";
			$this->creatingTheUser($user);
			$this->currentUser = $previous_user;
		}
		$this->userExists($user);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());

	}

	public function userExists($user){
		$fullUrl = $this->baseUrl . "v2.php/cloud/users/$user";
		$client = new Client();
		$options = [];
		$options['auth'] = $this->adminUser;

		$this->response = $client->get($fullUrl, $options);
	}

	/**
	 * @Then /^check that user "([^"]*)" belongs to group "([^"]*)"$/
	 */
	public function checkThatUserBelongsToGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/users/$user/groups";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$respondedArray = $this->getArrayOfGroupsResponded($this->response);
		sort($respondedArray);
		PHPUnit_Framework_Assert::assertContains($group, $respondedArray);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	public function userBelongsToGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/users/$user/groups";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$groups = array($group);
		$respondedArray = $this->getArrayOfGroupsResponded($this->response);

		if (array_key_exists($group, $respondedArray)) {
			return True;
		} else{
			return False;
		}
	}

	/**
	 * @Given /^user "([^"]*)" belongs to group "([^"]*)"$/
	 */
	public function assureUserBelongsToGroup($user, $group){
		if (!$this->userBelongsToGroup($user, $group)){			
			$previous_user = $this->currentUser;
			$this->currentUser = "admin";
			$this->addingUserToGroup($user, $group);
			$this->currentUser = $previous_user;
		}
		$this->checkThatUserBelongsToGroup($user, $group);

	}

	/**
	 * @Given /^user "([^"]*)" does not belong to group "([^"]*)"$/
	 */
	public function userDoesNotBelongToGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/users/$user/groups";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$groups = array($group);
		$respondedArray = $this->getArrayOfGroupsResponded($this->response);
		PHPUnit_Framework_Assert::assertNotEquals($groups, $respondedArray, "", 0.0, 10, true);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}


	/**
	 * @Given /^user "([^"]*)" is subadmin of group "([^"]*)"$/
	 */
	public function userIsSubadminOfGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/groups/$group/subadmins";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$respondedArray = $this->getArrayOfSubadminsResponded($this->response);
		sort($respondedArray);
		PHPUnit_Framework_Assert::assertContains($user, $respondedArray);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	/**
	 * @Given /^user "([^"]*)" is not a subadmin of group "([^"]*)"$/
	 */
	public function userIsNotSubadminOfGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/groups/$group/subadmins";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$respondedArray = $this->getArrayOfSubadminsResponded($this->response);
		sort($respondedArray);
		PHPUnit_Framework_Assert::assertNotContains($user, $respondedArray);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	/**
	 * @Given /^user "([^"]*)" does not exist$/
	 */
	public function userDoesNotExist($user) {
		try {
			$this->userExists($user);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
			PHPUnit_Framework_Assert::assertEquals(404, $ex->getResponse()->getStatusCode());
			return;
		}
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->deletingTheUser($user);
		$this->currentUser = $previous_user;
		try {
			$this->userExists($user);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
			PHPUnit_Framework_Assert::assertEquals(404, $ex->getResponse()->getStatusCode());
		}
	}

	/**
	 * @Given /^app "([^"]*)" is disabled$/
	 */
	public function appIsDisabled($app) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/apps?filter=disabled";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$respondedArray = $this->getArrayOfAppsResponded($this->response);
		PHPUnit_Framework_Assert::assertContains($app, $respondedArray);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	/**
	 * @Given /^app "([^"]*)" is enabled$/
	 */
	public function appIsEnabled($app) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/apps?filter=enabled";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
		$respondedArray = $this->getArrayOfAppsResponded($this->response);
		PHPUnit_Framework_Assert::assertContains($app, $respondedArray);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	public function createUser($user) {
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->creatingTheUser($user);
		$this->userExists($user);
		$this->currentUser = $previous_user;
	}

	public function deleteUser($user) {
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->deletingTheUser($user);
		$this->userDoesNotExist($user);
		$this->currentUser = $previous_user;
	}

	public function createGroup($group) {
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->creatingTheGroup($group);
		$this->groupExists($group);
		$this->currentUser = $previous_user;
	}

	public function deleteGroup($group) {
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->deletingTheGroup($group);
		$this->groupDoesNotExist($group);
		$this->currentUser = $previous_user;
	}

	public function creatingTheUser($user) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/users";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$options['body'] = [
							'userid' => $user,
							'password' => '123456'
							];

		$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
		$this->createdUsers[$user] = $user;
	}

	/**
	 * @When /^creating the group "([^"]*)"$/
	 */
	public function creatingTheGroup($group) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/groups";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$options['body'] = [
							'groupid' => $group,
							];

		$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
		$this->createdGroups[$group] = $group;
	}

	/**
	 * @When /^Deleting the user "([^"]*)"$/
	 */
	public function deletingTheUser($user) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/users/$user";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->send($client->createRequest("DELETE", $fullUrl, $options));
	}

	/**
	 * @When /^Deleting the group "([^"]*)"$/
	 */
	public function deletingTheGroup($group) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/groups/$group";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->send($client->createRequest("DELETE", $fullUrl, $options));
	}

	/**
	 * @Given /^Add user "([^"]*)" to the group "([^"]*)"$/
	 */
	public function addUserToGroup($user, $group) {
		$this->userExists($user);
		$this->groupExists($group);
		$this->addingUserToGroup($user, $group);

	}

	/**
	 * @When /^User "([^"]*)" is added to the group "([^"]*)"$/
	 */
	public function addingUserToGroup($user, $group) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/users/$user/groups";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$options['body'] = [
							'groupid' => $group,
							];

		$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
	}


	public function groupExists($group) {
		$fullUrl = $this->baseUrl . "v2.php/cloud/groups/$group";
		$client = new Client();
		$options = [];
		$options['auth'] = $this->adminUser;

		$this->response = $client->get($fullUrl, $options);
	}

	/**
	 * @Given /^group "([^"]*)" exists$/
	 */
	public function assureGroupExists($group) {
		try {
			$this->groupExists($group);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$previous_user = $this->currentUser;
			$this->currentUser = "admin";
			$this->creatingTheGroup($group);
			$this->currentUser = $previous_user;
		}
		$this->groupExists($group);
		PHPUnit_Framework_Assert::assertEquals(200, $this->response->getStatusCode());
	}

	/**
	 * @Given /^group "([^"]*)" does not exist$/
	 */
	public function groupDoesNotExist($group) {
		try {
			$this->groupExists($group);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
			PHPUnit_Framework_Assert::assertEquals(404, $ex->getResponse()->getStatusCode());
			return;
		}
		$previous_user = $this->currentUser;
		$this->currentUser = "admin";
		$this->deletingTheGroup($group);
		$this->currentUser = $previous_user;
		try {
			$this->groupExists($group);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
			PHPUnit_Framework_Assert::assertEquals(404, $ex->getResponse()->getStatusCode());
		}
	}

	/**
	 * @When /^sending "([^"]*)" to "([^"]*)" with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function sendingToWith($verb, $url, $body) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php" . $url;
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$this->currentUser, $this->regularUser];
		}
		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $body->getRowsHash();
			$options['body'] = $fd;
		}

		try {
			$this->response = $client->send($client->createRequest($verb, $fullUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * @When /^creating a public share with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function createPublicShare($body) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v1/shares";
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
			$this->response = $client->send($client->createRequest("POST", $fullUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}

		$this->lastShareData = $this->response->xml();
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
		$share_id = $this->lastShareData->data[0]->id;
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
		$share_id = $this->lastShareData->data[0]->id;
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

	public function isExpectedUrl($possibleUrl, $finalPart){
		$baseUrlChopped = substr($this->baseUrl, 0, -4);
		$endCharacter = strlen($baseUrlChopped) + strlen($finalPart);
		return (substr($possibleUrl,0,$endCharacter) == "$baseUrlChopped" . "$finalPart");
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
				elseif ($element->$field == $contentExpected){
					return True;
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
	 */
	public function checkSharedFileInResponse($filename){
		PHPUnit_Framework_Assert::assertEquals(True, $this->isFieldInResponse('file_target', "/$filename"));
	}

	/**
	 * @Then /^File "([^"]*)" should not be included in the response$/
	 */
	public function checkSharedFileNotInResponse($filename){
		PHPUnit_Framework_Assert::assertEquals(False, $this->isFieldInResponse('file_target', "/$filename"));
	}

	/**
	 * @Then /^User "([^"]*)" should be included in the response$/
	 */
	public function checkSharedUserInResponse($user){
		PHPUnit_Framework_Assert::assertEquals(True, $this->isFieldInResponse('share_with', "$user"));
	}

	/**
	 * @Then /^User "([^"]*)" should not be included in the response$/
	 */
	public function checkSharedUserNotInResponse($user){
		PHPUnit_Framework_Assert::assertEquals(False, $this->isFieldInResponse('share_with', "$user"));
	}

	public function isUserOrGroupInSharedData($userOrGroup){
		$data = $this->response->xml()->data[0];
		foreach($data as $element) {
			if ($element->share_with == $userOrGroup){
				return True;
			}
		}
		return False;
	}

	/**
	 * @Given /^file "([^"]*)" from user "([^"]*)" is shared with user "([^"]*)"$/
	 */
	public function assureFileIsShared($filepath, $user1, $user2){
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares" . "?path=$filepath";
		$client = new Client();
		$options = [];
		if ($user1 === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user1, $this->regularUser];
		}
		$this->response = $client->get($fullUrl, $options);
		if ($this->isUserOrGroupInSharedData($user2)){
			return;
		} else {
			$this->createShare($user1, $filepath, 0, $user2, null, null, null);
		}
		$this->response = $client->get($fullUrl, $options);
		PHPUnit_Framework_Assert::assertEquals(True, $this->isUserOrGroupInSharedData($user2));
	}

	/**
	 * @Given /^file "([^"]*)" from user "([^"]*)" is shared with group "([^"]*)"$/
	 */
	public function assureFileIsSharedWithGroup($filepath, $user, $group){
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/apps/files_sharing/api/v{$this->sharingApiVersion}/shares" . "?path=$filepath";
		$client = new Client();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$this->response = $client->get($fullUrl, $options);
		if ($this->isUserOrGroupInSharedData($group)){
			return;
		} else {
			$this->createShare($user, $filepath, 1, $group, null, null, null);
		}
		$this->response = $client->get($fullUrl, $options);
		PHPUnit_Framework_Assert::assertEquals(True, $this->isUserOrGroupInSharedData($group));
	}

	public function makeDavRequest($user, $method, $path, $headers){
		$fullUrl = substr($this->baseUrl, 0, -4) . "remote.php/webdav" . "$path";
		$client = new Client();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$request = $client->createRequest($method, $fullUrl, $options);
		foreach ($headers as $key => $value) {
			$request->addHeader($key, $value);	
		}
		$this->response = $client->send($request);
	}

	/**
	 * @Given /^User "([^"]*)" moved file "([^"]*)" to "([^"]*)"$/
	 */
	public function userMovedFile($user, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . "remote.php/webdav";
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->makeDavRequest($user, "MOVE", $fileSource, $headers);
		PHPUnit_Framework_Assert::assertEquals(201, $this->response->getStatusCode());
	}

	/**
	 * @When /^User "([^"]*)" moves file "([^"]*)" to "([^"]*)"$/
	 */
	public function userMovesFile($user, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . "remote.php/webdav";
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->makeDavRequest($user, "MOVE", $fileSource, $headers);
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
	 * @Then /^Share fields of last share match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function checkShareFields($body){
		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $body->getRowsHash();

			foreach($fd as $field => $value) {
				if (!$this->isFieldInResponse($field, $value)){
					PHPUnit_Framework_Assert::fail("$field" . " doesn't have value " . "$value");
				}
			}
		}
	}

	public static function removeFile($path, $filename){
		if (file_exists("$path" . "$filename")) {
			unlink("$path" . "$filename");
		}
	}

	/**
	 * @BeforeSuite
	 */
	public static function addFilesToSkeleton(){
		for ($i=0; $i<5; $i++){
			file_put_contents("../../core/skeleton/" . "textfile" . "$i" . ".txt", "ownCloud test text file\n");
		}
		if (!file_exists("../../core/skeleton/FOLDER")) {
			mkdir("../../core/skeleton/FOLDER", 0777, true);
		}

	}

	/**
	 * @AfterSuite
	 */
	public static function removeFilesFromSkeleton(){
		for ($i=0; $i<5; $i++){
			self::removeFile("../../core/skeleton/", "textfile" . "$i" . ".txt");
		}
		if (is_dir("../../core/skeleton/FOLDER")) {
			rmdir("../../core/skeleton/FOLDER");
		}
	}

	/**
	 * @BeforeScenario
	 * @AfterScenario
	 */
	public function cleanupUsers()
	{
		foreach($this->createdUsers as $user) {
			$this->deleteUser($user);
		}
	}


	/**
	 * @BeforeScenario
	 * @AfterScenario
	 */
	public function cleanupGroups()
	{
		foreach($this->createdGroups as $group) {
			$this->deleteGroup($group);
		}
	}
}
