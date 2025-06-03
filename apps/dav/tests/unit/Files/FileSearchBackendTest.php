<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files;

use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\ObjectTree;
use OCA\DAV\Files\FileSearchBackend;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchQuery;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IUser;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\Query\Limit;
use SearchDAV\Query\Literal;
use SearchDAV\Query\Operator;
use SearchDAV\Query\Query;
use SearchDAV\Query\Scope;
use Test\TestCase;

class FileSearchBackendTest extends TestCase {
	private ObjectTree&MockObject $tree;
	private IUser&MockObject $user;
	private IRootFolder&MockObject $rootFolder;
	private IManager&MockObject $shareManager;
	private View&MockObject $view;
	private Folder&MockObject $searchFolder;
	private Directory&MockObject $davFolder;
	private FileSearchBackend $search;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('test');

		$this->tree = $this->createMock(ObjectTree::class);
		$this->view = $this->createMock(View::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->searchFolder = $this->createMock(Folder::class);
		$fileInfo = $this->createMock(FileInfo::class);
		$this->davFolder = $this->createMock(Directory::class);

		$this->view->expects($this->any())
			->method('getRoot')
			->willReturn('');

		$this->view->expects($this->any())
			->method('getRelativePath')
			->willReturnArgument(0);

		$this->davFolder->expects($this->any())
			->method('getFileInfo')
			->willReturn($fileInfo);

		$this->rootFolder->expects($this->any())
			->method('get')
			->willReturn($this->searchFolder);

		$filesMetadataManager = $this->createMock(IFilesMetadataManager::class);

		$this->search = new FileSearchBackend($this->tree, $this->user, $this->rootFolder, $this->shareManager, $this->view, $filesMetadataManager);
	}

