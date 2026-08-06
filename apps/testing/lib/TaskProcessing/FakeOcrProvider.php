<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\File;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\TaskTypes\ImageToTextOpticalCharacterRecognition;
use RuntimeException;

class FakeOcrProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID . '-image2text-ocr';
	}

	#[\Override]
	public function getName(): string {
		return 'Fake OCR task processing provider';
	}

	#[\Override]
	public function getTaskTypeId(): string {
		return ImageToTextOpticalCharacterRecognition::ID;
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
		return [];
	}

	#[\Override]
	public function getOptionalInputShape(): array {
		return [];
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

		if (!isset($input['input']) || !is_array($input['input'])) {
			throw new RuntimeException('Invalid input');
		}
		$outputs = [];
		foreach ($input['input'] as $i => $inputImage) {
			if (!($inputImage instanceof File) || !$inputImage->isReadable()) {
				throw new RuntimeException('Invalid input images');
			}
			$outputs[] = '[' . $i . '] This is a fake OCR result.';
		}

		return ['output' => $outputs];
	}
}
