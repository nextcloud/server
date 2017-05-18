<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\OAuth2\Controller;

use OC\Authentication\Token\DefaultTokenMapper;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;

class SettingsController extends Controller {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ClientMapper */
	private $clientMapper;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var AccessTokenMapper  */
	private $accessTokenMapper;
	/** @var  DefaultTokenMapper */
	private $defaultTokenMapper;

	const validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ClientMapper $clientMapper
	 * @param ISecureRandom $secureRandom
	 * @param AccessTokenMapper $accessTokenMapper
	 * @param DefaultTokenMapper $defaultTokenMapper
	 */
	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								ClientMapper $clientMapper,
								ISecureRandom $secureRandom,
								AccessTokenMapper $accessTokenMapper,
								DefaultTokenMapper $defaultTokenMapper
	) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->secureRandom = $secureRandom;
		$this->clientMapper = $clientMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->defaultTokenMapper = $defaultTokenMapper;
	}

	/**
	 * @param string $name
	 * @param string $redirectUri
	 * @return RedirectResponse
	 */
	public function addClient($name,
							  $redirectUri) {
		$client = new Client();
		$client->setName($name);
		$client->setRedirectUri($redirectUri);
		$client->setSecret($this->secureRandom->generate(64, self::validChars));
		$client->setClientIdentifier($this->secureRandom->generate(64, self::validChars));
		$this->clientMapper->insert($client);
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/security'));
	}

	/**
	 * @param int $id
	 * @return RedirectResponse
	 */
	public function deleteClient($id) {
		$client = $this->clientMapper->getByUid($id);
		$this->accessTokenMapper->deleteByClientId($id);
		$this->defaultTokenMapper->deleteByName($client->getName());
		$this->clientMapper->delete($client);
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/security'));
	}
}
