<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */


namespace OC\Translation;

use InvalidArgumentException;
use OC\AppFramework\Bootstrap\Coordinator;
use OCP\IServerContainer;
use OCP\PreConditionNotMetException;
use OCP\Translation\IDetectLanguageProvider;
use OCP\Translation\ITranslationManager;
use OCP\Translation\ITranslationProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class TranslationManager implements ITranslationManager {
	/** @var ?ITranslationProvider[] */
	private ?array $providers = null;

	public function __construct(
		private IServerContainer $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
	) {
	}

	public function getLanguages(): array {
		$languages = [];
		foreach ($this->getProviders() as $provider) {
			$languages = array_merge($languages, $provider->getAvailableLanguages());
		}
		return $languages;
	}

	public function translate(string $text, ?string $fromLanguage, string $toLanguage): string {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No translation providers available');
		}

		foreach ($this->getProviders() as $provider) {
			if ($fromLanguage === null && $provider instanceof IDetectLanguageProvider) {
				$fromLanguage = $provider->detectLanguage($text);
			}

			if ($fromLanguage === null) {
				throw new InvalidArgumentException('Could not detect language');
			}

			try {
				return $provider->translate($fromLanguage, $toLanguage, $text);
			} catch (RuntimeException $e) {
				$this->logger->warning("Failed to translate from {$fromLanguage} to {$toLanguage}", ['exception' => $e]);
			}
		}

		throw new RuntimeException('Could not translate text');
	}

	public function getProviders(): array {
		$context = $this->coordinator->getRegistrationContext();

		if ($this->providers !== null) {
			return $this->providers;
		}

		$this->providers = [];
		foreach ($context->getTranslationProviders() as $providerRegistration) {
			$class = $providerRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load translation provider ' . $class, [
					'exception' => $e
				]);
			}
		}

		return $this->providers;
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		return !empty($context->getTranslationProviders());
	}

	public function canDetectLanguage(): bool {
		foreach ($this->getProviders() as $provider) {
			if ($provider instanceof IDetectLanguageProvider) {
				return true;
			}
		}
		return false;
	}
}
