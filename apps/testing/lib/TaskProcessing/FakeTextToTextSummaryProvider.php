<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\ShapeEnumValue;
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use RuntimeException;

class FakeTextToTextSummaryProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID . '-text2text-summary';
	}

	#[\Override]
	public function getName(): string {
		return 'Fake text2text summary task processing provider';
	}

	#[\Override]
	public function getTaskTypeId(): string {
		return TextToTextSummary::ID;
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
		return [
			'max_tokens' => new ShapeDescriptor(
				'Maximum output words',
				'The maximum number of words/tokens that can be generated in the completion.',
				EShapeType::Number
			),
			'model' => new ShapeDescriptor(
				'Model',
				'The model used to generate the completion',
				EShapeType::Enum
			),
		];
	}

	#[\Override]
	public function getOptionalInputShapeEnumValues(): array {
		return [
			'model' => [
				new ShapeEnumValue('Model 1', 'model_1'),
				new ShapeEnumValue('Model 2', 'model_2'),
				new ShapeEnumValue('Model 3', 'model_3'),
			],
		];
	}

	#[\Override]
	public function getOptionalInputShapeDefaults(): array {
		return [
			'max_tokens' => 1234,
			'model' => 'model_2',
		];
	}

	#[\Override]
	public function getOptionalOutputShape(): array {
		return [];
	}

	#[\Override]
	public function getOutputShapeEnumValues(): array {
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

		if (isset($input['model']) && is_string($input['model'])) {
			$model = $input['model'];
		} else {
			$model = 'unknown model';
		}

		if (!isset($input['input']) || !is_string($input['input'])) {
			throw new RuntimeException('Invalid prompt');
		}
		$prompt = $input['input'];

		$maxTokens = null;
		if (isset($input['max_tokens']) && is_int($input['max_tokens'])) {
			$maxTokens = $input['max_tokens'];
		}

		return [
			'output' => 'This is a fake summary: ',// . "\n\n- Prompt: " . $prompt . "\n- Model: " . $model . "\n- Maximum number of words: " . $maxTokens,
		];
	}
}
