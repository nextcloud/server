<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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


namespace OC\Core\Controller;

use InvalidArgumentException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Translation\CouldNotTranslateException;
use OCP\Translation\ITranslationManager;

class TranslationApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ITranslationManager $translationManager,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @PublicPage
	 *
	 * Get the list of supported languages
	 *
	 * @return DataResponse<Http::STATUS_OK, array{languages: array{from: string, fromLabel: string, to: string, toLabel: string}[], languageDetection: bool}, array{}>
	 *
	 * 200: Supported languages returned
	 */
	public function languages(): DataResponse {
		return new DataResponse([
			'languages' => array_map(fn ($lang) => $lang->jsonSerialize(), $this->translationManager->getLanguages()),
			'languageDetection' => $this->translationManager->canDetectLanguage(),
		]);
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=25, period=120)
	 * @AnonRateThrottle(limit=10, period=120)
	 *
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
