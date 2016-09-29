<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Encryption\IManager;
use \OCP\IURLGenerator;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IConfig;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;

/**
 * Class LostController
 *
 * Successfully changing a password will emit the post_passwordReset hook.
 *
 * @package OC\Core\Controller
 */
class LostController extends Controller {

	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IUserManager */
	protected $userManager;
	/** @var \OC_Defaults */
	protected $defaults;
	/** @var IL10N */
	protected $l10n;
	/** @var string */
	protected $from;
	/** @var IManager */
	protected $encryptionManager;
	/** @var IConfig */
	protected $config;
	/** @var ISecureRandom */
	protected $secureRandom;
	/** @var IMailer */
	protected $mailer;
	/** @var ITimeFactory */
	protected $timeFactory;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param \OC_Defaults $defaults
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param string $defaultMailAddress
	 * @param IManager $encryptionManager
	 * @param IMailer $mailer
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								\OC_Defaults $defaults,
								IL10N $l10n,
								IConfig $config,
								ISecureRandom $secureRandom,
								$defaultMailAddress,
								IManager $encryptionManager,
								IMailer $mailer,
								ITimeFactory $timeFactory) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->secureRandom = $secureRandom;
		$this->from = $defaultMailAddress;
		$this->encryptionManager = $encryptionManager;
		$this->config = $config;
		$this->mailer = $mailer;
		$this->timeFactory = $timeFactory;
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
		try {
			$this->checkPasswordResetToken($token, $userId);
		} catch (\Exception $e) {
			return new TemplateResponse(
				'core', 'error', [
					"errors" => array(array("error" => $e->getMessage()))
				],
				'guest'
			);
		}

		return new TemplateResponse(
			'core',
			'lostpassword/resetpassword',
			array(
				'link' => $this->urlGenerator->linkToRouteAbsolute('core.lost.setPassword', array('userId' => $userId, 'token' => $token)),
			),
			'guest'
		);
	}

	/**
	 * @param string $token
	 * @param string $userId
	 * @throws \Exception
	 */
	private function checkPasswordResetToken($token, $userId) {
		$user = $this->userManager->get($userId);

		$splittedToken = explode(':', $this->config->getUserValue($userId, 'core', 'lostpassword', null));
		if(count($splittedToken) !== 2) {
			throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is invalid'));
		}

		if ($splittedToken[0] < ($this->timeFactory->getTime() - 60*60*12) ||
			$user->getLastLogin() > $splittedToken[0]) {
			throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is expired'));
		}

		if (!hash_equals($splittedToken[1], $token)) {
			throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is invalid'));
		}
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
		if ($this->encryptionManager->isEnabled() && !$proceed) {
			return $this->error('', array('encryption' => true));
		}

		try {
			$this->checkPasswordResetToken($token, $userId);
			$user = $this->userManager->get($userId);

			if (!$user->setPassword($password)) {
				throw new \Exception();
			}

			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', array('uid' => $userId, 'password' => $password));

			$this->config->deleteUserValue($userId, 'core', 'lostpassword');
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

		$userObject = $this->userManager->get($user);
		$email = $userObject->getEMailAddress();

		if (empty($email)) {
			throw new \Exception(
				$this->l10n->t('Could not send reset email because there is no email address for this username. Please contact your administrator.')
			);
		}

		$token = $this->secureRandom->generate(21,
			ISecureRandom::CHAR_DIGITS.
			ISecureRandom::CHAR_LOWER.
			ISecureRandom::CHAR_UPPER);
		$this->config->setUserValue($user, 'core', 'lostpassword', $this->timeFactory->getTime() .':'. $token);

		$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', array('userId' => $user, 'token' => $token));

		$tmpl = new \OC_Template('core', 'lostpassword/email');
		$tmpl->assign('link', $link);
		$msg = $tmpl->fetchPage();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$email => $user]);
			$message->setSubject($this->l10n->t('%s password reset', [$this->defaults->getName()]));
			$message->setPlainBody($msg);
			$message->setFrom([$this->from => $this->defaults->getName()]);
			$this->mailer->send($message);
		} catch (\Exception $e) {
			throw new \Exception($this->l10n->t(
				'Couldn\'t send reset email. Please contact your administrator.'
			));
		}
	}

}
