<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;
use OCP\SpeechToText\ISpeechToTextManager;
use OCP\TextProcessing\IManager;
use OCP\TextProcessing\IProvider;
use OCP\TextProcessing\ITaskType;
use OCP\Translation\ITranslationManager;
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
				'class' => $provider::class,
				'name' => $provider->getName(),
			];
			$translationPreferences[] = $provider::class;
		}

		$sttProviders = [];
		foreach ($this->sttManager->getProviders() as $provider) {
			$sttProviders[] = [
				'class' => $provider::class,
				'name' => $provider->getName(),
			];
		}

		$textProcessingProviders = [];
		/** @var array<class-string<ITaskType>, class-string<IProvider>> $textProcessingSettings */
		$textProcessingSettings = [];
		foreach ($this->textProcessingManager->getProviders() as $provider) {
			$textProcessingProviders[] = [
				'class' => $provider::class,
				'name' => $provider->getName(),
				'taskType' => $provider->getTaskType(),
			];
			if (!isset($textProcessingSettings[$provider->getTaskType()])) {
				$textProcessingSettings[$provider->getTaskType()] = $provider::class;
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
						// Only show entries from $value (saved pref list) that are in $defaultValue (enabled providers)
						// and add all providers that are enabled but not in the pref list
						if (!is_array($defaultValue)) {
							break;
						}
						$value = array_values(array_unique(array_merge(array_intersect($value, $defaultValue), $defaultValue), SORT_STRING));
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
