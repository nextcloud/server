<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Contacts\ContactsMenu\Providers;

use OC\Contacts\ContactsMenu\Providers\EMailProvider;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\ILinkAction;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class EMailproviderTest extends TestCase {
	/** @var IActionFactory|MockObject */
	private $actionFactory;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	private EMailProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->actionFactory = $this->createMock(IActionFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->provider = new EMailProvider($this->actionFactory, $this->urlGenerator);
	}

	public function testProcess(): void {
		$entry = $this->createMock(IEntry::class);
		$action = $this->createMock(ILinkAction::class);
		$iconUrl = 'https://example.com/img/actions/icon.svg';
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->willReturn('img/actions/icon.svg');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('img/actions/icon.svg')
			->willReturn($iconUrl);
		$entry->expects($this->once())
			->method('getEMailAddresses')
			->willReturn([
				'user@example.com',
			]);
		$this->actionFactory->expects($this->once())
			->method('newEMailAction')
			->with($this->equalTo($iconUrl), $this->equalTo('user@example.com'), $this->equalTo('user@example.com'))
			->willReturn($action);
		$entry->expects($this->once())
			->method('addAction')
			->with($action);

		$this->provider->process($entry);
	}

	public function testProcessEmptyAddress(): void {
		$entry = $this->createMock(IEntry::class);
		$iconUrl = 'https://example.com/img/actions/icon.svg';
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->willReturn('img/actions/icon.svg');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('img/actions/icon.svg')
			->willReturn($iconUrl);
		$entry->expects($this->once())
			->method('getEMailAddresses')
			->willReturn([
				'',
			]);
		$this->actionFactory->expects($this->never())
			->method('newEMailAction');
		$entry->expects($this->never())
			->method('addAction');

		$this->provider->process($entry);
	}
}
