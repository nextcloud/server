<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\TaskTypes\TextToImage;
use RuntimeException;

class FakeTextToImageProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID . '-text2image';
	}

	#[\Override]
	public function getName(): string {
		return 'Fake text2image task processing provider';
	}

	#[\Override]
	public function getTaskTypeId(): string {
		return TextToImage::ID;
	}

	#[\Override]
	public function getExpectedRuntime(): int {
		return 1;
	}

	#[\Override]
	public function getInputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getInputShapeDefaults(): array {
		return [
			'numberOfImages' => 1,
		];
	}

	#[\Override]
	public function getOptionalInputShape(): array {
		return [
			'size' => new ShapeDescriptor(
				'Size',
				'Optional. The size of the generated images. Must be in 256x256 format.',
				EShapeType::Text
			),
		];
	}

	#[\Override]
	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	#[\Override]
	public function getOutputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getOptionalOutputShape(): array {
		return [];
	}

	#[\Override]
	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function process(?string $userId, array $input, callable $reportProgress): array {
		if ($this->appConfig->getAppValueBool('fail-' . $this->getId())) {
			throw new ProcessingException('Failing as set by AppConfig');
		}

		if (!isset($input['input']) || !is_string($input['input'])) {
			throw new RuntimeException('Invalid prompt');
		}
		$prompt = $input['input'];

		$nbImages = 1;
		if (isset($input['numberOfImages']) && is_int($input['numberOfImages'])) {
			$nbImages = $input['numberOfImages'];
		}

		$fakeContent = file_get_contents(__DIR__ . '/../../img/logo.png');

		$output = ['images' => []];
		foreach (range(1, $nbImages) as $i) {
			$output['images'][] = $fakeContent;
		}
		/** @var array<string, list<numeric|string>|numeric|string> $output */
		return $output;
	}
}
