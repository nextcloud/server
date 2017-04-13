<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Settings;

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\AppFramework\Utility\TimeFactory;
use OC\Authentication\Token\IProvider;
use OC\Server;
use OC\Settings\Activity\Provider;
use OC\Settings\Activity\Setting;
use OC\Settings\Mailer\NewUserMailHelper;
use OC\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\App;
use OCP\Defaults;
use OCP\IContainer;
use OCP\IL10N;
use OCP\IUser;
use OCP\Settings\IManager;
use OCP\Util;

/**
 * @package OC\Settings
 */
class Application extends App {


	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=[]){
		parent::__construct('settings', $urlParams);

		$container = $this->getContainer();

		// Register Middleware
		$container->registerAlias('SubadminMiddleware', SubadminMiddleware::class);
		$container->registerMiddleWare('SubadminMiddleware');

		/**
		 * Core class wrappers
		 */
		/** FIXME: Remove once OC_User is non-static and mockable */
		$container->registerService('isAdmin', function() {
			return \OC_User::isAdminUser(\OC_User::getUser());
		});
		/** FIXME: Remove once OC_SubAdmin is non-static and mockable */
		$container->registerService('isSubAdmin', function(IContainer $c) {
			$userObject = \OC::$server->getUserSession()->getUser();
			$isSubAdmin = false;
			if($userObject !== null) {
				$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
			}
			return $isSubAdmin;
		});
		$container->registerService('userCertificateManager', function(IContainer $c) {
			return $c->query('ServerContainer')->getCertificateManager();
		}, false);
		$container->registerService('systemCertificateManager', function (IContainer $c) {
			return $c->query('ServerContainer')->getCertificateManager(null);
		}, false);
		$container->registerService(IProvider::class, function (IContainer $c) {
			return $c->query('ServerContainer')->query(IProvider::class);
		});
		$container->registerService(IManager::class, function (IContainer $c) {
			return $c->query('ServerContainer')->getSettingsManager();
		});

		$container->registerService(NewUserMailHelper::class, function (IContainer $c) {
			/** @var Server $server */
			$server = $c->query('ServerContainer');
			/** @var Defaults $defaults */
			$defaults = $server->query(Defaults::class);

			return new NewUserMailHelper(
				$defaults,
				$server->getURLGenerator(),
				$server->getL10N('settings'),
				$server->getMailer(),
				$server->getSecureRandom(),
				new TimeFactory(),
				$server->getConfig(),
				$server->getCrypto(),
				Util::getDefaultEmailAddress('no-reply')
			);
		});
		$container->registerService(AppFetcher::class, function (IContainer $c) {
			/** @var Server $server */
			$server = $c->query('ServerContainer');
			return new AppFetcher(
				$server->getAppDataDir('appstore'),
				$server->getHTTPClientService(),
				$server->query(TimeFactory::class),
				$server->getConfig()
			);
		});
		$container->registerService(CategoryFetcher::class, function (IContainer $c) {
			/** @var Server $server */
			$server = $c->query('ServerContainer');
			return new CategoryFetcher(
				$server->getAppDataDir('appstore'),
				$server->getHTTPClientService(),
				$server->query(TimeFactory::class),
				$server->getConfig()
			);
		});
	}

	public function register() {
		$activityManager = $this->getContainer()->getServer()->getActivityManager();
		$activityManager->registerSetting(Setting::class); // FIXME move to info.xml
		$activityManager->registerProvider(Provider::class); // FIXME move to info.xml

		Util::connectHook('OC_User', 'post_setPassword', $this, 'onPasswordChange');
		Util::connectHook('OC_User', 'changeUser', $this, 'onChangeInfo');
	}

	/**
	 * @param array $parameters
	 * @throws \InvalidArgumentException
	 * @throws \BadMethodCallException
	 * @throws \Exception
	 */
	public function onPasswordChange(array $parameters) {
		$userManager = $this->getContainer()->getServer()->getUserManager();
		$user = $userManager->get($parameters['uid']);

		if (!$user instanceof IUser || $user->getEMailAddress() === null) {
			return;
		}

		$activityManager = $this->getContainer()->getServer()->getActivityManager();
		$event = $activityManager->generateEvent();
		$event->setApp('settings')
			->setType('personal_settings')
			->setAffectedUser($user->getUID());

		/** @var IL10N $l */
		$l = $this->getContainer()->query(IL10N::class);
		$urlGenerator = $this->getContainer()->getServer()->getURLGenerator();
		$instanceUrl = $urlGenerator->getAbsoluteURL('/');

		$actor = $this->getContainer()->getServer()->getUserSession()->getUser();
		if ($actor instanceof IUser) {
			if ($actor->getUID() !== $user->getUID()) {
				$text = $l->t('%1$s changed your password on %2$s.', [$actor->getDisplayName(), $instanceUrl]);
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::PASSWORD_CHANGED_BY, [$actor->getUID()]);
			} else {
				$text = $l->t('Your password on %s was changed.', [$instanceUrl]);
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::PASSWORD_CHANGED_SELF);
			}
		} else {
			$text = $l->t('Your password on %s was reset by an administrator.', [$instanceUrl]);
			$event->setSubject(Provider::PASSWORD_RESET);
		}

		$activityManager->publish($event);

		if ($user->getEMailAddress() !== null) {
			$mailer = $this->getContainer()->getServer()->getMailer();
			$template = $mailer->createEMailTemplate();
			$template->addHeader();
			$template->addHeading($l->t('Password changed for %s', $user->getDisplayName()), false);
			$template->addBodyText($text . ' ' . $l->t('If you did not request this, please contact an administrator as soon as possible.'));
			$template->addFooter();


			$message = $mailer->createMessage();
			$message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
			$message->setSubject($l->t('Password for %1$s changed on %2$s', [$user->getDisplayName(), $instanceUrl]));
			$message->setBody($template->renderText(), 'text/plain');
			$message->setHtmlBody($template->renderHTML());

			$mailer->send($message);
		}
	}

	public function onChangeInfo($parameters) {
		if ($parameters['feature'] !== 'eMailAddress') {
			return;
		}

		/** @var IUser $user */
		$user = $parameters['user'];

		$activityManager = $this->getContainer()->getServer()->getActivityManager();
		$event = $activityManager->generateEvent();
		$event->setApp('settings')
			->setType('personal_settings')
			->setAffectedUser($user->getUID());

		$actor = $this->getContainer()->getServer()->getUserSession()->getUser();
		if ($actor instanceof IUser) {
			if ($actor->getUID() !== $user->getUID()) {
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::EMAIL_CHANGED_BY, [$actor->getUID()]);
			} else {
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::EMAIL_CHANGED_SELF);
			}
		} else {
			$event->setSubject(Provider::EMAIL_CHANGED);
		}
		$activityManager->publish($event);
	}
}
