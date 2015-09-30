<?php

use Behat\Behat\Context\BehatContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext {

	/** @var string */
	private $baseUrl = '';

	/** @var ResponseInterface */
	private $response = null;

	/** @var string */
	private $currentUser = '';

	/** @var int */
	private $apiVersion = 1;

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param array $parameters context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters) {

		// Initialize your context here
		$this->baseUrl = $parameters['baseUrl'];
		$this->adminUser = $parameters['admin'];

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

	// /**
	//  * @Then /^the status code should be "([^"]*)"$/
	//  */
	// public function theStatusCodeShouldBe($statusCode) {
	// 	PHPUnit_Framework_Assert::assertEquals($statusCode, $this->response->getStatusCode());
	// }

   
    public function getOCSResponse($response){
         return $response->xml()->meta[0]->statuscode;
    }



    /**
	 * @Then /^the status code should be "([^"]*)"$/
	 */
	public function theStatusCodeShouldBe($statusCode) {
		PHPUnit_Framework_Assert::assertEquals($statusCode, $this->getOCSResponse($this->response));
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
	public function userExists($user) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/users/$user";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
	}

	/**
	 * @Given /^user "([^"]*)" does not exist$/
	 */
	public function userDoesNotExist($user) {
		try {
			$this->userExists($user);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}
	}


	/**
	 * @When /^creating the user "([^"]*)r"$/
	 */
	public function creatingTheUser($user) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/users/$user" ;
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->post($fullUrl, [
			'form_params' => [
				'userid' => $user,
				'password' => '123456'
			]
		]);

	}


    /**
	 * @When /^creating the group "([^"]*)r"$/
	 */
	public function creatingTheGroup($group) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/groups/addgroup" ;
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->post($fullUrl, [
			'form_params' => [
				'groupid' => $user
			]
		]);
	}


	/**
	 * @Given /^group "([^"]*)" exists$/
	 */
	public function groupExists($group) {
		$fullUrl = $this->baseUrl . "v{$this->apiVersion}.php/cloud/groups/$group";
		$client = new Client();
		$options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		$this->response = $client->get($fullUrl, $options);
	}

	/**
	 * @Given /^group "([^"]*)" does not exist$/
	 */
	public function groupDoesNotExist($group) {
		try {
			$this->groupExists($group);
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
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






}
