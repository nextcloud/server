<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
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


namespace OC\Core\Controller;

use InvalidArgumentException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Translation\ITranslationManager;
use RuntimeException;

class TranslationApiController extends \OCP\AppFramework\OCSController {
	private ITranslationManager $translationManager;
	private IL10N $l;

	public function __construct(
		string $appName,
		IRequest $request,
		ITranslationManager $translationManager,
		IL10N $l,
	) {
		parent::__construct($appName, $request);

		$this->translationManager = $translationManager;
		$this->l = $l;
	}

	/**
	 * @PublicPage
	 */
	public function languages(): DataResponse {
		return new DataResponse([
			'languages' => $this->translationManager->getLanguages(),
			'languageDetection' => $this->translationManager->canDetectLanguage(),
		]);
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=25, period=120)
	 * @AnonRateThrottle(limit=10, period=120)
	 */
	public function translate(string $text, ?string $fromLanguage, string $toLanguage): DataResponse {
		try {
			return new DataResponse([
				'text' => $this->translationManager->translate($text, $fromLanguage, $toLanguage)
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l->t('No translation provider available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (InvalidArgumentException) {
			return new DataResponse(['message' => $this->l->t('Could not detect language')], Http::STATUS_BAD_REQUEST);
		} catch (RuntimeException) {
			return new DataResponse(['message' => $this->l->t('Unable to translate')], Http::STATUS_BAD_REQUEST);
		}
	}
}
