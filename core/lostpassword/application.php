<?php
/**
 * @author Victor Dubiniuk
 * @copyright 2014 Victor Dubiniuk victor.dubiniuk@gmail.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\LostPassword;

use \OCP\AppFramework\App;
use OC\Core\LostPassword\Controller\LostController;

class Application extends App {
	public function __construct(array $urlParams=array()){
		parent::__construct('core', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('LostController', function($c) {
			return new LostController(
				$c->query('AppName'),
				$c->query('ServerContainer')->getRequest(),
				$c->query('ServerContainer')->getURLGenerator(),
				$c->query('ServerContainer')->getUserManager(),
				new \OC_Defaults(),
				$c->query('ServerContainer')->getL10N('core'),
				\OCP\Util::getDefaultEmailAddress('lostpassword-noreply'),
				\OC_App::isEnabled('files_encryption')
			);
		});
	}
}
