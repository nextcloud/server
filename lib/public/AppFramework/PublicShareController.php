<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\AppFramework;

use OC\AppFramework\Middleware\Share\Exceptions\AuthenticationRequiredException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;
use OCP\Share\IManager as ShareManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

abstract class PublicShareController extends Controller {

	/** @var ShareManager */
	protected $shareManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var ISession */
	protected $session;

	/** @var IShare */
	protected $share;

	/** @var EventDispatcherInterface */
	protected $eventDispatcher;

	/**
	 * PublicShareController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ShareManager $shareManager
	 * @param IURLGenerator $urlGenerator
	 * @param ISession $session
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function __construct($appName,
								IRequest $request,
								ShareManager $shareManager,
								IURLGenerator $urlGenerator,
								ISession $session,
								EventDispatcherInterface $eventDispatcher) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManager;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @throws ShareNotFound
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showAuthenticate($token) {
		$this->share = $this->shareManager->getShareByToken($token);

		if($this->isAuthenticated($this->share)) {
			return new RedirectResponse($this->urlGenerator->linkToRoute($this->getShowShareRoute(), ['token' => $token]));
		}

		return new TemplateResponse($this->appName, 'authenticate', [], 'guest');
	}

	/**
	 * @PublicPage
	 * @UseSession
	 * @BruteForceProtection(action=publicLinkAuth)
	 *
	 * Authenticates against password-protected shares
	 * @param string $token
	 * @param string $password
	 * @throws ShareNotFound
	 * @throws AuthenticationRequiredException
	 * @return TemplateResponse
	 */
	public function authenticate($token, $password = '') {
		$this->share = $this->shareManager->getShareByToken($token);

		if ($this->isAuthenticated($this->share) || $this->doAuthenticate($this->share, $password)) {
			throw new AuthenticationRequiredException($this->urlGenerator->linkToRoute($this->getShowShareRoute(), ['token' => $token]));
		}

		$response = new TemplateResponse($this->appName, 'authenticate', ['wrongpw' => true], 'guest');
		$response->throttle();
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @throws ShareNotFound
	 * @throws AuthenticationRequiredException
	 */
	public function showShare($token) {
		try {
			$this->share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			$event = new GenericEvent($token, ['errorCode' => 404, 'errorMessage' => 'Share not found']);
			$this->eventDispatcher->dispatch('OCP\Share::share_link_access', $event);
			throw $e;
		}

		if (!$this->isAuthenticated($this->share)) {
			throw new AuthenticationRequiredException($this->urlGenerator->linkToRoute($this->getAuthenticateRoute(), ['token' => $token]));
		}
	}

	/**
	 * @param IShare $share
	 * @return bool
	 */
	protected function isAuthenticated(IShare $share) {
		if ($share->getPassword() === null) {
			return true;
		}

		return !(!$this->session->exists('public_link_authenticated') || $this->session->get('public_link_authenticated') !== (string)$share->getId());
	}

	/**
	 * @param IShare $share
	 * @param string $password
	 * @return bool
	 */
	protected function doAuthenticate(IShare $share, $password) {
		if ($this->shareManager->checkPassword($share, $password)) {
			$this->session->set('public_link_authenticated', (string)$share->getId());
			return true;
		}

		$event = new GenericEvent($share, ['errorCode' => 403, 'errorMessage' => 'Wrong password']);
		$this->eventDispatcher->dispatch('OCP\Share::share_link_access', $event);
		return false;
	}

	/**
	 * @return string
	 */
	abstract protected function getAuthenticateRoute();

	/**
	 * @return string
	 */
	abstract protected function getShowShareRoute();
}
