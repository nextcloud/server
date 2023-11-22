<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Ferdinand Thiessen <opensource@fthiessen.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\TestApp\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'testapp';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
