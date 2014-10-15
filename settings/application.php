<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings;

use OC\AppFramework\Utility\SimpleContainer;
use OC\Settings\Controller\AppSettingsController;
use OC\Settings\Controller\MailSettingsController;
use \OCP\AppFramework\App;
use \OCP\Util;

/**
 * @package OC\Settings
 */
class Application extends App {


	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=array()){
		parent::__construct('settings', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('MailSettingsController', function(SimpleContainer $c) {
			return new MailSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config'),
				$c->query('UserSession'),
				$c->query('Defaults'),
				$c->query('Mail'),
				$c->query('DefaultMailAddress')
			);
		});
		$container->registerService('AppSettingsController', function(SimpleContainer $c) {
			return new AppSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('L10N'),
				$c->query('Config')
			);
		});
		/**
		 * Core class wrappers
		 */
		$container->registerService('Config', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getConfig();
		});
		$container->registerService('L10N', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getL10N('settings');
		});
		$container->registerService('UserSession', function(SimpleContainer $c) {
			return $c->query('ServerContainer')->getUserSession();
		});
		$container->registerService('Mail', function(SimpleContainer $c) {
			return new \OC_Mail;
		});
		$container->registerService('Defaults', function(SimpleContainer $c) {
			return new \OC_Defaults;
		});
		$container->registerService('DefaultMailAddress', function(SimpleContainer $c) {
			return Util::getDefaultEmailAddress('no-reply');
		});
	}
}
