<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\ShapeEnumValue;
use OCP\TaskProcessing\TaskTypes\TextToTextTranslate;
use RuntimeException;

class FakeTranslateProvider implements ISynchronousProvider {

	public function __construct(
		private IFactory $l10nFactory,
		protected IAppConfig $appConfig,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '-translate';
	}

	public function getName(): string {
		return 'Fake translate task processing provider';
	}

	public function getTaskTypeId(): string {
		return TextToTextTranslate::ID;
	}

	public function getExpectedRuntime(): int {
		return 1;
	}

	public function getInputShapeEnumValues(): array {
		$coreL = $this->l10nFactory->getLanguages();
		$languages = array_merge($coreL['commonLanguages'], $coreL['otherLanguages']);
		$languageEnumValues = array_map(static function (array $language) {
			return new ShapeEnumValue($language['name'], $language['code']);
		}, $languages);
		$detectLanguageEnumValue = new ShapeEnumValue('Detect language', 'detect_language');
		return [
			'origin_language' => array_merge([$detectLanguageEnumValue], $languageEnumValues),
			'target_language' => $languageEnumValues,
		];
	}

	public function getInputShapeDefaults(): array {
		return [
			'origin_language' => 'detect_language',
		];
	}

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

	public function getOptionalInputShapeEnumValues(): array {
		return [
			'model' => [
				new ShapeEnumValue('Model 1', 'model_1'),
				new ShapeEnumValue('Model 2', 'model_2'),
				new ShapeEnumValue('Model 3', 'model_3'),
			],
		];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [
			'max_tokens' => 200,
			'model' => 'model_3',
		];
	}

	public function getOptionalOutputShape(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	private function getCoreLanguagesByCode(): array {
		$coreL = $this->l10nFactory->getLanguages();
		$coreLanguages = array_reduce(array_merge($coreL['commonLanguages'], $coreL['otherLanguages']), function ($carry, $val) {
			$carry[$val['code']] = $val['name'];
			return $carry;
		});
		return $coreLanguages;
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		if ($this->appConfig->getAppValueBool('fail-' . $this->getId())) {
			throw new ProcessingException('Failing as set by AppConfig');
		}

		if (isset($input['model']) && is_string($input['model'])) {
			$model = $input['model'];
		} else {
			$model = 'model_3';
		}

		if (!isset($input['input']) || !is_string($input['input'])) {
			throw new RuntimeException('Invalid input text');
		}
		$inputText = $input['input'];

		$maxTokens = null;
		if (isset($input['max_tokens']) && is_int($input['max_tokens'])) {
			$maxTokens = $input['max_tokens'];
		}

		$coreLanguages = $this->getCoreLanguagesByCode();

		$toLanguage = $coreLanguages[$input['target_language']] ?? $input['target_language'];
		if ($input['origin_language'] !== 'detect_language') {
			$fromLanguage = $coreLanguages[$input['origin_language']] ?? $input['origin_language'];
			$prompt = 'Fake translation from ' . $fromLanguage . ' to ' . $toLanguage . ': ' . $inputText;
		} else {
			$prompt = 'Fake Translation to ' . $toLanguage . ': ' . $inputText;
		}

		$fakeResult = $prompt . "\n\nModel: " . $model . "\nMax tokens: " . $maxTokens;

		return ['output' => $fakeResult];
	}
}
