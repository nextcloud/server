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
    }

    /**
     * @When /^sending "([^"]*)" to "([^"]*)"$/
     */
    public function sendingTo($verb, $url) {
        $fullUrl = $this->baseUrl . "v{$this->apiVersion}.php" . $url;
        $client = new Client();
        // TODO: get admin user from config
        $options = [];
		if ($this->currentUser === 'admin') {
			$options['auth'] = $this->adminUser;
		}

		try {
			$this->response = $client->send($client->createRequest($verb, $fullUrl, $options));
		} catch (\GuzzleHttp\Exception\ClientException $ex) {
			$this->response = $ex->getResponse();
		}
    }

    /**
     * @Then /^the status code should be "([^"]*)"$/
     */
    public function theStatusCodeShouldBe($statusCode) {
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
	public function userExists($user) {
		throw new \Behat\Behat\Exception\PendingException();
	}

}
