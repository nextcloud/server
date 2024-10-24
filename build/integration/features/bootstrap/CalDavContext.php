<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class CalDavContext implements \Behat\Behat\Context\Context {
	/** @var string */
	private $baseUrl;
	/** @var Client */
	private $client;
	/** @var ResponseInterface */
	private $response;
	/** @var string */
	private $responseXml = '';

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
	public function setUpScenario() {
		$this->client = new Client();
		$this->responseXml = '';
	}

	/** @AfterScenario */
	public function afterScenario() {
		$davUrl = $this->baseUrl . '/remote.php/dav/calendars/admin/MyCalendar';
		try {
			$this->client->delete(
				$davUrl,
				[
					'auth' => [
						'admin',
						'admin',
					],
					'headers' => [
						'X-NC-CalDAV-No-Trashbin' => '1',
					]
				]
			);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
		}
	}

	/**
	 * @When :user requests calendar :calendar on the endpoint :endpoint
	 * @param string $user
	 * @param string $calendar
	 * @param string $endpoint
	 */
	public function requestsCalendar($user, $calendar, $endpoint) {
		$davUrl = $this->baseUrl . $endpoint . $calendar;

		$password = ($user === 'admin') ? 'admin' : '123456';
		try {
			$this->response = $this->client->request(
				'PROPFIND',
				$davUrl,
				[
					'auth' => [
						$user,
						$password,
					],
				]
			);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When :user requests principal :principal on the endpoint :endpoint
	 */
	public function requestsPrincipal(string $user, string $principal, string $endpoint): void {
		$davUrl = $this->baseUrl . $endpoint . $principal;

		$password = ($user === 'admin') ? 'admin' : '123456';
		try {
			$this->response = $this->client->request(
				'PROPFIND',
				$davUrl,
				[
					'headers' => [
						'Content-Type' => 'application/xml; charset=UTF-8',
						'Depth' => 0,
					],
					'body' => '<x0:propfind xmlns:x0="DAV:"><x0:prop><x0:displayname/><x1:calendar-user-type xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x1:calendar-user-address-set xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x0:principal-URL/><x0:alternate-URI-set/><x2:email-address xmlns:x2="http://sabredav.org/ns"/><x3:language xmlns:x3="http://nextcloud.com/ns"/><x1:calendar-home-set xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x1:schedule-inbox-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x1:schedule-outbox-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x1:schedule-default-calendar-URL xmlns:x1="urn:ietf:params:xml:ns:caldav"/><x3:resource-type xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-type xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-make xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-model xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-is-electric xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-range xmlns:x3="http://nextcloud.com/ns"/><x3:resource-vehicle-seating-capacity xmlns:x3="http://nextcloud.com/ns"/><x3:resource-contact-person xmlns:x3="http://nextcloud.com/ns"/><x3:resource-contact-person-vcard xmlns:x3="http://nextcloud.com/ns"/><x3:room-type xmlns:x3="http://nextcloud.com/ns"/><x3:room-seating-capacity xmlns:x3="http://nextcloud.com/ns"/><x3:room-building-address xmlns:x3="http://nextcloud.com/ns"/><x3:room-building-story xmlns:x3="http://nextcloud.com/ns"/><x3:room-building-room-number xmlns:x3="http://nextcloud.com/ns"/><x3:room-features xmlns:x3="http://nextcloud.com/ns"/><x0:principal-collection-set/><x0:supported-report-set/></x0:prop></x0:propfind>',
					'auth' => [
						$user,
						$password,
					],
				]
			);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Then The CalDAV response should contain a property :key
	 * @throws \Exception
	 */
	public function theCaldavResponseShouldContainAProperty(string $key): void {
		/** @var \Sabre\DAV\Xml\Response\MultiStatus $multiStatus */
		$multiStatus = $this->responseXml['value'];
		$responses = $multiStatus->getResponses()[0]->getResponseProperties();
		if (!isset($responses[200])) {
			throw new \Exception(
				sprintf(
					'Expected code 200 got [%s]',
					implode(',', array_keys($responses)),
				)
			);
		}

		$props = $responses[200];
		if (!array_key_exists($key, $props)) {
			throw new \Exception(
				sprintf(
					'Expected property %s in %s',
					$key,
					json_encode($props, JSON_PRETTY_PRINT),
				)
			);
		}
	}

	/**
	 * @Then The CalDAV response should contain a property :key with a href value :value
	 * @throws \Exception
	 */
	public function theCaldavResponseShouldContainAPropertyWithHrefValue(
		string $key,
		string $value,
	): void {
		/** @var \Sabre\DAV\Xml\Response\MultiStatus $multiStatus */
		$multiStatus = $this->responseXml['value'];
		$responses = $multiStatus->getResponses()[0]->getResponseProperties();
		if (!isset($responses[200])) {
			throw new \Exception(
				sprintf(
					'Expected code 200 got [%s]',
					implode(',', array_keys($responses)),
				)
			);
		}

		$props = $responses[200];
		if (!array_key_exists($key, $props)) {
			throw new \Exception("Cannot find property \"$key\"");
		}

		$actualValue = $props[$key]->getHref();
		if ($actualValue !== $value) {
			throw new \Exception("Property \"$key\" found with value \"$actualValue\", expected \"$value\"");
		}
	}

	/**
	 * @Then The CalDAV response should be multi status
	 * @throws \Exception
	 */
	public function theCaldavResponseShouldBeMultiStatus(): void {
		if ($this->response->getStatusCode() !== 207) {
			throw new \Exception(
				sprintf(
					'Expected code 207 got %s',
					$this->response->getStatusCode()
				)
			);
		}

		$body = $this->response->getBody()->getContents();
		if ($body && substr($body, 0, 1) === '<') {
			$reader = new Sabre\Xml\Reader();
			$reader->xml($body);
			$reader->elementMap['{DAV:}multistatus'] = \Sabre\DAV\Xml\Response\MultiStatus::class;
			$reader->elementMap['{DAV:}response'] = \Sabre\DAV\Xml\Element\Response::class;
			$reader->elementMap['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'] = \Sabre\DAV\Xml\Property\Href::class;
			$this->responseXml = $reader->parse();
		}
	}

	/**
	 * @Then The CalDAV HTTP status code should be :code
	 * @param int $code
	 * @throws \Exception
	 */
	public function theCaldavHttpStatusCodeShouldBe($code) {
		if ((int)$code !== $this->response->getStatusCode()) {
			throw new \Exception(
				sprintf(
					'Expected %s got %s',
					(int)$code,
					$this->response->getStatusCode()
				)
			);
		}

		$body = $this->response->getBody()->getContents();
		if ($body && substr($body, 0, 1) === '<') {
			$reader = new Sabre\Xml\Reader();
			$reader->xml($body);
			$this->responseXml = $reader->parse();
		}
	}

	/**
	 * @Then The exception is :message
	 * @param string $message
	 * @throws \Exception
	 */
	public function theExceptionIs($message) {
		$result = $this->responseXml['value'][0]['value'];

		if ($message !== $result) {
			throw new \Exception(
				sprintf(
					'Expected %s got %s',
					$message,
					$result
				)
			);
		}
	}

	/**
	 * @Then The error message is :message
	 * @param string $message
	 * @throws \Exception
	 */
	public function theErrorMessageIs($message) {
		$result = $this->responseXml['value'][1]['value'];

		if ($message !== $result) {
			throw new \Exception(
				sprintf(
					'Expected %s got %s',
					$message,
					$result
				)
			);
		}
	}

	/**
	 * @Given :user creates a calendar named :name
	 * @param string $user
	 * @param string $name
	 */
	public function createsACalendarNamed($user, $name) {
		$davUrl = $this->baseUrl . '/remote.php/dav/calendars/' . $user . '/' . $name;
		$password = ($user === 'admin') ? 'admin' : '123456';

		$this->response = $this->client->request(
			'MKCALENDAR',
			$davUrl,
			[
				'body' => '<c:mkcalendar xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:a="http://apple.com/ns/ical/" xmlns:o="http://owncloud.org/ns"><d:set><d:prop><d:displayname>test</d:displayname><o:calendar-enabled>1</o:calendar-enabled><a:calendar-color>#21213D</a:calendar-color><c:supported-calendar-component-set><c:comp name="VEVENT"/></c:supported-calendar-component-set></d:prop></d:set></c:mkcalendar>',
				'auth' => [
					$user,
					$password,
				],
			]
		);
	}

	/**
	 * @Then :user publicly shares the calendar named :name
	 *
	 * @param string $user
	 * @param string $name
	 */
	public function publiclySharesTheCalendarNamed($user, $name) {
		$davUrl = $this->baseUrl . '/remote.php/dav/calendars/' . $user . '/' . $name;
		$password = ($user === 'admin') ? 'admin' : '123456';

		$this->response = $this->client->request(
			'POST',
			$davUrl,
			[
				'body' => '<cs:publish-calendar xmlns:cs="http://calendarserver.org/ns/"/>',
				'auth' => [
					$user,
					$password,
				],
				'headers' => [
					'Content-Type' => 'application/xml; charset=UTF-8',
				],
			]
		);
	}

	/**
	 * @Then There should be :amount calendars in the response body
	 *
	 * @param string $amount
	 */
	public function t($amount) {
		$jsonEncoded = json_encode($this->responseXml);
		$arrayElement = json_decode($jsonEncoded, true);
		$actual = count($arrayElement['value']) - 1;
		if ($actual !== (int)$amount) {
			throw new InvalidArgumentException(
				sprintf(
					'Expected %s got %s',
					$amount,
					$actual
				)
			);
		}
	}

	/**
	 * @When :user sends a create calendar request to :calendar on the endpoint :endpoint
	 */
	public function sendsCreateCalendarRequest(string $user, string $calendar, string $endpoint) {
		$davUrl = $this->baseUrl . $endpoint . $calendar;
		$password = ($user === 'admin') ? 'admin' : '123456';

		try {
			$this->response = $this->client->request(
				'MKCALENDAR',
				$davUrl,
				[
					'body' => '<c:mkcalendar xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:a="http://apple.com/ns/ical/" xmlns:o="http://owncloud.org/ns"><d:set><d:prop><d:displayname>test</d:displayname><o:calendar-enabled>1</o:calendar-enabled><a:calendar-color>#21213D</a:calendar-color><c:supported-calendar-component-set><c:comp name="VEVENT"/></c:supported-calendar-component-set></d:prop></d:set></c:mkcalendar>',
					'auth' => [
						$user,
						$password,
					],
				]
			);
		} catch (GuzzleException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Given :user updates property :key to href :value of principal :principal on the endpoint :endpoint
	 */
	public function updatesHrefPropertyOfPrincipal(
		string $user,
		string $key,
		string $value,
		string $principal,
		string $endpoint,
	): void {
		$davUrl = $this->baseUrl . $endpoint . $principal;
		$password = ($user === 'admin') ? 'admin' : '123456';

		$propPatch = new \Sabre\DAV\Xml\Request\PropPatch();
		$propPatch->properties = [$key => new \Sabre\DAV\Xml\Property\Href($value)];

		$xml = new \Sabre\Xml\Service();
		$body = $xml->write('{DAV:}propertyupdate', $propPatch, '/');

		$this->response = $this->client->request(
			'PROPPATCH',
			$davUrl,
			[
				'headers' => [
					'Content-Type' => 'application/xml; charset=UTF-8',
				],
				'body' => $body,
				'auth' => [
					$user,
					$password,
				],
			]
		);
	}
}
