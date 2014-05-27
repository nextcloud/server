<?php
/**
 * @author Victor Dubiniuk
 * @copyright 2014 Victor Dubiniuk victor.dubiniuk@gmail.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
namespace OC\Core\LostPassword\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;

class AjaxController extends LostController {

	/**
	 * @PublicPage
	 */
	public function lost(){
		$response = new JSONResponse(array('status'=>'success'));
		try {
			$this->sendEmail($this->params('user', ''), $this->params('proceed', ''));
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
	public function resetPassword() {
		$response = new JSONResponse(array('status'=>'success'));
		try {
			$user = $this->params('user');
			$newPassword = $this->params('password');
			if (!$this->checkToken()) {
				throw new \RuntimeException('');
			}
			if (!\OC_User::setPassword($user, $newPassword)) {
				throw new \RuntimeException('');
			}
			\OC_Preferences::deleteKey($user, 'owncloud', 'lostpassword');
			\OC_User::unsetMagicInCookie();
		} catch (Exception $e){
			$response->setData(array(
				'status' => 'error',
				'msg' => $e->getMessage()
			));
		}
		return $response;
	}
	
	protected function sendEmail($user, $proceed) {
		$l = \OC_L10N::get('core');
		$isEncrypted = \OC_App::isEnabled('files_encryption');

		if ($isEncrypted && $proceed !== 'Yes'){
			throw new EncryptedDataException();
		}

		if (!\OC_User::userExists($user)) {
			throw new \Exception($l->t('Couldn’t send reset email. Please make sure your username is correct.'));
		}
		$token = hash('sha256', \OC_Util::generateRandomBytes(30).\OC_Config::getValue('passwordsalt', ''));
		\OC_Preferences::setValue($user, 'owncloud', 'lostpassword',
			hash('sha256', $token)); // Hash the token again to prevent timing attacks
		$email = \OC_Preferences::getValue($user, 'settings', 'email', '');
		if (empty($email)) {
			throw new \Exception($l->t('Couldn’t send reset email because there is no email address for this username. Please contact your administrator.'));
		}
		
		$parameters = array('token' => $token, 'user' => $user);
		$link = $this->urlGenerator->linkToRoute('core.lost.reset', $parameters);
		$link = $this->urlGenerator->getAbsoluteUrl($link);
		
		$tmpl = new \OC_Template('core/lostpassword', 'email');
		$tmpl->assign('link', $link, false);
		$msg = $tmpl->fetchPage();
		echo $link;
		$from = \OCP\Util::getDefaultEmailAddress('lostpassword-noreply');
		try {
			$defaults = new \OC_Defaults();
			\OC_Mail::send($email, $user, $l->t('%s password reset', array($defaults->getName())), $msg, $from, $defaults->getName());
		} catch (\Exception $e) {
			throw new \Exception( $l->t('Couldn’t send reset email. Please contact your administrator.'));
		}
	}
	
}
