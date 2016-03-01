<?php

require __DIR__ . '/../../vendor/autoload.php';

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

class ChecksumsContext implements \Behat\Behat\Context\Context {
	/** @var string  */
	private $baseUrl;
	/** @var Client */
	private $client;
	/** @var ResponseInterface */
	private $response;

	/**
	 * @param string $baseUrl
	 */
	public function __construct($baseUrl) {
		$this->baseUrl = $baseUrl;

		// in case of ci deployment we take the server url from the environment
		$testServerUrl = getenv('TEST_SERVER_URL');
		if ($testServerUrl !== false) {
			$this->baseUrl = substr($testServerUrl, 0, -5);
		}
	}

	/** @BeforeScenario */
	public function tearUpScenario() {
		$this->client = new Client();
	}

	/** @AfterScenario */
	public function tearDownScenario() {
	}


	/**
	 * @param string $userName
	 * @return string
	 */
	private function getPasswordForUser($userName) {
		if($userName === 'admin') {
			return 'admin';
		}
		return '123456';
	}

	/**
	 * @When user :user uploads file :source to :destination with checksum :checksum
	 */
	public function userUploadsFileToWithChecksum($user, $source, $destination, $checksum)
	{
		$file = \GuzzleHttp\Stream\Stream::factory(fopen($source, 'r'));
		try {
			$this->response = $this->client->put(
				$this->baseUrl . '/remote.php/webdav' . $destination,
				[
					'auth' => [
						$user,
						$this->getPasswordForUser($user)
					],
					'body' => $file,
					'headers' => [
						'OC-Checksum' => $checksum
					]
				]
			);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Then The webdav response should have a status code :statusCode
	 */
	public function theWebdavResponseShouldHaveAStatusCode($statusCode) {
		if((int)$statusCode !== $this->response->getStatusCode()) {
			throw new \Exception("Expected $statusCode, got ".$this->response->getStatusCode());
		}
	}

	/**
	 * @When user :user request the checksum of :path via propfind
	 */
	public function userRequestTheChecksumOfViaPropfind($user, $path)
	{
		$request = $this->client->createRequest(
			'PROPFIND',
			$this->baseUrl . '/remote.php/webdav' . $path,
			[
				'body' => '<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
  <d:prop>
    <oc:checksums />
  </d:prop>
</d:propfind>',
				'auth' => [
					$user,
					$this->getPasswordForUser($user),
				]
			]
		);
		$this->response = $this->client->send($request);
	}

	/**
	 * @Then The webdav checksum should match :checksum
	 */
	public function theWebdavChecksumShouldMatch($checksum)
	{
		$service = new Sabre\Xml\Service();
		$parsed = $service->parse($this->response->getBody()->getContents());

		/*
		 * Fetch the checksum array
		 * Maybe we want to do this a bit cleaner ;)
		 */
		$checksums = $parsed[0]['value'][1]['value'][0]['value'][0];

		if ($checksums['value'][0]['value'] !== $checksum) {
			throw new \Exception("Expected $checksum, got ".$checksums['value'][0]['value']);
		}
	}

	/**
	 * @When user :user downloads the file :path
	 */
	public function userDownloadsTheFile($user, $path)
	{
		$this->response = $this->client->get(
			$this->baseUrl . '/remote.php/webdav' . $path,
			[
				'auth' => [
					$user,
					$this->getPasswordForUser($user),
				]
			]
		);
	}

	/**
	 * @Then The header checksum should match :checksum
	 */
	public function theHeaderChecksumShouldMatch($checksum)
	{
		if ($this->response->getHeader('OC-Checksum') !== $checksum) {
			throw new \Exception("Expected $checksum, got ".$this->response->getHeader('OC-Checksum'));
		}
	}

	/**
	 * @Given User :user copied file :source to :destination
	 */
	public function userCopiedFileTo($user, $source, $destination)
	{
		$request = $this->client->createRequest(
			'MOVE',
			$this->baseUrl . '/remote.php/webdav' . $source,
			[
				'auth' => [
					$user,
					$this->getPasswordForUser($user),
				],
				'headers' => [
					'Destination' => $this->baseUrl . '/remote.php/webdav' . $destination,
				],
			]
		);
		$this->response = $this->client->send($request);
	}

	/**
	 * @Then The webdav checksum should be empty
	 */
	public function theWebdavChecksumShouldBeEmpty()
	{
		$service = new Sabre\Xml\Service();
		$parsed = $service->parse($this->response->getBody()->getContents());

		/*
		 * Fetch the checksum array
		 * Maybe we want to do this a bit cleaner ;)
		 */
		$status = $parsed[0]['value'][1]['value'][1]['value'];

		if ($status !== 'HTTP/1.1 404 Not Found') {
			throw new \Exception("Expected 'HTTP/1.1 404 Not Found', got ".$status);
		}
	}

	/**
	 * @Then The OC-Checksum header should not be there
	 */
	public function theOcChecksumHeaderShouldNotBeThere()
	{
		if ($this->response->hasHeader('OC-Checksum')) {
			throw new \Exception("Expected no checksum header but got ".$this->response->getHeader('OC-Checksum'));
		}
	}

	/**
	 * @Given user :user uploads chunk file :num of :total with :data to :destination with checksum :checksum
	 */
	public function userUploadsChunkFileOfWithToWithChecksum($user, $num, $total, $data, $destination, $checksum)
	{
		$num -= 1;
		$this->response = $this->client->put(
			$this->baseUrl . '/remote.php/webdav' . $destination . '-chunking-42-'.$total.'-'.$num,
			[
				'auth' => [
					$user,
					$this->getPasswordForUser($user)
				],
				'body' => $data,
				'headers' => [
					'OC-Checksum' => $checksum,
					'OC-Chunked' => '1',
				]
			]
		);

	}
}
