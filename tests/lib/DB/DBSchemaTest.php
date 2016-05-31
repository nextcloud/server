<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use Doctrine\DBAL\Connection;
use OC_DB;
use OCP\Security\ISecureRandom;

/**
 * Class DBSchemaTest
 *
 * @group DB
 */
class DBSchemaTest extends \Test\TestCase {
	protected $schema_file = 'static://test_db_scheme';
	protected $schema_file2 = 'static://test_db_scheme2';
	protected $table1;
	protected $table2;

	protected function setUp() {
		parent::setUp();

		$dbfile = \OC::$SERVERROOT.'/tests/data/db_structure.xml';
		$dbfile2 = \OC::$SERVERROOT.'/tests/data/db_structure2.xml';

		$r = '_' . \OC::$server->getSecureRandom()->
			generate(4, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS) . '_';
		$content = file_get_contents( $dbfile );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( $this->schema_file, $content );
		$content = file_get_contents( $dbfile2 );
		$content = str_replace( '*dbprefix*', '*dbprefix*'.$r, $content );
		file_put_contents( $this->schema_file2, $content );

		$this->table1 = $r.'cntcts_addrsbks';
		$this->table2 = $r.'cntcts_cards';
	}

	protected function tearDown() {
		unlink($this->schema_file);
		unlink($this->schema_file2);
		$this->mockDBPrefix('oc_');

		parent::tearDown();
	}

	// everything in one test, they depend on each other
	/**
	 * @medium
	 */
	public function testSchema() {
		$platform = \OC::$server->getDatabaseConnection()->getDatabasePlatform();
		$this->doTestSchemaCreating();
		$this->doTestSchemaChanging();
		$this->doTestSchemaDumping();
		$this->doTestSchemaRemoving();
	}

	public function doTestSchemaCreating() {
		OC_DB::createDbFromStructure($this->schema_file);
		$this->assertTableExist($this->table1);
		$this->assertTableExist($this->table2);
	}

	public function doTestSchemaChanging() {
		OC_DB::updateDbFromStructure($this->schema_file2);
		$this->assertTableExist($this->table2);
	}

	public function doTestSchemaDumping() {
		$outfile = 'static://db_out.xml';
		OC_DB::getDbStructure($outfile);
		$content = file_get_contents($outfile);
		$this->assertContains($this->table1, $content);
		$this->assertContains($this->table2, $content);
	}

	public function doTestSchemaRemoving() {
		OC_DB::removeDBStructure($this->schema_file);
		$this->assertTableNotExist($this->table1);
		$this->assertTableNotExist($this->table2);
	}

	public function testSchemaUnchanged() {
		$dbfile = \OC::$SERVERROOT.'/db_structure.xml';
		$schema_file = 'static://live_db_scheme';

		$randomPrefix = strtolower($this->getUniqueID('', 2, false) . '_');
		$content = file_get_contents($dbfile);

		// Add prefix to index names to make them unique for testing (oc_ exists in parallel)
		$content = str_replace('<name>', '<name>*dbprefix*', $content);
		$content = str_replace('*dbprefix**dbprefix*', '*dbprefix*', $content);
		$content = str_replace('*dbprefix*', $randomPrefix, $content);

		// Shorten index names that are too long, now that we added the prefix to make them unique
		$content = preg_replace_callback('/<name>([a-zA-Z0-9_]{28,})<\/name>/', function($match) use ($randomPrefix) {
			return $randomPrefix . substr(md5($match[1]), 0, 26);
		}, $content);

		file_put_contents($schema_file, $content);

		$this->mockDBPrefix($randomPrefix);

		// The method OC_DB::tableExists() adds the prefix itself
		$this->assertTableNotExist('filecache');
		\OC_DB::createDbFromStructure($schema_file);
		$this->assertTableExist('filecache');
		\OC_DB::updateDbFromStructure($schema_file);
		$this->assertTableExist('filecache');
		\OC_DB::removeDBStructure($schema_file);
		$this->assertTableNotExist('filecache');

		$this->mockDBPrefix('oc_');

		unlink($schema_file);
		$this->assertTrue(true, 'Asserting that no error occurred when updating with the same schema that is already installed');
	}

	protected function mockDBPrefix($prefix) {
		$connection = \OC::$server->getDatabaseConnection();
		$this->invokePrivate($connection, 'tablePrefix', [$prefix]);
		/** @var Connection $connection */
		$connection->getConfiguration()->setFilterSchemaAssetsExpression('/^' . $prefix . '/');

		\OC::$server->getConfig()->setSystemValue('dbtableprefix', $prefix);
	}

	/**
	 * @param string $table
	 */
	public function assertTableExist($table) {
		$this->assertTrue(OC_DB::tableExists($table), 'Table ' . $table . ' does not exist');
	}

	/**
	 * @param string $table
	 */
	public function assertTableNotExist($table) {
		$platform = \OC::$server->getDatabaseConnection()->getDatabasePlatform();
		if ($platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
			// sqlite removes the tables after closing the DB
			$this->assertTrue(true);
		} else {
			$this->assertFalse(OC_DB::tableExists($table), 'Table ' . $table . ' exists.');
		}
	}
}
