<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';


trait WebDav{

	/** @var string*/
	private $davPath = "remote.php/webdav";

	/**
	 * @Given /^using dav path "([^"]*)"$/
	 */
	public function usingDavPath($davPath) {
		$this->davPath = $davPath;
	}	

	public function makeDavRequest($user, $method, $path, $headers){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath . "$path";
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
		//$this->response = $client->send($request);
		return $client->send($request);
	}

	/**
	 * @Given /^User "([^"]*)" moved file "([^"]*)" to "([^"]*)"$/
	 */
	public function userMovedFile($user, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath;
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->response = $this->makeDavRequest($user, "MOVE", $fileSource, $headers);
		PHPUnit_Framework_Assert::assertEquals(201, $this->response->getStatusCode());
	}

	/**
	 * @When /^User "([^"]*)" moves file "([^"]*)" to "([^"]*)"$/
	 */
	public function userMovesFile($user, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath;
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->response = $this->makeDavRequest($user, "MOVE", $fileSource, $headers);
	}

}

