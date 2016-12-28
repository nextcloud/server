<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Settings;

use OC\DB\QueryBuilder\Literal;
use OC\Settings\Mapper;
use Test\TestCase;

/**
 * @group DB
 */
class MapperTest extends TestCase {
	const SECTION_PREFIX = 'test_section_';

	/** @var Mapper */
	private $mapper;

	public function setUp() {
		parent::setUp();
		$this->mapper = new Mapper(\OC::$server->getDatabaseConnection());
	}

	public function tearDown() {
		parent::tearDown();

		$db = \OC::$server->getDatabaseConnection();
		$builder = $db->getQueryBuilder();

		$builder->delete(Mapper::TABLE_ADMIN_SECTIONS)
			->where($builder->expr()->like('id', new Literal(self::SECTION_PREFIX . '%')));

		$builder->delete(Mapper::TABLE_ADMIN_SETTINGS)
			->where($builder->expr()->like('section', new Literal(self::SECTION_PREFIX . '%')));
	}

	public function testManipulateSettings() {
		$this->assertEquals(false, $this->mapper->has(Mapper::TABLE_ADMIN_SETTINGS, '\OC\Dummy'));
		$this->assertNotContains('\OC\Dummy', $this->mapper->getClasses(Mapper::TABLE_ADMIN_SETTINGS));

		$this->mapper->add(Mapper::TABLE_ADMIN_SETTINGS, [
			'class' => '\OC\Dummy',
			'section' => self::SECTION_PREFIX . '1',
			'priority' => 5
		]);

		$this->assertEquals(true, $this->mapper->has(Mapper::TABLE_ADMIN_SETTINGS, '\OC\Dummy'));

		$this->assertContains('\OC\Dummy', $this->mapper->getClasses(Mapper::TABLE_ADMIN_SETTINGS));

		$rows = $this->mapper->getAdminSettingsFromDB(self::SECTION_PREFIX . '1');
		$this->assertEquals([
			['class' => '\OC\Dummy', 'priority' => 5]
		], $rows);

		$this->mapper->update(Mapper::TABLE_ADMIN_SETTINGS, 'class', '\OC\Dummy', [
			'section' => self::SECTION_PREFIX . '1', 'priority' => 15
		]);

		$rows = $this->mapper->getAdminSettingsFromDB(self::SECTION_PREFIX . '1');
		$this->assertEquals([
			['class' => '\OC\Dummy', 'priority' => 15]
		], $rows);

		$this->mapper->update(Mapper::TABLE_ADMIN_SETTINGS, 'class', '\OC\Dummy', [
			'section' => self::SECTION_PREFIX . '2', 'priority' => 15
		]);

		$this->assertEquals([], $this->mapper->getAdminSettingsFromDB(self::SECTION_PREFIX . '1'));
		$rows = $this->mapper->getAdminSettingsFromDB(self::SECTION_PREFIX . '2');
		$this->assertEquals([
			['class' => '\OC\Dummy', 'priority' => 15]
		], $rows);

		$this->mapper->remove(Mapper::TABLE_ADMIN_SETTINGS, '\OC\Dummy');

		$this->assertEquals(false, $this->mapper->has(Mapper::TABLE_ADMIN_SETTINGS, '\OC\Dummy'));
	}

	public function testGetAdminSections() {
		$this->assertFalse($this->mapper->has(Mapper::TABLE_ADMIN_SECTIONS, '\OC\Dummy'));

		$this->mapper->add(Mapper::TABLE_ADMIN_SECTIONS, [
			'id' => self::SECTION_PREFIX . '1',
			'class' => '\OC\Dummy',
			'priority' => 1,
		]);

		$this->assertTrue($this->mapper->has(Mapper::TABLE_ADMIN_SECTIONS, '\OC\Dummy'));

		// until we add a setting for the section it's not returned
		$this->assertNotContains([
			'class' => '\OC\Dummy',
			'priority' => 1,
		], $this->mapper->getAdminSectionsFromDB());

		$this->mapper->add(Mapper::TABLE_ADMIN_SETTINGS, [
			'class' => '\OC\Dummy',
			'section' => self::SECTION_PREFIX . '1',
			'priority' => 5
		]);

		$this->assertContains([
			'class' => '\OC\Dummy',
			'priority' => 1,
		], $this->mapper->getAdminSectionsFromDB());

		$this->mapper->remove(Mapper::TABLE_ADMIN_SETTINGS, '\OC\Dummy');

		$this->assertNotContains([
			'class' => '\OC\Dummy',
			'priority' => 1,
		], $this->mapper->getAdminSectionsFromDB());

		$this->mapper->remove(Mapper::TABLE_ADMIN_SECTIONS, '\OC\Dummy');

		$this->assertFalse($this->mapper->has(Mapper::TABLE_ADMIN_SECTIONS, '\OC\Dummy'));
	}
}
