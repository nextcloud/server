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

class LostController extends Controller {
	
	protected $urlGenerator;
	protected $userManager;
	protected $defaults;
	protected $l10n;
	protected $from;
	protected $isDataEncrypted;
	
	public function __construct($appName, IRequest $request, IURLGenerator $urlGenerator, $userManager,
			$defaults, $l10n, $from, $isDataEncrypted) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->from = $from;
		$this->isDataEncrypted = $isDataEncrypted;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * 
	 * @param string $token
	 * @param string $uid
	 */
	public function reset($token, $uid) {
		// Someone wants to reset their password:
		if($this->checkToken($uid, $token)) {
			return new TemplateResponse(
				'core/lostpassword', 
				'resetpassword', 
				array(
					'link' => $link
				), 
				'guest'
			);
		} else {
			// Someone lost their password
			return new TemplateResponse(
				'core/lostpassword', 
				'lostpassword', 
				array(
					'isEncrypted' => $this->isDataEncrypted, 
					'link' => $this->getResetPasswordLink($uid, $token)
				),
				'guest'
			);
		}
	}
	
	/**
	 * @PublicPage
	 * 
	 * @param bool $proceed
	 */
	public function lost($user, $proceed){
		$response = new JSONResponse(array('status'=>'success'));
		try {
			$this->sendEmail($user, $proceed);
		} catch (EncryptedDataException $e){
			$response->setData(array(
				'status' => 'error',
				'encryption' => '1'
			));
		} catch (\Exception $e){
			$response->setData(array(
				'status' => 'error',
				'msg' => $e->getMessage()
			));
		}
		
		return $response;
	}
	
	/**
	 * @PublicPage
	 */
	public function resetPassword($user, $password, $token) {
		$response = new JSONResponse(array('status'=>'success'));
		try {
			if (!$this->checkToken($user, $token)) {
				throw new \RuntimeException('');
			}
			if (!$this->userManager->setPassword($user, $newPassword)) {
				throw new \RuntimeException('');
			}
			\OC_Preferences::deleteKey($user, 'owncloud', 'lostpassword');
			$this->userManager->unsetMagicInCookie();
		} catch (Exception $e){
			$response->setData(array(
				'status' => 'error',
				'msg' => $e->getMessage()
			));
		}
		return $response;
	}
	
	protected function sendEmail($user, $proceed) {
		if ($this->isDataEncrypted && $proceed !== 'Yes'){
			throw new EncryptedDataException();
		}

		if (!$this->userManager->userExists($user)) {
			throw new \Exception($this->l10n->t('Couldn’t send reset email. Please make sure your username is correct.'));
		}
		$token = hash('sha256', \OC_Util::generateRandomBytes(30));
		\OC_Preferences::setValue($user, 'owncloud', 'lostpassword', hash('sha256', $token)); // Hash the token again to prevent timing attacks
		$email = \OC_Preferences::getValue($user, 'settings', 'email', '');
		if (empty($email)) {
			throw new \Exception($this->l10n->t('Couldn’t send reset email because there is no email address for this username. Please contact your administrator.'));
		}
		
		$link = $this->getResetPasswordLink($user, $token);
		echo $link;
		$tmpl = new \OC_Template('core/lostpassword', 'email');
		$tmpl->assign('link', $link, false);
		$msg = $tmpl->fetchPage();
		try {
			\OC_Mail::send($email, $user, $this->l10n->t('%s password reset', array($this->defaults->getName())), $msg, $this->from, $this->defaults->getName());
		} catch (\Exception $e) {
			throw new \Exception( $this->l10n->t('Couldn’t send reset email. Please contact your administrator.'));
		}
	}

	protected function getResetPasswordLink($user, $token){
		$parameters = array(
			'token' => $token, 
			'uid' => $user
		);
		$link = $this->urlGenerator->linkToRoute('core.lost.reset', $parameters);
		return $this->urlGenerator->getAbsoluteUrl($link);
	}

	protected function checkToken($user, $token) {
		return \OC_Preferences::getValue($user, 'owncloud', 'lostpassword') === hash('sha256', $token);
	}
}
