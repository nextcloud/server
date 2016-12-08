<?php
/**
 * @copyright  Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory;

class PersonalController extends Controller {
	/** @var IFactory */
	private $l10nFactory;

	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	/** @var IL10N */
	private $l;

	/**
	 * PersonalController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IFactory $l10nFactory
	 * @param $userId
	 * @param IConfig $config
	 * @param IL10N $l
	 */
	public function __construct($appName,
								IRequest $request,
								IFactory $l10nFactory,
								$userId,
								IConfig $config,
								IL10N $l) {
		parent::__construct($appName, $request);

		$this->l10nFactory = $l10nFactory;
		$this->userId = $userId;
		$this->config = $config;
		$this->l = $l;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @param string $lang
	 * @return JSONResponse
	 */
	public function setLanguage($lang) {
		if ($lang !== '') {
			$languagesCodes = $this->l10nFactory->findAvailableLanguages();
			if (array_search($lang, $languagesCodes) || $lang === 'en') {
				$this->config->setUserValue($this->userId, 'core', 'lang', $lang);
				return new JSONResponse([]);
			}
		}

		return new JSONResponse(['message' => $this->l->t('Invalid request')], Http::STATUS_BAD_REQUEST);
	}
}
