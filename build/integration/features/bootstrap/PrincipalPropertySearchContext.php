<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require __DIR__ . '/../../vendor/autoload.php';

use Behat\Behat\Context\Context;
use GuzzleHttp\BodySummarizer;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;

class PrincipalPropertySearchContext implements Context {
	private string $baseUrl;
	private Client $client;
	private ResponseInterface $response;

	public function __construct(string $baseUrl) {
		$this->baseUrl = $baseUrl;

		// in case of ci deployment we take the server url from the environment
		$testServerUrl = getenv('TEST_SERVER_URL');
		if ($testServerUrl !== false) {
			$this->baseUrl = substr($testServerUrl, 0, -5);
		}
	}

	/** @BeforeScenario */
	public function setUpScenario(): void {
		$this->client = $this->createGuzzleInstance();
	}

	/**
	 * Create a Guzzle client with a higher truncateAt value to read full error responses.
	 */
	private function createGuzzleInstance(): Client {
		$bodySummarizer = new BodySummarizer(2048);

		$stack = new HandlerStack(Utils::chooseHandler());
		$stack->push(Middleware::httpErrors($bodySummarizer), 'http_errors');
		$stack->push(Middleware::redirect(), 'allow_redirects');
		$stack->push(Middleware::cookies(), 'cookies');
		$stack->push(Middleware::prepareBody(), 'prepare_body');

		return new Client(['handler' => $stack]);
	}

	/**
	 * @When searching for a principal matching :match
	 * @param string $match
	 * @throws \Exception
	 */
	public function principalPropertySearch(string $match) {
		$davUrl = $this->baseUrl . '/remote.php/dav/';
		$user = 'admin';
		$password = 'admin';

		$this->response = $this->client->request(
			'REPORT',
			$davUrl,
			[
				'body' => '<x0:principal-property-search xmlns:x0="DAV:" test="anyof">
	<x0:property-search>
		<x0:prop>
			<x0:displayname/>
			<x2:email-address xmlns:x2="http://sabredav.org/ns"/>
		</x0:prop>
		<x0:match>' . $match . '</x0:match>
	</x0:property-search>
	<x0:prop>
		<x0:displayname/>
		<x1:calendar-user-type xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x1:calendar-user-address-set xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x0:principal-URL/>
		<x0:alternate-URI-set/>
		<x2:email-address xmlns:x2="http://sabredav.org/ns"/>
		<x3:language xmlns:x3="http://nextcloud.com/ns"/>
		<x1:calendar-home-set xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x1:schedule-inbox-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x1:schedule-outbox-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x1:schedule-default-calendar-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
		<x3:resource-type xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-type xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-make xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-model xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-is-electric xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-range xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-vehicle-seating-capacity xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-contact-person xmlns:x3="http://nextcloud.com/ns"/>
		<x3:resource-contact-person-vcard xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-type xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-seating-capacity xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-building-address xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-building-story xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-building-room-number xmlns:x3="http://nextcloud.com/ns"/>
		<x3:room-features xmlns:x3="http://nextcloud.com/ns"/>
	</x0:prop>
	<x0:apply-to-principal-collection-set/>
</x0:principal-property-search>
',
				'auth' => [
					$user,
					$password,
				],
				'headers' => [
					'Content-Type' => 'application/xml; charset=UTF-8',
					'Depth' => '0',
				],
			]
		);
	}

	/**
	 * @Then The search HTTP status code should be :code
	 * @param string $code
	 * @throws \Exception
	 */
	public function theHttpStatusCodeShouldBe(string $code): void {
		if ((int)$code !== $this->response->getStatusCode()) {
			throw new \Exception('Expected ' . (int)$code . ' got ' . $this->response->getStatusCode());
		}
	}

	/**
	 * @Then The search response should contain :needle
	 * @param string $needle
	 * @throws \Exception
	 */
	public function theResponseShouldContain(string $needle): void {
		$body = $this->response->getBody()->getContents();

		if (str_contains($body, $needle) === false) {
			throw new \Exception('Response does not contain "' . $needle . '"');
		}
	}
}