	public function testSearchFilename(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->once())
			->method('search')
			->with(new SearchQuery(
				new SearchComparison(
					ISearchComparison::COMPARE_EQUAL,
					'name',
					'foo'
				),
				0,
				0,
				[],
				$this->user
			))
			->willReturn([
				new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
			]);

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, '{DAV:}displayname', 'foo');
		$result = $this->search->search($query);

		$this->assertCount(1, $result);
		$this->assertEquals('/files/test/test/path', $result[0]->href);
	}

	public function testSearchMimetype(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->once())
			->method('search')
			->with(new SearchQuery(
				new SearchComparison(
					ISearchComparison::COMPARE_EQUAL,
					'mimetype',
					'foo'
				),
				0,
				0,
				[],
				$this->user
			))
			->willReturn([
				new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
			]);

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, '{DAV:}getcontenttype', 'foo');
		$result = $this->search->search($query);

		$this->assertCount(1, $result);
		$this->assertEquals('/files/test/test/path', $result[0]->href);
	}

	public function testSearchSize(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->once())
			->method('search')
			->with(new SearchQuery(
				new SearchComparison(
					ISearchComparison::COMPARE_GREATER_THAN,
					'size',
					10
				),
				0,
				0,
				[],
				$this->user
			))
			->willReturn([
				new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
			]);

		$query = $this->getBasicQuery(Operator::OPERATION_GREATER_THAN, FilesPlugin::SIZE_PROPERTYNAME, 10);
		$result = $this->search->search($query);

		$this->assertCount(1, $result);
		$this->assertEquals('/files/test/test/path', $result[0]->href);
	}

	public function testSearchMtime(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->once())
			->method('search')
			->with(new SearchQuery(
				new SearchComparison(
					ISearchComparison::COMPARE_GREATER_THAN,
					'mtime',
					10
				),
				0,
				0,
				[],
				$this->user
			))
			->willReturn([
				new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
			]);

		$query = $this->getBasicQuery(Operator::OPERATION_GREATER_THAN, '{DAV:}getlastmodified', 10);
		$result = $this->search->search($query);

		$this->assertCount(1, $result);
		$this->assertEquals('/files/test/test/path', $result[0]->href);
	}

	public function testSearchIsCollection(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->once())
			->method('search')
			->with(new SearchQuery(
				new SearchComparison(
					ISearchComparison::COMPARE_EQUAL,
					'mimetype',
					FileInfo::MIMETYPE_FOLDER
				),
				0,
				0,
				[],
				$this->user
			))
			->willReturn([
				new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
			]);

		$query = $this->getBasicQuery(Operator::OPERATION_IS_COLLECTION, 'yes');
		$result = $this->search->search($query);

		$this->assertCount(1, $result);
		$this->assertEquals('/files/test/test/path', $result[0]->href);
	}


	public function testSearchInvalidProp(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$this->searchFolder->expects($this->never())
			->method('search');

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, '{DAV:}getetag', 'foo');
		$this->search->search($query);
	}

	private function getBasicQuery(string $type, string $property, int|string|null $value = null) {
		$scope = new Scope('/', 'infinite');
		$scope->path = '/';
		$from = [$scope];
		$orderBy = [];
		$select = [];
		if (is_null($value)) {
			$where = new Operator(
				$type,
				[new Literal($property)]
			);
		} else {
			$where = new Operator(
				$type,
				[new SearchPropertyDefinition($property, true, true, true), new Literal($value)]
			);
		}
		$limit = new Limit();

		return new Query($select, $from, $where, $orderBy, $limit);
	}


	public function testSearchNonFolder(): void {
		$this->expectException(\InvalidArgumentException::class);

		$davNode = $this->createMock(File::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($davNode);

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, '{DAV:}displayname', 'foo');
		$this->search->search($query);
	}

	public function testSearchLimitOwnerBasic(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		/** @var ISearchQuery|null $receivedQuery */
		$receivedQuery = null;
		$this->searchFolder
			->method('search')
			->willReturnCallback(function ($query) use (&$receivedQuery) {
				$receivedQuery = $query;
				return [
					new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
				];
			});

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, FilesPlugin::OWNER_ID_PROPERTYNAME, $this->user->getUID());
		$this->search->search($query);

		$this->assertNotNull($receivedQuery);
		$this->assertTrue($receivedQuery->limitToHome());

		/** @var ISearchBinaryOperator $operator */
		$operator = $receivedQuery->getSearchOperation();
		$this->assertInstanceOf(ISearchBinaryOperator::class, $operator);
		$this->assertEquals(ISearchBinaryOperator::OPERATOR_AND, $operator->getType());
		$this->assertEmpty($operator->getArguments());
	}

	public function testSearchLimitOwnerNested(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		/** @var ISearchQuery|null $receivedQuery */
		$receivedQuery = null;
		$this->searchFolder
			->method('search')
			->willReturnCallback(function ($query) use (&$receivedQuery) {
				$receivedQuery = $query;
				return [
					new \OC\Files\Node\Folder($this->rootFolder, $this->view, '/test/path'),
				];
			});

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, FilesPlugin::OWNER_ID_PROPERTYNAME, $this->user->getUID());
		$query->where = new Operator(
			Operator::OPERATION_AND,
			[
				new Operator(
					Operator::OPERATION_EQUAL,
					[new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true), new Literal('image/png')]
				),
				new Operator(
					Operator::OPERATION_EQUAL,
					[new SearchPropertyDefinition(FilesPlugin::OWNER_ID_PROPERTYNAME, true, true, true), new Literal($this->user->getUID())]
				),
			]
		);
		$this->search->search($query);

		$this->assertNotNull($receivedQuery);
		$this->assertTrue($receivedQuery->limitToHome());

		/** @var ISearchBinaryOperator $operator */
		$operator = $receivedQuery->getSearchOperation();
		$this->assertInstanceOf(ISearchBinaryOperator::class, $operator);
		$this->assertEquals(ISearchBinaryOperator::OPERATOR_AND, $operator->getType());
		$this->assertCount(2, $operator->getArguments());

		/** @var ISearchBinaryOperator $operator */
		$operator = $operator->getArguments()[1];
		$this->assertInstanceOf(ISearchBinaryOperator::class, $operator);
		$this->assertEquals(ISearchBinaryOperator::OPERATOR_AND, $operator->getType());
		$this->assertEmpty($operator->getArguments());
	}

	public function testSearchOperatorLimit(): void {
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($this->davFolder);

		$innerOperator = new Operator(
			Operator::OPERATION_EQUAL,
			[new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true), new Literal('image/png')]
		);
		// 5 child operators
		$level1Operator = new Operator(
			Operator::OPERATION_AND,
			[
				$innerOperator,
				$innerOperator,
				$innerOperator,
				$innerOperator,
				$innerOperator,
			]
		);
		// 5^2 = 25 child operators
		$level2Operator = new Operator(
			Operator::OPERATION_AND,
			[
				$level1Operator,
				$level1Operator,
				$level1Operator,
				$level1Operator,
				$level1Operator,
			]
		);
		// 5^3 = 125 child operators
		$level3Operator = new Operator(
			Operator::OPERATION_AND,
			[
				$level2Operator,
				$level2Operator,
				$level2Operator,
				$level2Operator,
				$level2Operator,
			]
		);

		$query = $this->getBasicQuery(Operator::OPERATION_EQUAL, FilesPlugin::OWNER_ID_PROPERTYNAME, $this->user->getUID());
		$query->where = $level3Operator;
		$this->expectException(\InvalidArgumentException::class);
		$this->search->search($query);
	}
}
