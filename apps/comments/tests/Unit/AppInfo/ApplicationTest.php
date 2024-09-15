<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Tests\Unit\AppInfo;

use OCA\Comments\AppInfo\Application;
use OCA\Comments\Notification\Notifier;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @group DB
 *
 * @package OCA\Comments\Tests\Unit\AppInfo
 */
class ApplicationTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		\OC::$server->getUserManager()->createUser('dummy', '456');
		\OC::$server->getUserSession()->setUser(\OC::$server->getUserManager()->get('dummy'));
	}

	protected function tearDown(): void {
		\OC::$server->getUserManager()->get('dummy')->delete();
		parent::tearDown();
	}

	public function test(): void {
		$app = new Application();
		$c = $app->getContainer();

		$services = [
			'OCA\Comments\Controller\NotificationsController',
			'OCA\Comments\Activity\Filter',
			'OCA\Comments\Activity\Listener',
			'OCA\Comments\Activity\Provider',
			'OCA\Comments\Activity\Setting',
			'OCA\Comments\Notification\Listener',
			Notifier::class,
		];

		foreach ($services as $service) {
			$s = $c->get($service);
			$this->assertInstanceOf($service, $s);
		}
	}
}
