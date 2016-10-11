<?php

namespace OCA\Provisioning_API\AppInfo;

use OC\AppFramework\Utility\SimpleContainer;
use OCA\Provisioning_API\Middleware\ProvisioningApiMiddleware;
use OCP\AppFramework\App;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('provisioning_api', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

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
