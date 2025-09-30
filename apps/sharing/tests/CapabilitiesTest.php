<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OCA\Sharing\AppInfo\Application;
use OCA\Sharing\Capabilities;
use OCA\Sharing\Features\NoteShareFeature;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\Registry;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class CapabilitiesTest extends TestCase {
	private Registry $registry;

	private Capabilities $capabilities;

	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(Registry::class);
		$this->registry->clear();

		$this->capabilities = Server::get(Capabilities::class);
	}

	public function testGetCapabilities(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$this->assertEquals(
			[
				Application::APP_ID => [
					'source_types' => [NodeShareSourceType::class],
					'recipient_types' => [UserShareRecipientType::class],
					'features' => [
						NoteShareFeature::class => [
							'compatibles' => [
								[
									'source_type' => NodeShareSourceType::class,
									'recipient_type' => UserShareRecipientType::class,
								],
								[
									'source_type' => NodeShareSourceType::class,
									'recipient_type' => GroupShareRecipientType::class,
								],
							],
						],
					],
				],
			],
			$this->capabilities->getCapabilities(),
		);
	}
}
