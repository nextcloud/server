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
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use OCP\Translation\ITranslationManager;
use RuntimeException;

class TranslationApiController extends \OCP\AppFramework\OCSController {
	private ITranslationManager $translationManager;

	public function __construct($appName, IRequest $request, ITranslationManager $translationManager) {
		parent::__construct($appName, $request);

		$this->translationManager = $translationManager;
	}

	/**
	 * @NoAdminRequired
	 */
	public function languages(): DataResponse {
		return new DataResponse([
			'languages' => $this->translationManager->getLanguages(),
			'languageDetection' => $this->translationManager->canDetectLanguage(),
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function translate(string $text, ?string $fromLanguage, string $toLanguage): DataResponse {
		try {
			return new DataResponse([
				'text' => $this->translationManager->translate($text, $fromLanguage, $toLanguage)
			]);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => 'No translation provider available'], Http::STATUS_PRECONDITION_FAILED);
		} catch (InvalidArgumentException) {
			return new DataResponse(['message' => 'Could not detect language', Http::STATUS_NOT_FOUND]);
		} catch (RuntimeException) {
			return new DataResponse(['message' => 'Unable to translate', Http::STATUS_INTERNAL_SERVER_ERROR]);
		}
	}
}
