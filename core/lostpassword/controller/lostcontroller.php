<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\LostPassword\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IURLGenerator;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IConfig;
use \OCP\IUserSession;
use \OC\Core\LostPassword\EncryptedDataException;

class LostController extends Controller {

	protected $urlGenerator;
	protected $userManager;
	protected $defaults;
	protected $l10n;
	protected $from;
	protected $isDataEncrypted;
	protected $config;
	protected $userSession;

	public function __construct($appName,
	                            IRequest $request,
	                            IURLGenerator $urlGenerator,
	                            $userManager,
	                            $defaults,
	                            IL10N $l10n,
	                            IConfig $config,
	                            IUserSession $userSession,
	                            $from,
	                            $isDataEncrypted) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->from = $from;
		$this->isDataEncrypted = $isDataEncrypted;
		$this->config = $config;
		$this->userSession = $userSession;
	}

	/**
	 * Someone wants to reset their password:
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string $uid
	 */
	public function resetform($token, $uid) {
		return new TemplateResponse(
			'core/lostpassword',
			'resetpassword',
			array(
				'isEncrypted' => $this->isDataEncrypted,
				'link' => $this->getLink('core.lost.setPassword', $uid, $token),
			),
			'guest'
		);
	}

	/**
	 * @PublicPage
	 *
	 * @param string $user
	 * @param bool $proceed
	 */
	public function email($user, $proceed){
		// FIXME: use HTTP error codes
		try {
			$this->sendEmail($user, $proceed);
		} catch (EncryptedDataException $e){
			array('status' => 'error', 'encryption' => '1');
		} catch (\Exception $e){
			return array('status' => 'error', 'msg' => $e->getMessage());
		}

		return array('status'=>'success');
	}


	/**
	 * @PublicPage
	 */
	public function setPassword($token, $uid, $password) {
		try {
			if (!$this->checkToken($uid, $token)) {
				throw new \Exception();
			}

			$user = $this->userManager->get($uid);
			if (!$user->setPassword($uid, $password)) {

				throw new \Exception();
			}

			// FIXME: should be added to the all config at some point
			\OC_Preferences::deleteKey($uid, 'owncloud', 'lostpassword');
			$this->userSession->unsetMagicInCookie();

		} catch (\Exception $e){
			return array('status' => 'error','msg' => $e->getMessage());
		}

		return array('status'=>'success');
	}


	protected function sendEmail($user, $proceed) {
		if ($this->isDataEncrypted && !$proceed){
			throw new EncryptedDataException();
		}

		if (!$this->userManager->userExists($user)) {
			throw new \Exception(
				$this->l10n->t('Couldn’t send reset email. Please make sure '.
				               'your username is correct.'));
		}

		$token = hash('sha256', \OC_Util::generateRandomBytes(30));

		// Hash the token again to prevent timing attacks
		$this->config->setUserValue(
			$user, 'owncloud', 'lostpassword', hash('sha256', $token)
		);

		$email = $this->config->getUserValue($user, 'settings', 'email');

		if (empty($email)) {
			throw new \Exception(
				$this->l10n->t('Couldn’t send reset email because there is no '.
				               'email address for this username. Please ' .
				               'contact your administrator.')
			);
		}

		$link = $this->getLink('core.lost.resetform', $user, $token);

		$tmpl = new \OC_Template('core/lostpassword', 'email');
		$tmpl->assign('link', $link, false);
		$msg = $tmpl->fetchPage();

		try {
			\OC_Mail::send($email, $user, $this->l10n->t(
				'%s password reset',
				array(
					$this->defaults->getName())),
					$msg,
					$this->from,
					$this->defaults->getName()
				));
		} catch (\Exception $e) {
			throw new \Exception($this->l10n->t('Couldn’t send reset email. ' .
				                                'Please contact your administrator.'));
		}
	}


	protected function getLink($route, $user, $token){
		$parameters = array(
			'token' => $token,
			'uid' => $user
		);
		$link = $this->urlGenerator->linkToRoute($route, $parameters);
		return $this->urlGenerator->getAbsoluteUrl($link);
	}


	protected function checkToken($user, $token) {
		return $this->config->getUserValue(
			$user, 'owncloud', 'lostpassword'
		) === hash('sha256', $token);
	}

}
