<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OC\Core\Controller;

use InvalidArgumentException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Translation\CouldNotTranslateException;
use OCP\Translation\ITranslationManager;

class TranslationApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ITranslationManager $translationManager,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the list of supported languages
	 *
	 * @return DataResponse<Http::STATUS_OK, array{languages: list<array{from: string, fromLabel: string, to: string, toLabel: string}>, languageDetection: bool}, array{}>
	 *
	 * 200: Supported languages returned
	 */
	#[PublicPage]
	#[ApiRoute(verb: 'GET', url: '/languages', root: '/translation')]
	public function languages(): DataResponse {
		return new DataResponse([
			'languages' => array_values(array_map(fn ($lang) => $lang->jsonSerialize(), $this->translationManager->getLanguages())),
			'languageDetection' => $this->translationManager->canDetectLanguage(),
		]);
	}

	/**
	 * Translate a text
	 *
	 * @param string $text Text to be translated
	 * @param string|null $fromLanguage Language to translate from
	 * @param string $toLanguage Language to translate to
	 * @return DataResponse<Http::STATUS_OK, array{text: string, from: ?string}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string, from?: ?string}, array{}>
	 *
	 * 200: Translated text returned
	 * 400: Language not detected or unable to translate
	 * 412: Translating is not possible
	 */
	#[PublicPage]
	#[UserRateLimit(limit: 25, period: 120)]
	#[AnonRateLimit(limit: 10, period: 120)]
	#[ApiRoute(verb: 'POST', url: '/translate', root: '/translation')]
	public function translate(string $text, ?string $fromLanguage, string $toLanguage): DataResponse {
		try {
			$translation = $this->translationManager->translate($text, $fromLanguage, $toLanguage);

			return new DataResponse([
				'text' => $translation,
				'from' => $fromLanguage,

			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l10n->t('No translation provider available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (InvalidArgumentException) {
			return new DataResponse(['message' => $this->l10n->t('Could not detect language')], Http::STATUS_BAD_REQUEST);
		} catch (CouldNotTranslateException $e) {
			return new DataResponse(['message' => $this->l10n->t('Unable to translate'), 'from' => $e->getFrom()], Http::STATUS_BAD_REQUEST);
		}
	}
}
