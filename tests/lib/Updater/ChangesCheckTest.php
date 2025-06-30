<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Updater;

use OC\Updater\Changes;
use OC\Updater\ChangesCheck;
use OC\Updater\ChangesMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ChangesCheckTest extends TestCase {
	/** @var IClientService|\PHPUnit\Framework\MockObject\MockObject */
	protected $clientService;

	/** @var ChangesCheck */
	protected $checker;

	/** @var ChangesMapper|\PHPUnit\Framework\MockObject\MockObject */
	protected $mapper;

	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->mapper = $this->createMock(ChangesMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->checker = new ChangesCheck($this->clientService, $this->mapper, $this->logger);
	}

	public static function statusCodeProvider(): array {
		return [
			[200, ChangesCheck::RESPONSE_HAS_CONTENT],
			[304, ChangesCheck::RESPONSE_USE_CACHE],
			[404, ChangesCheck::RESPONSE_NO_CONTENT],
			[418, ChangesCheck::RESPONSE_NO_CONTENT],
		];
	}

	/**
	 * @dataProvider statusCodeProvider
	 */
	public function testEvaluateResponse(int $statusCode, int $expected): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->atLeastOnce())
			->method('getStatusCode')
			->willReturn($statusCode);

		if (!in_array($statusCode, [200, 304, 404])) {
			$this->logger->expects($this->once())
				->method('debug');
		}

		$evaluation = $this->invokePrivate($this->checker, 'evaluateResponse', [$response]);
		$this->assertSame($expected, $evaluation);
	}

	public function testCacheResultInsert(): void {
		$version = '13.0.4';
		$entry = $this->createMock(Changes::class);
		$entry->expects($this->exactly(2))
			->method('__call')
			->willReturnMap([
				['getVersion', [], ''],
				['setVersion', [$version], null],
			]);

		$this->mapper->expects($this->once())
			->method('insert');
		$this->mapper->expects($this->never())
			->method('update');

		$this->invokePrivate($this->checker, 'cacheResult', [$entry, $version]);
	}

	public function testCacheResultUpdate(): void {
		$version = '13.0.4';
		$entry = $this->createMock(Changes::class);
		$entry->expects($this->once())
			->method('__call')
			->willReturn($version);

		$this->mapper->expects($this->never())
			->method('insert');
		$this->mapper->expects($this->once())
			->method('update');

		$this->invokePrivate($this->checker, 'cacheResult', [$entry, $version]);
	}

	public static function changesXMLProvider(): array {
		return [
			[ # 0 - full example
				'<?xml version="1.0" encoding="utf-8" ?>
<release xmlns:xsi= "http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://updates.nextcloud.com/changelog_server/schema.xsd"
         version="13.0.0">
    <changelog href="https://nextcloud.com/changelog/#13-0-0"/>
    <whatsNew lang="en">
        <regular>
            <item>Refined user interface</item>
            <item>End-to-end Encryption</item>
            <item>Video and Text Chat</item>
        </regular>
        <admin>
            <item>Changes to the Nginx configuration</item>
            <item>Theming: CSS files were consolidated</item>
        </admin>
    </whatsNew>
    <whatsNew lang="de">
        <regular>
            <item>Überarbeitete Benutzerschnittstelle</item>
            <item>Ende-zu-Ende Verschlüsselung</item>
            <item>Video- und Text-Chat</item>
        </regular>
        <admin>
            <item>Änderungen an der Nginx Konfiguration</item>
            <item>Theming: CSS Dateien wurden konsolidiert</item>
        </admin>
    </whatsNew>
</release>',
				[
					'changelogURL' => 'https://nextcloud.com/changelog/#13-0-0',
					'whatsNew' => [
						'en' => [
							'regular' => [
								'Refined user interface',
								'End-to-end Encryption',
								'Video and Text Chat'
							],
							'admin' => [
								'Changes to the Nginx configuration',
								'Theming: CSS files were consolidated'
							],
						],
						'de' => [
							'regular' => [
								'Überarbeitete Benutzerschnittstelle',
								'Ende-zu-Ende Verschlüsselung',
								'Video- und Text-Chat'
							],
							'admin' => [
								'Änderungen an der Nginx Konfiguration',
								'Theming: CSS Dateien wurden konsolidiert'
							],
						],
					],
				]
			],
			[ # 1- admin part not translated
				'<?xml version="1.0" encoding="utf-8" ?>
<release xmlns:xsi= "http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://updates.nextcloud.com/changelog_server/schema.xsd"
         version="13.0.0">
    <changelog href="https://nextcloud.com/changelog/#13-0-0"/>
    <whatsNew lang="en">
        <regular>
            <item>Refined user interface</item>
            <item>End-to-end Encryption</item>
            <item>Video and Text Chat</item>
        </regular>
        <admin>
            <item>Changes to the Nginx configuration</item>
            <item>Theming: CSS files were consolidated</item>
        </admin>
    </whatsNew>
    <whatsNew lang="de">
        <regular>
            <item>Überarbeitete Benutzerschnittstelle</item>
            <item>Ende-zu-Ende Verschlüsselung</item>
            <item>Video- und Text-Chat</item>
        </regular>
    </whatsNew>
</release>',
				[
					'changelogURL' => 'https://nextcloud.com/changelog/#13-0-0',
					'whatsNew' => [
						'en' => [
							'regular' => [
								'Refined user interface',
								'End-to-end Encryption',
								'Video and Text Chat'
							],
							'admin' => [
								'Changes to the Nginx configuration',
								'Theming: CSS files were consolidated'
							],
						],
						'de' => [
							'regular' => [
								'Überarbeitete Benutzerschnittstelle',
								'Ende-zu-Ende Verschlüsselung',
								'Video- und Text-Chat'
							],
							'admin' => [
							],
						],
					],
				]
			],
			[ # 2 - minimal set
				'<?xml version="1.0" encoding="utf-8" ?>
<release xmlns:xsi= "http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://updates.nextcloud.com/changelog_server/schema.xsd"
         version="13.0.0">
    <changelog href="https://nextcloud.com/changelog/#13-0-0"/>
    <whatsNew lang="en">
        <regular>
            <item>Refined user interface</item>
            <item>End-to-end Encryption</item>
            <item>Video and Text Chat</item>
        </regular>
    </whatsNew>
</release>',
				[
					'changelogURL' => 'https://nextcloud.com/changelog/#13-0-0',
					'whatsNew' => [
						'en' => [
							'regular' => [
								'Refined user interface',
								'End-to-end Encryption',
								'Video and Text Chat'
							],
							'admin' => [],
						],
					],
				]
			],
			[ # 3 - minimal set (procrastinator edition)
				'<?xml version="1.0" encoding="utf-8" ?>
<release xmlns:xsi= "http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://updates.nextcloud.com/changelog_server/schema.xsd"
         version="13.0.0">
    <changelog href="https://nextcloud.com/changelog/#13-0-0"/>
    <whatsNew lang="en">
        <regular>
            <item>Write this tomorrow</item>
        </regular>
    </whatsNew>
</release>',
				[
					'changelogURL' => 'https://nextcloud.com/changelog/#13-0-0',
					'whatsNew' => [
						'en' => [
							'regular' => [
								'Write this tomorrow',
							],
							'admin' => [],
						],
					],
				]
			],
			[ # 4 - empty
				'',
				[]
			],
		];
	}

	/**
	 * @dataProvider changesXMLProvider
	 */
	public function testExtractData(string $body, array $expected): void {
		$actual = $this->invokePrivate($this->checker, 'extractData', [$body]);
		$this->assertSame($expected, $actual);
	}

	public static function etagProvider() {
		return [
			[''],
			['a27aab83d8205d73978435076e53d143']
		];
	}

	/**
	 * @dataProvider etagProvider
	 */
	public function testQueryChangesServer(string $etag): void {
		$uri = 'https://changes.nextcloud.server/?13.0.5';
		$entry = $this->createMock(Changes::class);
		$entry->expects($this->any())
			->method('__call')
			->willReturn($etag);

		$expectedHeaders = $etag === '' ? [] : ['If-None-Match' => [$etag]];

		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('get')
			->with($uri, ['headers' => $expectedHeaders])
			->willReturn($this->createMock(IResponse::class));

		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$response = $this->invokePrivate($this->checker, 'queryChangesServer', [$uri, $entry]);
		$this->assertInstanceOf(IResponse::class, $response);
	}

	public static function versionProvider(): array {
		return [
			['13.0.7', '13.0.7'],
			['13.0.7.3', '13.0.7'],
			['13.0.7.3.42', '13.0.7'],
			['13.0', '13.0.0'],
			['13', '13.0.0'],
			['', '0.0.0'],
		];
	}

	/**
	 * @dataProvider versionProvider
	 */
	public function testNormalizeVersion(string $input, string $expected): void {
		$normalized = $this->checker->normalizeVersion($input);
		$this->assertSame($expected, $normalized);
	}

	public static function changeDataProvider():array {
		$testDataFound = $testDataNotFound = self::versionProvider();
		array_walk($testDataFound, static function (&$params): void {
			$params[] = true;
		});
		array_walk($testDataNotFound, static function (&$params): void {
			$params[] = false;
		});
		return array_merge($testDataFound, $testDataNotFound);
	}

	/**
	 * @dataProvider changeDataProvider
	 *
	 */
	public function testGetChangesForVersion(string $inputVersion, string $normalizedVersion, bool $isFound): void {
		$mocker = $this->mapper->expects($this->once())
			->method('getChanges')
			->with($normalizedVersion);

		if (!$isFound) {
			$this->expectException(DoesNotExistException::class);
			$mocker->willThrowException(new DoesNotExistException('Changes info is not present'));
		} else {
			$entry = $this->createMock(Changes::class);
			$entry->expects($this->once())
				->method('__call')
				->with('getData')
				->willReturn('{"changelogURL":"https:\/\/nextcloud.com\/changelog\/#13-0-0","whatsNew":{"en":{"regular":["Refined user interface","End-to-end Encryption","Video and Text Chat"],"admin":["Changes to the Nginx configuration","Theming: CSS files were consolidated"]},"de":{"regular":["\u00dcberarbeitete Benutzerschnittstelle","Ende-zu-Ende Verschl\u00fcsselung","Video- und Text-Chat"],"admin":["\u00c4nderungen an der Nginx Konfiguration","Theming: CSS Dateien wurden konsolidiert"]}}}');

			$mocker->willReturn($entry);
		}

		/** @noinspection PhpUnhandledExceptionInspection */
		$data = $this->checker->getChangesForVersion($inputVersion);
		$this->assertTrue(isset($data['whatsNew']['en']['regular']));
		$this->assertTrue(isset($data['changelogURL']));
	}

	public function testGetChangesForVersionEmptyData(): void {
		$entry = $this->createMock(Changes::class);
		$entry->expects($this->once())
			->method('__call')
			->with('getData')
			->willReturn('');

		$this->mapper->expects($this->once())
			->method('getChanges')
			->with('13.0.7')
			->willReturn($entry);

		$this->expectException(DoesNotExistException::class);
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->checker->getChangesForVersion('13.0.7');
	}
}
