<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\SpeechToText\ISpeechToTextProviderWithId;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\IProvider;
use OCP\TextProcessing\IProviderWithId;
use OCP\TextProcessing\ITaskType;
use OCP\Translation\ITranslationManager;
use OCP\Translation\ITranslationProviderWithId;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ArtificialIntelligence implements IDelegatedSettings {
	public function __construct(
		private IConfig $config,
		private IL10N $l,
		private IInitialState $initialState,
		private ITranslationManager $translationManager,
		private ISpeechToTextManager $sttManager,
		private IManager $textProcessingManager,
		private ContainerInterface $container,
		private \OCP\TextToImage\IManager $text2imageManager,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$translationProviders = [];
		$translationPreferences = [];
		foreach ($this->translationManager->getProviders() as $provider) {
			$translationProviders[] = [
				'class' => $provider instanceof ITranslationProviderWithId ? $provider->getId() : $provider::class,
				'name' => $provider->getName(),
			];
			$translationPreferences[] = $provider instanceof ITranslationProviderWithId ? $provider->getId() : $provider::class;
		}

		$sttProviders = [];
		foreach ($this->sttManager->getProviders() as $provider) {
			$sttProviders[] = [
				'class' => $provider instanceof ISpeechToTextProviderWithId ? $provider->getId() : $provider::class,
				'name' => $provider->getName(),
			];
		}

		$textProcessingProviders = [];
		/** @var array<class-string<ITaskType>, string|class-string<IProvider>> $textProcessingSettings */
		$textProcessingSettings = [];
		foreach ($this->textProcessingManager->getProviders() as $provider) {
			$textProcessingProviders[] = [
				'class' => $provider instanceof IProviderWithId ? $provider->getId() : $provider::class,
				'name' => $provider->getName(),
				'taskType' => $provider->getTaskType(),
			];
			if (!isset($textProcessingSettings[$provider->getTaskType()])) {
				$textProcessingSettings[$provider->getTaskType()] = $provider instanceof IProviderWithId ? $provider->getId() : $provider::class;
			}
		}
		$textProcessingTaskTypes = [];
		foreach ($textProcessingSettings as $taskTypeClass => $providerClass) {
			/** @var ITaskType $taskType */
			try {
				$taskType = $this->container->get($taskTypeClass);
			} catch (NotFoundExceptionInterface $e) {
				continue;
			} catch (ContainerExceptionInterface $e) {
				continue;
			}
			$textProcessingTaskTypes[] = [
				'class' => $taskTypeClass,
				'name' => $taskType->getName(),
				'description' => $taskType->getDescription(),
			];
		}

		$text2imageProviders = [];
		foreach ($this->text2imageManager->getProviders() as $provider) {
			$text2imageProviders[] = [
				'id' => $provider->getId(),
				'name' => $provider->getName(),
			];
		}

		$this->initialState->provideInitialState('ai-stt-providers', $sttProviders);
		$this->initialState->provideInitialState('ai-translation-providers', $translationProviders);
		$this->initialState->provideInitialState('ai-text-processing-providers', $textProcessingProviders);
		$this->initialState->provideInitialState('ai-text-processing-task-types', $textProcessingTaskTypes);
		$this->initialState->provideInitialState('ai-text2image-providers', $text2imageProviders);

		$settings = [
			'ai.stt_provider' => count($sttProviders) > 0 ? $sttProviders[0]['class'] : null,
			'ai.textprocessing_provider_preferences' => $textProcessingSettings,
			'ai.translation_provider_preferences' => $translationPreferences,
			'ai.text2image_provider' => count($text2imageProviders) > 0 ? $text2imageProviders[0]['id'] : null,
		];
		foreach ($settings as $key => $defaultValue) {
			$value = $defaultValue;
			$json = $this->config->getAppValue('core', $key, '');
			if ($json !== '') {
				$value = json_decode($json, true);
				switch($key) {
					case 'ai.textprocessing_provider_preferences':
						// fill $value with $defaultValue values
						$value = array_merge($defaultValue, $value);
						break;
					case 'ai.translation_provider_preferences':
						$value += array_diff($defaultValue, $value); // Add entries from $defaultValue that are not in $value to the end of $value
						break;
					default:
						break;
				}
			}
			$settings[$key] = $value;
		}

		$this->initialState->provideInitialState('ai-settings', $settings);

		return new TemplateResponse('settings', 'settings/admin/ai');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'ai';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 10;
	}

	public function getName(): ?string {
		return $this->l->t('Artificial Intelligence');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'core' => ['/ai..*/'],
		];
	}
}
