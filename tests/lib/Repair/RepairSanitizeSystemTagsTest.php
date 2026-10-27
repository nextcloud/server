<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\RepairSanitizeSystemTags;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see \OC\Repair\RepairSanitizeSystemTags
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class RepairSanitizeSystemTagsTest extends \Test\TestCase {

	private RepairSanitizeSystemTags $repair;
	private IDBConnection $connection;
	private IOutput&MockObject $output;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->output = $this->createMock(IOutput::class);
		$this->repair = new RepairSanitizeSystemTags($this->connection);

		$this->cleanUpTables();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->cleanUpTables();
		parent::tearDown();
	}

	private function cleanUpTables(): void {
		$this->connection->getQueryBuilder()->delete('systemtag_object_mapping')->executeStatement();
		$this->connection->getQueryBuilder()->delete('systemtag')->executeStatement();
	}

	public function testMigrationsAvailableFalseWhenAllClean(): void {
		$this->addTag('Clean');
		$this->addTag('Also clean');

		$this->assertFalse($this->repair->migrationsAvailable());
	}

	public function testMigrationsAvailableTrueWhenNameNeedsSanitizing(): void {
		$this->addTag('Clean');
		$this->addTag('  Needs trimming  ');

		$this->assertTrue($this->repair->migrationsAvailable());
	}

	public function testMigrationsAvailableIgnoresFalsyName(): void {
		// A PHP-falsy name ('0') must not stop the scan before later dirty tags are
		// seen. We avoid '' here because Oracle stores it as NULL, which the notnull
		// name column rejects.
		$this->addTag('0');
		$this->addTag('Needs  trimming');

		$this->assertTrue($this->repair->migrationsAvailable());
	}

	public function testRunSanitizesSingleTag(): void {
		// Leading/trailing whitespace is trimmed and inner runs collapse to a single space
		$id = $this->addTag('  My   Tag  ');

		$this->repair->run($this->output);

		$this->assertSame('My Tag', $this->getTagName($id));
		$this->assertFalse($this->repair->migrationsAvailable());
	}

	public function testRunMergesDuplicatesAndReassignsMappings(): void {
		// Both sanitize to 'Report card', same visibility/editable, so they must merge.
		// The variants differ by an inner double space, which is significant in the DB
		// unique index (unlike trailing spaces, which MySQL/Oracle ignore).
		$keep = $this->addTag('Report card');
		$dup = $this->addTag('Report  card');

		// The kept tag is the one with the most object mappings
		$this->addMapping($keep, '1');
		$this->addMapping($keep, '2');
		$this->addMapping($dup, '3');

		$this->repair->run($this->output);

		// Only one tag remains, named 'Report card'
		$this->assertSame(1, $this->countTags());
		$this->assertSame('Report card', $this->getTagName($keep));
		$this->assertNull($this->getTagName($dup));

		// The duplicate's mapping was reassigned to the kept tag
		$this->assertSame(3, $this->countMappings($keep));
	}

	public function testRunKeepsTagWithMostMappings(): void {
		$few = $this->addTag('Photo album');
		$many = $this->addTag('Photo  album');

		$this->addMapping($few, '1');
		$this->addMapping($many, '2');
		$this->addMapping($many, '3');

		$this->repair->run($this->output);

		$this->assertNull($this->getTagName($few));
		$this->assertSame('Photo album', $this->getTagName($many));
		$this->assertSame(3, $this->countMappings($many));
	}

	public function testRunDeletesConflictingMappings(): void {
		$a = $this->addTag('Draft doc');
		$b = $this->addTag('Draft  doc');

		// Object '1' is mapped to both tags. After the merge that conflict must be
		// deduplicated: the surviving tag ends up with objects '1' and '2' only once each.
		$this->addMapping($a, '1');
		$this->addMapping($b, '1');
		$this->addMapping($b, '2');

		$this->repair->run($this->output);

		// A single tag remains and holds exactly two mappings (no duplicated object '1')
		$this->assertSame(1, $this->countTags());
		$this->assertSame(2, $this->countAllMappings());
	}

	public function testRunSkipsMergeWhenVisibilityDiffers(): void {
		// Same sanitized name but different visibility → must not merge, only warn
		$a = $this->addTag('Shared note', visibility: 1);
		$b = $this->addTag('Shared  note', visibility: 0);

		$this->output->expects($this->atLeastOnce())->method('warning');

		$this->repair->run($this->output);

		$this->assertSame(2, $this->countTags());
		$this->assertNotNull($this->getTagName($a));
		$this->assertNotNull($this->getTagName($b));
	}

	private function addTag(string $name, int $visibility = 1, int $editable = 1): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('systemtag')
			->values([
				'name' => $qb->createNamedParameter($name),
				'visibility' => $qb->createNamedParameter($visibility, IQueryBuilder::PARAM_INT),
				'editable' => $qb->createNamedParameter($editable, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();

		return $qb->getLastInsertId();
	}

	private function addMapping(int $tagId, string $objectId, string $objectType = 'files'): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('systemtag_object_mapping')
			->values([
				'objectid' => $qb->createNamedParameter($objectId),
				'objecttype' => $qb->createNamedParameter($objectType),
				'systemtagid' => $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();
	}

	private function getTagName(int $id): ?string {
		$qb = $this->connection->getQueryBuilder();
		$name = $qb->select('name')
			->from('systemtag')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchOne();

		return $name === false ? null : $name;
	}

	private function countTags(): int {
		$qb = $this->connection->getQueryBuilder();
		return (int)$qb->select($qb->func()->count('*'))
			->from('systemtag')
			->executeQuery()
			->fetchOne();
	}

	private function countMappings(int $tagId): int {
		$qb = $this->connection->getQueryBuilder();
		return (int)$qb->select($qb->func()->count('*'))
			->from('systemtag_object_mapping')
			->where($qb->expr()->eq('systemtagid', $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchOne();
	}

	private function countAllMappings(): int {
		$qb = $this->connection->getQueryBuilder();
		return (int)$qb->select($qb->func()->count('*'))
			->from('systemtag_object_mapping')
			->executeQuery()
			->fetchOne();
	}
}
