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

class LostController extends Controller {
	
	protected $urlGenerator;
	
	public function __construct($appName, IRequest $request, IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function reset() {
		// Someone wants to reset their password:
		if($this->checkToken()) {
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
			$isEncrypted = \OC_App::isEnabled('files_encryption');
			return new TemplateResponse(
				'core/lostpassword', 
				'lostpassword', 
				array(
					'isEncrypted' => $isEncrypted, 
					'link' => $this->getResetPasswordLink()
				),
				'guest'
			);
		}
	}

	protected function getResetPasswordLink(){
		$parameters = array(
			'token' => $this->params('token'), 
			'user' => $this->params('user')
		);
		$link = $this->urlGenerator->linkToRoute('core.ajax.reset', $parameters);
		return $this->urlGenerator->getAbsoluteUrl($link);
	}

	protected function checkToken() {
		$user = $this->params('user');
		$token = $this->params('token');
		return \OC_Preferences::getValue($user, 'owncloud', 'lostpassword') === hash('sha256', $token);
	}
}
