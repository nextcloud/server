<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Repair;

/**
 * Tests for the cleaning the tags tables
 *
 * @see \OC\Repair\CleanTags
 */
class CleanTags extends \Test\TestCase {

	/** @var \OC\RepairStep */
	private $repair;

	/** @var \Doctrine\DBAL\Connection */
	private $connection;

	/** @var array */
	protected $tagCategories;

	/** @var int */
	protected $createdFile;

	protected function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->repair = new \OC\Repair\CleanTags($this->connection);
	}

	protected function tearDown() {
		$qb = $this->connection->createQueryBuilder();
		$qb->delete('*PREFIX*vcategory')
			->where('uid = ' . $qb->createNamedParameter('TestRepairCleanTags'))
			->execute();

		$qb->delete('*PREFIX*vcategory_to_object')
			->where($qb->expr()->in('categoryid', ':ids'));
		$qb->setParameter('ids', $this->tagCategories, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$qb->execute();

		$qb->delete('*PREFIX*filecache')
			->where('fileid = ' . $qb->createNamedParameter($this->createdFile, \PDO::PARAM_INT))
			->execute();

		parent::tearDown();
	}

	public function testRun() {
		$cat1 = $this->addTagCategory('TestRepairCleanTags', 'files'); // Retained
		$cat2 = $this->addTagCategory('TestRepairCleanTags2', 'files'); // Deleted: Category is empty
		$cat3 = $this->addTagCategory('TestRepairCleanTags', 'contacts'); // Retained
		$file = $this->getFileID();

		$this->addTagEntry($file, $cat2, 'files'); // Retained
		$this->addTagEntry($file + 1, $cat1, 'files'); // Deleted: File is NULL
		$this->addTagEntry(9999999, $cat3, 'contacts'); // Retained
		$this->addTagEntry($file, $cat3 + 1, 'files'); // Deleted: Category is NULL

		$this->assertEntryCount('*PREFIX*vcategory', 3, 'Assert tag categories count before repair step');
		$this->assertEntryCount('*PREFIX*vcategory_to_object', 4, 'Assert tag entries count before repair step');
		$this->repair->run();
		$this->assertEntryCount('*PREFIX*vcategory', 2, 'Assert tag categories count after repair step');
		$this->assertEntryCount('*PREFIX*vcategory_to_object', 2, 'Assert tag entries count after repair step');
	}

	/**
	 * @param string $tableName
	 * @param int $expected
	 * @param string $message
	 */
	protected function assertEntryCount($tableName, $expected, $message = '') {
		$qb = $this->connection->createQueryBuilder();
		$result = $qb->select('COUNT(*)')
			->from($tableName)
			->execute();

		$this->assertEquals($expected, $result->fetchColumn(), $message);
	}

	/**
	 * Adds a new tag category to the database
	 *
	 * @param string $category
	 * @param string $type
	 * @return int
	 */
	protected function addTagCategory($category, $type) {
		$qb = $this->connection->createQueryBuilder();
		$qb->insert('*PREFIX*vcategory')
			->values([
				'uid'			=> $qb->createNamedParameter('TestRepairCleanTags'),
				'category'		=> $qb->createNamedParameter($category),
				'type'			=> $qb->createNamedParameter($type),
			])
			->execute();

		$id = (int) $this->connection->lastInsertId();
		$this->tagCategories[] = $id;
		return $id;
	}

	/**
	 * Adds a new tag entry to the database
	 * @param int $objectId
	 * @param int $category
	 * @param string $type
	 */
	protected function addTagEntry($objectId, $category, $type) {
		$qb = $this->connection->createQueryBuilder();
		$qb->insert('*PREFIX*vcategory_to_object')
			->values([
				'objid'			=> $qb->createNamedParameter($objectId, \PDO::PARAM_INT),
				'categoryid'	=> $qb->createNamedParameter($category, \PDO::PARAM_INT),
				'type'			=> $qb->createNamedParameter($type),
			])
			->execute();
	}

	/**
	 * Gets the last fileid from the file cache
	 *
	 * @return int
	 */
	protected function getFileID() {
		$qb = $this->connection->createQueryBuilder();

		// We create a new file entry and delete it after the test again
		$qb->insert('*PREFIX*filecache')
			->values([
				'path'			=> $qb->createNamedParameter('TestRepairCleanTags'),
			])
			->execute();
		$this->createdFile = (int) $this->connection->lastInsertId();
		return $this->createdFile;
	}
}
