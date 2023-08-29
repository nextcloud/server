<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Metadata;

use OC\Metadata\FileMetadataMapper;
use OC\Metadata\FileMetadata;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DB
 * @package Test\DB\QueryBuilder
 */
class FileMetadataMapperTest extends \Test\TestCase {
	/** @var IDBConnection */
	protected $connection;

	/** @var SystemConfig|MockObject */
	protected $config;

	/** @var FileMetadataMapper|MockObject */
	protected $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->get(IDBConnection::class);
		$this->mapper = new FileMetadataMapper($this->connection);
	}

	public function testFindForGroupForFiles() {
		$file1 = new FileMetadata();
		$file1->setId(1);
		$file1->setGroupName('size');
		$file1->setArrayAsValue([]);

		$file2 = new FileMetadata();
		$file2->setId(2);
		$file2->setGroupName('size');
		$file2->setArrayAsValue(['width' => 293, 'height' => 23]);

		// not added, it's the default
		$file3 = new FileMetadata();
		$file3->setId(3);
		$file3->setGroupName('size');
		$file3->setArrayAsValue([]);

		$file4 = new FileMetadata();
		$file4->setId(4);
		$file4->setGroupName('size');
		$file4->setArrayAsValue(['complex' => ["yes", "maybe" => 34.0]]);

		$this->mapper->insert($file1);
		$this->mapper->insert($file2);
		$this->mapper->insert($file4);

		$files = $this->mapper->findForGroupForFiles([1, 2, 3, 4], 'size');

		$this->assertEquals($files[1]->getValue(), $file1->getValue());
		$this->assertEquals($files[2]->getValue(), $file2->getValue());
		$this->assertEquals($files[3]->getDecodedValue(), $file3->getDecodedValue());
		$this->assertEquals($files[4]->getValue(), $file4->getValue());

		$this->mapper->clear(1);
		$this->mapper->clear(2);
		$this->mapper->clear(4);
	}
}
