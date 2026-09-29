<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Search;

use NCU\Search\AccountScopedSearchResult;
use OC\Files\Search\SearchComparison;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Jail;
use OCA\Files\Search\FileAccountScopedSearchProvider;
use OCP\Files\IRootFolder;
use OCP\Files\Search\ISearchComparison;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

#[Group('DB')]
class FileAccountScopedSearchProviderTest extends TestCase {
	use MountProviderTrait;
	use UserTrait;

	private string $userId;
	private FileAccountScopedSearchProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userId = $this->getUniqueID('user');
		$this->createUser($this->userId, $this->userId);

		$home = new Temporary([]);
		$home->mkdir('Docs');
		$home->file_put_contents('Docs/in-docs.txt', 'x');
		$home->file_put_contents('root.txt', 'x');
		$this->registerMount($this->userId, $home, '/' . $this->userId . '/files');

		$external = new Temporary([]);
		$external->file_put_contents('external.txt', 'x');
		$this->registerMount($this->userId, $external, '/' . $this->userId . '/files/External');

		$nested = new Temporary([]);
		$nested->file_put_contents('nested.txt', 'x');
		$this->registerMount($this->userId, $nested, '/' . $this->userId . '/files/Docs/Nested');

		// A jail into a shared storage, as group folders and received shares are mounted.
		$shared = new Temporary([]);
		$shared->mkdir('__groupfolders');
		$shared->mkdir('__groupfolders/1');
		$shared->mkdir('__groupfolders/2');
		$shared->file_put_contents('__groupfolders/1/team.txt', 'x');
		$shared->file_put_contents('__groupfolders/2/other-team.txt', 'x');
		$shared->getScanner()->scan('');
		$this->registerMount($this->userId, new Jail(['storage' => $shared, 'root' => '__groupfolders/1']), '/' . $this->userId . '/files/Team');

		$this->loginAsUser($this->userId);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$this->provider = new FileAccountScopedSearchProvider(
			$l10n,
			Server::get(IRootFolder::class),
			$this->createMock(IShareManager::class),
			$this->createMock(ISystemTagObjectMapper::class),
			$this->createMock(ISystemTagManager::class),
			$this->createMock(IFullTextSearchManager::class),
			$this->createMock(IAppConfig::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->logout();
		parent::tearDown();
	}

	/**
	 * @return list<string>
	 */
	private function searchPaths(ISearchComparison $filter): array {
		$paths = array_map(
			static fn (AccountScopedSearchResult $result): string => $result->getMetadata()['path'],
			iterator_to_array($this->provider->search($this->userId, $filter, 100), false),
		);
		sort($paths);

		return $paths;
	}

	public static function pathProvider(): array {
		return [
			'home file' => [ISearchComparison::COMPARE_EQUAL, 'root.txt', ['/root.txt']],
			'leading slash' => [ISearchComparison::COMPARE_EQUAL, '/Docs/in-docs.txt', ['/Docs/in-docs.txt']],
			'external storage file' => [ISearchComparison::COMPARE_EQUAL, 'External/external.txt', ['/External/external.txt']],
			'jailed file' => [ISearchComparison::COMPARE_EQUAL, 'Team/team.txt', ['/Team/team.txt']],
			'missing file' => [ISearchComparison::COMPARE_EQUAL, 'missing.txt', []],
			'in' => [
				ISearchComparison::COMPARE_IN,
				['External/external.txt', 'Team/team.txt', 'missing.txt'],
				['/External/external.txt', '/Team/team.txt'],
			],
			'empty in' => [ISearchComparison::COMPARE_IN, [], []],
			'folder with a mount inside' => [ISearchComparison::COMPARE_LIKE, 'Docs/%', ['/Docs/Nested/nested.txt', '/Docs/in-docs.txt']],
			'external storage root' => [ISearchComparison::COMPARE_LIKE, 'External/%', ['/External/external.txt']],
			'jail root excludes the rest of the storage' => [ISearchComparison::COMPARE_LIKE, 'Team/%', ['/Team/team.txt']],
			'user folder' => [
				ISearchComparison::COMPARE_LIKE,
				'/%',
				['/Docs/Nested/nested.txt', '/Docs/in-docs.txt', '/External/external.txt', '/Team/team.txt', '/root.txt'],
			],
			'missing folder' => [ISearchComparison::COMPARE_LIKE, 'Missing/%', []],
			'file as folder' => [ISearchComparison::COMPARE_LIKE, 'root.txt/%', []],
		];
	}

	#[DataProvider('pathProvider')]
	public function testSearchByPath(string $type, string|array $value, array $expected): void {
		$this->assertSame($expected, $this->searchPaths(new SearchComparison($type, 'path', $value)));
	}

	public static function unsupportedPathProvider(): array {
		return [
			'suffix pattern' => [ISearchComparison::COMPARE_LIKE, '%.txt'],
			'wildcard in folder' => [ISearchComparison::COMPARE_LIKE, 'Do_s/%'],
			'partial name' => [ISearchComparison::COMPARE_LIKE, 'Do%'],
			'ordering' => [ISearchComparison::COMPARE_GREATER_THAN, 'Docs'],
		];
	}

	#[DataProvider('unsupportedPathProvider')]
	public function testUnsupportedPathComparison(string $type, string $value): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->searchPaths(new SearchComparison($type, 'path', $value));
	}
}
