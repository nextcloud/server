<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Message\ResponseInterface;
use Sabre\DAV\Client as SClient;

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
		$client = new GClient();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$request = $client->createRequest($method, $fullUrl, $options);
		if (!is_null($headers)){
			foreach ($headers as $key => $value) {
				$request->addHeader($key, $value);	
			}
		}
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

	public function listFolder($user, $path){
		$fullUrl = substr($this->baseUrl, 0, -4);

		$settings = array(
			'baseUri' => $fullUrl,
			'userName' => $user,
		);

		echo "password del admin: " . $this->adminUser[1] . "\n";
		echo "fullUrl: " . $fullUrl . "\n";

		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}

		$client = new SClient($settings);

		$response = $client->propfind($this->davPath . "/", array(
			'{DAV:}getetag',
			1
		));

		print_r($response);
		/*$features = $client->options();

		print_r($features);*/
		//return $this->response->xml();
	}

	/**
	 * @Then /^user "([^"]*)" should see following folders$/
	 */
	public function checkList($user){
		$this->listFolder($user, '/');
	}
	

}

