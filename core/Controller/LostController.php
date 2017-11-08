<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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
use OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\Encryption\IManager;
use \OCP\IURLGenerator;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
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
	/** @var Defaults */
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
	/** @var ICrypto */
	protected $crypto;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param IUserManager $userManager
	 * @param Defaults $defaults
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param ISecureRandom $secureRandom
	 * @param string $defaultMailAddress
	 * @param IManager $encryptionManager
	 * @param IMailer $mailer
	 * @param ITimeFactory $timeFactory
	 * @param ICrypto $crypto
	 */
	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								Defaults $defaults,
								IL10N $l10n,
								IConfig $config,
								ISecureRandom $secureRandom,
								$defaultMailAddress,
								IManager $encryptionManager,
								IMailer $mailer,
								ITimeFactory $timeFactory,
								ICrypto $crypto) {
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
		$this->crypto = $crypto;
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
		if ($this->config->getSystemValue('lost_password_link', '') !== '') {
			return new TemplateResponse('core', 'error', [
					'errors' => [['error' => $this->l10n->t('Password reset is disabled')]]
				],
				'guest'
			);
		}

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
	protected function checkPasswordResetToken($token, $userId) {
		$user = $this->userManager->get($userId);
		if($user === null || !$user->isEnabled()) {
			throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is invalid'));
		}

		try {
			$encryptedToken = $this->config->getUserValue($userId, 'core', 'lostpassword', null);
			$mailAddress = !is_null($user->getEMailAddress()) ? $user->getEMailAddress() : '';
			$decryptedToken = $this->crypto->decrypt($encryptedToken, $mailAddress.$this->config->getSystemValue('secret'));
		} catch (\Exception $e) {
			throw new \Exception($this->l10n->t('Couldn\'t reset password because the token is invalid'));
		}

		$splittedToken = explode(':', $decryptedToken);
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
	 * @BruteForceProtection(action=passwordResetEmail)
	 * @AnonRateThrottle(limit=10, period=300)
	 *
	 * @param string $user
	 * @return JSONResponse
	 */
	public function email($user){
		if ($this->config->getSystemValue('lost_password_link', '') !== '') {
			return new JSONResponse($this->error($this->l10n->t('Password reset is disabled')));
		}

		// FIXME: use HTTP error codes
		try {
			$this->sendEmail($user);
		} catch (\Exception $e){
			$response = new JSONResponse($this->error($e->getMessage()));
			$response->throttle();
			return $response;
		}

		$response = new JSONResponse($this->success());
		$response->throttle();
		return $response;
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
		if ($this->config->getSystemValue('lost_password_link', '') !== '') {
			return $this->error($this->l10n->t('Password reset is disabled'));
		}

		if ($this->encryptionManager->isEnabled() && !$proceed) {
			return $this->error('', array('encryption' => true));
		}

		try {
			$this->checkPasswordResetToken($token, $userId);
			$user = $this->userManager->get($userId);

			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'pre_passwordReset', array('uid' => $userId, 'password' => $password));

			if (!$user->setPassword($password)) {
				throw new \Exception();
			}

			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', array('uid' => $userId, 'password' => $password));

			$this->config->deleteUserValue($userId, 'core', 'lostpassword');
			@\OC::$server->getUserSession()->unsetMagicInCookie();
		} catch (\Exception $e){
			return $this->error($e->getMessage());
		}

		return $this->success();
	}

	/**
	 * @param string $input
	 * @throws \Exception
	 */
	protected function sendEmail($input) {
		$user = $this->findUserByIdOrMail($input);
		$email = $user->getEMailAddress();

		if (empty($email)) {
			throw new \Exception(
				$this->l10n->t('Could not send reset email because there is no email address for this username. Please contact your administrator.')
			);
		}

		// Generate the token. It is stored encrypted in the database with the
		// secret being the users' email address appended with the system secret.
		// This makes the token automatically invalidate once the user changes
		// their email address.
		$token = $this->secureRandom->generate(
			21,
			ISecureRandom::CHAR_DIGITS.
			ISecureRandom::CHAR_LOWER.
			ISecureRandom::CHAR_UPPER
		);
		$tokenValue = $this->timeFactory->getTime() .':'. $token;
		$encryptedValue = $this->crypto->encrypt($tokenValue, $email . $this->config->getSystemValue('secret'));
		$this->config->setUserValue($user->getUID(), 'core', 'lostpassword', $encryptedValue);

		$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', array('userId' => $user->getUID(), 'token' => $token));

		$emailTemplate = $this->mailer->createEMailTemplate('core.ResetPassword', [
			'link' => $link,
		]);

		$emailTemplate->setSubject($this->l10n->t('%s password reset', [$this->defaults->getName()]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Password reset'));

		$emailTemplate->addBodyText(
			$this->l10n->t('Click the following button to reset your password. If you have not requested the password reset, then ignore this email.'),
			$this->l10n->t('Click the following link to reset your password. If you have not requested the password reset, then ignore this email.')
		);

		$emailTemplate->addBodyButton(
			$this->l10n->t('Reset your password'),
			$link,
			false
		);
		$emailTemplate->addFooter();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$email => $user->getUID()]);
			$message->setFrom([$this->from => $this->defaults->getName()]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);
		} catch (\Exception $e) {
			throw new \Exception($this->l10n->t(
				'Couldn\'t send reset email. Please contact your administrator.'
			));
		}
	}

	/**
	 * @param string $input
	 * @return IUser
	 * @throws \InvalidArgumentException
	 */
	protected function findUserByIdOrMail($input) {
		$user = $this->userManager->get($input);
		if ($user instanceof IUser) {
			if (!$user->isEnabled()) {
				throw new \InvalidArgumentException($this->l10n->t('Couldn\'t send reset email. Please make sure your username is correct.'));
			}

			return $user;
		}
		$users = $this->userManager->getByEmail($input);
		if (count($users) === 1) {
			$user = $users[0];
			if (!$user->isEnabled()) {
				throw new \InvalidArgumentException($this->l10n->t('Couldn\'t send reset email. Please make sure your username is correct.'));
			}

			return $user;
		}

		throw new \InvalidArgumentException($this->l10n->t('Couldn\'t send reset email. Please make sure your username is correct.'));
	}
}
