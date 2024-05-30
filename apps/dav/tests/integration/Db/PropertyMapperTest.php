<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
