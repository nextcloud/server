<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\LostPassword\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IURLGenerator;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IConfig;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use \OC_Defaults;
use OCP\Security\StringUtils;

/**
 * Class LostController
 *
 * Successfully changing a password will emit the post_passwordReset hook.
 *
 * @package OC\Core\LostPassword\Controller
 */
class LostController extends Controller {

	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IUserManager */
	protected $userManager;
	/** @var OC_Defaults */
	protected $defaults;
	/** @var IL10N */
	protected $l10n;
	/** @var string */
	protected $from;
	/** @var bool */
	protected $isDataEncrypted;
	/** @var IConfig */
	protected $config;
	/** @var ISecureRandom */
	protected $secureRandom;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param OC_Defaults $defaults
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param string $from
	 * @param string $isDataEncrypted
	 */
	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								OC_Defaults $defaults,
								IL10N $l10n,
								IConfig $config,
								ISecureRandom $secureRandom,
								$from,
								$isDataEncrypted) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->secureRandom = $secureRandom;
		$this->from = $from;
		$this->isDataEncrypted = $isDataEncrypted;
		$this->config = $config;
	}

	/**
	 * Someone wants to reset their password:
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 * @param string $userId
	 * @return TemplateResponse
	 */
	public function resetform($token, $userId) {
		return new TemplateResponse(
			'core/lostpassword',
			'resetpassword',
			array(
				'link' => $this->urlGenerator->linkToRouteAbsolute('core.lost.setPassword', array('userId' => $userId, 'token' => $token)),
			),
			'guest'
		);
	}

	/**
	 * @param $message
	 * @param array $additional
	 * @return array
	 */
	private function error($message, array $additional=array()) {
		return array_merge(array('status' => 'error', 'msg' => $message), $additional);
	}

	/**
	 * @return array
	 */
	private function success() {
		return array('status'=>'success');
	}

	/**
	 * @PublicPage
	 *
	 * @param string $user
	 * @return array
	 */
	public function email($user){
		// FIXME: use HTTP error codes
		try {
			$this->sendEmail($user);
		} catch (\Exception $e){
			return $this->error($e->getMessage());
		}

		return $this->success();
	}

	/**
	 * @PublicPage
	 * @param string $token
	 * @param string $userId
	 * @param string $password
	 * @param boolean $proceed
	 * @return array
	 */
	public function setPassword($token, $userId, $password, $proceed) {
		if ($this->isDataEncrypted && !$proceed){
			return $this->error('', array('encryption' => true));
		}

		try {
			$user = $this->userManager->get($userId);

			if (!StringUtils::equals($this->config->getUserValue($userId, 'owncloud', 'lostpassword'), $token)) {
				throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is invalid'));
			}

			if (!$user->setPassword($password)) {
				throw new \Exception();
			}

			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', array('uid' => $userId, 'password' => $password));

			$this->config->deleteUserValue($userId, 'owncloud', 'lostpassword');
			@\OC_User::unsetMagicInCookie();

		} catch (\Exception $e){
			return $this->error($e->getMessage());
		}

		return $this->success();
	}

	/**
	 * @param string $user
	 * @throws \Exception
	 */
	protected function sendEmail($user) {
		if (!$this->userManager->userExists($user)) {
			throw new \Exception($this->l10n->t('Couldn\'t send reset email. Please make sure your username is correct.'));
		}

		$email = $this->config->getUserValue($user, 'settings', 'email');

		if (empty($email)) {
			throw new \Exception(
				$this->l10n->t('Couldn\'t send reset email because there is no '.
					'email address for this username. Please ' .
					'contact your administrator.')
			);
		}

		$token = $this->secureRandom->getMediumStrengthGenerator()->generate(21,
			ISecureRandom::CHAR_DIGITS.
			ISecureRandom::CHAR_LOWER.
			ISecureRandom::CHAR_UPPER);
		$this->config->setUserValue($user, 'owncloud', 'lostpassword', $token);

		$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', array('userId' => $user, 'token' => $token));

		$tmpl = new \OC_Template('core/lostpassword', 'email');
		$tmpl->assign('link', $link, false);
		$msg = $tmpl->fetchPage();

		try {
			// FIXME: should be added to the container and injected in here
			\OC_Mail::send(
				$email,
				$user,
				$this->l10n->t('%s password reset',	array($this->defaults->getName())),
				$msg,
				$this->from,
				$this->defaults->getName()
			);
		} catch (\Exception $e) {
			throw new \Exception($this->l10n->t(
				'Couldn\'t send reset email. Please contact your administrator.'
			));
		}
	}

}
