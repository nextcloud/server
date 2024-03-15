<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\DAV\Tests\integration\Db;

use OCA\DAV\Db\PropertyMapper;
use Test\TestCase;

/**
 * @group DB
 */
class PropertyMapperTest extends TestCase {

	/** @var PropertyMapper */
	private PropertyMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = \OC::$server->get(PropertyMapper::class);
	}

	public function testFindNonExistent(): void {
		$props = $this->mapper->findPropertyByPathAndName(
			'userthatdoesnotexist',
			'path/that/does/not/exist/either',
			'nope',
		);

		self::assertEmpty($props);
	}

}
