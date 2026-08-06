<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

class StaticVarsTest {
	public static $forbiddenStaticProperty;
	protected static $forbiddenProtectedStaticProperty;
	private static $forbiddenPrivateStaticProperty;
	private static $forbiddenPrivateStaticPropertyWithValue = [];

	public function normalFunction(): void {
		static $forbiddenStaticVar = false;
	}

	public static function staticFunction(): void {
		static $forbiddenStaticVar = false;
	}
}
