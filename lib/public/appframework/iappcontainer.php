<?php

namespace OCP\AppFramework;

use OCP\AppFramework\IApi;
use OCP\Core\IContainer;

/**
 * Class IAppContainer
 * @package OCP\AppFramework
 *
 * This container interface provides short cuts for app developers to access predefined app service.
 */
interface IAppContainer extends IContainer{

	/**
	 * @return IApi
	 */
	function getCoreApi();

	/**
	 * @return \OCP\Core\IServerContainer
	 */
	function getServer();
}
