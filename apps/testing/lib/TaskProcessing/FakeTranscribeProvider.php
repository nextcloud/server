<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\File;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\TaskTypes\AudioToText;
use RuntimeException;

class FakeTranscribeProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '-audio2text';
	}

	public function getName(): string {
		return 'Fake audio2text task processing provider';
	}

	public function getTaskTypeId(): string {
		return AudioToText::ID;
	}

	public function getExpectedRuntime(): int {
		return 1;
	}

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShape(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShape(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		if (!isset($input['input']) || !$input['input'] instanceof File || !$input['input']->isReadable()) {
			throw new RuntimeException('Invalid input file');
		}
		if ($this->appConfig->getAppValueBool('fail-' . $this->getId())) {
			throw new ProcessingException('Failing as set by AppConfig');
		}

		$inputFile = $input['input'];
		$transcription = 'Fake transcription result';

		return ['output' => $transcription];
	}
}
