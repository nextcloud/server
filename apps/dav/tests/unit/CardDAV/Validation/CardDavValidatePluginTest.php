<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV\Validation;

use OCA\DAV\CardDAV\Validation\CardDavValidatePlugin;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class CardDavValidatePluginTest extends TestCase {

	private CardDavValidatePlugin $plugin;
	private IAppConfig|MockObject $config;
	private RequestInterface|MockObject $request;
	private ResponseInterface|MockObject $response;

	protected function setUp(): void {
		parent::setUp();
		// construct mock objects
		$this->config = $this->createMock(IAppConfig::class);
		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->plugin = new CardDavValidatePlugin(
			$this->config,
		);
	}

	public function testPutSizeLessThenLimit(): void {
		
		// construct method responses
		$this->config
			->method('getValueInt')
			->with('dav', 'card_size_limit', 5242880)
			->willReturn(5242880);
		$this->request
			->method('getRawServerValue')
			->with('CONTENT_LENGTH')
			->willReturn('1024');
		// test condition
		$this->assertTrue(
			$this->plugin->beforePut($this->request, $this->response)
		);
		
	}

	public function testPutSizeMoreThenLimit(): void {
		
		// construct method responses
		$this->config
			->method('getValueInt')
			->with('dav', 'card_size_limit', 5242880)
			->willReturn(5242880);
		$this->request
			->method('getRawServerValue')
			->with('CONTENT_LENGTH')
			->willReturn('6242880');
		$this->expectException(Forbidden::class);
		// test condition
		$this->plugin->beforePut($this->request, $this->response);
		
	}

	public function testPutNoEmail(): void {
		$vcard = $this->getVCardWithEmail();

		$this->request
			->method('getBodyAsString')
			->willReturn($vcard);
		$this->assertTrue(
			$this->plugin->beforePut($this->request, $this->response),
		);
	}
	public function testPutValidEmail(): void {
		$vcard = $this->getVCardWithEmail('example@nextcloud.com');

		$this->request
			->method('getBodyAsString')
			->willReturn($vcard);
		$this->assertTrue(
			$this->plugin->beforePut($this->request, $this->response),
		);
	}
	public function testPutInvalidEmail(): void {
		$vcard = $this->getVCardWithEmail('example-nextcloud');

		$this->request
			->method('getBodyAsString')
			->willReturn($vcard);
		$this->expectException(BadRequest::class);
		$this->assertTrue(
			$this->plugin->beforePut($this->request, $this->response),
		);
	}

	public function testPutTrimmingEmail(): void {
		$vcard = $this->getVCardWithEmail(' example@nextcloud.com ');
		$vcardTrimmed = $this->getVCardWithEmail('example@nextcloud.com');

		$this->request
			->method('getBodyAsString')
			->willReturn($vcard);
		
		$this->request
			->expects($this->once())
			->method('setBody')
			->with($this->equalTo($vcardTrimmed));

		$this->assertTrue(
			$this->plugin->beforePut($this->request, $this->response),
		);
	}

	private function getVCardWithEmail($email) {
		return <<<EOF
		BEGIN:VCARD\r
		VERSION:4.0\r
		PRODID:-//Nextcloud Contacts v7.0.4\r
		UID:b9a0f10b-a304-4970-8a09-c62bb6a1831b\r
		FN:Test\r
		EMAIL;TYPE=HOME:$email\r
		REV;VALUE=DATE-AND-OR-TIME:20250314T200304Z\r
		END:VCARD\r\n
		EOF;
	}

}
