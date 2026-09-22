<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Listener;

use OCA\DAV\BackgroundJob\RegenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Listener\BirthdayListener;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BirthdayListenerTest extends TestCase {
	private BirthdayService&MockObject $birthdayService;
	private IJobList&MockObject $jobList;
	private BirthdayListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->birthdayService = $this->createMock(BirthdayService::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->listener = new BirthdayListener(
			$this->birthdayService,
			$this->jobList,
		);
	}

	public function testCardCreatedDelegatesToOnCardChanged(): void {
		$cardData = [
			'uri' => 'alice.vcf',
			'carddata' => 'BEGIN:VCARD\r\nEND:VCARD',
		];

		$this->birthdayService->expects(self::once())
			->method('onCardChanged')
			->with(42, 'alice.vcf', 'BEGIN:VCARD\r\nEND:VCARD');

		$this->birthdayService->expects(self::never())
			->method('onCardDeleted');

		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle(new CardCreatedEvent(
			42,
			[],
			[],
			$cardData,
		));
	}

	public function testCardUpdatedDelegatesToOnCardChanged(): void {
		$cardData = [
			'uri' => 'alice.vcf',
			'carddata' => 'BEGIN:VCARD\r\nVERSION:3.0\r\nEND:VCARD',
		];

		$this->birthdayService->expects(self::once())
			->method('onCardChanged')
			->with(42, 'alice.vcf', 'BEGIN:VCARD\r\nVERSION:3.0\r\nEND:VCARD');

		$this->birthdayService->expects(self::never())
			->method('onCardDeleted');

		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle(new CardUpdatedEvent(
			42,
			[],
			[],
			$cardData,
		));
	}

	public function testCardDeletedDelegatesToOnCardDeleted(): void {
		$cardData = [
			'uri' => 'alice.vcf',
			'carddata' => 'BEGIN:VCARD\r\nVERSION:3.0\r\nEND:VCARD',
		];

		$this->birthdayService->expects(self::once())
			->method('onCardDeleted')
			->with(42, 'alice.vcf');

		$this->birthdayService->expects(self::never())
			->method('onCardChanged');

		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle(new CardDeletedEvent(
			42,
			[],
			[],
			$cardData,
		));
	}

	public function testAddressBookDeletedQueuesOwnerRebuild(): void {
		$this->birthdayService->expects(self::once())
			->method('getAffectedPrincipalsFromShares')
			->with([])
			->willReturn([]);

		$queuedJobs = [];

		$this->jobList->expects(self::once())
			->method('add')
			->willReturnCallback(
				function (string $jobClass, array $argument) use (&$queuedJobs): void {
					$queuedJobs[] = [$jobClass, $argument];
				},
			);

		$this->birthdayService->expects(self::never())
			->method('onCardChanged');

		$this->birthdayService->expects(self::never())
			->method('onCardDeleted');

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts',
				'principaluri' => 'principals/users/alice',
			],
			[],
		));

		self::assertSame([
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'alice'],
			],
		], $queuedJobs);
	}

	public function testAddressBookDeletedQueuesOwnerAndDirectShareeRebuilds(): void {
		$shares = [
			[
				'{http://owncloud.org/ns}principal' => 'principals/users/bob',
				'{http://owncloud.org/ns}group-share' => false,
			],
		];

		$this->birthdayService->expects(self::once())
			->method('getAffectedPrincipalsFromShares')
			->with($shares)
			->willReturn([
				'principals/users/bob',
			]);

		$queuedJobs = [];

		$this->jobList->expects(self::exactly(2))
			->method('add')
			->willReturnCallback(
				function (string $jobClass, array $argument) use (&$queuedJobs): void {
					$queuedJobs[] = [$jobClass, $argument];
				},
			);

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts',
				'principaluri' => 'principals/users/alice',
			],
			$shares,
		));

		self::assertSame([
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'alice'],
			],
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'bob'],
			],
		], $queuedJobs);
	}

	public function testAddressBookDeletedQueuesGroupMembersReturnedByBirthdayService(): void {
		$shares = [
			[
				'{http://owncloud.org/ns}principal' => 'principals/groups/family',
				'{http://owncloud.org/ns}group-share' => true,
			],
		];

		$this->birthdayService->expects(self::once())
			->method('getAffectedPrincipalsFromShares')
			->with($shares)
			->willReturn([
				'principals/users/bob',
				'principals/users/carol',
			]);

		$queuedJobs = [];

		$this->jobList->expects(self::exactly(3))
			->method('add')
			->willReturnCallback(
				function (string $jobClass, array $argument) use (&$queuedJobs): void {
					$queuedJobs[] = [$jobClass, $argument];
				},
			);

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts',
				'principaluri' => 'principals/users/alice',
			],
			$shares,
		));

		self::assertSame([
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'alice'],
			],
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'bob'],
			],
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'carol'],
			],
		], $queuedJobs);
	}

	public function testAddressBookDeletedDeduplicatesPrincipals(): void {
		$shares = [
			[
				'{http://owncloud.org/ns}principal' => 'principals/users/bob',
				'{http://owncloud.org/ns}group-share' => false,
			],
			[
				'{http://owncloud.org/ns}principal' => 'principals/users/carol',
				'{http://owncloud.org/ns}group-share' => false,
			],
		];

		$this->birthdayService->expects(self::once())
			->method('getAffectedPrincipalsFromShares')
			->with($shares)
			->willReturn([
				'principals/users/bob',
				'principals/users/bob',
				'principals/users/carol',
			]);

		$queuedJobs = [];

		$this->jobList->expects(self::exactly(3))
			->method('add')
			->willReturnCallback(
				function (string $jobClass, array $argument) use (&$queuedJobs): void {
					$queuedJobs[] = [$jobClass, $argument];
				},
			);

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts',
				'principaluri' => 'principals/users/alice',
			],
			$shares,
		));

		self::assertSame([
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'alice'],
			],
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'bob'],
			],
			[
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'carol'],
			],
		], $queuedJobs);
	}

  public function testAddressBookDeletedQueuesOnlyUserPrincipals(): void {
  	$shares = [
	  	[
		  	'{http://owncloud.org/ns}principal' => 'principals/groups/family',
		  	'{http://owncloud.org/ns}group-share' => true,
		  ],
	  ];

  	$this->birthdayService->expects(self::once())
  		->method('getAffectedPrincipalsFromShares')
  		->with($shares)
  		->willReturn([
  			'principals/users/carol',
  		]);

  	$this->jobList->expects(self::exactly(2))
  		->method('add')
  		->willReturnCallback(
  			function (string $jobClass, array $argument): void {
  				static $expected = [
  					[
  						RegenerateBirthdayCalendarBackgroundJob::class,
   						['userId' => 'alice'],
	  				],
		  			[
		  				RegenerateBirthdayCalendarBackgroundJob::class,
		  				['userId' => 'carol'],
		  			],
		  		];

  				self::assertSame(array_shift($expected), [$jobClass, $argument]);
  			},
  		);

	  $this->listener->handle(new AddressBookDeletedEvent(
  		123,
  		[
  			'principaluri' => 'principals/users/alice',
  			'uri' => 'contacts',
  		],
  		$shares,
  	));
  }

	public function testAddressBookDeletedWithMissingOwnerDoesNotQueueAJob(): void {
		$this->birthdayService->expects(self::never())
			->method('getAffectedPrincipalsFromShares');

		$this->jobList->expects(self::never())
			->method('add');

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts',
			],
			[],
		));
	}

	public function testAddressBookDeletedDoesNotPassCardUrisOrCardData(): void {
		$this->birthdayService->expects(self::once())
			->method('getAffectedPrincipalsFromShares')
			->with([])
			->willReturn([]);

		$this->jobList->expects(self::once())
			->method('add')
			->with(
				RegenerateBirthdayCalendarBackgroundJob::class,
				['userId' => 'alice'],
			);

		$this->listener->handle(new AddressBookDeletedEvent(
			123,
			[
				'id' => 123,
				'uri' => 'contacts-with--delimiters',
				'principaluri' => 'principals/users/alice',
			],
			[],
		));
	}
}
