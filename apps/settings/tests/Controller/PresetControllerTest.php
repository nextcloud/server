<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\Config\PresetManager;
use OCA\Settings\Controller\PresetController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Config\Lexicon\Preset;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PresetControllerTest extends TestCase {
	private PresetManager&MockObject $presetManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private PresetController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->presetManager = $this->createMock(PresetManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->controller = new PresetController(
			'settings',
			$this->createMock(IRequest::class),
			$this->presetManager,
			$this->eventDispatcher,
		);
	}

	public function testSetCurrentPresetIsAudited(): void {
		$preset = Preset::cases()[0];
		$this->presetManager->expects($this->once())
			->method('setLexiconPreset')
			->with($preset);
		$this->presetManager->method('getLexiconPreset')->willReturn($preset);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'settings.preset.applied'
				&& $event->getParameters() === ['presetName' => $preset->name]
				&& $event->getLogMessage() === 'Configuration preset "%s" was applied'));

		$this->controller->setCurrentPreset($preset->name);
	}

	public function testSetInvalidPresetIsNotAudited(): void {
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->expectException(OCSBadRequestException::class);
		$this->controller->setCurrentPreset('not-a-preset');
	}
}
