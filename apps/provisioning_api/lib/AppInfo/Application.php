<?php

namespace OCA\Provisioning_API\AppInfo;

use OC\AppFramework\Utility\SimpleContainer;
use OC\AppFramework\Utility\TimeFactory;
use OC\Settings\Mailer\NewUserMailHelper;
use OCA\Provisioning_API\Middleware\ProvisioningApiMiddleware;
use OCP\AppFramework\App;
use OCP\Defaults;
use OCP\Util;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('provisioning_api', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		$container->registerService(NewUserMailHelper::class, function(SimpleContainer $c) use ($server) {
			return new NewUserMailHelper(
				$server->query(Defaults::class),
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
		$container->registerService('ProvisioningApiMiddleware', function(SimpleContainer $c) use ($server) {
			$user = $server->getUserManager()->get($c['UserId']);
			$isAdmin = $user !== null ? $server->getGroupManager()->isAdmin($user->getUID()) : false;
			$isSubAdmin = $user !== null ? $server->getGroupManager()->getSubAdmin()->isSubAdmin($user) : false;
			return new ProvisioningApiMiddleware(
				$c['ControllerMethodReflector'],
				$isAdmin,
				$isSubAdmin
			);
		});
		$container->registerMiddleWare('ProvisioningApiMiddleware');
	}
}
