<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Resource\Permission;

use NCU\Resource\Permission\CorePermission;
use NCU\Resource\Permission\EffectivePermissions;
use Test\TestCase;

class EffectivePermissionsTest extends TestCase {
	public function testIsAllowedById(): void {
		$permissions = new EffectivePermissions([CorePermission::DOWNLOAD, 'tables:export']);

		$this->assertTrue($permissions->isAllowed(CorePermission::DOWNLOAD));
		$this->assertTrue($permissions->isAllowed('tables:export'));
		$this->assertFalse($permissions->isAllowed('tables:import'));
	}

	public function testIsAllowedByCorePermission(): void {
		$permissions = new EffectivePermissions([CorePermission::Read->value]);

		$this->assertTrue($permissions->isAllowed(CorePermission::Read));
		$this->assertTrue($permissions->isAllowed(CorePermission::Read->value));
		$this->assertFalse($permissions->isAllowed(CorePermission::Update));
	}

	public function testGetAllowedPermissionIds(): void {
		$this->assertSame([], (new EffectivePermissions([]))->getAllowedPermissionIds());
		$this->assertSame(
			[CorePermission::Update->value, CorePermission::Read->value, 'tables:export'],
			(new EffectivePermissions([CorePermission::Update->value, CorePermission::Read->value, 'tables:export']))->getAllowedPermissionIds(),
		);
	}
}
