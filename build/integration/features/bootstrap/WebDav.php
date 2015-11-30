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

	/*Returns the elements of a propfind, $folderDepth requires 1 to see elements without children*/
	public function listFolder($user, $path, $folderDepth){
		$fullUrl = substr($this->baseUrl, 0, -4);

		$settings = array(
			'baseUri' => $fullUrl,
			'userName' => $user,
		);

		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}

		$client = new SClient($settings);

		$response = $client->propfind($this->davPath . "/", array(
			'{DAV:}getetag'
		), $folderDepth);

		return $response;
	}

	/**
	 * @Then /^user "([^"]*)" should see following elements$/
	 * @param \Behat\Gherkin\Node\TableNode|null $expectedElements
	 */
	public function checkElementList($user, $expectedElements){
		$elementList = $this->listFolder($user, '/', 2);
		if ($expectedElements instanceof \Behat\Gherkin\Node\TableNode) {
			$elementRows = $expectedElements->getRows();
			$elementsSimplified = $this->simplifyArray($elementRows);
			foreach($elementsSimplified as $expectedElement) {
				$webdavPath = "/" . $this->davPath . $expectedElement;
				if (!array_key_exists($webdavPath,$elementList)){
					PHPUnit_Framework_Assert::fail("$webdavPath" . " is not in propfind answer");
				}
			}
		}
	}
	

}

