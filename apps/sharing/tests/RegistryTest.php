<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


use OCA\Sharing\Features\NoteShareFeature;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\Registry;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\Server;
use Test\TestCase;

class RegistryTest extends TestCase {
	private Registry $registry;

	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(Registry::class);
		$this->registry->clear();
	}

	public function testRegisterSourceType(): void {
		$sourceType = new NodeShareSourceType();
		$this->registry->registerSourceType($sourceType);

		$this->assertEquals([NodeShareSourceType::class => $sourceType], $this->registry->getSourceTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerSourceType($sourceType);
	}

	public function testRegisterRecipientType(): void {
		$recipientType = new UserShareRecipientType();
		$this->registry->registerRecipientType($recipientType);

		$this->assertEquals([UserShareRecipientType::class => $recipientType], $this->registry->getRecipientTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerRecipientType($recipientType);
	}

	public function testRegisterFeature(): void {
		$feature = new NoteShareFeature();
		$this->registry->registerFeature($feature);

		$this->assertEquals([NoteShareFeature::class => $feature], $this->registry->getFeatures());

		$this->expectException(RuntimeException::class);
		$this->registry->registerFeature($feature);
	}

	public function testClear(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$this->registry->clear();

		$this->assertEquals([], $this->registry->getFeatures());
	}
}
